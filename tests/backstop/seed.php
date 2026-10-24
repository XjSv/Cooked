<?php
/**
 * Idempotent VRT fixture: published recipe slug vrt-golden-recipe.
 *
 * @package Cooked
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'cooked_vrt_seed_golden_recipe' ) ) {
	/**
	 * Ensure the golden visual-regression recipe exists and is frozen.
	 *
	 * @return int Recipe post ID.
	 */
	function cooked_vrt_seed_golden_recipe() {
		$slug  = 'vrt-golden-recipe';
		$title = 'VRT Golden Recipe';

		$existing = get_posts(
			[
				'name'           => $slug,
				'post_type'      => 'cp_recipe',
				'post_status'    => 'any',
				'posts_per_page' => 1,
			]
		);

		$author_id = 1;
		$e2e       = get_user_by( 'login', 'e2e_admin' );
		if ( $e2e ) {
			$author_id = (int) $e2e->ID;
		}

		$postarr = [
			'post_title'  => $title,
			'post_name'   => $slug,
			'post_status' => 'publish',
			'post_type'   => 'cp_recipe',
			'post_author' => $author_id,
			'post_content' => '',
		];

		if ( ! empty( $existing ) ) {
			$recipe_id        = (int) $existing[0]->ID;
			$postarr['ID']    = $recipe_id;
			wp_update_post( $postarr );
		} else {
			$recipe_id = (int) wp_insert_post( $postarr, true );
			if ( is_wp_error( $recipe_id ) || ! $recipe_id ) {
				if ( class_exists( 'WP_CLI' ) ) {
					WP_CLI::error( 'Could not create VRT golden recipe.' );
				}
				return 0;
			}
		}

		$content = '<p>[cooked-info left="author,taxonomies,difficulty,rating,favorite" right="print,fullscreen"]</p><p>[cooked-excerpt]</p><p>[cooked-image]</p><p>[cooked-info left="servings" right="prep_time,cook_time,total_time"]</p><p>[cooked-ingredients]</p><p>[cooked-directions]</p><p>[cooked-notes show_header=true]</p>';
		if ( class_exists( 'Cooked_Recipes' ) ) {
			$content = Cooked_Recipes::default_content();
			$content = str_replace(
				'left="author,taxonomies,difficulty"',
				'left="author,taxonomies,difficulty,rating,favorite"',
				$content
			);
		}

		$recipe_meta = [
			'cooked_version'    => defined( 'COOKED_VERSION' ) ? COOKED_VERSION : '1.0.0',
			'content'           => $content,
			'excerpt'           => 'VRTGOLDEN frozen visual regression fixture.',
			'notes'             => 'VRT notes stay the same between captures.',
			'prep_time'         => 10,
			'cook_time'         => 20,
			'total_time'        => 30,
			'difficulty_level'  => 1,
			'servings'          => 4,
			'seo_description'   => 'VRTGOLDEN frozen visual regression fixture.',
			'nutrition'         => [
				'servings' => 4,
				'calories' => 250,
				'fat'      => 8,
			],
			'ingredients'       => [
				[
					'amount'               => '2',
					'measurement'          => 'cups',
					'name'                 => 'flour',
					'section_heading_name' => '',
				],
				[
					'amount'               => '1',
					'measurement'          => 'cup',
					'name'                 => 'water',
					'section_heading_name' => '',
				],
			],
			'directions'        => [
				[
					'content'              => 'Mix the VRTGOLDEN batter until smooth.',
					'image'                => '',
					'section_heading_name' => '',
				],
				[
					'content'              => 'Cook until golden.',
					'image'                => '',
					'section_heading_name' => '',
				],
			],
		];

		update_post_meta( $recipe_id, '_recipe_settings', $recipe_meta );

		$term = term_exists( 'vrt', 'cp_recipe_category' );
		if ( ! $term ) {
			$term = wp_insert_term( 'VRT', 'cp_recipe_category', [ 'slug' => 'vrt' ] );
		}
		if ( ! is_wp_error( $term ) ) {
			$term_id = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
			wp_set_object_terms( $recipe_id, [ $term_id ], 'cp_recipe_category' );
		}

		$attachment_id = cooked_vrt_seed_hero_image( $recipe_id, $author_id );
		if ( $attachment_id ) {
			set_post_thumbnail( $recipe_id, $attachment_id );
		}

		return $recipe_id;
	}
}

if ( ! function_exists( 'cooked_vrt_seed_hero_image' ) ) {
	/**
	 * Sideload the frozen hero PNG if needed.
	 *
	 * @param int $recipe_id Recipe post ID.
	 * @param int $author_id Attachment author.
	 * @return int Attachment ID or 0.
	 */
	function cooked_vrt_seed_hero_image( $recipe_id, $author_id ) {
		$existing_id = (int) get_option( 'cooked_vrt_hero_attachment_id' );
		if ( $existing_id && get_post( $existing_id ) ) {
			return $existing_id;
		}

		$file = __DIR__ . '/fixtures/hero.png';
		if ( ! file_exists( $file ) ) {
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = wp_tempnam( 'vrt-golden-hero.png' );
		copy( $file, $tmp );

		$file_array = [
			'name'     => 'vrt-golden-hero.png',
			'tmp_name' => $tmp,
		];

		$attachment_id = media_handle_sideload( $file_array, $recipe_id, 'vrt-golden-hero' );
		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $tmp );
			return 0;
		}

		wp_update_post(
			[
				'ID'          => $attachment_id,
				'post_title'  => 'vrt-golden-hero',
				'post_author' => $author_id,
			]
		);

		update_option( 'cooked_vrt_hero_attachment_id', (int) $attachment_id, false );

		return (int) $attachment_id;
	}
}

if ( ! defined( 'COOKED_VRT_SEED_AS_LIBRARY' ) ) {
	$cooked_vrt_recipe_id = cooked_vrt_seed_golden_recipe();
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::success( 'VRT golden recipe ready (ID ' . intval( $cooked_vrt_recipe_id ) . ').' );
	}
}
