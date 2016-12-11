/*
*
*		WORDPRESS GULP FILE
*
*		To be used with a css and js concat/uglify Wordpress plugin of your choice.
*		BWP Minify is installed in Wordpress. Please activate it for production.
*
*		Available tasks:
*
*	 	- gulp dev
*	 	- gulp watch - without BrowserSync
*	 	- gulp build
*
*/

var manifest      = './manifest.json';
var gulp          = require('gulp'),
    autoprefixer  = require('gulp-autoprefixer'),
    browserSync   = require('browser-sync'),
    sass          = require('gulp-sass'),
    concat        = require('gulp-concat'),
	gulpif        = require('gulp-if'),
	gutil         = require('gulp-util'),
	htmlhint      = require('gulp-htmlhint'),
	imagemin      = require('gulp-imagemin'),
	jeditor       = require("gulp-json-editor"),
	jshint        = require('gulp-jshint'),
	notify        = require('gulp-notify'),
	plumber       = require('gulp-plumber'),
	rename        = require('gulp-rename'),
	sourcemaps    = require('gulp-sourcemaps'),
	svgmin        = require('gulp-svgmin'),
	uglify        = require('gulp-uglify'),
	useref        = require('gulp-useref'),
	path          = require('path'),
	reload        = browserSync.reload,
	config        = require( manifest ),
    assets        = ( config.assets.length )? config.assets+'/' : '',
	src           = ( config.src.length )? config.src+'/' : '';

// Gulp plumber error handler
var onError = function(err) {
    //console.log(err); // Commenting out because it's mostly annoying. Enable as needed.
    //this.emit('end');
};

/*
*	IMAGE/SVG TASKS
------------------------------------------------------*/

// Compresses images for production.
gulp.task('images', function() {
	return gulp.src( './'+assets+'images/**/*.{jpg,jpeg,png,gif}' )
		.pipe(imagemin())
		.pipe(gulp.dest( './'+assets+'images/' ));
});

// Compresses SVG files for production.
gulp.task('svg', function() {
    return gulp.src( './'+assets+'images/**/*.svg' )
        .pipe(svgmin({
            js2svg: {
                pretty: true
            }
        }))
        .pipe(gulp.dest( './'+assets+'images/'));
});



/*
*	JAVASCRIPT TASKS
------------------------------------------------------*/

// Development JS creation.
// Checks for errors and concats. Does not minify.
gulp.task('js', function () {
    return gulp.src( [ './'+assets+'js/*.js'] )
   	.pipe(plumber({errorHandler: notify.onError( {
			message : "<%= error.message %>", title : 'ERROR' }
		)}))
		.pipe(jshint())
		.pipe(jshint.reporter('fail'))
		.pipe(notify(function (file) {
		    if (file.jshint.success) {
		    	return { message : 'JS much excellent success!',
									title : file.relative,
									sound: false,
									icon: path.join('node_modules/gulp-notify/assets', 'gulp.png'),
								};
		    }
		    var errors = file.jshint.results.map(function (data) {
		       	if (data.error) {
		        	return "(" + data.error.line + ':' + data.error.character + ') ' + data.error.reason;
		        }
		    }).join("\n");
		    return { message : file.relative + " (" + file.jshint.results.length + " errors)\n" + errors,
								sound: "Frog",
								emitError : true,
								icon: path.join('node_modules/gulp-notify/assets', 'gulp-error.png'),
								title : 'JSLint'
							};
    	}))
    .pipe(reload({stream: true}));
});




/*
*	CSS TASKS
------------------------------------------------------*/

// Development CSS creation.
// Checks for errors and concats. Does not minify.
gulp.task('scss', function() {
	return gulp.src( './'+src+'scss/**/*.scss')
    .pipe(plumber({errorHandler: onError}))
    .pipe(sass({outputStyle: 'compressed'}))
    	.on('error', notify.onError(function( err ){
    			return { message: err.message, title : 'ERROR', sound: "Frog"};
    		})
    	)
    	.pipe(autoprefixer({browsers: ['last 2 versions', 'ie >= 9', '> 1%']}))
		.pipe(gulp.dest( './'))
		.on('error', notify.onError(function( err ){
				return { message: err.message, title : 'ERROR', sound: "Frog"};
			})
		)
		.pipe(notify({ message: 'Styles much compiled success!', title : 'sass', sound: false }))
		.pipe(reload({stream: true}));
});




/*
	BUILD
------------------------------------------------------*/

// This runs all the tasks for production.
gulp.task('build:app', [ 'images', 'svg'], function () {
    browserSync({ proxy : config.hostname});
});



/*
*	BOWER ASSETS
------------------------------------------------------*/

// Pulls some of the bower assets to the src folders.
// Add/modify as needed.
gulp.task('bower-assets', function(){

	// This copies the bower normalize css file over to the scss components folder.
	// If you updated normalize it will get updated in your app src on next [gulp dev].
	gulp.src( './'+assets+'/bower_components/normalize.css/normalize.css' )
		.pipe(rename("_normalize.scss"))
		.pipe(gulp.dest( './'+src+"scss/components/"));

	// Copies over animate.css from bower to scss components folder.
	gulp.src( './'+assets+'/bower_components/animate.css/animate.css')
		.pipe(rename("_animate.scss"))
		.pipe(gulp.dest( './'+src+"scss/components/"));

    // Copies over flexboxgrid.css from bower to scss components folder.
    gulp.src( './'+assets+'/bower_components/flexboxgrid/dist/flexboxgrid.css')
    	.pipe(rename("_flexboxgrid.scss"))
    	.pipe(gulp.dest( './'+src+"scss/components/"));

	// This swtiches to the main theme
	gulp.src( manifest )
		.pipe(jeditor({
			'theme': 'main',
			'env': 'dev'
  		}))
  	.pipe(gulp.dest("./"));

});




/*
*	TASKS
* ------------------------------------------------------
*
* [gulp dev] - Development task with BrowserSync
*
* [gulp watch] - Development task w/o BrowserSync
*
* [gulp build] - Build task, just compresses images
*
*/

// gulp dev
gulp.task('dev', ['bower-assets'], function () {

    browserSync( {
	    			proxy : config.hostname,
	    			files: ['{partials,classes,inc}/**/*.php', '*.php'],
						logLevel : 'info',
						logFileChanges : false,
						notify: false,
	    			snippetOptions: {
		    			whitelist: ['/wp-admin/admin-ajax.php'],
		    			blacklist: [ '/wp-admin/**', './.sass-cache/']
		    		}
		    	});

	// The rest... watch the files and run the task(s).
    gulp.watch( src+"scss/**/*.scss", ['scss']);
    gulp.watch([ assets+"js/**/*.js"], ['js']);

});

// gulp watch
gulp.task('watch', ['bower-assets'], function () {

	// The rest... watch the files and run the task(s).
    gulp.watch( src+"scss/**/*.scss", ['scss']);
    gulp.watch([ assets+"js/**/*.js"], ['js']);

});

// gulp build
gulp.task('build', ['build:app']);
