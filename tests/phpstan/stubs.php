<?php
/**
 * Symbols defined by optional software (WP-CLI, Polylang, WPML, Yoast, Rank
 * Math, WooCommerce Memberships, Slider Revolution), declared here so PHPStan
 * can resolve them. Never loaded at runtime.
 *
 * @package Cooked
 */

namespace {
    if ( ! class_exists( 'WP_CLI', false ) ) {
        class WP_CLI {
            public static function error( $message ): void {}
            public static function success( $message ): void {}
            public static function log( $message ): void {}
            public static function add_command( $name, $callable ): void {}
        }
    }

    if ( ! class_exists( 'WC_Memberships_User_Messages', false ) ) {
        class WC_Memberships_User_Messages {
            /**
             * @param string $message
             * @param array  $args
             * @return string
             */
            public static function get_message_html( $message, $args = [] ) {
                return '';
            }
        }
    }

    if ( ! class_exists( 'RevSlider', false ) ) {
        class RevSlider {
            /** @return array<int, RevSliderSlide> */
            public function getArrSliders(): array {
                return [];
            }
        }

        class RevSliderSlide {
            public function getAlias(): string {
                return '';
            }

            public function getTitle(): string {
                return '';
            }
        }
    }

    if ( ! function_exists( 'pll_get_post' ) ) {
        function pll_get_post( $post_id, $lang = null ) {
            return false;
        }
    }

    if ( ! function_exists( 'pll_current_language' ) ) {
        function pll_current_language( $field = 'slug' ) {
            return false;
        }
    }

    if ( ! function_exists( 'pll_get_post_translations' ) ) {
        function pll_get_post_translations( $post_id ) {
            return [];
        }
    }

    if ( ! function_exists( 'pll_languages_list' ) ) {
        function pll_languages_list( $args = [] ) {
            return [];
        }
    }

    if ( ! function_exists( 'icl_object_id' ) ) {
        function icl_object_id( $id, $type, $return_original_if_missing = false, $lang = null ) {
            return false;
        }
    }

    if ( ! function_exists( 'rank_math_register_var_replacement' ) ) {
        function rank_math_register_var_replacement( $var, $replacement, $callback ): void {}
    }

    if ( ! function_exists( 'wpseo_register_var_replacement' ) ) {
        function wpseo_register_var_replacement( $var, $callback, $type, $description ): void {}
    }
}

namespace WP_CLI\Utils {
    if ( ! function_exists( 'WP_CLI\Utils\format_items' ) ) {
        function format_items( $format, $items, $fields ): void {}
    }
}
