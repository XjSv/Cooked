<?php

class Cooked_Widget_Recipe_List extends WP_Widget {

    public function __construct() {
        $widget_ops = array(
            'classname' => 'cooked_widget_recipe_list',
            'description' => 'Display a list of recipes.',
        );
        parent::__construct( 'cooked_widget_recipe_list', 'Cooked - Recipe List', $widget_ops );
    }

    public function widget( $args, $instance ) {

        echo wp_kses_post( $args['before_widget'] );
        if ( ! empty( $instance['title'] ) ) {
            echo wp_kses_post( $args['before_title'] ) . apply_filters( 'widget_title', $instance['title'] ) . wp_kses_post( $args['after_title'] );
        }

        $recipes = ( isset($instance['orderby']) && $instance['orderby'] == 'ids' && isset($instance['recipes']) && !empty($instance['recipes']) ? ' recipes="' . implode( ',', $instance['recipes'] ) . '"' : '' );
        $orderby = ( !$recipes && isset($instance['orderby']) && $instance['orderby'] ? ' orderby="' . esc_attr( $instance['orderby'] ) . '"' : '' );
        $show = ( !$recipes && isset($instance['show']) && $instance['show'] ? ' show="' . esc_attr( $instance['show'] ) . '"' : '' );
        $width = ( isset($instance['width']) && $instance['width'] ? ' width="' . esc_attr( $instance['width'] ) . '"' : '' );
        $hide_image = ( isset($instance['hide_image']) && $instance['hide_image'] ? ' hide_image="true"' : '' );
        $hide_author = ( isset($instance['hide_author']) && $instance['hide_author'] ? ' hide_author="true"' : '' );
        echo do_shortcode( '[cooked-recipe-list' . wp_kses_post( $orderby . $show . $recipes . $width . $hide_image . $hide_author ) . ']' );
        echo wp_kses_post( $args['after_widget'] );

    }

    public function form( $instance ) {

        $title = ( !empty( $instance['title'] ) ? $instance['title'] : false );
        $orderby = ( !empty( $instance['orderby'] ) ? $instance['orderby'] : 'date' );
        $show = ( !empty( $instance['show'] ) ? $instance['show'] : 5 );
        $recipes = ( !empty( $instance['recipes'] ) ? $instance['recipes'] : '' );
        $width = ( !empty( $instance['width'] ) ? $instance['width'] : '100%' );
        $hide_image = ( isset( $instance['hide_image'] ) && $instance['hide_image'] ? true : false );
        $hide_author = ( isset( $instance['hide_author'] ) && $instance['hide_author'] ? true : false );

        $recipes_style = ( $orderby == 'date' ? ' style="display:none;' : '' );
        $show_style = ( $orderby == 'ids' ? ' style="display:none;' : '' );

        ?>

        <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Widget Title (optional):', 'cooked' ); ?></label>
        <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>

        <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>"><?php esc_attr_e( 'Sorted by:', 'cooked' ); ?></label>
        <select class="cooked-widget-conditional widefat" id="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'orderby' ) ); ?>">
            <option value="date"<?php echo ( !$orderby || $orderby == 'date' ? ' selected' : '' ); ?>><?php esc_html_e( 'Most Recent', 'cooked' ); ?></option>
            <option value="ids"<?php echo ( !$orderby || $orderby == 'ids' ? ' selected' : '' ); ?>><?php esc_html_e( 'Choose Recipes', 'cooked' ); ?></option>
        </select>
        </p>

        <?php $max_show = 15; ?>
        <p data-condition="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>" data-value="date"<?php echo esc_attr( $show_style ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show' ) ); ?>"><?php esc_attr_e( 'Show:', 'cooked' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'show' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show' ) ); ?>">
                <?php $temp_counter = 0; do {
                    $temp_counter++;
                    ?><option value="<?php echo esc_attr( $temp_counter ); ?>"<?php echo ( !$show || $show == $temp_counter ? ' selected' : '' ); ?>><?php echo esc_html( $temp_counter ); ?></option><?php
                } while( $temp_counter < $max_show ); ?>
            </select>
        </p>

        <div data-condition="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>" data-value="ids"<?php echo esc_attr( $recipes_style ); ?>>
            <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'recipes' ) ); ?>"><?php esc_attr_e( 'Recipes:', 'cooked' ); ?></label>
            </p>
            <?php
                $field_id = $this->get_field_id( 'recipes' );
                $field_name = $this->get_field_name( 'recipes' ) . '[]';
                Cooked_Widgets::recipe_finder( $field_id, $field_name, $recipes );
            ?>
        </div>

        <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'width' ) ); ?>"><?php esc_attr_e( 'Width:', 'cooked' ); ?></label>
        <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'width' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'width' ) ); ?>" type="text" value="<?php echo esc_attr( $width ); ?>">
        </p>

        <p>
        <input id="<?php echo esc_attr( $this->get_field_id( 'hide_image' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hide_image' ) ); ?>"<?php echo ( $hide_image ? ' checked' : '' ); ?> type="checkbox" value="1">
        <label for="<?php echo esc_attr( $this->get_field_id( 'hide_image' ) ); ?>"><?php esc_attr_e( 'Hide Image', 'cooked' ); ?></label>
        </p>

        <p>
        <input id="<?php echo esc_attr( $this->get_field_id( 'hide_author' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hide_author' ) ); ?>"<?php echo ( $hide_author ? ' checked' : '' ); ?> type="checkbox" value="1">
        <label for="<?php echo esc_attr( $this->get_field_id( 'hide_author' ) ); ?>"><?php esc_attr_e( 'Hide Author', 'cooked' ); ?></label>
        </p>

        <?php
    }

    public function update( $new_instance, $old_instance ) {

        $instance = array();
        $instance['title'] = ( !empty( $new_instance['title'] ) ? wp_strip_all_tags( $new_instance['title'] ) : '' );
        $instance['orderby'] = ( !empty( $new_instance['orderby'] ) ? wp_strip_all_tags( $new_instance['orderby'] ) : 'date' );
        $instance['width'] = ( !empty( $new_instance['width'] ) ? wp_strip_all_tags( $new_instance['width'] ) : '100%' );
        $instance['show'] = ( !empty( $new_instance['show'] ) ? wp_strip_all_tags( $new_instance['show'] ) : 5 );
        $instance['recipes'] = ( !empty( $new_instance['recipes'] ) ? $new_instance['recipes'] : '' );
        $instance['hide_image'] = ( !empty( $new_instance['hide_image'] ) ? wp_strip_all_tags( $new_instance['hide_image'] ) : false );
        $instance['hide_author'] = ( !empty( $new_instance['hide_author'] ) ? wp_strip_all_tags( $new_instance['hide_author'] ) : false );
        return $instance;
    }

}
