<?php

$_cooked_settings = Cooked_Settings::get();

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

if ( isset($_cooked_settings['dark_mode']) && $_cooked_settings['dark_mode'] ):

?>/* Dark Mode */
.cooked-recipe-search .cooked-browse-select { background:rgba(255,255,255,0.075); box-shadow:inset 0 0 0 1px rgba(255,255,255,0.05); }
.cooked-recipe-search .cooked-browse-select:hover { background:rgba(255,255,255,0.10); box-shadow:inset 0 0 0 1px rgba(255,255,255,0.05); }
.cooked-recipe-search .cooked-browse-select-block { background:rgba(0,0,0,0.95); box-shadow:none; border-radius:5px; }
.cooked-recipe-search input[type="text"] { background:rgba(0,0,0,0.25); border-color:rgba(255,255,255,0.15); }
.cooked-recipe-search .cooked-sortby-wrap { background:rgba(255,255,255,0.10); }
.cooked-recipe-search .cooked-sortby-wrap:hover { background:rgba(255,255,255,0.15); }
.cooked-recipe-search .cooked-sortby-wrap > select { border-color:rgba(0,0,0,0.15); }
.cooked-recipe-search .cooked-sortby-wrap > select > option { color:#333; }
.cooked-recipe-grid .cooked-recipe-inside { background:rgba(0,0,0,0.25); box-shadow:none !important }
.cooked-pagination-numbered > span { color:rgba(255,255,255,0.5); }
.cooked-recipe-info span.cooked-print > a,
.cooked-recipe-info span.cooked-fsm-button { color:rgba(255,255,255,0.5); }
.cooked-recipe-info span.cooked-print > a:hover,
.cooked-recipe-info span.cooked-fsm-button:hover { color:#fff; }
.cooked-recipe-ingredients .cooked-ingredient-checkbox { border-color:rgba(255,255,255,0.25); }
.cooked-recipe-ingredients .cooked-ingredient-checkbox:hover { border-color:rgba(255,255,255,0.5); }
.cooked-nutrition-label { background:rgba(0,0,0,0.25); border:none; border-radius:5px; }
.cooked-nutrition-label .cooked-nutrition-title { color:#fff; }
body .cooked-nutrition-label .cooked-nut-hr { border-color:rgba(255,255,255,0.15); }
body .cooked-nutrition-label dt.cooked-nut-spacer { background:rgba(255,255,255,0.15); }
body .cooked-nutrition-label dt,
body .cooked-nutrition-label dl.cooked-nut-bottom dt,
body .cooked-nutrition-label dl.cooked-nut-bottom dt:nth-last-child(2):nth-child(2n) { border-color:rgba(255,255,255,0.15); }
.cooked-recipe-card { background:rgba(0,0,0,0.25); box-shadow:none !important; }
.cooked-recipe-card-modern .cooked-recipe-card-sep, .cooked-recipe-card-modern-centered .cooked-recipe-card-sep { background:rgba(255,255,255,0.25); }
.cooked-recipe-grid .cooked-recipe-image-empty { background:rgba(255,255,255,0.15); }
.cooked-fsm { background:#000; color:#fff; }
.cooked-fsm .cooked-fsm-ingredients { background:rgba(255,255,255,0.1); }
.cooked-recipe .cooked-rating-stars > .cooked-rating-star.cooked-rating-star-empty,
.cooked-recipe .cooked-ratable .cooked-rating-stars.cooked-user-rated > .cooked-rating-star.cooked-rating-star-empty { color:rgba(255,255,255,0.25); }
<?php

endif;
