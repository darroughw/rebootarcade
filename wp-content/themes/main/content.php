                        <article class="blog-entry">
                            <header class="blog-header">
                                <h2 class="blog-title">
                                    <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </h2>
                                
                                <?php
                                //$localDate = strtotime( get_post_time('U', true) );
                                //date_default_timezone_set("UTC");
                                $utcDate = '' //= date("Y-d-mTG:i:sz", $localDate);
                                ?>
                                <p class="blog-date-author"><time pubdate="<?php echo $utcDate ?>"><?php the_time('F jS, Y') ?></time> | <span class="blog-author">By <?php the_author() ?></span></p>
                            </header>
                            <div class="blog-content">
                            
                                <?php the_content('') ?>
                            
                            </div>
                            <footer class="blog-footer">
                                <div class="read-more text-right">
                                
                                    <a class="continue-reading-link" href="<?php the_permalink() ?>">Continue Reading <i class="fa fa-angle-right"></i></a>
                                
                                </div>
                                <div class="shares">
                                    <div class="twitter-share" data-url="<?php the_permalink() ?>" data-title="<?php the_title_attribute(); ?>"></div>
                                    <div class="facebook-share" data-url="<?php the_permalink() ?>" data-title="<?php the_title_attribute(); ?>"></div>
                                    <div class="googleplus-share" data-url="<?php the_permalink() ?>" data-title="<?php the_title_attribute(); ?>"></div>
                                    <div class="pinterest-share" data-url="<?php the_permalink() ?>" data-title="<?php the_title_attribute(); ?>"></div>
                                </div>
                            </footer>
                        </article> <!-- END .blog-entry -->