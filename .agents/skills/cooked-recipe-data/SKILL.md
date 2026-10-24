---
name: cooked-recipe-data
description: >-
  Documents the Cooked recipe data model: post type, meta structure, taxonomies,
  and how to query/retrieve recipe data. Use when working with recipe data,
  writing queries, modifying recipe fields, or building features that read/write
  recipe information.
---
# Cooked Recipe Data Model

## Post Type

- Slug: `cp_recipe`
- Registered in: `Cooked_Post_Types`
- Custom capabilities: `edit_cooked_recipes`, etc.
- Image sizes: `cooked-featured`, `cooked-thumbnail`, `cooked-large`

## Taxonomies

| Taxonomy slug | Source | Hierarchical | Purpose |
|---------------|--------|-------------|---------|
| `cp_recipe_category` | Core | Yes | Recipe categories |
| `cp_recipe_cuisine` | Pro (taxonomies module) | Yes | Cuisines |
| `cp_recipe_cooking_method` | Pro (taxonomies module) | Yes | Cooking methods |
| `cp_recipe_diet` | Pro (taxonomies module) | Yes | Diets |
| `cp_recipe_tags` | Pro (taxonomies module) | No | Tags |

Taxonomy registration is filterable via `cooked_taxonomies`.

## Recipe Meta Structure

All recipe data is stored in a single post meta key: `_recipe_settings` (serialized array).

```php
$recipe_settings = get_post_meta( $post_id, '_recipe_settings', true );
```

### Key fields in `_recipe_settings`:

| Key | Type | Description |
|-----|------|-------------|
| `cooked_version` | string | Version that last saved this recipe |
| `content` | string | Recipe description/content |
| `excerpt` | string | Short description |
| `notes` | string | Recipe notes |
| `prep_time` | int | Prep time in minutes |
| `cook_time` | int | Cook time in minutes |
| `total_time` | int | Total time in minutes |
| `difficulty_level` | string | Difficulty setting |
| `servings` | int | Number of servings |
| `ingredients` | array | Indexed array of ingredient groups/items |
| `directions` | array | Indexed array of direction steps |
| `nutrition` | array | Nutrition facts (`servings`, `calories`, `fat`, etc.) |
| `gallery` | array | Gallery image IDs |
| `seo` | array | SEO-specific data |

### Ingredient structure:
```php
$recipe_settings['ingredients'] = [
    'ingredient_key_1' => [
        'name' => 'flour',
        'amount' => '2',
        'measurement' => 'cups',
        'section_heading_name' => '',  // Non-empty = section divider
    ],
];
```

### Direction structure:
```php
$recipe_settings['directions'] = [
    'direction_key_1' => [
        'content' => 'Preheat oven to 350°F.',
        'image' => '',           // Attachment ID
        'section_heading_name' => '',
    ],
];
```

## Querying Recipes

### Using `Cooked_Recipes::get()`

This is the primary API for fetching recipes:

```php
// Single recipe by ID (returns settings array with 'id', 'title', 'author', etc.)
$recipe = Cooked_Recipes::get( $post_id, true );

// All recipes
$recipes = Cooked_Recipes::get();

// IDs only
$ids = Cooked_Recipes::get( false, false, true );

// Custom WP_Query args
$recipes = Cooked_Recipes::get([
    'post_type'      => 'cp_recipe',
    'posts_per_page' => 10,
    'tax_query'      => [[ 'taxonomy' => 'cp_recipe_category', 'field' => 'term_id', 'terms' => 5 ]],
]);
```

### Using `Cooked_Recipes::get_settings()`

Fetches and normalizes the `_recipe_settings` meta for a single recipe:

```php
$settings = Cooked_Recipes::get_settings( $post_id );
// Returns array with: title, author, id, nutrition defaults, and all _recipe_settings fields
// Also applies filter: 'cooked_single_recipe_settings'
```

## Saving Recipe Data

Recipe meta is saved via `Cooked_Recipe_Meta::save_recipe_meta_box()`:

1. Verifies nonce (`cooked_recipe_custom_box`)
2. Checks `current_user_can('edit_cooked_recipes', $post_id)`
3. Runs `Cooked_Recipe_Meta::meta_cleanup()` for sanitization
4. Calls `update_post_meta( $post_id, '_recipe_settings', $recipe_settings )`
5. Updates `post_excerpt` and `post_content` (SEO content with shortcodes)

### Sanitization rules in `meta_cleanup()`:
- `content`, `excerpt`, `notes`: `wp_kses_post()` for allowed roles, `wp_strip_all_tags()` for others
- Ingredient `name`, direction `content`, section headings: same role-based sanitization
- Everything else: `Cooked_Functions::sanitize_text_field()`

## Plugin Settings

Global settings stored in `cooked_settings` option:

```php
$_cooked_settings = Cooked_Settings::get();
$browse_page_id = $_cooked_settings['browse_page'];
```
