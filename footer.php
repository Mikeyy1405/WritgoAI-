<?php
/**
 * The footer template
 *
 * @package WritgoCMS
 * @version 1.0.0
 */

?>
    </div><!-- #content -->

    <footer id="colophon" class="site-footer">
        <div class="footer-container">
            <?php if ( is_active_sidebar( 'footer-1' ) || is_active_sidebar( 'footer-2' ) || is_active_sidebar( 'footer-3' ) ) : ?>
                <div class="footer-widgets">
                    <?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
                        <div class="footer-widget-area">
                            <?php dynamic_sidebar( 'footer-1' ); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( is_active_sidebar( 'footer-2' ) ) : ?>
                        <div class="footer-widget-area">
                            <?php dynamic_sidebar( 'footer-2' ); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( is_active_sidebar( 'footer-3' ) ) : ?>
                        <div class="footer-widget-area">
                            <?php dynamic_sidebar( 'footer-3' ); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ( has_nav_menu( 'footer' ) ) : ?>
                <nav class="footer-navigation">
                    <?php
                    wp_nav_menu( array(
                        'theme_location' => 'footer',
                        'menu_class'     => 'footer-menu',
                        'container'      => false,
                        'depth'          => 1,
                        'fallback_cb'    => false,
                    ) );
                    ?>
                </nav>
            <?php endif; ?>

            <div class="footer-bottom">
                <div class="site-info">
                    <span class="copyright">
                        &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> 
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                            <?php bloginfo( 'name' ); ?>
                        </a>
                    </span>
                    <span class="theme-credit">
                        <?php
                        printf(
                            /* translators: %s: Theme name */
                            esc_html__( 'Powered by %s', 'writgocms' ),
                            '<a href="https://github.com/Mikeyy1405/WritgoCMS" target="_blank" rel="noopener">WritgoCMS</a>'
                        );
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </footer>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
