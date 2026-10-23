<?php
/**
 * Tests for the built-in recipe gallery ([cooked-gallery] with the "cooked" gallery type).
 *
 * @package Cooked
 * @subpackage Tests
 */

use PHPUnit\Framework\Attributes\DataProvider;

class GalleryTest extends FilterTestCase {

    protected $shortcodes;

    protected function setUp(): void {
        parent::setUp();
        $this->shortcodes = new Cooked_Shortcodes();
        $GLOBALS['recipe_settings'] = [
            'id'      => 1,
            'title'   => 'Test Recipe',
            'gallery' => [
                'type'      => 'cooked',
                'items'     => [ 5, 6 ],
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ],
        ];
    }

    public static function video_url_provider() {
        return [
            'youtube watch'     => [ 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ' ],
            'youtube short url' => [ 'https://youtu.be/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ' ],
            'youtube shorts'    => [ 'https://youtube.com/shorts/abcDEF12345', 'https://www.youtube-nocookie.com/embed/abcDEF12345' ],
            'youtube mobile'    => [ 'https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ' ],
            'vimeo'             => [ 'https://vimeo.com/123456789', 'https://player.vimeo.com/video/123456789' ],
            'vimeo player'      => [ 'https://player.vimeo.com/video/987654', 'https://player.vimeo.com/video/987654' ],
            'other host'        => [ 'https://example.com/video.mp4', false ],
            'not a url'         => [ 'javascript:alert(1)', false ],
            'bad youtube id'    => [ 'https://www.youtube.com/watch?v=<script>', false ],
        ];
    }

    #[DataProvider( 'video_url_provider' )]
    public function test_video_embed_url( $url, $expected ) {
        $this->assertSame( $expected, Cooked_Shortcodes::gallery_video_embed_url( $url ) );
    }

    public function test_renders_video_and_image_slides_with_default_options() {
        $html = $this->shortcodes->cooked_gallery_shortcode( [] );

        $this->assertStringContainsString( 'class="cooked-gallery"', $html );
        $this->assertSame( 3, substr_count( $html, 'class="cooked-gallery-slide ' ) );
        $this->assertMatchesRegularExpression( '#cooked-gallery-slide-video[^>]*data-video-embed="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"#', $html );
        $this->assertLessThan( strpos( $html, 'cooked-gallery-slide-image' ), strpos( $html, 'cooked-gallery-slide-video' ) );
        $this->assertStringContainsString( 'data-pswp-width="900" data-pswp-height="900"', $html );
        $this->assertStringContainsString( 'data-nav="dots"', $html );
        $this->assertStringContainsString( '--cooked-gallery-ratio:800 / 600', $html );
        $this->assertSame( 3, substr_count( $html, 'class="cooked-gallery-nav-item"' ) );
    }

    public function test_video_last_option_moves_the_video_to_the_end() {
        $html = $this->with_filter( 'cooked_gallery_video_last_option', fn() => true, function () {
            return $this->shortcodes->cooked_gallery_shortcode( [] );
        } );

        $this->assertGreaterThan( strrpos( $html, 'cooked-gallery-slide-image' ), strpos( $html, 'cooked-gallery-slide-video' ) );
    }

    public function test_options_filter_still_controls_the_gallery() {
        $html = $this->with_filter( 'cooked_recipe_gallery_options', function ( $options ) {
            $options['data-nav']   = 'thumbs';
            $options['data-ratio'] = '16/9';
            $options['data-loop']  = 'false';
            return $options;
        }, function () {
            return $this->shortcodes->cooked_gallery_shortcode( [] );
        } );

        $this->assertStringContainsString( 'cooked-gallery-nav-thumbs', $html );
        $this->assertStringContainsString( '--cooked-gallery-ratio:16 / 9', $html );
        $this->assertStringContainsString( 'data-loop="false"', $html );
        $this->assertStringContainsString( 'class="size-thumbnail"', $html );
    }

    public function test_shortcode_attributes_are_validated() {
        $html = $this->shortcodes->cooked_gallery_shortcode( [
            'nav'   => '"><script>alert(1)</script>',
            'width' => 'expression(alert(1))',
            'ratio' => '1/0',
            'fit'   => 'scaledown',
        ] );

        $this->assertStringNotContainsString( '<script', $html );
        $this->assertStringNotContainsString( 'expression(', $html );
        $this->assertStringContainsString( 'data-nav="false"', $html );
        $this->assertStringNotContainsString( 'cooked-gallery-nav', $html );
        // An invalid width falls back to the default, which sets no max-width (the theme's
        // content width applies).
        $this->assertStringNotContainsString( 'max-width', $html );
        $this->assertStringContainsString( '--cooked-gallery-ratio:4 / 3', $html );
        $this->assertStringContainsString( '--cooked-gallery-fit:scale-down', $html );
    }

    public function test_pixel_width_attribute() {
        $html = $this->shortcodes->cooked_gallery_shortcode( [ 'width' => '600' ] );

        $this->assertStringContainsString( 'max-width:600px', $html );
    }

    public function test_unsupported_video_url_is_skipped() {
        $GLOBALS['recipe_settings']['gallery']['video_url'] = 'https://example.com/video.mp4';
        $GLOBALS['recipe_settings']['gallery']['items']     = [];

        $this->assertSame( '', $this->shortcodes->cooked_gallery_shortcode( [] ) );
    }

    public function test_a_custom_width_sets_max_width() {
        $html = $this->shortcodes->cooked_gallery_shortcode( [ 'width' => '600' ] );
        $this->assertStringContainsString( 'max-width:600px', $html );
    }
}
