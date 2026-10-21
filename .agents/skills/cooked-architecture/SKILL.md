---
name: cooked-architecture
description: >-
  Explains the Cooked plugin architecture: bootstrap sequence, core/pro
  relationship, class structure, constants, and singleton pattern. Use when
  creating new classes, understanding plugin load order, or modifying bootstrap
  behavior.
---
# Cooked Plugin Architecture

## Bootstrap Sequence

### Cooked Core (`cooked.php`)
1. `require_once vendor/autoload.php` (Composer: the bundled libraries and the `Cooked\` PSR-4 classes in `src/`), then `src/aliases.php` (keeps the old `Cooked_*` names working)
2. Define `COOKED_VERSION`, `COOKED_DEV`
3. `Cooked_Plugin::instance()` (singleton):
   - `setup_constants()` — defines `COOKED_DIR`, `COOKED_URL`, `COOKED_FOLDER`, etc.
   - `includes()` — manual `require_once` for all core classes
   - Instantiates all core objects (roles, menus, enqueues, settings, post_types, recipe_meta, shortcodes, ajax, widgets, gutenberg, elementor, etc.)
   - `module_setup()` — scans `COOKED_DIR/modules/`, parent theme `cooked-modules/`, child theme `cooked-modules/`
   - Conditionally loads Yoast/RankMath support on `init`
   - Fires `do_action('cooked_loaded')`
4. `Cooked()` global helper returns the singleton

### Cooked Pro (`cooked-pro.php`)
1. Define `COOKED_PRO_VERSION`, `COOKED_PRO_FILE`
2. Load EDD license handler (`license/license.php`)
3. `Cooked_Pro_Plugin::instance()` (singleton):
   - `setup_constants()` — `COOKED_PRO_DIR`, `COOKED_PRO_URL`, `COOKED_PRO_FOLDER`
   - `includes()` — loads enqueues, session-functions, core-tweaks
   - Hooks `init()` to `plugins_loaded`
4. On `plugins_loaded` → `init()`:
   - Checks `Cooked_Plugin` exists; shows admin notice + deactivates if not
   - `module_setup()` — scans `COOKED_PRO_DIR/modules/`
   - Instantiates enqueues and session objects
5. `Cooked_Pro()` global helper returns the singleton

### Load Order
Cooked core loads first → fires `cooked_loaded` → Pro hooks into `plugins_loaded` → loads Pro modules. Pro modules hook into `cooked_*` actions/filters defined by core.

## Constants

| Constant | Source | Purpose |
|----------|--------|---------|
| `COOKED_VERSION` | cooked.php | Current core version |
| `COOKED_DEV` | cooked.php | `true` = load unminified assets |
| `COOKED_DIR` | core | Plugin dir path (trailing slash) |
| `COOKED_URL` | core | Plugin dir URL |
| `COOKED_FOLDER` | core | Plugin basename folder |
| `COOKED_PLUGIN_FILE` | core | `__FILE__` of cooked.php |
| `COOKED_AJAX_URL` | core | `admin_url('admin-ajax.php')` |
| `COOKED_PRO_VERSION` | cooked-pro.php | Current Pro version |
| `COOKED_PRO_DIR` | Pro | Pro plugin dir path |
| `COOKED_PRO_URL` | Pro | Pro plugin dir URL |
| `COOKED_PRO_FILE` | Pro | `__FILE__` of cooked-pro.php |

## Class Naming & File Naming

Cooked 2.0 is moving its classes from `includes/` to namespaced classes in `src/`,
one class at a time. `src/aliases.php` lists every class moved so far. Each class
keeps its name without the `Cooked_` prefix (`Cooked_Recipes` → `Cooked\Recipe\Recipes`).

Groups in `src/`: `Recipe` (recipes, meta, post types, taxonomies, related,
allergens, SEO schema), `Measurement`, `User` (users, roles), `Admin` (menus,
admin enqueues, settings, migration, updates), `Frontend` (enqueues, shortcodes,
widgets), `Import` (all importers), `Integrations` (Gutenberg, Elementor, Yoast,
Rank Math, Pixwell, multilingual), `System` (AJAX, CLI, functions). `Cooked_Plugin`
moves last, to `Cooked\Plugin`.

| Pattern | Example class | Example file |
|---------|--------------|-------------|
| Core class (new, namespaced) | `Cooked\Measurement\UnitConverter` | `src/Measurement/UnitConverter.php` |
| Core class (not moved yet) | `Cooked_Recipes` | `includes/class.cooked-recipes.php` |
| Pro class | `Cooked_Pro_Enqueues` | `includes/class.cooked-enqueues.php` (Pro repo) |
| Pro module | `Cooked_Interactions` | `modules/interactions/interactions.php` (Pro repo) |

- Every moved class keeps its old name as an alias in `src/aliases.php`. The old
  names are public API (Cooked Pro and themes use them): never remove an alias.
- Hooks, option keys, meta keys and `COOKED_*` constants never change.
- Pro modules live in the Cooked Pro repo; see its `cooked-modules` skill.

## Key Singleton Access

```php
// Get plugin instances
$cooked = Cooked();                    // Cooked_Plugin
$pro    = Cooked_Pro();                // Cooked_Pro_Plugin

// Access core objects
$cooked->recipes                       // Cooked_Recipes
$cooked->recipe_meta                   // Cooked_Recipe_Meta
$cooked->shortcodes                    // Cooked_Shortcodes
$cooked->ajax                          // Cooked_Ajax
$cooked->admin_settings                // Cooked_Settings

// Global settings array
global $_cooked_settings;
$_cooked_settings = Cooked_Settings::get();
```

## Adding a New Core Class

1. Create `src/{Group}/{Name}.php` with `namespace Cooked\{Group};` and class
   `{Name}`, in one of the groups above.
2. Prefix global classes with `\` (`\WP_Query`, `\WP_Error`) or import them with `use`.
3. No `require_once`: Composer loads it. Run `composer dump-autoload` if a new
   folder is not picked up.
4. Keep file-scope code out of the file (no `new`, `add_action()` at the bottom):
   an autoloaded file only runs when its class is first used. Instantiate and hook
   it from `Cooked_Plugin::instance()`.
5. A brand-new class has no old name, so it gets no alias.

## Moving an Existing Class

One class per commit:

1. `git mv includes/class.cooked-x.php src/{Group}/{Name}.php`.
2. Add the namespace, rename the class, prefix global classes with `\`.
3. Move any file-scope code (a `new`, `add_action()` or global function at the
   bottom of the file) into `Cooked_Plugin` so it still runs at startup.
4. Add `'Cooked_Old_Name' => \Cooked\Group\Name::class` to the map in `src/aliases.php`.
5. Remove every `require_once` of the old path, including in
   `tests/phpunit/bootstrap.php` and other classes.
6. Run PHPUnit, PHPStan and PHPCS (see `cooked-build-and-test`).
