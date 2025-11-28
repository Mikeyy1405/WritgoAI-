<?php
/**
 * The footer for the WritgoCMS theme
 *
 * @package WritgoCMS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

    <footer id="colophon" class="site-footer">
        <div class="footer-widgets">
            <div class="footer-widgets-inner">
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
        </div><!-- .footer-widgets -->

        <div class="site-info">
            <div class="site-info-inner">
                <?php
                if ( has_nav_menu( 'footer' ) ) :
                    wp_nav_menu(
                        array(
                            'theme_location' => 'footer',
                            'menu_class'     => 'footer-menu',
                            'container'      => false,
                            'depth'          => 1,
                            'fallback_cb'    => false,
                        )
                    );
                endif;
                ?>

                <div class="copyright">
                    <?php
                    printf(
                        /* translators: %1$s: Year, %2$s: Site name */
                        esc_html__( '© %1$s %2$s. All rights reserved.', 'writgocms' ),
                        esc_html( gmdate( 'Y' ) ),
                        esc_html( get_bloginfo( 'name' ) )
                    );
                    ?>
                    <span class="theme-credit">
                        <?php
                        printf(
                            /* translators: %s: Theme name */
                            esc_html__( 'Powered by %s', 'writgocms' ),
                            '<a href="https://github.com/Mikeyy1405/WritgoCMS" rel="nofollow">WritgoCMS</a>'
                        );
                        ?>
                    </span>
                </div>
            </div>
        </div><!-- .site-info -->
    </footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
