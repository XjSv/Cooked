<?php
/**
 * Search Widget
 *
 * @package     Cooked
 * @subpackage  Widgets
 * @since       1.2.0
 */

namespace Cooked\Frontend\Widgets;

/**
 * Search Class
 *
 * This class handles the Search Form widget display.
 *
 * @since 1.2.0
 */
class Search extends \WP_Widget {

    /**
     * Registers the recipe search widget.
     */
    public function __construct() {
        $widget_ops = [
            'classname'   => 'cooked_widget_search',
            'description' => 'Display the recipe search form.',
        ];
        parent::__construct( 'cooked_widget_search', 'Cooked - Recipe Search', $widget_ops );
    }

    /**
     * Outputs the recipe search widget.
     *
     * @param array<string, mixed> $args     Widget area arguments (before_widget, after_widget, ...).
     * @param array<string, mixed> $instance Widget settings.
     * @return void
     */
    public function widget( $args, $instance ) {

        echo wp_kses_post( $args['before_widget'] );
        if ( ! empty( $instance['title'] ) ) {
            echo wp_kses_post( $args['before_title'] ) . esc_html( apply_filters( 'widget_title', $instance['title'] ) ) . wp_kses_post( $args['after_title'] );
        }
        $size    = isset( $instance['size'] ) && 'compact' === $instance['size'] ? ' compact="true"' : '';
        $browse  = isset( $instance['hide_browse'] ) && $instance['hide_browse'] ? ' hide_browse="true"' : '';
        $sorting = isset( $instance['hide_sorting'] ) && $instance['hide_sorting'] ? ' hide_sorting="true"' : '';
        echo do_shortcode( '[cooked-search' . esc_attr( $size . $browse . $sorting ) . ']' );
        echo wp_kses_post( $args['after_widget'] );
    }

    /**
     * Renders the recipe search widget settings form.
     *
     * @param array<string, mixed> $instance Current widget settings.
     * @return string|null Nothing; the form is printed (WP_Widget::form() returns string|null).
     */
    public function form( $instance ) {

        $title        = ! empty( $instance['title'] ) ? $instance['title'] : false;
        $size         = ! empty( $instance['size'] ) ? $instance['size'] : 'compact';
        $hide_browse  = isset( $instance['hide_browse'] ) && $instance['hide_browse'] ? true : false;
        $hide_sorting = isset( $instance['hide_sorting'] ) && $instance['hide_sorting'] ? true : false;

        ?>

        <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title (optional):', 'cooked' ); ?></label>
        <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>

        <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'size' ) ); ?>"><?php esc_attr_e( 'Size:', 'cooked' ); ?></label>
        <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'size' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'size' ) ); ?>">
            <option value="compact"<?php echo 'compact' === $size ? ' selected' : ''; ?>><?php esc_html_e( 'Compact', 'cooked' ); ?></option>
            <option value="wide"<?php echo 'wide' === $size ? ' selected' : ''; ?>><?php esc_html_e( 'Wide', 'cooked' ); ?></option>
        </select>
        </p>

        <p>
        <input id="<?php echo esc_attr( $this->get_field_id( 'hide_browse' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hide_browse' ) ); ?>"<?php echo ( $hide_browse ? ' checked' : '' ); ?> type="checkbox" value="1">
        <label for="<?php echo esc_attr( $this->get_field_id( 'hide_browse' ) ); ?>"><?php esc_attr_e( 'Hide "Browse" dropdown', 'cooked' ); ?></label>
        </p>

        <p>
        <input id="<?php echo esc_attr( $this->get_field_id( 'hide_sorting' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hide_sorting' ) ); ?>"<?php echo ( $hide_sorting ? ' checked' : '' ); ?> type="checkbox" value="1">
        <label for="<?php echo esc_attr( $this->get_field_id( 'hide_sorting' ) ); ?>"><?php esc_attr_e( 'Hide "Sorting" dropdown', 'cooked' ); ?></label>
        </p>

        <?php
        return null;
    }

    /**
     * Saves the recipe search widget settings.
     *
     * @param array<string, mixed> $new_instance Settings submitted in the widget form.
     * @param array<string, mixed> $old_instance Previously saved settings.
     * @return array<string, mixed>
     */
    public function update( $new_instance, $old_instance ) {
        $instance                 = [];
        $instance['title']        = ! empty( $new_instance['title'] ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
        $instance['size']         = ! empty( $new_instance['size'] ) ? wp_strip_all_tags( $new_instance['size'] ) : 'compact';
        $instance['hide_browse']  = ! empty( $new_instance['hide_browse'] ) ? 1 : 0;
        $instance['hide_sorting'] = ! empty( $new_instance['hide_sorting'] ) ? 1 : 0;
        return $instance;
    }
}
