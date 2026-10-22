<?php
/**
 * Misc Functions
 *
 * @package     Cooked
 * @subpackage  Misc Functions
 * @since       1.0.0
 */

namespace Cooked\System;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Functions {

    /**
     * Guest message ID
     *
     * @var string
     */
    private static $guest_message_id = false;

    public static function sanitize_text_field( $text ) {
        $text = htmlentities( wp_unslash( $text ) );
        $text = sanitize_text_field( $text );
        return $text;
    }

    public static function array_splice_assoc( &$input, $offset, $length, $replacement = [] ) {
        $replacement = (array) $replacement;

        if ( is_array( $input ) ) :
            $key_indexes = array_flip( array_keys( $input ) );
            if ( isset( $input[ $offset ] ) && is_string( $offset ) ) {
                $offset = $key_indexes[ $offset ];
                $offset = (int) $offset + 1;
            }
            if ( isset( $input[ $length ] ) && is_string( $length ) ) {
                $length = $key_indexes[ $length ] - $offset;
            }

            $input = array_slice( $input, 0, $offset, true )
                    + $replacement
                    + array_slice( $input, $offset + $length, null, true );
        endif;
    }

    public static function wpml_xml( $cooked_tabs_fields ) {
        $indent = '    ';
        $xml    = [
            '<wpml-config>',
            $indent . '<custom-fields>',
            str_repeat( $indent, 2 ) . '<custom-field action="copy">_recipe_settings</custom-field>',
            $indent . '</custom-fields>',
            $indent . '<custom-types>',
            str_repeat( $indent, 2 ) . '<custom-type translate="1">cp_recipe</custom-type>',
            $indent . '</custom-types>',
            $indent . '<taxonomies>',
            str_repeat( $indent, 2 ) . '<taxonomy translate="1">cp_recipe_category</taxonomy>',
            $indent . '</taxonomies>',
            $indent . '<admin-texts>',
            str_repeat( $indent, 2 ) . '<key name="cooked_settings">',
        ];
        foreach ( $cooked_tabs_fields as $key => $val ) {
            $xml[] = str_repeat( $indent, 3 ) . '<key name="' . $key . '">';
            foreach ( $val['fields'] as $sub_key => $sub_val ) {
                $xml[] = str_repeat( $indent, 4 ) . '<key name="' . $sub_key . '" />';
            }
            $xml[] = str_repeat( $indent, 3 ) . '</key>';
        }
        $xml[] = str_repeat( $indent, 2 ) . '</key>';
        $xml[] = $indent . '</admin-texts>';
        $xml[] = '</wpml-config>';

        echo '<details class="cooked-code-block" open>';
        echo '<summary><span class="cooked-icon cooked-icon-angle-right" aria-hidden="true"></span>' . esc_html__( 'WPML config XML', 'cooked' ) . '</summary>';
        echo '<button type="button" class="cooked-copy-code" data-target="cooked-wpml-config">' . esc_html__( 'Copy', 'cooked' ) . '</button>';
        echo '<pre><code id="cooked-wpml-config">' . implode( "\n", array_map( [ __CLASS__, 'highlight_xml_line' ], $xml ) ) . '</code></pre>';
        echo '</details>';
    }

    /**
     * Escape one line of XML and wrap its parts in spans for syntax colouring.
     *
     * @since 2.0.0
     * @param string $line A line of XML.
     * @return string Escaped HTML.
     */
    public static function highlight_xml_line( $line ) {
        $html = '';
        foreach ( preg_split( '#(<[^>]+>)#', $line, -1, PREG_SPLIT_DELIM_CAPTURE ) as $part ) {
            if ( ! preg_match( '#^<(/?)([\w:-]+)(.*?)(/?)>$#s', $part, $tag ) ) {
                $html .= esc_html( $part );
                continue;
            }

            $html .= '<span class="cooked-xml-punct">&lt;' . esc_html( $tag[1] ) . '</span><span class="cooked-xml-tag">' . esc_html( $tag[2] ) . '</span>';

            preg_match_all( '#([\w:-]+)="([^"]*)"#', $tag[3], $attributes, PREG_SET_ORDER );
            foreach ( $attributes as $attribute ) {
                $html .= ' <span class="cooked-xml-attr">' . esc_html( $attribute[1] ) . '</span><span class="cooked-xml-punct">=</span><span class="cooked-xml-value">&quot;' . esc_html( $attribute[2] ) . '&quot;</span>';
            }

            $html .= '<span class="cooked-xml-punct">' . ( $tag[4] ? ' /' : '' ) . '&gt;</span>';
        }

        return $html;
    }

    /**
     * Unserializes stored legacy or imported data without creating objects (no PHP Object
     * Injection, unlike unserialize() / maybe_unserialize() with their defaults). Values
     * that aren't serialized are returned unchanged.
     *
     * @since 2.0.0
     * @param mixed $value Possibly serialized value.
     * @return mixed
     */
    public static function safe_unserialize( $value ) {
        if ( ! is_string( $value ) || ! is_serialized( $value ) ) {
            return $value;
        }
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- Objects are disallowed.
        return unserialize( trim( $value ), [ 'allowed_classes' => false ] );
    }

    public static function hex2rgb( $hex ) {
        $rgb = sscanf( (string) $hex, '#%02x%02x%02x' );
        // Malformed colours (sscanf() returns null or leaves channels unset) fall back to black.
        if ( ! is_array( $rgb ) || in_array( null, $rgb, true ) ) {
            return '0,0,0';
        }
        return implode( ',', $rgb );
    }

    public static function parse_readme_changelog( $readme_url = false, $title = false ) {
        $readme = self::readme_changelog_section( $readme_url );
        if ( '' === trim( $readme ) ) {
            return '';
        }
        // Name the release the entries below belong to (the newest "= x.y.z =" heading), not
        // the installed plugin version, which may not have a changelog entry yet.
        $version = preg_match( '/= (.*?) =/', $readme, $matches ) ? $matches[1] : COOKED_VERSION;
        /* translators: a title for the "What's new in Cooked?" section. */
        $whats_new_title = '<h4>' . ( $title ? esc_html( $title ) : apply_filters( 'cooked_whats_new_title', sprintf( __( "What's new in %s?", 'cooked' ), 'Cooked ' . $version ) ) ) . '</h4>';
        $readme          = (string) preg_replace( '/= (.*?) =/', $whats_new_title, $readme );
        $readme          = self::changelog_entries_html( $readme );
        $readme          = explode( $whats_new_title, $readme );
        $readme          = $whats_new_title . $readme[1];

        return $readme;
    }

    /**
     * The whole changelog as HTML: a "Version x.y.z" heading per release, followed by its
     * entries (tagged by change type, as in parse_readme_changelog()).
     *
     * @since 2.0.0
     * @param string|false $readme_url Path to a readme.txt or changelog.txt; defaults to Cooked's changelog.txt.
     * @return string
     */
    public static function parse_readme_full_changelog( $readme_url = false ) {
        $readme = self::readme_changelog_section( $readme_url );
        $readme = (string) preg_replace_callback(
            '/= (.*?) =/',
            function ( $matches ) {
                /* translators: %s: a version number, e.g. 1.16.2 */
                return '<h4 class="cooked-changelog-version">' . sprintf( esc_html__( 'Version %s', 'cooked' ), $matches[1] ) . '</h4>';
            },
            $readme
        );

        return self::changelog_entries_html( $readme );
    }

    /**
     * Reads a readme.txt or changelog.txt and returns its "== Changelog ==" section (the
     * whole file if it has no such heading), escaped, with links, `code`, **bold** and
     * *italic* converted to HTML.
     *
     * Cooked's own changelog lives in changelog.txt, which ships with the plugin; Cooked
     * Pro passes the path to its readme.txt.
     *
     * @since 2.0.0
     * @param string|false $readme_url Path to a readme.txt or changelog.txt; defaults to Cooked's changelog.txt.
     * @return string
     */
    private static function readme_changelog_section( $readme_url ) {
        $file = $readme_url ? (string) $readme_url : COOKED_DIR . 'changelog.txt';
        // Read as text (include would run any PHP in the file); only .txt files.
        if ( 'txt' !== strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ) || ! is_readable( $file ) ) {
            return '';
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin file.
        $readme = (string) file_get_contents( $file );

        $readme = esc_html( $readme );
        // [text](https://…) links, as in the changelog's credits. Before make_clickable(),
        // which would otherwise link the bare URL and leave the brackets.
        // preg_replace() returns null on a regex error; keep a string throughout.
        $readme = (string) preg_replace( '/\[([^\]\n]+)\]\((https?:\/\/[^\s)]+)\)/', '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>', $readme );
        $readme = make_clickable( $readme );
        $readme = (string) preg_replace( '/`(.*?)`/', '<code>\\1</code>', $readme );
        $readme = (string) preg_replace( '/\*\*(.*?)\*\*/', '<strong>\\1</strong>', $readme );
        $readme = (string) preg_replace( '/\*(.*?)\*/', '<em>\\1</em>', $readme );
        $readme = explode( '== Changelog ==', $readme );

        return isset( $readme[1] ) ? $readme[1] : $readme[0];
    }

    /**
     * Turns the changelog's "* entry" lines into lists, one per release.
     *
     * Each entry with a leading **FIX:**, **NEW:**, … is tagged with its change type so
     * the What's New screen can colour it. The label text itself is unchanged.
     *
     * @since 2.0.0
     * @param string $readme Changelog section with its release headings already replaced.
     * @return string
     */
    private static function changelog_entries_html( $readme ) {
        $readme = (string) preg_replace( '/\*+(.*)?/i', "<ul class='cooked-whatsnew-list'><li>$1</li></ul>", $readme );
        $readme = (string) preg_replace( "/(\<\/ul\>\n(.*)\<ul class=\'cooked-whatsnew-list\'\>*)+/", '', $readme );

        return (string) preg_replace_callback(
            '/<li>\s*<strong>([A-Za-z ]+):<\/strong>\s*/',
            function ( $matches ) {
                // The pattern only captures letters and spaces, so this is a safe class name.
                $type = strtolower( str_replace( ' ', '-', trim( $matches[1] ) ) );
                // The ": " after the label sits in a span the new What's New layout hides
                // (the badge has its own margin); the classic page still reads "FIX: …".
                return '<li class="cooked-change-' . $type . '"><strong class="cooked-change-label">' . $matches[1] . '<span class="cooked-change-colon">: </span></strong>';
            },
            $readme
        );
    }

    public static function print_options() {
        $default_print_options = apply_filters(
            'cooked_default_print_options',
            [
				'print_options_title'       => 'checked',
				'print_options_info'        => '',
				'print_options_excerpt'     => '',
				'print_options_images'      => '',
				'print_options_ingredients' => 'checked',
				'print_options_directions'  => 'checked',
				'print_options_notes'       => 'checked',
				'print_options_nutrition'   => '',
			]
        );

        echo '<div id="cooked-print-options" class="cooked-clearfix">';

            echo '<button class="cooked-button" onclick="window.print();">' . esc_html__( 'Print', 'cooked' ) . '</button>';
            echo '<h3>' . esc_html__( 'Print Options:', 'cooked' ) . '</h3>';

            echo '<input id="print_options_title" type="checkbox" name="print_options" value="1" ' . esc_attr( isset( $default_print_options['print_options_title'] ) ? $default_print_options['print_options_title'] : '' ) . ' /> <label for="print_options_title">' . esc_html__( 'Title', 'cooked' ) . '</label>';
            echo '<input id="print_options_info" type="checkbox" name="print_options" value="1" ' . esc_attr( isset( $default_print_options['print_options_info'] ) ? $default_print_options['print_options_info'] : '' ) . ' /> <label for="print_options_info">' . esc_html__( 'Information', 'cooked' ) . '</label>';
            echo '<input id="print_options_excerpt" type="checkbox" name="print_options" value="1" ' . esc_attr( isset( $default_print_options['print_options_excerpt'] ) ? $default_print_options['print_options_excerpt'] : '' ) . ' /> <label for="print_options_excerpt">' . esc_html__( 'Excerpt', 'cooked' ) . '</label>';
            echo '<input id="print_options_images" type="checkbox" name="print_options" value="1" ' . esc_attr( isset( $default_print_options['print_options_images'] ) ? $default_print_options['print_options_images'] : '' ) . ' /> <label for="print_options_images">' . esc_html__( 'Images', 'cooked' ) . '</label>';
            echo '<input id="print_options_ingredients" type="checkbox" name="print_options" value="1" ' . esc_attr( isset( $default_print_options['print_options_ingredients'] ) ? $default_print_options['print_options_ingredients'] : '' ) . ' /> <label for="print_options_ingredients">' . esc_html__( 'Ingredients', 'cooked' ) . '</label>';
            echo '<input id="print_options_directions" type="checkbox" name="print_options" value="1" ' . esc_attr( isset( $default_print_options['print_options_directions'] ) ? $default_print_options['print_options_directions'] : '' ) . ' /> <label for="print_options_directions">' . esc_html__( 'Directions', 'cooked' ) . '</label>';
            echo '<input id="print_options_notes" type="checkbox" name="print_options" value="1" ' . esc_attr( isset( $default_print_options['print_options_notes'] ) ? $default_print_options['print_options_notes'] : '' ) . ' /> <label for="print_options_notes">' . esc_html__( 'Notes', 'cooked' ) . '</label>';
            echo '<input id="print_options_nutrition" type="checkbox" name="print_options" value="1" ' . esc_attr( isset( $default_print_options['print_options_nutrition'] ) ? $default_print_options['print_options_nutrition'] : '' ) . ' /> <label for="print_options_nutrition">' . esc_html__( 'Nutrition', 'cooked' ) . '</label>';

        echo '</div>';
    }

    public static function print_options_js() {
        ?><script type="text/javascript">

            var print_options = document.getElementsByTagName('input');
            for (var i = 0, len = print_options.length; i < len; i++) {
                if ( print_options[i].getAttribute("name") == "print_options") {
                    update_print_options( print_options[i] );
                }
            }

            document.addEventListener("click", function (e) {
                update_print_options( e.target );
            });

            function update_print_options( printOpt ) {

                if (printOpt.id == "print_options_title" && typeof document.getElementById('printTitle') != 'undefined') {
                    if ( printOpt.checked ){
                        document.getElementById('printTitle').style.display = 'block';
                    } else {
                        document.getElementById('printTitle').style.display = 'none';
                    }
                }

                if (printOpt.id == "print_options_nutrition" && typeof document.getElementsByClassName('cooked-nutrition-label')[0] != 'undefined') {
                    if ( printOpt.checked ){
                        document.getElementsByClassName('cooked-nutrition-label')[0].style.display = 'block';
                    } else {
                        document.getElementsByClassName('cooked-nutrition-label')[0].style.display = 'none';
                    }
                }

                if (printOpt.id == "print_options_info" && typeof document.getElementsByClassName('cooked-recipe-info')[0] != 'undefined') {
                    if ( printOpt.checked ){
                        document.getElementsByClassName('cooked-recipe-info')[0].style.display = 'block';
                    } else {
                        document.getElementsByClassName('cooked-recipe-info')[0].style.display = 'none';
                    }
                }

                if (printOpt.id == "print_options_excerpt" && typeof document.getElementsByClassName('cooked-recipe-excerpt')[0] != 'undefined') {
                    if ( printOpt.checked ){
                        document.getElementsByClassName('cooked-recipe-excerpt')[0].style.display = 'block';
                    } else {
                        document.getElementsByClassName('cooked-recipe-excerpt')[0].style.display = 'none';
                    }
                }

                if (printOpt.id == "print_options_images" && typeof document.getElementsByClassName('cooked-post-featured-image')[0] != 'undefined') {
                    if ( printOpt.checked ){
                        document.getElementsByClassName('cooked-post-featured-image')[0].style.display = 'block';
                        var print_images = document.getElementsByTagName('img');
                        for (var i = 0, len = print_images.length; i < len; i++) {
                            if ( print_images[i].closest && print_images[i].closest('#cooked-print-logo') ) {
                                continue;
                            }
                            print_images[i].style.display = 'block';
                        }
                    } else {
                        document.getElementsByClassName('cooked-post-featured-image')[0].style.display = 'none';
                        var print_images = document.getElementsByTagName('img');
                        for (var i = 0, len = print_images.length; i < len; i++) {
                            if ( print_images[i].closest && print_images[i].closest('#cooked-print-logo') ) {
                                continue;
                            }
                            print_images[i].style.display = 'none';
                        }
                    }
                }

                if (printOpt.id == "print_options_ingredients" && typeof document.getElementsByClassName('cooked-recipe-ingredients')[0] != 'undefined') {
                    if ( printOpt.checked ){
                        document.getElementsByClassName('cooked-recipe-ingredients')[0].style.display = 'block';
                    } else {
                        document.getElementsByClassName('cooked-recipe-ingredients')[0].style.display = 'none';
                    }
                }

                if (printOpt.id == "print_options_directions" && typeof document.getElementsByClassName('cooked-recipe-directions')[0] != 'undefined' ) {
                    if ( printOpt.checked ){
                        document.getElementsByClassName('cooked-recipe-directions')[0].style.display = 'block';
                    } else {
                        document.getElementsByClassName('cooked-recipe-directions')[0].style.display = 'none';
                    }
                }

                if (printOpt.id == "print_options_notes" && typeof document.getElementsByClassName('cooked-recipe-notes')[0] != 'undefined' ) {
                    if ( printOpt.checked ){
                        document.getElementsByClassName('cooked-recipe-notes')[0].style.display = 'block';
                    } else {
                        document.getElementsByClassName('cooked-recipe-notes')[0].style.display = 'none';
                    }
                }

            }

        </script>
        <?php
    }

    // Store the message
    public static function set_transient_message( $message ) {
        $user_id = get_current_user_id();
        if ( ! empty( $message ) ) {
            if ( ! empty( $user_id ) ) {
                set_transient( 'cooked_user_' . $user_id . '_message', $message, 60 ); // Expires in 60 seconds
            } else {
                // For guests
                self::$guest_message_id = uniqid();
                set_transient( 'cooked_guest_' . self::$guest_message_id . '_message', $message, 60 ); // Expires in 60 seconds
                return self::$guest_message_id;
            }
        }
    }

    // Retrieve and delete the message
    public static function get_and_delete_transient_message( $guest_message_id = false ) {
        $user_id = get_current_user_id();
        if ( ! empty( $user_id ) ) {
            $message = get_transient( 'cooked_user_' . $user_id . '_message' );
            if ( ! empty( $message ) ) {
                delete_transient( 'cooked_user_' . $user_id . '_message' );
                return $message;
            }
        } else {
            // For guests - use provided guest_message_id or fall back to static property
            $message_id = $guest_message_id ?: self::$guest_message_id;
            if ( $message_id ) {
                $message = get_transient( 'cooked_guest_' . $message_id . '_message' );
                if ( ! empty( $message ) ) {
                    delete_transient( 'cooked_guest_' . $message_id . '_message' );
                    self::$guest_message_id = false;
                    return $message;
                }
            }
        }
        return false;
    }
}
