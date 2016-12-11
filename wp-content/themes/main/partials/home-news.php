            <section class="container section homepage-blog">
                <div class="row">
                    
                    <?php
                    $args = array( 'posts_per_page' => 3);
                    $wp_query = new WP_Query( $args );
                    
                    if ( $wp_query->have_posts() ) :
                        while ( $wp_query->have_posts() ) : $wp_query->the_post();
                    ?>
                    
                    <article class="span4 homepage-blog-entry">
                        <header>
                            <h2 class="homepage-blog-title"><a href="<?php the_permalink() ?>"><?php the_title() ?></a></h2>
                            <p class="homepage-blog-date"><time pubdate=""><?php the_time('F jS, Y') ?></time></p>
                        </header>
                        <div class="homepage-blog-content">
                            <?php the_content('', true) ?>
                        </div>
                        <footer class="homepage-blog-footer">
                            <a class="homepage-read-more" href="<?php the_permalink() ?>">Continue Reading <i class="fa fa-angle-right"></i></a>
                        </footer>
                    </article><!-- END .span4.homepage-blog-entry -->
                    
                    <?php   
                        endwhile;
                        
                        wp_reset_postdata();
                    
                    endif; 
                    ?>

                </div> <!-- END .row -->
            </section><!-- END .container.section.homepage-news -->  