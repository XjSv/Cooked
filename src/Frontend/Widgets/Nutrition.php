<?php
/**
 * Nutrition Facts Widget
 *
 * @package     Cooked
 * @subpackage  Widgets
 * @since       1.2.0
 */

namespace Cooked\Frontend\Widgets;

/**
 * Nutrition Class
 *
 * This class handles the Nutrition Facts widget display.
 *
 * @since 1.2.0
 */
class Nutrition extends \WP_Widget {

    /**
     * Registers the nutrition facts widget.
     */
    public function __construct() {
        $widget_ops = array(
            'classname'   => 'cooked_nutrition_widget',
            'description' => 'Display nutrition facts on recipe sidebars.',
        );
        parent::__construct( 'cooked_nutrition_widget', 'Cooked - Nutrition Facts', $widget_ops );
    }

    /**
     * Outputs the nutrition facts widget.
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
        echo do_shortcode( '[cooked-nutrition]' );
        echo wp_kses_post( $args['after_widget'] );
    }

    /**
     * Renders the nutrition facts widget settings form.
     *
     * @param array<string, mixed> $instance Current widget settings.
     * @return string|null Nothing; the form is printed (WP_Widget::form() returns string|null).
     */
    public function form( $instance ) {

        $title = ( ! empty( $instance['title'] ) ? $instance['title'] : false );

        ?>

        <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title (optional):', 'cooked' ); ?></label>
        <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>

        <?php
        return null;
    }

    /**
     * Saves the nutrition facts widget settings.
     *
     * @param array<string, mixed> $new_instance Settings submitted in the widget form.
     * @param array<string, mixed> $old_instance Previously saved settings.
     * @return array<string, mixed>
     */
    public function update( $new_instance, $old_instance ) {
        $instance          = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
        return $instance;
    }
}
