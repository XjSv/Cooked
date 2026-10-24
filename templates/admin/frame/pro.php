<?php
/**
 * Cooked Pro upsell admin page
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       1.0.0
 */

// Icon (from the Cooked icon font) => feature name.
$cooked_pro_features = [
    'question'    => __( 'Premium Support', 'cooked' ),
    'user'        => __( 'User Profiles', 'cooked' ),
    'star'        => __( 'User Ratings', 'cooked' ),
    'heart'       => __( 'User Favorites', 'cooked' ),
    'link'        => __( 'Ingredient Links', 'cooked' ),
    'thumbs-up'   => __( 'Social Sharing', 'cooked' ),
    'pencil'      => __( 'Recipe Submissions', 'cooked' ),
    'desktop'     => __( 'Modern Grid Layout', 'cooked' ),
    'fullscreen'  => __( 'Full-Width Layout', 'cooked' ),
    'list'        => __( 'Pagination Options', 'cooked' ),
    'check-bold'  => __( 'Auto Nutrition Facts', 'cooked' ),
    'bars'        => __( 'Compact List Layout', 'cooked' ),
    'clock'       => __( 'Fitness Layout', 'cooked' ),
    'recipe-icon' => __( 'Cuisines', 'cooked' ),
    'wrench'      => __( 'Cooking Methods', 'cooked' ),
    'asterisk'    => __( 'Tags', 'cooked' ),
];

?>
<div id="cooked-pro-page" class="cooked-pro">

    <img src="<?php echo esc_url( COOKED_URL . 'assets/images/pro-banner.png' ); ?>" class="cooked-admin-banner" alt="Cooked Pro" width="1449" height="385">

    <div class="cooked-admin-intro">
        <h2>
        <?php
            /* translators: referring to "Cooked Pro" */
            printf( esc_html__( 'Ready for %s?', 'cooked' ), 'Cooked Pro' );
        ?>
        </h2>
        <p>
        <?php
            /* translators: referring to "Cooked Pro" */
            printf( esc_html__( 'The %s upgrade adds loads of new features like ratings, favorites, user profiles, automatic nutrition information and more. Check out the list below for all of the details.', 'cooked' ), 'Cooked Pro' );
        ?>
        </p>
    </div>

    <div class="cooked-pro-cta">
        <p class="cooked-pro-coupon">
            <span class="cooked-pro-coupon-icon"><i class="cooked-icon cooked-icon-star" aria-hidden="true"></i></span>
            <span>
            <?php
                /* translators: referring to the "COOKED10" coupon code to get "10%" off Cooked Pro */
                echo wp_kses_post( sprintf( __( 'Use coupon code %1$s for %2$s off!', 'cooked' ), '<code id="cooked-pro-coupon-code">COOKED10</code>', '<strong>10%</strong>' ) );
            ?>
            </span>
            <button type="button" class="cooked-copy-code" data-target="cooked-pro-coupon-code"><?php esc_html_e( 'Copy', 'cooked' ); ?></button>
        </p>
        <a href="https://cooked.pro/" target="_blank" rel="noopener noreferrer" class="components-button is-primary cooked-pro-button">
        <?php
            /* translators: referring to "Cooked Pro" */
            printf( esc_html__( 'Get %s', 'cooked' ), 'Cooked Pro' );
        ?>
        </a>
    </div>

    <ul class="cooked-pro-features">
        <?php foreach ( $cooked_pro_features as $cooked_pro_icon => $cooked_pro_feature ) : ?>
            <li>
                <span class="cooked-pro-feature-icon"><i class="cooked-icon cooked-icon-<?php echo esc_attr( $cooked_pro_icon ); ?>" aria-hidden="true"></i></span>
                <?php echo esc_html( $cooked_pro_feature ); ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
