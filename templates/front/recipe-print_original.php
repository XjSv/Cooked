<?php global $post,$recipe_settings;

?><!DOCTYPE html>
<html>
<head>
	<title><?php the_title(); ?></title>
	<link rel="stylesheet" href="<?php echo COOKED_URL . 'assets/css/icons.css'; ?>">
	<link rel="stylesheet" href="<?php echo COOKED_URL . 'assets/css/print.css'; ?>" media="screen,print">
</head>
<body><?php

$recipe_settings = Cooked_Recipes::get_settings( $post->ID );

Cooked_Functions::print_options();

echo '<h1 id="printTitle">' . get_the_title() . '</h1>';
echo wpautop( do_shortcode( Cooked_Recipes::print_content() ) );

Cooked_Functions::print_options_js();

?></body>
</html>