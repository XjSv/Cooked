<?php

class Cooked_Widget_Nutrition extends WP_Widget {

    public function __construct() {
        $widget_ops = array(
            'classname' => 'cooked_nutrition_widget',
            'description' => 'Display nutrition facts on recipe sidebars.',
        );
        parent::__construct( 'cooked_nutrition_widget', 'Cooked - Nutrition Facts', $widget_ops );
    }

    public function widget( $args, $instance ) {

        echo wp_kses_post( $args['before_widget'] );
        if ( ! empty( $instance['title'] ) ) {
            echo wp_kses_post( $args['before_title'] ) . apply_filters( 'widget_title', $instance['title'] ) . wp_kses_post( $args['after_title'] );
        }
        echo do_shortcode( '[cooked-nutrition]' );
        echo wp_kses_post( $args['after_widget'] );

    }

    public function form( $instance ) {

        $title = ( !empty( $instance['title'] ) ? $instance['title'] : false );

        ?>

        <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title (optional):', 'cooked' ); ?></label>
        <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>

        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        return $instance;
    }

}
