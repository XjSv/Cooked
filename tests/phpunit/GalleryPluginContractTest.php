<?php

class GalleryPluginContractTest extends FilterTestCase {

    public function test_envira_lite_keeps_the_class_post_type_and_shortcode() {
        $file = $this->plugin_file( 'envira-gallery-lite/envira-gallery-lite.php' );
        $shortcode = $this->plugin_file( 'envira-gallery-lite/includes/global/shortcode.php' );
        $post_type = $this->plugin_file( 'envira-gallery-lite/includes/global/posttype.php' );

        $this->assertStringContainsString( 'class Envira_Gallery_Lite', $file );
        $this->assertStringContainsString( "add_shortcode( 'envira-gallery'", $shortcode );
        $this->assertStringContainsString( "register_post_type( 'envira'", $post_type );
    }

    public function test_soliloquy_lite_keeps_the_class_post_type_and_shortcode() {
        $file = $this->plugin_file( 'soliloquy-lite/soliloquy-lite.php' );
        $shortcode = $this->plugin_file( 'soliloquy-lite/includes/global/shortcode.php' );
        $post_type = $this->plugin_file( 'soliloquy-lite/includes/global/posttype.php' );

        $this->assertStringContainsString( 'class Soliloquy_Lite', $file );
        $this->assertStringContainsString( "add_shortcode( 'soliloquy'", $shortcode );
        $this->assertStringContainsString( "register_post_type( 'soliloquy'", $post_type );
    }

    public function test_slider_revolution_keeps_the_legacy_listing_api_and_shortcode() {
        $bootstrap = $this->plugin_file( 'revslider/revslider.php' );
        $compat = $this->plugin_file( 'revslider/includes/backwards.php' );
        $slider = $this->plugin_file( 'revslider/includes/slider.class.php' );

        $this->assertStringContainsString( "add_shortcode('rev_slider'", $bootstrap );
        $this->assertStringContainsString( 'class RevSlider extends RevSliderSlider', $compat );
        $this->assertStringContainsString( 'function getArrSliders(', $slider );
        $this->assertStringContainsString( 'function getAlias(', $slider );
        $this->assertStringContainsString( 'function getTitle(', $slider );
    }

    private function plugin_file( $relative ) {
        $path = realpath( COOKED_DIR . '../' . $relative );
        if ( ! $path || ! is_readable( $path ) ) {
            $this->markTestSkipped( $relative . ' is not installed next to Cooked.' );
        }

        $contents = file_get_contents( $path );
        $this->assertNotFalse( $contents );

        return $contents;
    }
}
