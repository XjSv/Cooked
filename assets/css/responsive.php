<?php
/**
 * Dynamic responsive CSS
 *
 * @package     Cooked
 * @subpackage  Styles
 * @since       1.0.0
 */

$_cooked_settings       = Cooked_Settings::get();
$_cooked_dark_css_scope = Cooked_Settings::get_dark_mode_scope();

?>@media screen and ( max-width: <?php echo esc_html( $_cooked_settings['responsive_breakpoint_1'] ); ?>px ) {

	.cooked-recipe-term-grid { margin:3% -1.5%; width: 103%; }
	.cooked-term-block.cooked-col-25 { width: 47%; margin: 0 1.5% 3%; padding: 0; }

}

@media screen and ( max-width: <?php echo esc_html( $_cooked_settings['responsive_breakpoint_2'] ); ?>px ) {

	.cooked-recipe-term-grid { margin:5% -2.5%; width:105%; }
	.cooked-term-block.cooked-col-25 { width:95%; margin:0 2.5% 5%; padding:0; }

	.cooked-recipe-info .cooked-left, .cooked-recipe-info .cooked-right { float:none; display:block; text-align:center; }
	.cooked-recipe-info > section.cooked-right > span, .cooked-recipe-info > section.cooked-left > span { margin:0.5rem 1rem 1rem; }
	.cooked-recipe-info > section.cooked-left > span:last-child, .cooked-recipe-info > span:last-child { margin-right:1rem; }
	.cooked-recipe-info > section.cooked-right > span:first-child { margin-left:1rem; }

	/* Keep the browse filter and search on one row, with sorting below. */
	.cooked-recipe-search form { display:flex; flex-wrap:wrap; gap:0.5rem; }
	.cooked-recipe-search .cooked-fields-wrap { flex:1 1 100%; flex-wrap:nowrap; padding:0; }
	.cooked-recipe-search .cooked-field-wrap-select { flex:0 1 auto; width:auto; position:static; }
	.cooked-recipe-search .cooked-browse-select { width:auto; max-width:9rem; }
	.cooked-recipe-search .cooked-search-field { flex:1 1 auto; width:auto; }
	.cooked-recipe-search .cooked-sortby-wrap { flex:1 1 100%; width:100%; }
	.cooked-recipe-search .cooked-browse-select-block { top:calc(100% + 0.5rem); left:0; width:100%; min-width:0; max-width:none; max-height:60vh; flex-wrap:wrap; }
	.cooked-recipe-search .cooked-browse-select-block .cooked-tax-column { flex:1 1 100%; width:100%; }
	.cooked-recipe-search .cooked-browse-select-block .cooked-tax-column > div:not(.cooked-tax-column-head) { max-height:none; }

	#cooked-timers-wrap { width:32rem; }
	#cooked-timers-wrap .cooked-timer-block.cooked-visible { padding:1.25rem 3.25rem 1.5rem 1.25rem; }
	#cooked-timers-wrap .cooked-timer-block .cooked-timer-desc { padding-right:0; }
	#cooked-timers-wrap .cooked-timer-block .cooked-timer-obj { position:static; transform:none; justify-content:flex-start; margin-top:0.35rem; font-size:1.75rem; }

	body.cooked-fsm-active #cooked-timers-wrap { bottom:0; }

	/* "Slide up" on mobile */
	.cooked-fsm { opacity:1; transform:scale(1); top:120%; }
	.cooked-fsm.cooked-active { top:0; }
	.cooked-fsm .cooked-fsm-mobile-nav { display:block; }
	.cooked-fsm .cooked-fsm-ingredients,
	.cooked-fsm .cooked-fsm-directions-wrap { padding:9rem 2rem 1rem; width:100%; display:none; left:0; background:#fff; }
	.cooked-fsm .cooked-fsm-ingredients.cooked-active,
	.cooked-fsm .cooked-fsm-directions-wrap.cooked-active { display:block; }

	<?php
    if ( false !== $_cooked_dark_css_scope ) :

		$_cooked_fsm_dark_selectors = Cooked_Settings::prefix_dark_mode_css( '.cooked-fsm .cooked-fsm-ingredients, .cooked-fsm .cooked-fsm-directions-wrap' );

		?>
		<?php echo wp_kses( $_cooked_fsm_dark_selectors, [] ); ?> { background:#000; color:#fff; }
		<?php

	endif;
    ?>

	.cooked-fsm {
		-webkit-transition: all 350ms cubic-bezier(0.420, 0.000, 0.150, 1.000);
		-moz-transition: all 350ms cubic-bezier(0.420, 0.000, 0.150, 1.000);
		-o-transition: all 350ms cubic-bezier(0.420, 0.000, 0.150, 1.000);
		transition: all 350ms cubic-bezier(0.420, 0.000, 0.150, 1.000);
	}

}

@media screen and ( max-width: <?php echo esc_html( $_cooked_settings['responsive_breakpoint_3'] ); ?>px ) {

	.cooked-fsm .cooked-fsm-directions p { font-size:1rem; }
	.cooked-fsm .cooked-recipe-directions .cooked-heading { font-size:1.25rem; }

	#cooked-timers-wrap { width:100%; left:0; right:0; transform:translate3d(0,7.2em,0); }
	#cooked-timers-wrap.cooked-visible { transform:translate3d(0,0,0); }
	#cooked-timers-wrap.cooked-multiples { width:min(20rem, 100%); left:auto; right:0; }

	.cooked-recipe-search .cooked-browse-select-block { max-height:50vh; }

}
