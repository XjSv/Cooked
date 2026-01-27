<?php global $post, $recipe_settings;

// Print Screen Enqueues
add_action( 'wp_enqueue_scripts', 'cooked_print_enqueues', 99, 1 );
function cooked_print_enqueues(){
	$min = COOKED_DEV ? '' : '.min';
	wp_enqueue_style( 'cooked-print', COOKED_URL . 'assets/css/print' . esc_attr( $min ) . '.css', [], COOKED_VERSION, 'screen,print' );
	wp_enqueue_style( 'cooked-icons', COOKED_URL . 'assets/css/icons' . esc_attr( $min ) . '.css', [], COOKED_VERSION, 'screen,print' );
	wp_dequeue_style( 'cooked-essentials' );
	wp_dequeue_style( 'cooked-styling' );
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<?php wp_head(); ?>
	<meta name="robots" content="noindex,nofollow">
</head>
<body><?php

$recipe_settings = Cooked_Recipes::get_settings( $post->ID );

Cooked_Functions::print_options();

echo '<h1 id="printTitle">' . esc_html( get_the_title() ) . '</h1>';
echo wpautop( do_shortcode( Cooked_Recipes::print_content() ) );

Cooked_Functions::print_options_js();

wp_footer();

?></body>
</html>