<?php
/**
 * Dynamic color CSS
 *
 * @package     Cooked
 * @subpackage  Styles
 * @since       1.0.0
 */

$_cooked_settings       = Cooked_Settings::get();
$_cooked_dark_css_scope = Cooked_Settings::get_dark_mode_scope();
// White text sits on the main colours (and the main colour is used as text on white), so
// darken them where needed to meet WCAG AA contrast. Hover aims a little higher so it stays darker.
$_cooked_main_color       = Cooked_Functions::accessible_color( $_cooked_settings['main_color'] );
$_cooked_main_color_hover = Cooked_Functions::accessible_color( $_cooked_settings['main_color_hover'], 5.5 );

?>/* Main Color */
.cooked-button,
.cooked-fsm .cooked-fsm-top,
.cooked-fsm .cooked-fsm-mobile-nav,
.cooked-fsm .cooked-fsm-mobile-nav a.cooked-active,
.cooked-browse-search-button,
.cooked-icon-loading,
.cooked-progress span,
.cooked-recipe-search .cooked-field-wrap-select.cooked-taxonomy-selected .cooked-browse-select,
.cooked-recipe-card-modern .cooked-recipe-card-sep,
.cooked-recipe-card-modern-centered .cooked-recipe-card-sep { background:<?php echo esc_html( $_cooked_main_color ); ?>; }
.cooked-timer-obj,
.cooked-fsm a { color:<?php echo esc_html( $_cooked_main_color ); ?>; }
/* A ring, not text: the main colour as set. */
.cooked-gallery .cooked-gallery-nav-thumbs .cooked-gallery-active::after { box-shadow:inset 0 0 0 2px <?php echo esc_html( $_cooked_settings['main_color'] ); ?>; }

/* Main Color Darker */
.cooked-button:hover,
.cooked-recipe-search .cooked-field-wrap-select.cooked-taxonomy-selected .cooked-browse-select:hover,
.cooked-browse-search-button:hover { background:<?php echo esc_html( $_cooked_main_color_hover ); ?>; }
<?php

if ( false !== $_cooked_dark_css_scope ) :

	$dm = function ( $selectors ) {
		return Cooked_Settings::prefix_dark_mode_css( $selectors );
	};

	?>
    /* Dark Mode */
	<?php echo wp_kses( $dm( '.cooked-recipe-card' ), [] ); ?> { background:rgba(255,255,255,0.06); border-color:rgba(255,255,255,0.12) !important; box-shadow:none; }
	<?php echo wp_kses( $dm( '.cooked-recipe-card:hover' ), [] ); ?> { border-color:rgba(255,255,255,0.3) !important; box-shadow:none; }
	<?php echo wp_kses( $dm( '.cooked-recipe-card-image, .cooked-recipe-card .cooked-recipe-image-empty' ), [] ); ?> { background-color:rgba(255,255,255,0.08); }
	<?php echo wp_kses( $dm( '.cooked-recipe-card-author, .cooked-recipe-card-excerpt' ), [] ); ?> { color:rgba(255,255,255,0.65); }
	<?php echo wp_kses( $dm( '.cooked-recipe-grid .cooked-recipe:hover .cooked-recipe-card-title' ), [] ); ?> { color:#00e0a9; }
	<?php echo wp_kses( $dm( '.cooked-recipe-search .cooked-browse-select' ), [] ); ?> { background:rgba(255,255,255,0.075); border-color:rgba(255,255,255,0.15); }
	<?php echo wp_kses( $dm( '.cooked-recipe-search .cooked-browse-select:hover' ), [] ); ?> { background:rgba(255,255,255,0.10); border-color:rgba(255,255,255,0.25); }
	<?php echo wp_kses( $dm( '.cooked-recipe-search .cooked-browse-select-block' ), [] ); ?> { background:rgba(0,0,0,0.95); border-color:rgba(255,255,255,0.15); box-shadow:0 16px 40px rgba(0,0,0,0.5); color:#fff; }
	<?php echo wp_kses( $dm( '.cooked-recipe-search input[type="text"].cooked-browse-search' ), [] ); ?> { background:rgba(0,0,0,0.25); border-color:rgba(255,255,255,0.15); }
	<?php echo wp_kses( $dm( '.cooked-recipe-search input[type="text"].cooked-browse-search:focus' ), [] ); ?> { border-color:rgba(255,255,255,0.6); box-shadow:0 0 0 1px rgba(255,255,255,0.6); }
	<?php echo wp_kses( $dm( '.cooked-recipe-search .cooked-sortby-wrap > select' ), [] ); ?> { background:rgba(255,255,255,0.075); border-color:rgba(255,255,255,0.15); }
	<?php echo wp_kses( $dm( '.cooked-recipe-search .cooked-sortby-wrap .ts-wrapper.single .ts-control' ), [] ); ?> { background:rgba(255,255,255,0.075); border-color:rgba(255,255,255,0.15); }
	<?php echo wp_kses( $dm( '.cooked-recipe-search .cooked-sortby-wrap .ts-wrapper.single.focus .ts-control, .cooked-recipe-search .cooked-sortby-wrap .ts-wrapper.single.dropdown-active .ts-control' ), [] ); ?> { border-color:rgba(255,255,255,0.6); box-shadow:0 0 0 1px rgba(255,255,255,0.6); }
	<?php echo wp_kses( $dm( '.cooked-recipe-search .cooked-sortby-wrap .ts-dropdown' ), [] ); ?> { background:#1f2328; border-color:rgba(255,255,255,0.15); color:#fff; box-shadow:0 12px 32px rgba(0,0,0,0.5); }
	<?php echo wp_kses( $dm( '.cooked-recipe-search .cooked-sortby-wrap .ts-dropdown .active' ), [] ); ?> { background:rgba(255,255,255,0.12); color:#fff; }
	<?php echo wp_kses( $dm( '.cooked-recipe-search .cooked-browse-select-block .cooked-tax-column a:not(.cooked-filter-all):hover, .cooked-recipe-search .cooked-browse-select-block .cooked-tax-column a:not(.cooked-filter-all):focus-visible, .cooked-browse-select-inline-block .cooked-tax-column a:not(.cooked-filter-all):hover, .cooked-browse-select-inline-block .cooked-tax-column a:not(.cooked-filter-all):focus-visible' ), [] ); ?> { background:rgba(255,255,255,0.08); color:#fff; }
	<?php echo wp_kses( $dm( '.cooked-recipe-search .cooked-browse-select-block .cooked-tax-column span.cooked-tax-column-title, .cooked-recipe-search .cooked-browse-select-block .cooked-tax-column-head, .cooked-browse-select-inline-block .cooked-tax-column span.cooked-tax-column-title, .cooked-browse-select-inline-block .cooked-tax-column-head' ), [] ); ?> { border-bottom-color:rgba(255,255,255,0.15); }
	<?php echo wp_kses( $dm( '.cooked-recipe-search .cooked-browse-select-block .cooked-tax-column i.cooked-icon, .cooked-browse-select-inline-block .cooked-tax-column i.cooked-icon' ), [] ); ?> { color:rgba(255,255,255,0.5); }
	<?php echo wp_kses( $dm( '.cooked-browse-select-inline-block' ), [] ); ?> { background:rgba(255,255,255,0.05); border-color:rgba(255,255,255,0.12); box-shadow:none; }
	<?php echo wp_kses( $dm( '.cooked-recipe-search .cooked-filter-option:hover' ), [] ); ?> { background:rgba(255,255,255,0.08); }
	<?php echo wp_kses( $dm( '.cooked-recipe-search .cooked-filter-option input[type="checkbox"]' ), [] ); ?> { accent-color:#00e0a9; }
	<?php echo wp_kses( $dm( '.cooked-recipe-search .cooked-filter-all' ), [] ); ?> { color:#ff8a65; }
	<?php echo wp_kses( $dm( '.cooked-filter-chip' ), [] ); ?> { background:rgba(255,255,255,0.1); color:#fff; }
	<?php echo wp_kses( $dm( '.cooked-filter-chip:hover, .cooked-filter-chip:focus-visible' ), [] ); ?> { background:rgba(255,255,255,0.18); color:#fff; }
	<?php echo wp_kses( $dm( '.cooked-recipe-results.cooked-is-loading::after' ), [] ); ?> { border-color:rgba(255,255,255,0.2); border-top-color:#00e0a9; }
	<?php echo wp_kses( $dm( '.cooked-pagination-numbered' ), [] ); ?> > span { color:rgba(255,255,255,0.5); }
	<?php echo wp_kses( $dm( '.cooked-recipe-info span.cooked-print' ), [] ); ?> > a,
	<?php echo wp_kses( $dm( '.cooked-recipe-info span.cooked-fsm-button' ), [] ); ?> { color:rgba(255,255,255,0.5); }
	<?php echo wp_kses( $dm( '.cooked-recipe-info span.cooked-print' ), [] ); ?> > a:hover,
	<?php echo wp_kses( $dm( '.cooked-recipe-info span.cooked-fsm-button:hover' ), [] ); ?> { color:#fff; }
	<?php echo wp_kses( $dm( '.cooked-recipe-info span.cooked-time-icon, .cooked-recipe-info span.cooked-servings-icon' ), [] ); ?> { color:#fff; opacity:0.5; }
	<?php echo wp_kses( $dm( '.single-cp_recipe .cooked-recipe-ingredients' ), [] ); ?> { border-color:rgba(255,255,255,0.25); }
	<?php echo wp_kses( $dm( '.single-cp_recipe .cooked-recipe-directions' ), [] ); ?> { border-color:rgba(255,255,255,0.25); }
	<?php echo wp_kses( $dm( '.cooked-recipe-ingredients .cooked-ingredient-checkbox' ), [] ); ?> { border-color:rgba(255,255,255,0.25); color:#fff; }
	<?php echo wp_kses( $dm( '.cooked-recipe-ingredients .cooked-ingredient-checkbox:hover' ), [] ); ?> { border-color:rgba(255,255,255,0.5); }
	<?php echo wp_kses( $dm( '.cooked-nutrition-label' ), [] ); ?> { background:rgba(0,0,0,0.25); border:none; border-radius:5px; }
	<?php echo wp_kses( $dm( '.cooked-nutrition-label .cooked-nutrition-title' ), [] ); ?> { color:#fff; }
	<?php echo wp_kses( $dm( '.single-cp_recipe .cooked-nutrition-label .cooked-nut-hr' ), [] ); ?> { border-color:rgba(255,255,255,0.15); }
	<?php echo wp_kses( $dm( '.cooked-nutrition-label dt.cooked-nut-spacer, .cooked-nutrition-label dl.cooked-nut-spacer' ), [] ); ?> { background:rgba(255,255,255,0.15); }
	<?php echo wp_kses( $dm( '.cooked-nutrition-label dt, .cooked-nutrition-label dl.cooked-nut-bottom dt, .cooked-nutrition-label dl.cooked-nut-bottom dt:nth-last-child(2):nth-child(2n)' ), [] ); ?> { border-color:rgba(255,255,255,0.15); }
	<?php echo wp_kses( $dm( '.cooked-recipe-card-modern .cooked-recipe-card-sep, .cooked-recipe-card-modern-centered .cooked-recipe-card-sep' ), [] ); ?> { background:rgba(255,255,255,0.25); }
	<?php echo wp_kses( $dm( '.cooked-fsm' ), [] ); ?> { background:#000; color:#fff; }
	<?php echo wp_kses( $dm( '.cooked-fsm .cooked-fsm-ingredients' ), [] ); ?> { background:rgba(255,255,255,0.1); }
	<?php echo wp_kses( $dm( '.cooked-recipe .cooked-rating-stars' ), [] ); ?> > .cooked-rating-star.cooked-rating-star-empty,
	<?php echo wp_kses( $dm( '.cooked-recipe .cooked-ratable .cooked-rating-stars.cooked-user-rated' ), [] ); ?> > .cooked-rating-star.cooked-rating-star-empty { color:rgba(255,255,255,0.25); }
	<?php echo wp_kses( $dm( '.cooked-allergen, .cooked-allergen .cooked-icon' ), [] ); ?> { color:rgba(255,255,255,0.5); }
	<?php echo wp_kses( $dm( '.cooked-allergen:hover .cooked-icon' ), [] ); ?> { color:#fff; }
	<?php echo wp_kses( $dm( '#cooked-timers-wrap' ), [] ); ?> { background:#191919; color:#fff; border-color:rgba(255,255,255,0.12); box-shadow:0 -10px 40px rgba(0,0,0,0.4); }
	<?php echo wp_kses( $dm( '#cooked-timers-wrap .cooked-timer-block .cooked-timer-step' ), [] ); ?> { color:rgba(255,255,255,0.55); }
	<?php echo wp_kses( $dm( '#cooked-timers-wrap .cooked-timer-block .cooked-timer-desc' ), [] ); ?> { color:rgba(255,255,255,0.65); }
	<?php echo wp_kses( $dm( '#cooked-timers-wrap .cooked-timer-block .cooked-timer-close, #cooked-timers-wrap .cooked-timer-block .cooked-timer-control' ), [] ); ?> { color:rgba(255,255,255,0.6); }
	<?php echo wp_kses( $dm( '#cooked-timers-wrap .cooked-timer-block .cooked-timer-close:hover, #cooked-timers-wrap .cooked-timer-block .cooked-timer-control:hover' ), [] ); ?> { background:rgba(255,255,255,0.08); color:#fff; }
	<?php echo wp_kses( $dm( '#cooked-timers-wrap .cooked-timer-block.cooked-paused' ), [] ); ?> { background:rgba(255,255,255,0.05); }
	<?php echo wp_kses( $dm( '#cooked-timers-wrap .cooked-timer-block.cooked-paused .cooked-timer-obj' ), [] ); ?> { color:rgba(255,255,255,0.5); }
	<?php echo wp_kses( $dm( '#cooked-timers-wrap .cooked-timer-block.cooked-timer-flash' ), [] ); ?> { background:rgba(255,255,255,0.1); }
	<?php echo wp_kses( $dm( '#cooked-timers-wrap .cooked-timer-block .cooked-progress' ), [] ); ?> { background:rgba(255,255,255,0.15); }
	<?php
	echo wp_kses( $dm( '.cooked-related-recipes-empty' ), [] );
	?>
    { background:rgba(255, 255,255,0.25); color:#fff; }
	<?php
	echo wp_kses( $dm( '.cooked-recipe-search select.cooked-sortby-select option' ), [] );
	?>
    { background:rgba(79, 79, 79, 1); color:#fff; }
	<?php

endif;
