<?php global $post,$recipe_settings; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <title><?php the_title(); ?></title>
    <meta name="description" content="<?php echo get_post_meta( $post->ID, 'rank_math_description', true ); ?>">
    <meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php bloginfo( 'charset' ); ?>">
    <link rel="stylesheet" href="<?php echo COOKED_URL . 'assets/css/icons.css'; ?>">
    <link rel="stylesheet" href="<?php echo COOKED_URL . 'assets/css/print.css'; ?>" media="screen,print">
</head>
<body>
<?php
$recipe_settings = Cooked_Recipes::get_settings( $post->ID );

Cooked_Functions::print_options();

echo '<h1 id="printTitle">' . get_the_title() . '</h1>';
echo wpautop( do_shortcode( Cooked_Recipes::print_content() ) );

Cooked_Functions::print_options_js();
?>
</body>
</html>
