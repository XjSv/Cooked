<div id="cooked-welcome-screen">
	<div class="wrap about-wrap">
		<div id="cooked-welcome-panel" class="cooked-welcome-panel">

			<img src="<?php echo apply_filters( 'cooked_welcome_banner_img', COOKED_URL . '/assets/admin/images/welcome-banner.png' ); ?>" class="cooked-welcome-banner">

			<div class="cooked-welcome-panel-intro">
				<h1><?php
					/* translators: referring to "Cooked" */
					echo sprintf( __( 'Thanks for using %s!', 'cooked'), 'Cooked' );
				?></h1>
                <?php
					/* translators: referring to "Cooked", "Settings" and "Documentation" */
					echo wpautop(
						sprintf(
							__('If this is your first time using %1$s, head over to the %2$s page for some initial configuration. You can also check out the %3$s if you get stuck or contact me on %4$s. If you just recently updated, you can find out what\'s new below.', 'cooked'),
							'Cooked',
							'<a href="' . untrailingslashit( admin_url() ) . '/admin.php?page=cooked_settings">' . __( 'Settings', 'cooked' ) . '</a>',
							'<a href="https://docs.cooked.pro/" target="_blank">' . __( 'documentation', 'cooked' ) . '</a>' ,
							'<a href="https://discord.gg/qdGwXaXxc6" target="_blank">' . __( 'Discord', 'cooked' ) . '</a>'
						)
					);
				?>
            </div>

			<div class="cooked-welcome-panel-content">
				<div class="cooked-welcome-panel-column-container">
					<div class="cooked-welcome-panel-column">
						<h4><?php _e( 'Quick Links', 'cooked' ); ?></h4>
						<ul>
							<li><i class="cooked-icon cooked-icon-link-lt cooked-icon-fw"></i>&nbsp;&nbsp;<a href="https://docs.cooked.pro/" target="_blank"><?php _e( 'Documentation','cooked' ); ?></a></li>
							<li><i class="cooked-icon cooked-icon-comment cooked-icon-fw"></i>&nbsp;&nbsp;<a href="https://discord.gg/qdGwXaXxc6" target="_blank"><?php _e( 'Discord','cooked' ); ?></a></li>
							<li><i class="cooked-icon cooked-icon-gear cooked-icon-fw"></i>&nbsp;&nbsp;<a href="<?php echo admin_url('admin.php?page=cooked_settings'); ?>"><?php _e('Cooked Settings','cooked'); ?></a></li>
							<li><i class="cooked-icon cooked-icon-pencil cooked-icon-fw"></i>&nbsp;&nbsp;<a href="<?php echo admin_url('post-new.php?post_type=cp_recipe'); ?>"><?php _e('Create a Recipe','cooked'); ?></a></li>
							<?php if ( !class_exists( 'Cooked_Pro_Plugin' ) ): ?><li class="cooked-pro"><i class="cooked-icon cooked-icon-star-lg cooked-icon-fw"></i>&nbsp;&nbsp;<a href="<?php echo admin_url('admin.php?page=cooked_pro'); ?>"><?php esc_html_e('Upgrade to Pro','cooked'); ?></a></li><?php endif; ?>
						</ul>
					</div>
					<div class="cooked-welcome-panel-column cooked-welcome-panel-last">
						<?php do_action( 'cooked_welcome_before_changelog' ); ?>
						<?php echo Cooked_Functions::parse_readme_changelog(); ?>
						<?php do_action( 'cooked_welcome_after_changelog' ); ?>
					</div>
				</div>
			</div>

		</div>
	</div>
</div>
