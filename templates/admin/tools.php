<div id="cooked-settings-prewrap">
    <div id="cooked-settings-wrap">

        <div class="cooked-settings-header">
            <i class="cooked-icon cooked-icon-cooked-icon"></i>&nbsp;&nbsp;<?php _e( 'Cooked Tools', 'cooked' ); ?>
        </div>
        <div id="cooked-settings-panel" class="wrap">

            <div id="cooked_recipe_settings" class="cooked-settings-form-wrapper">

                <div class="cooked-settings-tab-content-wrapper">
                    <div id="cooked-settings-tab-content-tools" class="cooked-settings-tab-content">
                        <h2 class="screen-reader-text"><?php esc_html_e( 'Tools', 'cooked' ); ?></h2>
                        <div class="recipe-setting-block calculate_related_button cooked-bm-25">
                            <h3 class="cooked-settings-title"><?php esc_html_e( 'Calculate Related Recipes', 'cooked' ); ?></h3>
                            <p><?php esc_html_e( 'Pre-calculate related recipes for every published recipe. Uses default shortcode options. Run this after importing or adding many recipes, or when the cache was cleared. One recipe is processed per step to avoid memory issues on large sites.', 'cooked' ); ?></p>
                            <?php Cooked_Settings::field_calculate_related_button( 'cooked_calculate_related_button', __( 'Calculate Related Recipes', 'cooked' ) ); ?>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>
