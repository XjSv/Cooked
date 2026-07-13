<?php

$_cooked_settings = Cooked_Settings::get();
$_cooked_dark_css_scope = Cooked_Settings::get_dark_mode_scope();

?>/* Main Color */
.cooked-button,
.cooked-fsm .cooked-fsm-top,
.cooked-fsm .cooked-fsm-mobile-nav,
.cooked-fsm .cooked-fsm-mobile-nav a.cooked-active,
.cooked-browse-search-button,
.cooked-icon-loading,
.cooked-progress span,
.cooked-recipe-search .cooked-taxonomy-selected,
.cooked-recipe-card-modern:hover .cooked-recipe-card-sep,
.cooked-recipe-card-modern-centered:hover .cooked-recipe-card-sep { background:<?php echo esc_html( $_cooked_settings['main_color'] ); ?>; }
.cooked-timer-obj,
.cooked-fsm a { color:<?php echo esc_html( $_cooked_settings['main_color'] ); ?>; }

/* Main Color Darker */
.cooked-button:hover,
.cooked-recipe-search .cooked-taxonomy-selected:hover,
.cooked-browse-search-button:hover { background:<?php echo esc_html( $_cooked_settings['main_color_hover'] ); ?>; }<?php

if ( false !== $_cooked_dark_css_scope ):

$dm = function ( $selectors ) {
    return Cooked_Settings::prefix_dark_mode_css( $selectors );
};

?>/* Dark Mode */
<?php echo $dm( '.cooked-recipe-search .cooked-browse-select' ); ?> { background:rgba(255,255,255,0.075); box-shadow:inset 0 0 0 1px rgba(255,255,255,0.05); }
<?php echo $dm( '.cooked-recipe-search .cooked-browse-select:hover' ); ?> { background:rgba(255,255,255,0.10); box-shadow:inset 0 0 0 1px rgba(255,255,255,0.05); }
<?php echo $dm( '.cooked-recipe-search .cooked-browse-select-block' ); ?> { background:rgba(0,0,0,0.95); box-shadow:none; border-radius:5px; }
<?php echo $dm( '.cooked-recipe-search input[type="text"]' ); ?> { background:rgba(0,0,0,0.25); border-color:rgba(255,255,255,0.15); }
<?php echo $dm( '.cooked-recipe-search .cooked-sortby-wrap' ); ?> { background:rgba(255,255,255,0.10); }
<?php echo $dm( '.cooked-recipe-search .cooked-sortby-wrap:hover' ); ?> { background:rgba(255,255,255,0.15); }
<?php echo $dm( '.cooked-recipe-search .cooked-sortby-wrap > select' ); ?> { border-color:rgba(0,0,0,0.15); }
<?php echo $dm( '.cooked-recipe-search .cooked-sortby-wrap > select > option' ); ?> { color:#333; }
<?php echo $dm( '.cooked-recipe-search .cooked-browse-select-block .cooked-tax-column > span.cooked-tax-column-title' ); ?> { border-bottom-color:rgba(255,255,255,0.15); }
<?php echo $dm( '.cooked-recipe-search .cooked-browse-select-block .cooked-tax-column i.cooked-icon' ); ?> { color:rgba(255,255,255,0.5); }
<?php echo $dm( '.cooked-recipe-search .cooked-field-wrap-select:before, .cooked-recipe-search .cooked-sortby-wrap:before' ); ?> { color:#fff; }
<?php echo $dm( '.cooked-recipe-grid .cooked-recipe-inside' ); ?> { background:rgba(0,0,0,0.25); box-shadow:none !important }
<?php echo $dm( '.cooked-pagination-numbered > span' ); ?> { color:rgba(255,255,255,0.5); }
<?php echo $dm( '.cooked-recipe-info span.cooked-print > a, .cooked-recipe-info span.cooked-fsm-button' ); ?> { color:rgba(255,255,255,0.5); }
<?php echo $dm( '.cooked-recipe-info span.cooked-print > a:hover, .cooked-recipe-info span.cooked-fsm-button:hover' ); ?> { color:#fff; }
<?php echo $dm( '.cooked-recipe-info span.cooked-time-icon, .cooked-recipe-info span.cooked-servings-icon' ); ?> { color:#fff; opacity:0.5; }
<?php echo $dm( '.cooked-recipe-ingredients .cooked-ingredient-checkbox' ); ?> { border-color:rgba(255,255,255,0.25); color:#fff; }
<?php echo $dm( '.cooked-recipe-ingredients .cooked-ingredient-checkbox:hover' ); ?> { border-color:rgba(255,255,255,0.5); }
<?php echo $dm( '.cooked-nutrition-label' ); ?> { background:rgba(0,0,0,0.25); border:none; border-radius:5px; }
<?php echo $dm( '.cooked-nutrition-label .cooked-nutrition-title' ); ?> { color:#fff; }
<?php echo $dm( '.cooked-nutrition-label .cooked-nut-hr' ); ?> { border-color:rgba(255,255,255,0.15); }
<?php echo $dm( '.cooked-nutrition-label dt.cooked-nut-spacer, .cooked-nutrition-label dl.cooked-nut-spacer' ); ?> { background:rgba(255,255,255,0.15); }
<?php echo $dm( '.cooked-nutrition-label dt, .cooked-nutrition-label dl.cooked-nut-bottom dt, .cooked-nutrition-label dl.cooked-nut-bottom dt:nth-last-child(2):nth-child(2n)' ); ?> { border-color:rgba(255,255,255,0.15); }
<?php echo $dm( '.cooked-recipe-card' ); ?> { background:rgba(0,0,0,0.25); box-shadow:none !important; }
<?php echo $dm( '.cooked-recipe-card-modern, .cooked-recipe-card-modern-centered' ); ?> { box-shadow:none !important; }
<?php echo $dm( '.cooked-recipe-card-modern .cooked-recipe-card-sep, .cooked-recipe-card-modern-centered .cooked-recipe-card-sep' ); ?> { background:rgba(255,255,255,0.25); }
<?php echo $dm( '.cooked-recipe-grid .cooked-recipe-image-empty' ); ?> { background:rgba(255,255,255,0.15); }
<?php echo $dm( '.cooked-fsm' ); ?> { background:#000; color:#fff; }
<?php echo $dm( '.cooked-fsm .cooked-fsm-ingredients' ); ?> { background:rgba(255,255,255,0.1); }
<?php echo $dm( '.cooked-recipe .cooked-rating-stars > .cooked-rating-star.cooked-rating-star-empty, .cooked-recipe .cooked-ratable .cooked-rating-stars.cooked-user-rated > .cooked-rating-star.cooked-rating-star-empty' ); ?> { color:rgba(255,255,255,0.25); }
<?php echo $dm( '.cooked-allergen, .cooked-allergen .cooked-icon' ); ?> { color:rgba(255,255,255,0.5); }
<?php echo $dm( '.cooked-allergen:hover .cooked-icon' ); ?> { color:#fff; }
<?php echo $dm( '#cooked-timers-wrap' ); ?> { background:#191919; color:#fff; box-shadow:0 -5px 30px rgba(0,0,0,0.4); }
<?php echo $dm( '#cooked-timers-wrap .cooked-timer-block i.cooked-icon-times' ); ?> { color:rgba(255,255,255,0.35); }
<?php echo $dm( '#cooked-timers-wrap .cooked-timer-block .cooked-timer-desc' ); ?> { color:rgba(255,255,255,0.65); }
<?php echo $dm( '#cooked-timers-wrap .cooked-timer-block.cooked-paused' ); ?> { background:rgba(255,255,255,0.05); }
<?php echo $dm( '#cooked-timers-wrap .cooked-timer-block.cooked-paused .cooked-timer-obj' ); ?> { color:rgba(255,255,255,0.5); }
<?php echo $dm( '#cooked-timers-wrap .cooked-timer-block .cooked-progress' ); ?> { background:rgba(255,255,255,0.15); }
<?php

endif;
