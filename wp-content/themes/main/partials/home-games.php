            <section class="container section cabinets">
                <div class="row">
                    <h1>Games</h1>

                    <?php if( have_rows('games') ): ?>
                        <ul class="games">

                        <?php while( have_rows('games') ): the_row(); 

                            // vars
                            $name = get_sub_field('game');

                            ?>

                            <li class="game">
                                <?php echo $name ?>
                                <hr>
                            </li>

                        <?php endwhile; ?>

                        </ul>

                    <?php endif; ?> 
                </div>
            </section><!-- END .container.section.intro -->
