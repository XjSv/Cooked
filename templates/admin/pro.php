<div id="cooked-welcome-screen">
    <div class="wrap about-wrap">
        <div id="cooked-welcome-panel" class="cooked-welcome-panel">

            <img src="<?php echo COOKED_URL; ?>/assets/admin/images/pro-banner.png" class="cooked-welcome-banner">

            <div class="cooked-welcome-panel-intro">
                <h1><?php
                    /* translators: referring to "Cooked Pro" */
                    echo sprintf( __('Ready for %s?','cooked'), 'Cooked Pro' );
                ?></h1>
                <?php
                    /* translators: referring to "Cooked Pro" */
                    echo wpautop( sprintf( __( 'The %s upgrade adds loads of new features like ratings, favorites, user profiles, automatic nutrition information and more. Check out the list below for all of the details.', 'cooked' ), 'Cooked Pro' ) );
                ?>
            </div>

            <div class="cooked-welcome-panel-content">
                <div class="cooked-welcome-panel-column-container">
                    <div class="cooked-welcome-panel-column cooked-welcome-panel-full">
                        <div class="cooked-pro-features">
                            <ul class="cooked-whatsnew-list cooked-whatsnew-pro">
                                <li><i class="cooked-icon cooked-icon-star"></i> <?php _e( "Premium Support","cooked" ); ?></li>
                                <li><i class="cooked-icon cooked-icon-star"></i> <?php _e( "User Profiles","cooked" ); ?></li>
                                <li><i class="cooked-icon cooked-icon-star"></i> <?php _e( "User Ratings","cooked" ); ?></li>
                                <li><i class="cooked-icon cooked-icon-star"></i> <?php _e( "User Favorites","cooked" ); ?></li>
                                <li><i class="cooked-icon cooked-icon-star"></i> <?php _e( "Ingredient Links","cooked" ); ?></li>
                            </ul>
                            <ul class="cooked-whatsnew-list cooked-whatsnew-pro">
                                <li><i class="cooked-icon cooked-icon-star"></i> <?php _e( "Social Sharing","cooked" ); ?></li>
                                <li><i class="cooked-icon cooked-icon-star"></i> <?php _e( "Recipe Submissions","cooked" ); ?></li>
                                <li><i class="cooked-icon cooked-icon-star"></i> <?php _e( "Modern Grid Layout","cooked" ); ?></li>
                                <li><i class="cooked-icon cooked-icon-star"></i> <?php _e( "Full-Width Layout","cooked" ); ?></li>
                                <li><i class="cooked-icon cooked-icon-star"></i> <?php _e( "Pagination Options","cooked" ); ?></li>
                                <li><i class="cooked-icon cooked-icon-star"></i> <?php _e( "Auto Nutrition Facts","cooked" ); ?></li>
                            </ul>
                            <ul class="cooked-whatsnew-list cooked-whatsnew-pro">
                                <li><i class="cooked-icon cooked-icon-star"></i> <?php _e( "Compact List Layout","cooked" ); ?></li>
                                <li><i class="cooked-icon cooked-icon-star"></i> <?php _e( "Fitness Layout","cooked" ); ?></li>
                                <li><i class="cooked-icon cooked-icon-star"></i> <?php _e( "Cuisines","cooked" ); ?></li>
                                <li><i class="cooked-icon cooked-icon-star"></i> <?php _e( "Cooking Methods","cooked" ); ?></li>
                                <li><i class="cooked-icon cooked-icon-star"></i> <?php _e( "Tags","cooked" ); ?></li>
                            </ul>
                        </div>
                        <div class="cooked-welcome-bottom">
                            <a href="https://cooked.pro/" target="_blank" class="cooked-pro-button"><?php
                                /* translators: referring to "Cooked Pro" */
                                echo sprintf( __( "Get %s","cooked" ), "Cooked Pro" );
                            ?></a>
                            <span class="cooked-coupon-code"><?php
                                /* translators: referring to the "COOKED10" coupon code to get "10%" off Cooked Pro */
                                echo sprintf( __( 'Use coupon code %1$s for %2$s off!', 'cooked' ), '<strong>COOKED10</strong>', '<strong>10%</strong>' );
                            ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
