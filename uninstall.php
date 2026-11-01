<?php
/**
 * Uninstall Cooked
 *
 * @package Cooked
 * @since 1.14.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

flush_rewrite_rules();

$cooked_settings = get_option( 'cooked_settings' );
$advanced        = ( is_array( $cooked_settings ) && isset( $cooked_settings['advanced'] ) && is_array( $cooked_settings['advanced'] ) ) ? $cooked_settings['advanced'] : [];

if ( ! in_array( 'delete_cooked_data_on_uninstall', $advanced, true ) ) {
	return;
}

// Delete all plugin options.
$options = [
	'cooked_settings',
	'cooked_settings_saved',
	'cooked_version',
	'cooked_settings_version',
	'cooked_pro_settings_version',
	'cooked_delicious_recipes_imported',
	'cooked_wp_recipe_maker_imported',
	'cooked_related_version',
	'cooked_related_calculation_last',
	'cooked_pro_license_status',
	'cooked_pro_license_data',
	'cooked_pro_version',
	'cooked_use_php_sessions',
];

foreach ( $options as $option ) {
	delete_option( $option );
}

// Delete transients.
delete_transient( 'cooked_classic_recipes' );
delete_transient( 'cooked_widget_recipes_list' );

// Clean up legacy related-recipes transients.
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cooked_%' OR option_name LIKE '_transient_timeout_cooked_%'" );

// Remove custom role and capabilities.
if ( class_exists( 'WP_Roles' ) ) {
	$wp_roles = new WP_Roles();

	$roles = [ 'subscriber', 'contributor', 'author', 'editor', 'cooked_recipe_editor', 'administrator' ];
	$caps  = [
		'edit_cooked_recipes',
		'edit_cooked_settings',
		'approve_cooked_recipes',
		'delete_cooked_recipes',
		'edit_cooked_default_template',
	];

	foreach ( $roles as $role ) {
		foreach ( $caps as $cap ) {
			$wp_roles->remove_cap( $role, $cap );
		}
	}

	remove_role( 'cooked_recipe_editor' );
}

$recipe_ids = get_posts(
	[
		'post_type'      => 'cp_recipe',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'nopaging'       => true,
	]
);
if ( is_array( $recipe_ids ) ) {
	foreach ( $recipe_ids as $recipe_id ) {
		wp_delete_post( (int) $recipe_id, true );
	}
}

// Taxonomies are not registered while the plugin is uninstalled.
$taxonomies    = [ 'cp_recipe_category', 'cp_recipe_cuisine', 'cp_recipe_cooking_method', 'cp_recipe_diet', 'cp_recipe_tags' ];
$placeholders  = implode( ', ', array_fill( 0, count( $taxonomies ), '%s' ) );
$taxonomy_sql  = [
	"DELETE tr FROM {$wpdb->term_relationships} tr INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy IN ($placeholders)",
	"DELETE tm FROM {$wpdb->termmeta} tm INNER JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id WHERE tt.taxonomy IN ($placeholders)",
	"DELETE t FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ($placeholders)",
	"DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ($placeholders)",
];
foreach ( $taxonomy_sql as $sql ) {
	$wpdb->query( $wpdb->prepare( $sql, ...$taxonomies ) );
}

delete_metadata( 'user', 0, 'cooked_user_meta', '', true );

$upload_dir = wp_upload_dir();
$dir        = ( is_array( $upload_dir ) && ! empty( $upload_dir['basedir'] ) ) ? $upload_dir['basedir'] . '/cooked' : '';

if ( $dir && is_dir( $dir ) ) {
	$it    = new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS );
	$files = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $files as $file ) {
		if ( $file->isDir() ) {
			rmdir( $file->getRealPath() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		} else {
			wp_delete_file( $file->getRealPath() );
		}
	}
	rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
}
