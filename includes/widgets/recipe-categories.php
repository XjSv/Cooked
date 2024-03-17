<?php

class Cooked_Widget_Recipe_Categories extends WP_Widget {

    public function __construct() {
        $widget_ops = array(
            'classname' => 'cooked_widget_recipe_categories',
            'description' => 'Display a list of recipe categories.',
        );
        parent::__construct( 'cooked_widget_recipe_categories', 'Cooked - Recipe Categories', $widget_ops );
    }

    public function widget( $args, $instance ) {

        echo wp_kses_post( $args['before_widget'] );
        if ( ! empty( $instance['title'] ) ) {
            echo wp_kses_post( $args['before_title'] ) . apply_filters( 'widget_title', $instance['title'] ) . wp_kses_post( $args['after_title'] );
        }

        $width = ( isset($instance['width']) && $instance['width'] ? ' width="' . esc_attr( $instance['width'] ) . '"' : '' );
        $child_of = ( isset($instance['child_of']) && $instance['child_of'] ? ' child_of="' . esc_attr( $instance['child_of'] ) . '"' : '' );
        $hide_empty = ( isset($instance['hide_empty']) && $instance['hide_empty'] ? ' hide_empty="' . esc_attr( $instance['hide_empty'] ) . '"' : '' );
        $show_description = ( isset($instance['show_description']) && $instance['show_description'] ? ' show_description="' . esc_attr( $instance['show_description'] ) . '"' : '' );

        echo do_shortcode( '[cooked-categories' . esc_attr( $width . $child_of . $hide_empty . $show_description ) . ' style="list"]' );
        echo wp_kses_post( $args['after_widget'] );

    }

    public function form( $instance ) {

        $title = ( !empty( $instance['title'] ) ? $instance['title'] : false );
        $child_of = ( !empty( $instance['child_of'] ) ? $instance['child_of'] : false );
        $hide_empty = ( !empty( $instance['hide_empty'] ) ? $instance['hide_empty'] : false );
        $categories_array = Cooked_Settings::terms_array( 'cp_recipe_category', false, false, false, false, false );

        ?>

        <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Widget Title (optional):', 'cooked' ); ?></label>
        <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'child_of' ) ); ?>"><?php esc_attr_e( 'Parent Category', 'cooked' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'child_of' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'child_of' ) ); ?>">
                <?php if ( !empty($categories_array) ):
                    ?><option value=""<?php echo ( !$child_of ? ' selected' : '' ); ?>><?php esc_html_e( 'All Categories', 'cooked' ); ?></option><?php
                    foreach( $categories_array as $key => $val ):
                        ?><option value="<?php echo esc_attr( $key ); ?>"<?php echo ( $child_of == $key ? ' selected' : '' ); ?>><?php echo esc_html( $val ); ?></option><?php
                    endforeach;
                endif; ?>
            </select>
        </p>

        <p>
        <input id="<?php echo esc_attr( $this->get_field_id( 'hide_empty' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hide_empty' ) ); ?>"<?php echo ( $hide_empty ? ' checked' : '' ); ?> type="checkbox" value="1">
        <label for="<?php echo esc_attr( $this->get_field_id( 'hide_empty' ) ); ?>"><?php esc_attr_e( 'Hide Empty Categories', 'cooked' ); ?></label>
        </p>

        <?php
    }

    public function update( $new_instance, $old_instance ) {

        $instance = array();
        $instance['title'] = ( !empty( $new_instance['title'] ) ? strip_tags( $new_instance['title'] ) : '' );
        $instance['child_of'] = ( !empty( $new_instance['child_of'] ) ? strip_tags( $new_instance['child_of'] ) : false );
        $instance['hide_empty'] = ( !empty( $new_instance['hide_image'] ) ? strip_tags( $new_instance['hide_image'] ) : false );
        return $instance;
    }

}
