<?php
/**
 * Cooked Welcome (What's New) admin page
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       1.0.0
 */

$cooked_banner = apply_filters( 'cooked_welcome_banner_img', COOKED_URL . 'assets/images/welcome-banner.png' );
// Cooked Pro 1.x still returns the pre-2.0 images path.
$cooked_banner = str_replace( [ COOKED_URL . '/assets/admin/images/', COOKED_URL . 'assets/admin/images/' ], COOKED_URL . 'assets/images/', $cooked_banner );

$cooked_quick_links = [
    [
        'icon'     => 'link-lt',
        'label'    => __( 'Documentation', 'cooked' ),
        'url'      => 'https://docs.cooked.pro/',
        'external' => true,
    ],
    [
        'icon'     => 'comment',
        'label'    => __( 'Discord', 'cooked' ),
        'url'      => 'https://discord.gg/qdGwXaXxc6',
        'external' => true,
    ],
    [
        'icon'  => 'gear',
        'label' => __( 'Cooked Settings', 'cooked' ),
        'url'   => admin_url( 'admin.php?page=cooked_settings' ),
    ],
    [
        'icon'  => 'pencil',
        'label' => __( 'Create a Recipe', 'cooked' ),
        'url'   => admin_url( 'post-new.php?post_type=cp_recipe' ),
    ],
];
if ( ! class_exists( 'Cooked_Pro_Plugin' ) ) {
    $cooked_quick_links[] = [
        'icon'      => 'star',
        'label'     => __( 'Upgrade to Pro', 'cooked' ),
        'url'       => admin_url( 'admin.php?page=cooked_pro' ),
        'highlight' => true,
    ];
}

?>
<div id="cooked-welcome-page" class="cooked-welcome">

    <img src="<?php echo esc_url( $cooked_banner ); ?>" class="cooked-admin-banner" alt="Cooked" width="1449" height="385">

    <div class="cooked-admin-intro">
        <h2>
        <?php
            /* translators: referring to "Cooked" */
            printf( esc_html__( 'Thank you for using %s!', 'cooked' ), 'Cooked' );
        ?>
        </h2>
        <p>
        <?php
            echo wp_kses_post(
                sprintf(
                    /* translators: 1: plugin name, 2: Settings link, 3: documentation link, 4: Discord link */
                    __( 'If this is your first time using %1$s, head over to the %2$s page for some initial configuration. You can also check out the %3$s if you get stuck or contact us on %4$s. If you just recently updated, you can find out what\'s new below.', 'cooked' ),
                    'Cooked',
                    '<a href="' . esc_url( admin_url( 'admin.php?page=cooked_settings' ) ) . '">' . __( 'Settings', 'cooked' ) . '</a>',
                    '<a href="https://docs.cooked.pro/" target="_blank" rel="noopener noreferrer">' . __( 'documentation', 'cooked' ) . '</a>',
                    '<a href="https://discord.gg/qdGwXaXxc6" target="_blank" rel="noopener noreferrer">' . __( 'Discord', 'cooked' ) . '</a>'
                )
            );
            ?>
        </p>
    </div>

    <div class="cooked-welcome-columns">
        <section class="cooked-welcome-card cooked-welcome-links">
            <h3><?php esc_html_e( 'Quick Links', 'cooked' ); ?></h3>
            <ul>
                <?php foreach ( $cooked_quick_links as $cooked_link ) : ?>
                    <li>
                        <a href="<?php echo esc_url( $cooked_link['url'] ); ?>"<?php echo ! empty( $cooked_link['highlight'] ) ? ' class="is-highlight"' : ''; ?><?php echo ! empty( $cooked_link['external'] ) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
                            <span class="cooked-welcome-link-icon"><i class="cooked-icon cooked-icon-<?php echo esc_attr( $cooked_link['icon'] ); ?>" aria-hidden="true"></i></span>
                            <span class="cooked-welcome-link-label"><?php echo esc_html( $cooked_link['label'] ); ?></span>
                            <?php if ( ! empty( $cooked_link['external'] ) ) : ?>
                                <span class="cooked-welcome-link-external" aria-hidden="true">&#8599;</span>
                                <span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'cooked' ); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="cooked-welcome-card cooked-welcome-changelog">
            <?php do_action( 'cooked_welcome_before_changelog' ); ?>
            <?php echo wp_kses_post( Cooked_Functions::parse_readme_changelog() ); ?>
            <?php do_action( 'cooked_welcome_after_changelog' ); ?>
            <button type="button" class="cooked-full-changelog-button"><?php esc_html_e( 'View Full Changelog', 'cooked' ); ?></button>
        </section>
    </div>
</div>
