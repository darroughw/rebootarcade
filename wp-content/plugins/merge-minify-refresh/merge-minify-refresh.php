<?php
/**
 * Plugin Name: Merge + Minify + Refresh
 * Plugin URI: https://wordpress.org/plugins/merge-minify-refresh
 * Description: 
 * Version: 1.6.12
 * Author: Launch Interactive
 * Author URI: http://launchinteractive.com.au
 * License: GPL2
*/
/*
Copyright 2015  Marc Castles  (email : marc@launchinteractive.com.au)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class MergeMinifyRefresh {
	
	private $host = '';
	private $root = '';
	
	private $mergecss = true;
	private $mergejs = true;
	private $cssmin = true;
	private $jsmin = true;
	private $http2push = false;
	private $outputbuffering = false;
	private $ignore = array();
	
	private $wordpressdir = '';

	public function __construct() {
		
		/*
		Valid Configs:
		
		MMR_CACHE_DIR + MMR_CACHE_URL
		MMR_CACHE_DIR + MMR_JS_CACHE_URL + MMR_CSS_CACHE_URL
		MMR_CACHE_DIR + MMR_CACHE_URL + MMR_JS_CACHE_URL + MMR_CSS_CACHE_URL // MMR_CACHE_URL becomes unnecessary
		MMR_CACHE_DIR + MMR_CACHE_URL + MMR_JS_CACHE_URL
		MMR_CACHE_DIR + MMR_CACHE_URL + MMR_CSS_CACHE_URL
		MMR_CACHE_URL
		MMR_JS_CACHE_URL + MMR_CSS_CACHE_URL
		MMR_CACHE_URL + MMR_JS_CACHE_URL + MMR_CSS_CACHE_URL // MMR_CACHE_URL becomes unnecessary
		MMR_CACHE_URL + MMR_JS_CACHE_URL
		MMR_CACHE_URL + MMR_CSS_CACHE_URL
		MMR_CSS_CACHE_URL
		MMR_JS_CACHE_URL	
		*/
		
		if(!defined('MMR_CACHE_DIR')) {
			define('MMR_CACHE_DIR', WP_CONTENT_DIR.'/mmr');
			
			if(!defined('MMR_CACHE_URL')) {
				define('MMR_CACHE_URL', WP_CONTENT_URL.'/mmr');
			}
		} else if(WP_DEBUG && !defined('MMR_CACHE_URL') && (!defined('MMR_JS_CACHE_URL') || !defined('MMR_CSS_CACHE_URL'))) {
			wp_die("You must specify MMR_CACHE_URL or MMR_JS_CACHE_URL & MMR_CSS_CACHE_URL");
		}
		
		if(!defined('MMR_JS_CACHE_URL')) {
			define('MMR_JS_CACHE_URL', MMR_CACHE_URL);
		}
		if(!defined('MMR_CSS_CACHE_URL')) {
			define('MMR_CSS_CACHE_URL', MMR_CACHE_URL);
		}

		if(!is_dir(MMR_CACHE_DIR)) {
			mkdir(MMR_CACHE_DIR);
		}

		$this->root = $_SERVER["DOCUMENT_ROOT"];
		$this->wordpressdir = parse_url(get_site_url(), PHP_URL_PATH);

		if(is_admin()) {
		
			add_action( 'admin_menu', array($this,'admin_menu') );
		
			add_action( 'admin_enqueue_scripts', array($this,'load_admin_jscss') );
		
			add_action( 'wp_ajax_mmr_files', array($this,'mmr_files_callback') );
		
			add_action( 'admin_init', array($this,'mmr_register_settings') );
		
			register_deactivation_hook( __FILE__, array($this, 'plugin_deactivate') );
		
		  } else {

			$this->host = $_SERVER['HTTP_HOST'];
			//php < 5.4.7 returns null if host without scheme entered
			if(mb_substr($this->host, 0, 4) !== 'http') $this->host = 'http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '').'://' . $this->host;
			$this->host = parse_url( $this->host, PHP_URL_HOST );
		
			$this->mergecss = !get_option('mmr-nomergecss');
			$this->mergejs = !get_option('mmr-nomergejs');
			$this->cssmin = !get_option('mmr-nocssmin');
			$this->jsmin = !get_option('mmr-nojsmin');
			$this->http2push = get_option('mmr-http2push');
			$this->outputbuffering = get_option('mmr-outputbuffering');
			$this->ignore = array_map('trim',explode(PHP_EOL,get_option('mmr-ignore')));
		
			add_action( 'compress_css',array($this,'compress_css_action'), 10, 1 );
				add_action( 'compress_js', array($this,'compress_js_action'), 10, 1 );
		
			if($this->outputbuffering) {
				add_action( 'init', array($this,'start_buffer'), PHP_INT_MIN );
			}
		
			add_action( 'wp_print_scripts', array($this,'inspect_scripts'), PHP_INT_MAX );
			add_action( 'wp_print_styles', array($this,'inspect_styles'), PHP_INT_MAX );
		
			add_filter( 'style_loader_src', array($this,'remove_cssjs_ver'), 10, 2 );
			add_filter( 'script_loader_src', array($this,'remove_cssjs_ver'), 10, 2 );

			add_action( 'wp_print_footer_scripts', array($this,'inspect_stylescripts_footer'), 9.999999 ); //10 = Internal WordPress Output
		}
	}

	public function mmr_files_callback() {

		if(isset($_POST['purge']) && $_POST['purge'] == 'all') {
			$this->rrmdir(MMR_CACHE_DIR); 
		} else if(isset($_POST['purge'])) {
			array_map('unlink', glob(MMR_CACHE_DIR.'/'.$_POST['purge'].'*'));
		}

		$return = array('js'=>array(),'css'=>array(),'stamp'=>$_POST['stamp']);

		$files = glob(MMR_CACHE_DIR.'/*.{js,css}', GLOB_BRACE);

		if(count($files) > 0) {
			
			$css = null;
			
			foreach($files as $file) {

				$ext = pathinfo($file, PATHINFO_EXTENSION);
				
				if(in_array($ext, array('js','css'))) {
					//loop over non minified files
					if($ext == 'css' && substr($file, -8) != '.min.css' || $ext == 'js' && substr($file, -7) != '.min.js') {

						$scheduled = false;

						if(wp_next_scheduled( 'compress_'.$ext, array($file) ) !== false) {
							$scheduled = true;
						}

						$log = file_get_contents($file.'.log');
						
						$error = false;
						if(strpos($log,'COMPRESSION FAILED') !== false) {
							$error = true;
						}
						
						$mincss = substr($file,0,-4).'.min.css';
						$minjs = substr($file,0,-3).'.min.js';
						
						$filename = basename($file);
						if($ext == 'css' && file_exists($mincss)) {
							$filename = basename($mincss);
						}
						if($ext == 'js' && file_exists($minjs)) {
							$filename = basename($minjs);
						}
						
						$hash = substr($filename,0,strpos($filename,'-'));
						$accessed = 'Unknown';
						if( file_exists($file.'.accessed'))
						{
							$accessed = file_get_contents($file.'.accessed');
							if(strtotime('today') <= $accessed) {
								$accessed = 'Today';
							} else if(strtotime('yesterday') <= $accessed) {
								$accessed = 'Yesterday';
							} else if(strtotime('this week') <= $accessed) {
								$accessed = 'This Week';
							} else if(strtotime('this month') <= $accessed) {
								$accessed = 'This Month';
							} else {
								$accessed = date(get_option('date_format'), $accessed);
							}
						}
						
						array_push($return[$ext], array('hash'=>$hash,'filename'=>$filename,'scheduled'=>$scheduled,'log'=>$log, 'error'=>$error, 'accessed'=>$accessed) );							
					}
				}
			}
		}

		header('Content-Type: application/json');
		echo json_encode($return);

		wp_die(); // this is required to terminate immediately and return a proper response
	}

	public function plugin_deactivate() {
		if(is_dir(MMR_CACHE_DIR)) {
			$this->rrmdir(MMR_CACHE_DIR); 
		}
	}

	private function rrmdir($dir) { 
		foreach(glob($dir.'/{,.}*', GLOB_BRACE) as $file) { 
			if(basename($file) != '.' && basename($file) != '..') {
				if(is_dir($file)) $this->rrmdir($file); else unlink($file); 
			}
		}
		rmdir($dir); 
	}

	public function load_admin_jscss($hook) {
		if ( 'settings_page_merge-minify-refresh' != $hook ) {
			return;
		}
		wp_enqueue_style( 'merge-minify-refresh', plugins_url('admin.css', __FILE__) );
		wp_enqueue_script( 'merge-minify-refresh', plugins_url('admin.js', __FILE__), array(), false, true );
	}

	public function admin_menu() {
		add_options_page( 'Merge + Minify + Refresh Settings', 'Merge + Minify + Refresh', 'manage_options', 'merge-minify-refresh', array($this,'merge_minify_refresh_settings') );
	}

	public function mmr_register_settings() {
		register_setting( 'mmr-group', 'mmr-nomergecss' );
		register_setting( 'mmr-group', 'mmr-nomergejs' );
		register_setting( 'mmr-group', 'mmr-nocssmin' );
		register_setting( 'mmr-group', 'mmr-nojsmin' );
		register_setting( 'mmr-group', 'mmr-http2push' );
		register_setting( 'mmr-group', 'mmr-outputbuffering' );
		register_setting( 'mmr-group', 'mmr-ignore' );
	}
	
	public function merge_minify_refresh_settings() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		//echo '<pre>';var_dump(_get_cron_array()); echo '</pre>';

		$files = glob(MMR_CACHE_DIR.'/*.{js,css}', GLOB_BRACE);
		
		echo '<div id="merge-minify-refresh">
				<h2>Merge + Minify + Refresh Settings</h2>
				<p>When a CSS or JS file is modified MMR will automatically re-process the files. However, when a dependancy changes these files may become stale.</p>
		
				<div id="mmr_processed">
					<a href="#" class="button button-secondary purgeall">Purge All</a>
				
					<div id="mmr_jsprocessed">
						<h4>The following Javascript files have been processed:</h4>
						<ul class="processed"></ul>
					</div>
				
					<div id="mmr_cssprocessed">
						<h4>The following CSS files have been processed:</h4>
						<ul class="processed"></ul>
					</div>
				</div>
			
				<p id="mmr_noprocessed"><strong>No files have been processed</strong></p>
			
			</div>
		';

		echo '<form method="post" id="mmr_options" action="options.php">';
		settings_fields( 'mmr-group' ); 
		do_settings_sections( 'mmr-group' ); 
		echo '<p><label><input type="checkbox" name="mmr-nomergecss" value="1" '.checked( 1 == get_option('mmr-nomergecss') , true, false).'/> Don\'t Merge CSS</label>';
		echo '<label><input type="checkbox" name="mmr-nomergejs" value="1" '.checked( 1 == get_option('mmr-nomergejs') , true, false).'/> Don\'t Merge JS</label>';
		echo '<br/><em>Note: Selecting these will increase requests but may be required for some themes. e.g. Themes using @import</em></p>';

		echo '<p><label><input type="checkbox" name="mmr-nocssmin" value="1" '.checked( 1 == get_option('mmr-nocssmin') , true, false).'/> Disable CSS Minification</label>';

		echo '<label><input type="checkbox" name="mmr-nojsmin" value="1" '.checked( 1 == get_option('mmr-nojsmin') , true, false).'/> Disable JS Minification</label>';
		echo '<br/><em>Note: Disabling CSS/JS minification may require a "Purge All" to take effect.</em></p>';

		echo '<p><label><input type="checkbox" name="mmr-http2push" value="1" '.checked( 1 == get_option('mmr-http2push') , true, false).'/> Enable HTTP2 Server Push</label>';
		echo '<br/><em>Enables the server to send multiple responses (in parallel) for a single client request.</em></p>';

		echo '<p><label><input type="checkbox" name="mmr-outputbuffering" value="1" '.checked( 1 == get_option('mmr-outputbuffering') , true, false).'/> Enable Output Buffering</label>';

		echo '<br/><em>Output buffering may be required for compatibility with some plugins. If its disabled only the header requests will be be sent for HTTP2 Server Push.</em></p>';

		echo '<p><label class="textlabel">Ignore these files (view logs to view paths):<textarea name="mmr-ignore" placeholder="/full/path/to/js/file.js or /full/path/to/css/file.css ">'.get_option('mmr-ignore').'</textarea></label></p>';

		echo '<p><button type="submit" class="button">SAVE</button></p></form>';

	}

	public function remove_cssjs_ver( $src ) {
		if( strpos( $src, '?ver=' ) )
			$src = remove_query_arg( 'ver', $src );
		return $src;
	}

	private function host_match( $url ) {
		if( empty($url) ) {
			return false;
		}

		$url = $this->ensure_scheme($url);

		$url_host = parse_url( $url, PHP_URL_HOST );
		
		if(  !$url_host || $url_host == $this->host ) {
			return true;
		} else {
			return false;
		}
	}
	
	//php < 5.4.7 parse_url returns null if host without scheme entered
	private function ensure_scheme($url) {
		return preg_replace("/(http(s)?:\/\/|\/\/)(.*)/i", "http$2://$3", $url);
	}
	
	private function remove_scheme($url) {
		return preg_replace("/(http(s)?:\/\/|\/\/)(.*)/i", "//$3", $url);
	}

	public function start_buffer() {
		ob_start();
	}

	public function inspect_scripts() {

		wp_scripts(); //ensure scripts is initialised
		
		global $wp_scripts;

		if($wp_scripts) {

			$scripts = clone( $wp_scripts );

			$scripts->all_deps($scripts->queue);

			$header = array();

			// Loop through queue and determine groups of handles & latest modified date
			foreach( $scripts->to_do as $handle ) :

				$script_path = parse_url($this->ensure_scheme($wp_scripts->registered[$handle]->src), PHP_URL_PATH);
				if($this->wordpressdir != '' && substr($script_path, 0, strlen($this->wordpressdir)) != $this->wordpressdir) {
					$script_path = $this->wordpressdir . $script_path;
				}

				$is_footer = isset($wp_scripts->registered[$handle]->extra['group']);

				if(!$is_footer) { //footer scripts get delt within the inspect_stylescripts_footer action

					if( $this->host_match($wp_scripts->registered[$handle]->src) && !in_array($wp_scripts->registered[$handle]->src, $this->ignore) && !isset($wp_scripts->registered[$handle]->extra["conditional"])) { //is a local script
		
						if(!$this->mergejs || isset($header[count($header)-1]['handle']) || count($header) == 0  ) {
							array_push($header, array('modified'=>0,'handles'=>array()));
						}

						$modified = 0;

						if(is_file($this->root.$script_path)) {
							$modified = filemtime($this->root.$script_path);
						}

						array_push($header[count($header)-1]['handles'], $handle);
	
						if($modified > $header[count($header)-1]['modified']) {
							$header[count($header)-1]['modified'] = $modified;
						}
					} else { //external script
						array_push($header, array('handle'=>$handle));
					}
				}
	
			endforeach;
			
			$done = $scripts->done;

			//loop through header scripts and merge + schedule wpcron
			for($i=0,$l=count($header);$i<$l;$i++) {

				if(!isset($header[$i]['handle'])) {
					
					$done = array_merge($done, $header[$i]['handles']);

					$hash = hash('adler32',implode('',$header[$i]['handles']));					
				
					$file_path = '/'.$hash.'-'.$header[$i]['modified'].'.js';
				
					$full_path = MMR_CACHE_DIR.$file_path;
				
					$min_path = '/'.$hash.'-'.$header[$i]['modified'].'.min.js';
				
					$min_exists = file_exists(MMR_CACHE_DIR.$min_path);

					if(!file_exists($full_path) && !$min_exists) {

						$js = '';
					
						$log = "";
					
						foreach( $header[$i]['handles'] as $handle ) :
					
							$log .= " - ".$handle." - ".$wp_scripts->registered[$handle]->src;

							$script_path = parse_url($this->ensure_scheme($wp_scripts->registered[$handle]->src), PHP_URL_PATH);
							if($this->wordpressdir != '' && substr($script_path, 0, strlen($this->wordpressdir)) != $this->wordpressdir) {
								$script_path = $this->wordpressdir . $script_path;
							}
						
							if(substr($script_path, -7) == '.min.js') {
								$nomin_path = substr($script_path, 0, -7) . '.js';
								if(is_file($this->root.$nomin_path)) {
									$script_path = $nomin_path;
									$log .= " - unminified version used";
								}
							}
						
							$contents = '';

							if(isset($wp_scripts->registered[$handle]->extra['before']) && count($wp_scripts->registered[$handle]->extra['before']) > 0) {
								$contents .= implode(";\n",$wp_scripts->registered[$handle]->extra['before']) . ";\n";
							}

							// Remove the BOM
							$contents .= preg_replace("/^\xEF\xBB\xBF/", '', file_get_contents($this->root.$script_path)) . ";\n";
							
							if(isset($wp_scripts->registered[$handle]->extra['after']) && count($wp_scripts->registered[$handle]->extra['after']) > 0) {
								$contents .= implode(";\n",$wp_scripts->registered[$handle]->extra['after']) . ";\n";
							}
							
							$js .= $contents;

							$log .= "\n";
	
						endforeach;

						//remove existing expired files
						array_map('unlink', glob(MMR_CACHE_DIR.'/'.$hash.'-*.js'));
					
						file_put_contents($full_path , $js);
					
						file_put_contents($full_path.'.log', date('c')." - MERGED:\n".$log);

						wp_clear_scheduled_hook('compress_js', array($full_path) );
						if($this->jsmin) {
							wp_schedule_single_event( time(), 'compress_js', array($full_path) );
						}
					} else {
						file_put_contents($full_path.'.accessed', current_time('timestamp'));
					}
	
					$data = '';
					foreach( $header[$i]['handles'] as $handle ) :					
						if(isset($wp_scripts->registered[$handle]->extra['data'])) {
							$data .= $wp_scripts->registered[$handle]->extra['data'];
						}
					endforeach;

					if($min_exists) {
						if($this->http2push && !headers_sent()) {
							header( 'Link: <'.MMR_JS_CACHE_URL.$min_path.'>; rel=preload', false );
						}
						wp_register_script('header-'.$i, MMR_JS_CACHE_URL.$min_path);
					} else {
						if($this->http2push && !headers_sent()) {
							header( 'Link: <'.MMR_JS_CACHE_URL.$file_path.'>; rel=preload', false );
						}
						wp_register_script('header-'.$i, MMR_JS_CACHE_URL.$file_path);
					}

					//set any existing data that was added with wp_localize_script
					if($data != '') {
						$wp_scripts->registered['header-'.$i]->extra['data'] = $data;
					}
			
					wp_enqueue_script('header-'.$i);
				
				} else { //external
					wp_dequeue_script($header[$i]['handle']); //need to do this so the order of scripts is retained
					wp_enqueue_script($header[$i]['handle']);
				}
			}
			$wp_scripts->done = $done;
		}
	}

	public function inspect_stylescripts_footer() {

		global $wp_scripts;

		if($wp_scripts) {

			$scripts = clone( $wp_scripts );

			$scripts->all_deps($scripts->queue);

			$footer = array();

			// Loop through queue and determine groups of handles & latest modified date
			foreach( $scripts->to_do as $handle ) :

				$script_path = parse_url($this->ensure_scheme($wp_scripts->registered[$handle]->src), PHP_URL_PATH);
				if($this->wordpressdir != '' && substr($script_path, 0, strlen($this->wordpressdir)) != $this->wordpressdir) {
					$script_path = $this->wordpressdir . $script_path;
				}

				if( $this->host_match($wp_scripts->registered[$handle]->src) && !in_array($wp_scripts->registered[$handle]->src, $this->ignore) && !isset($wp_scripts->registered[$handle]->extra["conditional"])) { //is a local script
	
					if(!$this->mergejs || isset($footer[count($footer)-1]['handle']) || count($footer) == 0  ) {
						array_push($footer, array('modified'=>0,'handles'=>array()));
					}

					$modified = 0;
					
					if(is_file($this->root.$script_path)) {
						$modified = filemtime($this->root.$script_path);
					}

					array_push($footer[count($footer)-1]['handles'], $handle);

					if($modified > $footer[count($footer)-1]['modified']) {
						$footer[count($footer)-1]['modified'] = $modified;
					}
				} else { //external script
					array_push($footer, array('handle'=>$handle));
				}
			endforeach;
			
			$done = $scripts->done;

			//loop through footer scripts and merge + schedule wpcron
			for($i=0,$l=count($footer);$i<$l;$i++) {

				if(!isset($footer[$i]['handle'])) {

					$done = array_merge($done, $footer[$i]['handles']);

					$hash = hash('adler32',implode('',$footer[$i]['handles']));

					$file_path = '/'.$hash.'-'.$footer[$i]['modified'].'.js';
	
					$full_path = MMR_CACHE_DIR.$file_path;

					$min_path = '/'.$hash.'-'.$footer[$i]['modified'].'.min.js';

					$min_exists = file_exists(MMR_CACHE_DIR.$min_path);

					if(!file_exists($full_path) && !$min_exists) {

						$js = '';

						$log = "";

						foreach( $footer[$i]['handles'] as $handle ) :

							$log .= " - ".$handle." - ".$wp_scripts->registered[$handle]->src;

							$script_path = parse_url($this->ensure_scheme($wp_scripts->registered[$handle]->src), PHP_URL_PATH);
							if($this->wordpressdir != '' && substr($script_path, 0, strlen($this->wordpressdir)) != $this->wordpressdir) {
								$script_path = $this->wordpressdir . $script_path;
							}

							if(substr($script_path, -7) == '.min.js') {
								$nomin_path = substr($script_path, 0, -7) . '.js';
								if(is_file($this->root.$nomin_path)) {
									$script_path = $nomin_path;
									$log .= " - unminified version used";
								}
							}

							$contents = '';

							if(isset($wp_scripts->registered[$handle]->extra['before']) && count($wp_scripts->registered[$handle]->extra['before']) > 0) {
								$contents .= implode(";\n",$wp_scripts->registered[$handle]->extra['before']) . ";\n";
							}

							// Remove the BOM
							$contents .= preg_replace("/^\xEF\xBB\xBF/", '', file_get_contents($this->root.$script_path)) . ";\n";
							
							if(isset($wp_scripts->registered[$handle]->extra['after']) && count($wp_scripts->registered[$handle]->extra['after']) > 0) {
								$contents .= implode(";\n",$wp_scripts->registered[$handle]->extra['after']) . ";\n";
							}

							$js .= $contents;

							$log .= "\n";

						endforeach;

						//remove existing expired files
						array_map('unlink', glob(MMR_CACHE_DIR.'/'.$hash.'-*.js'));

						file_put_contents($full_path , $js);
					
						file_put_contents($full_path.'.log', date('c')." - MERGED:\n".$log);
					
						wp_clear_scheduled_hook('compress_js', array($full_path));
						if($this->jsmin) {
							wp_schedule_single_event( time(), 'compress_js', array($full_path) );
						}

					} else {
						file_put_contents($full_path.'.accessed', current_time('timestamp'));
					}

					$data = '';
					foreach( $footer[$i]['handles'] as $handle ) :					
						if(isset($wp_scripts->registered[$handle]->extra['data'])) {
							$data .= $wp_scripts->registered[$handle]->extra['data'];
						}
					endforeach;

					if($min_exists) {
						if($this->http2push && !headers_sent()) {
							header( 'Link: <'.MMR_JS_CACHE_URL.$min_path.'>; rel=preload', false );
						}
						wp_register_script('footer-'.$i, MMR_JS_CACHE_URL.$min_path, false, false, true);
					} else {
						if($this->http2push && !headers_sent()) {
							header( 'Link: <'.MMR_JS_CACHE_URL.$file_path.'>; rel=preload', false );
						}
						wp_register_script('footer-'.$i, MMR_JS_CACHE_URL.$file_path, false, false, true);
					}

					//set any existing data that was added with wp_localize_script
					if($data != '') {
						$wp_scripts->registered['footer-'.$i]->extra['data'] = $data;
					}

					wp_enqueue_script('footer-'.$i);

				} else { //external
					wp_dequeue_script($footer[$i]['handle']); //need to do this so the order of scripts is retained
					wp_enqueue_script($footer[$i]['handle']);
				}
			}

			$wp_scripts->done = $done;

			global $wp_styles;		

			$styles = clone( $wp_styles );
		
			$styles->all_deps($styles->queue);
		
			$footer = array();
		
			// Loop through queue and determine groups of handles & latest modified date
			foreach( $styles->to_do as $handle ) :

				$style_path = parse_url($this->ensure_scheme($wp_styles->registered[$handle]->src), PHP_URL_PATH);
				if($this->wordpressdir != '' && substr($style_path, 0, strlen($this->wordpressdir)) != $this->wordpressdir) {
					$style_path = $this->wordpressdir . $style_path;
				}

				if( $this->host_match($wp_styles->registered[$handle]->src) && !in_array($wp_styles->registered[$handle]->src, $this->ignore) && !isset($wp_styles->registered[$handle]->extra["conditional"])) { //is a local script

					if(!$this->mergecss || isset($footer[count($footer)-1]['handle']) || count($footer) == 0 || $footer[count($footer)-1]['media'] != $wp_styles->registered[$handle]->args ) {
						$media = isset($wp_styles->registered[$handle]->args) ? $wp_styles->registered[$handle]->args : 'all';

						array_push($footer, array('modified'=>0,'handles'=>array(),'media'=>$media ));
					}

					$modified = 0;
					
					if(is_file($this->root.$style_path)) {
						$modified = filemtime($this->root.$style_path);
					}
	
					array_push($footer[count($footer)-1]['handles'], $handle);
	
					if($modified > $footer[count($footer)-1]['modified']) {
						$footer[count($footer)-1]['modified'] = $modified;
					}
				
				} else { //external script
					array_push($footer, array('handle'=>$handle));
				}
	
			endforeach;
			
			$done = $styles->done;

			//loop through header styles and merge + schedule wpcron
			for($i=0,$l=count($footer);$i<$l;$i++) {

				if(!isset($footer[$i]['handle'])) {
				
					$done = array_merge($done, $footer[$i]['handles']);
				
					$hash = hash('adler32',implode('',$footer[$i]['handles']));

					$file_path = '/'.$hash.'-'.$footer[$i]['modified'].'.css';

					$full_path = MMR_CACHE_DIR.$file_path;
				
					$min_path = '/'.$hash.'-'.$footer[$i]['modified'].'.min.css';
				
					$min_exists = file_exists(MMR_CACHE_DIR.$min_path);
				
					if(!file_exists($full_path) && !$min_exists) {

						$css = '';
					
						$log = "";

						foreach( $footer[$i]['handles'] as $handle ) :
						
							$style_path = parse_url($this->ensure_scheme($wp_styles->registered[$handle]->src), PHP_URL_PATH);
							if($this->wordpressdir != '' && substr($style_path, 0, strlen($this->wordpressdir)) != $this->wordpressdir) {
								$style_path = $this->wordpressdir . $style_path;
							}
						
							$log .= " - ".$handle." - ".$wp_styles->registered[$handle]->src;
						
							if(substr($style_path, -8) == '.min.css') {
								$nomin_path = substr($style_path, 0, -8) . '.css';
								if(is_file($this->root.$nomin_path)) {
									$style_path = $nomin_path;
									$log .= " - unminified version used";
								}
							}
						
							$css_contents = file_get_contents($this->root.$style_path);

							// Remove the BOM
							$css_contents = preg_replace("/^\xEF\xBB\xBF/", '', $css_contents);
							
							if(isset($wp_styles->registered[$handle]->extra['after']) && count($wp_styles->registered[$handle]->extra['after']) > 0) {
								$css_contents .= "\n" . implode("\n",$wp_styles->registered[$handle]->extra['after']);
							}

							//convert relative paths to absolute & ignore data: or absolute paths (starts with /)
							$css_contents =preg_replace("/url\(\s*['\"]?(?!data:)(?!http)(?![\/'\"])(.+?)['\"]?\s*\)/i", "url(".dirname($style_path)."/$1)", $css_contents);

							$css .= $css_contents . "\n";
						
							$log .= "\n";
	
						endforeach;
					
						//remove existing out of date files
						array_map('unlink', glob(MMR_CACHE_DIR.'/'.$hash.'-*.css'));

						file_put_contents($full_path , $css);
					
						file_put_contents($full_path.'.log', date('c')." - MERGED:\n".$log);
						
						wp_clear_scheduled_hook('compress_css', array($full_path) );
						if($this->cssmin) {
							wp_schedule_single_event( time(), 'compress_css', array($full_path) );
						}
					} else {
						file_put_contents($full_path.'.accessed', current_time('timestamp'));
					}
				
					if($min_exists) {
						if($this->http2push && !headers_sent()) {
							header( 'Link: <'.MMR_CSS_CACHE_URL.$min_path.'>; rel=preload', false );
						}
						wp_register_style('footer-'.$i, MMR_CSS_CACHE_URL.$min_path,false,false,$footer[$i]['media']);
					} else {
						if($this->http2push && !headers_sent()) {
							header( 'Link: <'.MMR_CSS_CACHE_URL.$file_path.'>; rel=preload', false );
						}
						wp_register_style('footer-'.$i, MMR_CSS_CACHE_URL.$file_path,false,false,$footer[$i]['media']);
					}
				
					wp_enqueue_style('footer-'.$i);
			
				} else { //external
					wp_dequeue_style($footer[$i]['handle']); //need to do this so the order of scripts is retained
					wp_enqueue_style($footer[$i]['handle']);
				}

			}
		
			$wp_styles->done = $done;
		}
		
		if($this->outputbuffering) {
			ob_end_flush();
		}
	}
  
	public function inspect_styles() {

		wp_styles(); //ensure styles is initialised

		global $wp_styles;		
		
		if($wp_styles) {

			$styles = clone( $wp_styles );

			$styles->all_deps($styles->queue);

			$header = array();

			// Loop through queue and determine groups of handles & latest modified date
			foreach( $styles->to_do as $handle ) :

				$style_path = parse_url($this->ensure_scheme($wp_styles->registered[$handle]->src), PHP_URL_PATH);
				if($this->wordpressdir != '' && substr($style_path, 0, strlen($this->wordpressdir)) != $this->wordpressdir) {
					$style_path = $this->wordpressdir . $style_path;
				}

				if( $this->host_match($wp_styles->registered[$handle]->src) && !in_array($wp_styles->registered[$handle]->src, $this->ignore) && !isset($wp_styles->registered[$handle]->extra["conditional"])) { //is a local script

					if(!$this->mergecss || isset($header[count($header)-1]['handle']) || count($header) == 0 || $header[count($header)-1]['media'] != $wp_styles->registered[$handle]->args ) {
						$media = isset($wp_styles->registered[$handle]->args) ? $wp_styles->registered[$handle]->args : 'all';
						array_push($header, array('modified'=>0,'handles'=>array(),'media'=>$media ));
					}
	
					$modified = 0;
					
					if(is_file($this->root.$style_path)) {
						$modified = filemtime($this->root.$style_path);
					}
	
					array_push($header[count($header)-1]['handles'], $handle);

					if($modified > $header[count($header)-1]['modified']) {
						$header[count($header)-1]['modified'] = $modified;
					}

				} else { //external script
					array_push($header, array('handle'=>$handle));
				}
	
			endforeach;
			
			$done = $styles->done;
	
			//loop through header styles and merge + schedule wpcron
			for($i=0,$l=count($header);$i<$l;$i++) {
	
				if(!isset($header[$i]['handle'])) {

					$done = array_merge($done, $header[$i]['handles']);

					$hash = hash('adler32',implode('',$header[$i]['handles']));

					$file_path = '/'.$hash.'-'.$header[$i]['modified'].'.css';

					$full_path = MMR_CACHE_DIR.$file_path;

					$min_path = '/'.$hash.'-'.$header[$i]['modified'].'.min.css';

					$min_exists = file_exists(MMR_CACHE_DIR.$min_path);

					if(!file_exists($full_path) && !$min_exists) {
	
						$css = '';

						$log = "";

						foreach( $header[$i]['handles'] as $handle ) :
							
							$style_path = parse_url($this->ensure_scheme($wp_styles->registered[$handle]->src), PHP_URL_PATH);
							if($this->wordpressdir != '' && substr($style_path, 0, strlen($this->wordpressdir)) != $this->wordpressdir) {
								$style_path = $this->wordpressdir . $style_path;
							}

							$log .= " - ".$handle." - ".$wp_styles->registered[$handle]->src;
							
							if(substr($style_path, -8) == '.min.css') {
								$nomin_path = substr($style_path, 0, -8) . '.css';
								if(is_file($this->root.$nomin_path)) {
									$style_path = $nomin_path;
									$log .= " - unminified version used";
								}
							}

							$css_contents = file_get_contents($this->root.$style_path);

							// Remove the BOM
							$css_contents = preg_replace("/^\xEF\xBB\xBF/", '', $css_contents);
							
							if(isset($wp_styles->registered[$handle]->extra['after']) && count($wp_styles->registered[$handle]->extra['after']) > 0) {
								$css_contents .= "\n" . implode("\n",$wp_styles->registered[$handle]->extra['after']);
							}

							//convert relative paths to absolute & ignore data: or absolute paths (starts with /)
							$css_contents =preg_replace("/url\(\s*['\"]?(?!data:)(?!http)(?![\/'\"])(.+?)['\"]?\s*\)/i", "url(".dirname($style_path)."/$1)", $css_contents);

							$css .= $css_contents . "\n";

							$log .= "\n";
		
						endforeach;

						//remove existing out of date files
						array_map('unlink', glob(MMR_CACHE_DIR.'/'.$hash.'-*.css'));

						file_put_contents($full_path , $css);

						file_put_contents($full_path.'.log', date('c')." - MERGED:\n".$log);

						wp_clear_scheduled_hook('compress_css', array($full_path) );
						if($this->cssmin) {
							wp_schedule_single_event( time(), 'compress_css', array($full_path) );
						}
					} else {
						file_put_contents($full_path.'.accessed', current_time('timestamp'));
					}

					if($min_exists) {
						if($this->http2push && !headers_sent()) {
							header( 'Link: <'.MMR_CSS_CACHE_URL.$min_path.'>; rel=preload', false );
						}
						wp_register_style('header-'.$i, MMR_CSS_CACHE_URL.$min_path,false,false,$header[$i]['media']);
					} else {
						if($this->http2push && !headers_sent()) {
							header( 'Link: <'.MMR_CSS_CACHE_URL.$file_path.'>; rel=preload', false );
						}
						wp_register_style('header-'.$i, MMR_CSS_CACHE_URL.$file_path,false,false,$header[$i]['media']);
					}

					wp_enqueue_style('header-'.$i);

				} else { //external
					wp_dequeue_style($header[$i]['handle']); //need to do this so the order of scripts is retained
					wp_enqueue_style($header[$i]['handle']);
				}
			}
			$wp_styles->done = $done;
		}
	}
	
	public function compress_css_action($full_path) {

		if(is_file($full_path)) {

			require_once('Minify/src/Minify.php');
			require_once('Minify/src/CSS.php');
			require_once('Minify/Converter.php');
			require_once('Minify/src/Exception.php');
			require_once('Minify/src/JS.php');

			file_put_contents($full_path.'.log', date('c')." - COMPRESSING CSS\n",FILE_APPEND);

			$file_size_before = filesize($full_path);

			$minifier = new MatthiasMullie\Minify\CSS($full_path);

			$min_path = str_replace('.css','.min.css',$full_path);

			$minifier->minify($min_path);

			$file_size_after = filesize($min_path);

			file_put_contents($full_path.'.log', date('c')." - COMPRESSION COMPLETE - ".$this->human_filesize($file_size_before-$file_size_after)." saved\n",FILE_APPEND);
		}
	}
	
	public function compress_js_action($full_path) {

		if(is_file($full_path)) {

			$file_size_before = filesize($full_path);

			if(function_exists('exec') && exec('command -v java >/dev/null && echo "yes" || echo "no"') == 'yes' && exec('java -version 2>&1',$jvoutput) && preg_match("/version\ \"[0-9]+\.([7-9]{1}+|[0-9]{2,}+)\..*/", $jvoutput[0])) {

				file_put_contents($full_path.'.log', date('c')." - COMPRESSING JS WITH CLOSURE\n",FILE_APPEND);

				// Remove Javascript String Continuations
				$contents = file_get_contents($full_path);
				if(strpos($contents, "\\".PHP_EOL) !== FALSE) { //only remove continuations if they exist
					$contents = preg_replace('#\\\\(\n|\r\n?)#', '', $contents);
					file_put_contents($full_path, $contents);
				}

				$cmd = 'java -jar \''.WP_PLUGIN_DIR.'/merge-minify-refresh/closure-compiler.jar\' --language_in ECMASCRIPT5 --warning_level QUIET --js \''.$full_path.'\' --js_output_file \''.$full_path.'.tmp\'';

				exec($cmd . ' 2>&1', $output);

				if(count($output) == 0) {
					$min_path = str_replace('.js','.min.js',$full_path);
					rename($full_path.'.tmp',$min_path);
					$file_size_after = filesize($min_path);
					file_put_contents($full_path.'.log', date('c')." - COMPRESSION COMPLETE - ".$this->human_filesize($file_size_before-$file_size_after)." saved\n",FILE_APPEND);
				} else {

					ob_start();
					var_dump($output);
					$error=ob_get_contents();
					ob_end_clean();

					file_put_contents($full_path.'.log', date('c')." - COMPRESSION FAILED\n".$error,FILE_APPEND);
					unlink($full_path.'.tmp');
				}
			} else {
				
				require_once('Minify/src/Minify.php');
				require_once('Minify/src/CSS.php');
				require_once('Minify/Converter.php');
				require_once('Minify/src/Exception.php');
				require_once('Minify/src/JS.php');
				
				file_put_contents($full_path.'.log', date('c')." - COMPRESSING WITH MINIFY (PHP exec not available or java not found)\n",FILE_APPEND);
				
				$minifier = new MatthiasMullie\Minify\JS($full_path);
			
				$min_path = str_replace('.js','.min.js',$full_path);
				
				$minifier->minify($min_path);
				
				$file_size_after = filesize($min_path);
				
				file_put_contents($full_path.'.log', date('c')." - COMPRESSION COMPLETE - ".$this->human_filesize($file_size_before-$file_size_after)." saved\n",FILE_APPEND);
			}
		}
	}
	
	//thanks to http://php.net/manual/en/function.filesize.php#106569
	private function human_filesize($bytes, $decimals = 2) {
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}
}
 
$mergeminifyrefresh = new MergeMinifyRefresh();