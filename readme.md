# Cooked - A Modern and Customizable Recipe Plugin for WordPress

Cooked is the absolute best way to create & display recipes with WordPress. SEO optimized (rich snippets), galleries, cooking timers, printable recipes and much more.

## Description

Cooked is the absolute best way to create & display recipes with WordPress. SEO optimized (rich snippets), galleries, cooking timers, printable recipes and much more. Check out the full list below.

#### Quality design & usability

Using the drag & drop recipe builder, you can create your recipes quickly and without limitations. Add ingredients, directions—and then add a gallery, nutrition facts, cooking times and much more.

#### Google-friendly

Cooked automatically includes semantic structure and schema.org microdata into each and every recipe you publish. This allows Google to display your recipes across a variety of device sizes and platforms.

#### Many premium features already included

Most recipe plugins require that you purchase a PRO version for features like nutrition facts, galleries, powerful searching, timers, etc. The standard version of Cooked includes all of these. Here's what you get out of the box:

## Features

* Drag & drop ingredients and directions.
* SEO Optimized - Google Structured Data and Schema.org support.
* Beautiful grid-based masonry recipe lists.
* Prep & Cooking Times
* Photo Galleries
* Nutrition Facts
* Difficulty Levels
* Recipe Notes
* Powerful recipe search with a text search, categories & sorting options.
* Author template to list recipes by a single author.
* Cooking times with clickable, interactive timers.
* Very developer-friendly with loads of hooks & filters.
* Servings switcher to adjust ingredient amounts.
* Ingredient Substitutions - Add alternative ingredients for dietary restrictions or preferences.
* CSV Import - Bulk import recipes from CSV files with support for ingredients, directions, substitutions, and more.
* And more to come...

Of course, if you want even more, you can always check out the [PRO](https://cooked.pro) version of Cooked. It adds features like ratings & favorites, recipe submissions, and so much more.

## Installation/Update - WordPress.org (Recommended)

Cooked is available for automatic updates through the WordPress Admin Dashboard. You can install it from the [WordPress.org Plugin Directory](https://wordpress.org/plugins/cooked/).

1. Search for "Cooked" in the WordPress Admin Dashboard under `Plugins > Add New`.
2. Install and activate the Cooked plugin.

## Installation/Update (Manual)

1. Download the latest release from the [Cooked repository](https://github.com/XjSv/Cooked) on GitHub.
2. Navigate to your WordPress installation's `wp-content/plugins` directory and extract the downloaded ZIP file there.
3. Activate the Cooked plugin through the WordPress Admin Dashboard by navigating to `Appearance > Plugins`.

## Contributing

We welcome contributions from the community! If you'd like to contribute to Cooked, please follow these steps:

1. Fork the [Cooked repository](https://github.com/XjSv/Cooked) on GitHub.
2. Create a new branch for your feature or bug fix: `git checkout -b my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin my-new-feature`
5. Submit a Pull Request describing your changes.

### Development
This project uses [Bun](https://bun.sh) for package management and running scripts. Install Bun, then from the repo root run `bun install`.

#### Development environment
You can run the plugin locally with either wp-env or DDEV; pick one.

**wp-env** — Requires Node.js and Docker; `@wordpress/env` is in devDependencies. Config in `.wp-env.json` (PHP 7.4, port 8888, plugin mounted from repo root).
``` bash
bun run start:wp-env   # start
bun run stop:wp-env    # stop
bun run reset:wp-env   # wipe all db uploads
bun run destroy:wp-env # remove containers and data
bun run shell:wp-env   # run bash inside the container
```
Site at port 8888, tests at 8889.

**DDEV** — Requires [DDEV](https://ddev.com) installed.
First time: run `bun run init:ddev` (creates `wordpress/` and `.ddev/`, installs WP, activates Cooked; credentials and URL are printed).
Then use `bun run start:ddev` to start and `bun run launch:ddev` to open wp-admin.
Stop with `bun run stop:ddev`, full remove with `bun run destroy:ddev`.
``` bash
bun run init:ddev    # first-time setup only
bun run start:ddev   # start
bun run launch:ddev  # open wp-admin
bun run stop:ddev    # stop
bun run destroy:ddev # remove env and wordpress/
```

#### Generating language files
Run with wp-env or DDEV started. The script detects which environment you use.

Add or change French strings in `bin/fix_fr_translations_data.py`, then:

``` bash
bun run i18n              # make .pot, update all .po files, apply French, compile .mo
bun run i18n:make-pot     # generate cooked.pot only
bun run i18n:update-po    # update all .po files in languages/ from the .pot
bun run i18n:apply-fr     # copy French from the data file into cooked-fr_FR.po
bun run i18n:make-mo      # compile all .po files in languages/ to .mo
```

`bun run i18n` prints any French strings still empty after applying the data file (`MISSING:`). Add those msgids to the data file and run `i18n` again.
#### Compiling assets (JS/CSS)
``` bash
bun run build   # one-off build
bun run watch   # watch and rebuild on change
```
#### Creating distribution bundle
``` bash
bun run bundle
```
Runs build and i18n, then creates `build/cooked.zip` ready for distribution (excludes dev files).

#### Testing

**PHPCS** — Lint PHP files against WordPress Coding Standards and PHPCompatibility (PHP 7.4+):
``` bash
bun run lint       # check for violations
bun run lint-fix   # auto-fix violations
```

**PHPUnit** — Run the test suite (14 test classes covering CSV import, recipes, settings, SEO, and more):
``` bash
bun run test
```

Tests are in `tests/phpunit/` with CSV fixtures in `tests/test_data/`. Requires a running wp-env or DDEV environment.

## Documentation

Detailed documentation for Cooked can be found in the [wiki](https://github.com/XjSv/Cooked/wiki).
Cooked has a whole bunch of actions and filters to customize Cooked as much as you need to. Be sure to check out the [Developer Documentation](https://github.com/XjSv/Cooked/wiki).

## Support

If you encounter any issues or have questions about Cooked, please open an issue on the [GitHub repository](https://github.com/XjSv/Cooked/issues).

## License

Cooked is released under the [GPL-3.0 License](https://github.com/XjSv/Cooked/blob/main/LICENSE).

## Credits

Cooked was created by [Boxy Studio](https://www.boxystudio.com) and is now maintained by [Gora Tech](https://goratech.dev)
