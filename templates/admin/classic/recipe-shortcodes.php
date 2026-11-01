<?php
/**
 * Recipe editor: Shortcodes tab, classic layout
 *
 * Included by cooked_render_recipe_fields(). The shortcodes for this recipe (Display Recipe, Recipe
 * Card) come through the cooked_recipe_shortcodes_after action (cooked_recipe_shortcodes_content()).
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       2.0.0
 */

?>
            <div class="recipe-setting-block">

                <?php do_action( 'cooked_recipe_shortcodes_before' ); ?>

                <!-- [cooked-info] -->
                <div class="cooked-clearfix">

                    <div class="cooked-setting-column-23">

                        <h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Recipe Information', 'cooked' ); ?></h3>
                        <p class="cooked-bm-10"><?php esc_html_e( 'This will display the recipe author, cooking times, etc.', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class='cooked-shortcode-field' type='text' readonly value='[cooked-info]' />
                        </div>

                        <div class="cooked-clearfix">
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>
                                <?php
                                    /* translators: "include and exclude" section title */
                                    printf( esc_html__( '"%1$s" and "%2$s"', 'cooked' ), 'include', 'exclude' );
                                ?>
                                </strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'This will allow you to include or exclude content from the shortcode output.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='include="author,total_time"' />
                                </div>
                            </div>
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>
                                <?php
                                    /* translators: "left and right" section title */
                                    printf( esc_html__( '"%1$s" and "%2$s"', 'cooked' ), 'left', 'right' );
                                ?>
                                    </strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Used like "include", but will position the content to the left or right.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='left="author" right="total_time"' />
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="cooked-setting-column-13">
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">

                            <?php
                            $available_cooked_info_vars = apply_filters(
                                'cooked_available_info_vars',
                                [
                                    'author'           => __( 'Author', 'cooked' ),
                                    'prep_time'        => __( 'Prep Time', 'cooked' ),
                                    'cook_time'        => __( 'Cook Time', 'cooked' ),
                                    'total_time'       => __( 'Total Time', 'cooked' ),
                                    'difficulty_level' => __( 'Difficulty', 'cooked' ),
                                    'servings'         => __( 'Servings Switcher', 'cooked' ),
                                    'taxonomies'       => __( 'Category', 'cooked' ),
                                    'print'            => esc_html__( 'Print Mode', 'cooked' ),
                                    'fullscreen'       => __( 'Full-Screen Mode', 'cooked' ),
                                ]
                            );

                            foreach ( $available_cooked_info_vars as $var => $name ) :
                                echo '<strong>' . esc_html( $var ) . '</strong> (' . esc_html( $name ) . ')<br>';
                            endforeach;
                            ?>

                        </p>
                    </div>

                </div>

                <hr class="cooked-hr">

                <!-- [cooked-ingredients] -->
                <div class="cooked-clearfix">

                    <div class="cooked-setting-column-23">

                        <h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Ingredients', 'cooked' ); ?></h3>
                        <p class="cooked-bm-10"><?php esc_html_e( 'This will display the list of ingredients, added via the "Ingredients" tab.', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class='cooked-shortcode-field' type='text' readonly value='[cooked-ingredients]' />
                        </div>

                        <p class="cooked-bm-5"><strong>"checkboxes"</strong></p>
                        <p class="cooked-bm-10"><?php esc_html_e( 'This will allow you to hide or show the checkboxes:', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class='cooked-shortcode-field' type='text' readonly value='checkboxes=false' />
                        </div>

                    </div>

                    <div class="cooked-setting-column-13">
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <strong>true</strong> (<?php esc_html_e( 'Show checkboxes', 'cooked' ); ?>)<br>
                            <strong>false</strong> (<?php esc_html_e( 'Hide checkboxes', 'cooked' ); ?>)
                        </p>
                    </div>

                </div>

                <hr class="cooked-hr">

                <!-- [cooked-directions] -->
                <div class="cooked-clearfix">

                    <div class="cooked-setting-column-23">

                        <h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Directions', 'cooked' ); ?></h3>
                        <p class="cooked-bm-10"><?php esc_html_e( 'This will display the list of directions, added via the "Directions" tab.', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class='cooked-shortcode-field' type='text' readonly value='[cooked-directions]' />
                        </div>

                        <p class="cooked-bm-5"><strong>"numbers"</strong></p>
                        <p class="cooked-bm-10"><?php esc_html_e( 'This will allow you to hide or show the numbers:', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class='cooked-shortcode-field' type='text' readonly value='numbers=false' />
                        </div>

                    </div>

                    <div class="cooked-setting-column-13">
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <strong>true</strong> (<?php esc_html_e( 'Show numbers', 'cooked' ); ?>)<br>
                            <strong>false</strong> (<?php esc_html_e( 'Hide numbers', 'cooked' ); ?>)
                        </p>
                    </div>

                </div>

                <hr class="cooked-hr">

                <!-- [cooked-image] -->
                <div class="cooked-clearfix">

                    <div class="cooked-setting-column-23">

                        <h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Featured Image', 'cooked' ); ?></h3>
                        <p class="cooked-bm-10"><?php esc_html_e( 'This will display the featured image, if one is set.', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class='cooked-shortcode-field' type='text' readonly value='[cooked-image]' />
                        </div>

                    </div>

                    <div class="cooked-setting-column-13">
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <em><?php esc_html_e( 'None', 'cooked' ); ?></em>
                        </p>
                    </div>

                </div>

                <hr class="cooked-hr">

                <!-- [cooked-nutrition] -->
                <div class="cooked-clearfix">

                    <div class="cooked-setting-column-23">

                        <h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Nutrition Label', 'cooked' ); ?></h3>
                        <p class="cooked-bm-10"><?php esc_html_e( 'This will display the Nutrition Facts label, if data is present.', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class='cooked-shortcode-field' type='text' readonly value='[cooked-nutrition]' />
                        </div>

                    </div>

                    <div class="cooked-setting-column-13">
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <em><?php esc_html_e( 'None', 'cooked' ); ?></em>
                        </p>
                    </div>

                </div>

                <div class="cooked-conditional-hidden" data-condition="cooked_gallery_type" data-value="cooked">

                    <hr class="cooked-hr">

                    <!-- [cooked-gallery] -->
                    <div class="cooked-clearfix">

                        <div class="cooked-setting-column-23">

                            <h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Gallery', 'cooked' ); ?></h3>
                            <p class="cooked-bm-10"><?php esc_html_e( 'This will display the gallery, if one is set or created from the "Gallery" tab.', 'cooked' ); ?></p>
                            <div class="cooked-bm-20 cooked-block">
                                <input class='cooked-shortcode-field' type='text' readonly value='[cooked-gallery]' />
                            </div>

                            <div class="cooked-clearfix">
                                <div class="cooked-setting-column-12">
                                    <p class="cooked-bm-5"><strong>"width"</strong></p>
                                    <p class="cooked-bm-10"><?php esc_html_e( 'Set the width of the gallery.', 'cooked' ); ?></p>
                                    <div class="cooked-bm-20 cooked-block">
                                        <input class='cooked-shortcode-field' type='text' readonly value='width="350px"' />
                                    </div>
                                </div>
                                <div class="cooked-setting-column-12">
                                    <p class="cooked-bm-5"><strong>"ratio"</strong></p>
                                    <p class="cooked-bm-10"><?php esc_html_e( 'Set the image size ratio.', 'cooked' ); ?></p>
                                    <div class="cooked-bm-20 cooked-block">
                                        <input class='cooked-shortcode-field' type='text' readonly value='ratio="500/500"' />
                                    </div>
                                </div>
                            </div>

                            <div class="cooked-clearfix">
                                <div class="cooked-setting-column-12">
                                    <p class="cooked-bm-5"><strong>"nav"</strong></p>
                                    <p class="cooked-bm-10"><?php esc_html_e( 'Set the navigation style.', 'cooked' ); ?></p>
                                    <div class="cooked-bm-20 cooked-block">
                                        <input class='cooked-shortcode-field' type='text' readonly value='nav="thumbs"' />
                                    </div>
                                </div>
                                <div class="cooked-setting-column-12">
                                    <p class="cooked-bm-5"><strong>"allowfullscreen"</strong></p>
                                    <p class="cooked-bm-10"><?php esc_html_e( 'Enable or disable "Full-Screen" mode.', 'cooked' ); ?></p>
                                    <div class="cooked-bm-20 cooked-block">
                                        <input class='cooked-shortcode-field' type='text' readonly value='allowfullscreen="true"' />
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="cooked-setting-column-13">
                            <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                            <p class="cooked-bm-10">
                                <strong>width</strong><br>
                                <?php
                                    /* translators: related to the width of  slideshows: "80% or 300px" section title */
                                    printf( esc_html__( 'ex: "%1$s" or "%2$s"', 'cooked' ), '80%', '300px' );
                                ?>
                                <br><br>
                                <strong>ratio</strong><br>
                                <?php
                                    /* translators: related to the image ratio for slideshows: "ex: 800/600" section title */
                                    printf( esc_html__( 'ex: "%s"', 'cooked' ), '800/600' );
                                ?>
                                <br><br>
                                <strong>nav</strong><br>
                                <?php
                                    /* translators: related to the navigation style for slideshows: "dots, thumbs or false" section title */
                                    printf( esc_html__( '"%1$s", "%2$s", or "%3$s"', 'cooked' ), 'dots', 'thumbs', 'false' );
                                ?>
                                <br><br>
                                <strong>allowfullscreen</strong><br>
                                <?php
                                    /* translators: related to allowing full screen for slideshows: "true or false" section title */
                                    printf( esc_html__( '"%1$s" or "%2$s"', 'cooked' ), 'true', 'false' );
                                ?>
                            </p>
                        </div>

                    </div>

                </div>

                <hr class="cooked-hr">

                <!-- [cooked-excerpt] -->
                <div class="cooked-clearfix">

                    <div class="cooked-setting-column-23">

                        <h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Excerpt', 'cooked' ); ?></h3>
                        <p class="cooked-bm-10"><?php esc_html_e( 'This will display the excerpt, if one is available from the "Layout & Content" tab.', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class='cooked-shortcode-field' type='text' readonly value='[cooked-excerpt]' />
                        </div>

                    </div>

                    <div class="cooked-setting-column-13">
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <em><?php esc_html_e( 'None', 'cooked' ); ?></em>
                        </p>
                    </div>

                </div>

                <hr class="cooked-hr">

                <!-- [cooked-notes] -->
                <div class="cooked-clearfix">

                <div class="cooked-setting-column-23">

                    <h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Notes', 'cooked' ); ?></h3>
                    <p class="cooked-bm-10"><?php esc_html_e( 'This will display the notes, if one is available from the "Layout & Content" tab.', 'cooked' ); ?></p>
                    <div class="cooked-bm-20 cooked-block">
                        <input class='cooked-shortcode-field' type='text' readonly value='[cooked-notes]' />
                    </div>

                    <p class="cooked-bm-5"><strong>"show_header"</strong></p>
                    <p class="cooked-bm-10"><?php esc_html_e( 'This will allow you to hide or show the header for the notes section:', 'cooked' ); ?></p>
                    <div class="cooked-bm-20 cooked-block">
                        <input class='cooked-shortcode-field' type='text' readonly value='show_header=true' />
                    </div>

                </div>

                <div class="cooked-setting-column-13">
                    <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                    <p class="cooked-bm-10">
                        <strong>true</strong> (<?php esc_html_e( 'Show header', 'cooked' ); ?>)<br>
                        <strong>false</strong> (<?php esc_html_e( 'Hide header', 'cooked' ); ?>)
                    </p>
                </div>

                </div>

                <hr class="cooked-hr">

                <!-- [timer] -->
                <div class="cooked-clearfix">

                    <div class="cooked-setting-column-23">

                        <h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Timer', 'cooked' ); ?></h3>
                        <p class="cooked-bm-10"><?php esc_html_e( 'This will display a special link to start a cooking timer.', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class='cooked-shortcode-field' type='text' readonly value='<?php echo ( shortcode_exists( 'cooked-timer' ) ? '[cooked-timer minutes="5"]5 Minutes[/cooked-timer]' : '[timer minutes=5]5 Minutes[/timer]' ); ?>' />
                        </div>

                        <div class="cooked-clearfix">
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>
                                <?php
                                    /* translators: "seconds, minutes and hours" section title */
                                    printf( esc_html__( '"%1$s", "%2$s" and "%3$s"', 'cooked' ), 'seconds', 'minutes', 'hours' );
                                ?>
                                </strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Use just one or a combination of all three to set the timer length', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='minutes="5" seconds="30"' />
                                </div>
                            </div>
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"desc"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Add a short description for this timer, if applicable.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='desc="Boil for 20 minutes"' />
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="cooked-setting-column-13">
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <strong>seconds</strong> (<?php esc_html_e( 'Time in seconds', 'cooked' ); ?>)<br>
                            <strong>minutes</strong> (<?php esc_html_e( 'Time in minutes', 'cooked' ); ?>)<br>
                            <strong>hours</strong> (<?php esc_html_e( 'Time in hours', 'cooked' ); ?>)<br>
                            <strong>desc</strong> (<?php esc_html_e( 'Timer Description', 'cooked' ); ?>)
                        </p>
                    </div>

                </div>

                <hr class="cooked-hr">

                <!-- [cooked-next-previous] -->
                <div class="cooked-clearfix">

                    <div class="cooked-setting-column-23">

                        <h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Next & Previous Recipe Navigation', 'cooked' ); ?></h3>
                        <p class="cooked-bm-10"><?php esc_html_e( 'This will display navigation links to the next and previous recipes.', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class='cooked-shortcode-field' type='text' readonly value='[cooked-next-previous]' />
                        </div>
                        <p class="cooked-bm-10"><?php esc_html_e( 'This shortcode automatically displays the previous and next recipe links based on the current recipe page. No parameters are required.', 'cooked' ); ?></p>

                    </div>

                    <div class="cooked-setting-column-13">
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Usage', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <?php esc_html_e( 'Simply add the shortcode to your recipe template or content area. The navigation will automatically show the previous and next recipes based on recipe ID order.', 'cooked' ); ?>
                        </p>
                    </div>

                </div>

                <hr class="cooked-hr">

                <!-- [cooked-related-recipes] -->
                <div class="cooked-clearfix">

                    <div class="cooked-setting-column-23">

                        <h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Related Recipes', 'cooked' ); ?></h3>
                        <p class="cooked-bm-10"><?php esc_html_e( 'Displays a grid of related recipes based on shared terms in any of the recipe taxonomies (categories, cuisines, cooking methods, tags, diets), shown in random order. No cache or pre-calculation.', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class='cooked-shortcode-field' type='text' readonly value='[cooked-related-recipes]' />
                        </div>

                        <div class="cooked-clearfix">
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"id"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Specify a recipe ID. If not provided, uses the current recipe.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='id="123"' />
                                </div>
                            </div>
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"include_ids"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Comma-separated recipe IDs to pin at the start of the grid. Remaining slots are filled with related recipes.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='include_ids="101,102"' />
                                </div>
                            </div>
                        </div>

                        <div class="cooked-clearfix">
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"title"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Set the heading text for the related recipes section.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='title="You May Also Like"' />
                                </div>
                            </div>
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"limit"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Number of recipes to display (default: 4).', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='limit="4"' />
                                </div>
                            </div>
                        </div>

                        <div class="cooked-clearfix">
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"columns"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Number of columns in the grid (default: 2).', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='columns="2"' />
                                </div>
                            </div>
                        </div>

                        <div class="cooked-clearfix">
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"hide_image"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Hide recipe card images.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='hide_image="true"' />
                                </div>
                            </div>
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"hide_excerpt"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Hide recipe card excerpts.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='hide_excerpt="true"' />
                                </div>
                            </div>
                        </div>

                        <div class="cooked-clearfix">
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"hide_author"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Hide recipe card authors.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='hide_author="true"' />
                                </div>
                            </div>
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"match_*"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Toggle which taxonomies are used: match_categories, match_cuisines, match_cooking_methods, match_tags, match_diets (default: true).', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='match_categories="true"' />
                                </div>
                            </div>
                        </div>

                        <p class="cooked-bm-5"><strong><?php esc_html_e( 'Matching options:', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10"><?php esc_html_e( 'Set to false to exclude a taxonomy from related recipes, e.g. match_cuisines="false" to ignore cuisines.', 'cooked' ); ?></p>

                    </div>

                    <div class="cooked-setting-column-13">
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <strong>id</strong> (<?php esc_html_e( 'Recipe ID', 'cooked' ); ?>)<br>
                            <strong>include_ids</strong> (<?php esc_html_e( 'Pinned recipe IDs', 'cooked' ); ?>)<br>
                            <strong>title</strong> (<?php esc_html_e( 'Section heading', 'cooked' ); ?>)<br>
                            <strong>limit</strong> (<?php esc_html_e( 'Number of recipes', 'cooked' ); ?>)<br>
                            <strong>columns</strong> (<?php esc_html_e( 'Grid columns', 'cooked' ); ?>)<br>
                            <strong>hide_image</strong> (<?php esc_html_e( 'true/false', 'cooked' ); ?>)<br>
                            <strong>hide_excerpt</strong> (<?php esc_html_e( 'true/false', 'cooked' ); ?>)<br>
                            <strong>hide_author</strong> (<?php esc_html_e( 'true/false', 'cooked' ); ?>)<br>
                            <strong>match_*</strong> (<?php esc_html_e( 'true/false', 'cooked' ); ?>)
                        </p>
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Example', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <code>[cooked-related-recipes include_ids="101,102" limit="4" columns="2" title="Related Recipes"]</code>
                        </p>
                    </div>

                </div>

                <?php do_action( 'cooked_recipe_shortcodes_after' ); ?>

            </div>
