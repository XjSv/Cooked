<div id="cooked-settings-prewrap"><div id="cooked-settings-wrap">

	<form action="options.php" method="post">

	<div class="cooked-settings-update-button">
		<?php submit_button( esc_html__( 'Update Settings','cooked' ), 'primary', 'submit', false ); ?>
	</div>

	<div class="cooked-settings-header">
		<i class="cooked-icon cooked-icon-cooked-icon"></i>&nbsp;&nbsp;<?php esc_html_e('Cooked Settings','cooked'); ?>
	</div>
	<div id="cooked-settings-panel" class="wrap"><?php

		global $_cooked_settings;
		$Cooked_Settings = new Cooked_Settings();
		$cooked_settings_tabs_fields = $Cooked_Settings->tabs_fields();
		$parent_page_slug = ( isset($_cooked_settings['browse_page']) && $_cooked_settings['browse_page'] ? basename( get_permalink( $_cooked_settings['browse_page'] ) ) : false );

		$tab_counter = 1;
		echo '<ul id="cooked-settings-tabs" class="cooked-clearfix">';
		foreach( $cooked_settings_tabs_fields as $tab_slug => $tab ):
			echo '<li id="cooked-settings-tab-' . esc_attr( $tab_slug ) . '"' . ( $tab_counter == 1 ? ' class="active"' : '' ) . '><a href="#' . esc_attr( $tab_slug ) . '"><i class="cooked-icon cooked-icon-' . esc_attr( $tab['icon'] ) . '"></i>&nbsp;&nbsp;' . esc_attr( $tab['name'] ) . '</a></li>';
			$tab_counter++;
		endforeach;
		echo '</ul>';

		?>

		<?php settings_errors(); ?>

		<div id="cooked_recipe_settings" class="cooked-settings-form-wrapper">

				<input type="hidden" name="cooked_settings_saved" value="true" />

				<?php settings_fields( 'cooked_settings_group' ); ?>

				<div class="cooked-settings-tab-content-wrapper">

					<?php

					global $conditions;
					$conditions = array();
					$conditional_requirements_js = array();
					$tab_counter = 1;

					foreach( $cooked_settings_tabs_fields as $tab_slug => $tab ):
						echo '<div id="cooked-settings-tab-content-' . esc_attr( $tab_slug ) . '" class="cooked-settings-tab-content">';

							// Add a hidden H2 tag for the messages, if any, to show up below it.
							if ($tab_counter == 1): echo '<h2 style="display:none;"></h2>'; endif;
							foreach( $tab['fields'] as $name => $field ):

								$notitle = false;
								$conditional_requirement = '';

								$class = ( isset($field['class']) ? ' ' . $field['class'] : '' );
								$current_val = ( isset($_cooked_settings[$name]) ? $_cooked_settings[$name] : null );
								$field_type = 'field_' . esc_attr( $field['type'] );
								$field_options = ( isset($field['options']) ? $field['options'] : false );
								$field_options = ( !$field_options ? ( isset($field['placeholder']) ? $field['placeholder'] : $field_options ) : $field_options );
								$color = ( isset($field['color']) && $field['color'] ? $field['color'] : false );

								if ( method_exists ( $Cooked_Settings, $field_type ) ):

									if ( $field['type'] == 'misc_button' || $field['type'] == 'nonce' ):
										$notitle = true;
										$field_options = ( isset($field['title']) ? $field['title'] : false );
									endif;

									if ( isset($field['conditional_requirement']) && $field['conditional_requirement'] ):
										if ( is_array($field['conditional_requirement']) ):
											$conditional_requirement = ' v-show="' . implode( ' && ', $field['conditional_requirement'] ) . '"';
										else:
											$conditional_requirement = ' v-show="' . esc_attr($field['conditional_requirement']) . '"';
										endif;
									endif;

									echo ( $conditional_requirement ? '<transition name="fade">' : '' );
									echo '<div' . $conditional_requirement . ' class="recipe-setting-block ' . esc_attr( $field['type'] ) . ' cooked-bm-25' . esc_attr( $class ) . '">';
										echo ( !$notitle ? '<h3 class="cooked-settings-title">' . wp_kses_post( $field['title'] ) . '</h3>' : '' );
										echo ( isset($field['desc']) && $field['desc'] ? '<p>' . wp_kses_post( $field['desc'] ). '</p>' : '' );
										$Cooked_Settings->$field_type( $name, $field_options, $color );
									echo '</div>';
									echo ( $conditional_requirement ? '</transition>' : '' );

								endif;

							endforeach;
							$tab_counter++;

						echo '</div>';
					endforeach; ?>

				</div>

		</div>

		<?php if ( COOKED_DEV ):
			Cooked_Functions::wpml_xml( $cooked_settings_tabs_fields );
		endif; ?>

	</div><?php

	if ( !empty($conditions) ):

		foreach( $conditions as $setting_name => $array ):
			foreach( $array as $name => $true_val ):
				$val = ( isset( $_cooked_settings[$setting_name] ) && is_array( $_cooked_settings[$setting_name] ) && in_array( $true_val, $_cooked_settings[$setting_name] ) ? 'true' : 'false' );
				$conditional_requirements_js[] = "'$name':$val";
			endforeach;
		endforeach; ?>

		<script type="text/javascript">

			var vm = new Vue({
			  	el: '#cooked-settings-panel',
			  	data: {
			  		<?php echo implode( ',', $conditional_requirements_js ); ?>
			  	}
			});

		</script>

		<style type="text/css">
			.fade-enter-active { transition: opacity .5s }
			.fade-enter, .fade-leave-to { opacity: 0 }
		</style><?php

	endif;

	?></form>

</div></div>