---
name: cooked-build-and-test
description: >-
  How to run Cooked's checks (PHPUnit, PHPStan, PHPCS, Playwright, BackstopJS),
  the local WordPress environments (wp-env, DDEV) and the asset build. Use when
  testing a change, adding tests, adding CSS/JS files, or setting up the dev
  environment.
---
# Cooked Build & Test

Scripts run with [Bun](https://bun.sh) (`bun run <script>`), PHP tools from
`vendor/bin/`. `readme.md` (Development section) has the full command list.

## Checks to run after a PHP change

| Check | Command | Needs WordPress running? |
|-------|---------|--------------------------|
| PHPUnit 11 | `bun run test:php` | No. `tests/phpunit/bootstrap.php` stubs WordPress functions. |
| PHPStan (level 8, PHP 8.2) | `bun run phpstan` | No |
| PHPCS (WPCS + PHPCompatibility 8.2+) | `bun run lint` (`bun run lint-fix` to auto-fix) | No |

- PHPStan has many existing findings. Compare against a run on the unchanged
  code and only fix what the change introduced.
- PHPCS may insert tabs when auto-fixing. The project uses 4 spaces (`.editorconfig`); convert them back.
- New PHPUnit tests: `tests/phpunit/*Test.php`. Data providers are `public static`
  and attached with `#[DataProvider( 'name' )]`. Stub any missing WordPress
  function in `tests/phpunit/bootstrap.php`.

## Browser tests (need wp-env or DDEV running)

| Suite | Command |
|-------|---------|
| Playwright E2E | `bun run test:e2e` (first time: `bun run test:e2e:install`) |
| Playwright accessibility | `bun run test:e2e:a11y` |
| BackstopJS visual | `bun run test:visual`; approve intended changes with `bun run test:visual:approve` |

Playwright specs live in `tests/playwright/tests/` (`admin`, `browse`, `home`,
`security`, `accessibility`). CSV fixtures are in `tests/test_data/`.

## Local WordPress

- **wp-env:** `bun run start:wp-env`. PHP 8.2, site on port 8888, Cooked mounted from the repo root.
- **DDEV:** `bun run init:ddev` once, then `bun run start:ddev`. Prints the URL and credentials.

## Assets

- Do not run the build after editing CSS/JS (rule `no-build-process`). Edit the
  source file only; the `.min` files are regenerated at release time.
- Load assets with the `COOKED_DEV` switch:
  ```php
  $min = COOKED_DEV ? '' : '.min';
  wp_enqueue_script( 'cooked-my-js', COOKED_URL . 'assets/js/my-file' . $min . '.js', [ 'jquery' ], COOKED_VERSION, true );
  ```
- Styles are SCSS: `assets/scss/` (front end) and `assets/admin/scss/` (admin), each
  with `abstracts/`, `base/`, `layout/` and `components/` partials and entry files that
  only `@use` them. Gulp compiles each entry to an expanded `.css` and a `.min.css`;
  never edit the compiled CSS. New styles go in a partial added to the entry's `@use`
  list. One declaration per line, 4-space indent, and nest media queries inside the
  selector (`.x { @media (...) { ... } }`).
- Colours come from the design tokens in `assets/scss/abstracts/_variables.scss`
  (`--cooked-color-*` custom properties); use them instead of new hex values.
- A new first-party JS file needs a minify entry in `gulpfile.js` (`src()` list,
  `watch()` line, and the `build` series).
- Third-party libraries go in `assets/vendor/<lib>/`, unmodified, with their
  licence and a README naming the version and source. The enqueue version is the
  library's version, not `COOKED_VERSION`.

## Release zip

`bun run bundle` builds `build/cooked.zip` from an explicit folder list in
`package.json`. A new top-level folder that the plugin needs at runtime must be
added to that list, and anything dev-only must be in `.distignore` (used by the
WordPress.org deploy).
