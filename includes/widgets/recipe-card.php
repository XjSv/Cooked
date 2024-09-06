<?php

class Cooked_Widget_Recipe_Card extends WP_Widget {

    public function __construct() {
        $widget_ops = array(
            'classname' => 'cooked_widget_recipe_card',
            'description' => 'Display a recipe card.',
        );
        parent::__construct( 'cooked_widget_recipe_card', 'Cooked - Recipe Card', $widget_ops );
    }

    public function widget( $args, $instance ) {

        $recipe_id = ( isset($instance['recipe_id']) && $instance['recipe_id'] ? ' id="' . esc_attr( $instance['recipe_id'] ) . '"' : '' );
        $style = ( isset($instance['style']) && $instance['style'] ? ' style="' . esc_attr( $instance['style'] ) . '"' : '' );
        $width = ( isset($instance['width']) && $instance['width'] ? ' width="' . esc_attr( $instance['width'] ) . '"' : '' );
        $hide_image = ( isset($instance['hide_image']) && $instance['hide_image'] ? ' hide_image="true"' : '' );
        $hide_title = ( isset($instance['hide_title']) && $instance['hide_title'] ? ' hide_title="true"' : '' );
        $hide_excerpt = ( isset($instance['hide_excerpt']) && $instance['hide_excerpt'] ? ' hide_excerpt="true"' : '' );
        $hide_author = ( isset($instance['hide_author']) && $instance['hide_author'] ? ' hide_author="true"' : '' );

        if ( apply_filters( 'cooked_can_show_recipe', true, $instance['recipe_id'] ) ):

            echo wp_kses_post( $args['before_widget'] );

            if ( ! empty( $instance['title'] ) ) {
                echo wp_kses_post( $args['before_title'] ) . apply_filters( 'widget_title', $instance['title'] ) . wp_kses_post( $args['after_title'] );
            }

            echo do_shortcode( '[cooked-recipe-card' . wp_kses_post( $recipe_id . $width . $hide_image . $hide_title . $hide_excerpt . $hide_author . $style ) . ']' );

            echo wp_kses_post( $args['after_widget'] );

        endif;

    }

    public function form( $instance ) {

        $title = ( !empty( $instance['title'] ) ? $instance['title'] : false );
        $recipe_id = ( !empty( $instance['recipe_id'] ) ? $instance['recipe_id'] : false );
        $width = ( !empty( $instance['width'] ) ? $instance['width'] : '100%' );
        $recipe_id = ( isset( $instance['recipe_id'] ) && $instance['recipe_id'] ? $instance['recipe_id'] : false );
        $style = ( isset( $instance['style'] ) && $instance['style'] ? $instance['style'] : false );
        $hide_image = ( isset( $instance['hide_image'] ) && $instance['hide_image'] ? true : false );
        $hide_title = ( isset( $instance['hide_title'] ) && $instance['hide_title'] ? true : false );
        $hide_excerpt = ( isset( $instance['hide_excerpt'] ) && $instance['hide_excerpt'] ? true : false );
        $hide_author = ( isset( $instance['hide_author'] ) && $instance['hide_author'] ? true : false );

        $recipe_list = get_transient( 'cooked_widget_recipes_list' );

        if ( empty($recipe_list) ):

            $args = [
                'post_type' => 'cp_recipe',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC'
            ];

            // Filter out the pending/draft recipes.
            $args = apply_filters( 'cooked_recipe_public_query_filters', $args );

            $recipes = Cooked_Recipes::get( $args );
            if ( isset($recipes['raw']) ): unset( $recipes['raw'] ); endif;
            $recipe_list = [];

            if ( !empty($recipes) ):
                foreach( $recipes as $key => $recipe ):
                    $recipe_list[$recipe['id']] = $recipe['title'];
                endforeach;
            endif;

            set_transient( 'cooked_widget_recipes_list', $recipe_list, 300 );

        endif;

        ?>

        <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Widget Title (optional):', 'cooked' ); ?></label>
        <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>

        <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'recipe_id' ) ); ?>"><?php esc_attr_e( 'Recipe:', 'cooked' ); ?></label>
        <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'recipe_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'recipe_id' ) ); ?>">
            <?php foreach( $recipe_list as $rid => $name ): ?>
                <option value="<?php echo esc_attr($rid); ?>"<?php echo ( $recipe_id == $rid ? ' selected' : '' ); ?>><?php echo esc_html($name); ?></option>
            <?php endforeach; ?>
        </select>
        </p>

        <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>"><?php esc_attr_e( 'Style:', 'cooked' ); ?></label>
        <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'style' ) ); ?>">
            <option value=""<?php echo ( !$style ? ' selected' : '' ); ?>><?php esc_html_e( 'Simple', 'cooked' ); ?></option>
            <option value="centered"<?php echo ( $style == 'centered' ? ' selected' : '' ); ?>><?php esc_html_e( 'Simple Centered', 'cooked' ); ?></option>
            <option value="modern"<?php echo ( $style == 'modern' ? ' selected' : '' ); ?>><?php esc_html_e( 'Modern', 'cooked' ); ?></option>
            <option value="modern-centered"<?php echo ( $style == 'modern-centered' ? ' selected' : '' ); ?>><?php esc_html_e( 'Modern Centered', 'cooked' ); ?></option>
        </select>
        </p>

        <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'width' ) ); ?>"><?php esc_attr_e( 'Width:', 'cooked' ); ?></label>
        <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'width' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'width' ) ); ?>" type="text" value="<?php echo esc_attr( $width ); ?>">
        </p>

        <p>
        <input id="<?php echo esc_attr( $this->get_field_id( 'hide_image' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hide_image' ) ); ?>"<?php echo ( $hide_image ? ' checked' : '' ); ?> type="checkbox" value="1">
        <label for="<?php echo esc_attr( $this->get_field_id( 'hide_image' ) ); ?>"><?php esc_attr_e( 'Hide Image', 'cooked' ); ?></label>
        </p>

        <p>
        <input id="<?php echo esc_attr( $this->get_field_id( 'hide_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hide_title' ) ); ?>"<?php echo ( $hide_title ? ' checked' : '' ); ?> type="checkbox" value="1">
        <label for="<?php echo esc_attr( $this->get_field_id( 'hide_title' ) ); ?>"><?php esc_attr_e( 'Hide Title', 'cooked' ); ?></label>
        </p>

        <p>
        <input id="<?php echo esc_attr( $this->get_field_id( 'hide_author' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hide_author' ) ); ?>"<?php echo ( $hide_author ? ' checked' : '' ); ?> type="checkbox" value="1">
        <label for="<?php echo esc_attr( $this->get_field_id( 'hide_author' ) ); ?>"><?php esc_attr_e( 'Hide Author', 'cooked' ); ?></label>
        </p>

        <p>
        <input id="<?php echo esc_attr( $this->get_field_id( 'hide_excerpt' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hide_excerpt' ) ); ?>"<?php echo ( $hide_excerpt ? ' checked' : '' ); ?> type="checkbox" value="1">
        <label for="<?php echo esc_attr( $this->get_field_id( 'hide_excerpt' ) ); ?>"><?php esc_attr_e( 'Hide Excerpt', 'cooked' ); ?></label>
        </p>

        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( !empty( $new_instance['title'] ) ? wp_strip_all_tags( $new_instance['title'] ) : '' );
        $instance['recipe_id'] = ( !empty( $new_instance['recipe_id'] ) ? wp_strip_all_tags( $new_instance['recipe_id'] ) : false );
        $instance['width'] = ( !empty( $new_instance['width'] ) ? wp_strip_all_tags( $new_instance['width'] ) : '100%' );
        $instance['style'] = ( !empty( $new_instance['style'] ) ? wp_strip_all_tags( $new_instance['style'] ) : false );
        $instance['hide_image'] = ( !empty( $new_instance['hide_image'] ) ? wp_strip_all_tags( $new_instance['hide_image'] ) : false );
        $instance['hide_title'] = ( !empty( $new_instance['hide_title'] ) ? wp_strip_all_tags( $new_instance['hide_title'] ) : false );
        $instance['hide_excerpt'] = ( !empty( $new_instance['hide_excerpt'] ) ? wp_strip_all_tags( $new_instance['hide_excerpt'] ) : false );
        $instance['hide_author'] = ( !empty( $new_instance['hide_author'] ) ? wp_strip_all_tags( $new_instance['hide_author'] ) : false );
        return $instance;
    }

}
