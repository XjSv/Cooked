# BackstopJS test plan

Status: planning only. The npm scripts already point at
`tests/backstop/backstop.config.js`, but that file does not exist yet. The only
tracked file in `tests/backstop/` is `.gitignore`.

The visual suite should sit beside the PHPUnit and Playwright suites, use the
same seeded WordPress site, and cover the parts of Cooked that are most likely
to regress: the recipe display, recipe cards, print layout, and the admin
screens.

## Files to add

```text
tests/backstop/
├── backstop.config.js              BackstopJS entry point (required)
├── README.md                       setup, seed, reference, and approve steps
├── seed.php                        deterministic recipes, terms, pages, options
├── seed.sh                         wp-env/DDEV wrapper around seed.php + media
├── scenarios/
│   ├── index.js                    combines the scenario groups
│   ├── frontend.js                 home, browse, search, single recipe, print
│   ├── admin.js                    settings, import, editor, welcome, Pro
│   └── responsive.js               shared viewport/visibility cases
├── auth/
│   └── .gitkeep                    storage-state JSON is generated here
└── backstop_data/
    ├── engine_scripts/
    │   └── playwright/
    │       ├── onBefore.js         page setup and deterministic storage state
    │       ├── onReady.js          waits, animation removal, UI cleanup
    │       └── clickAndHoverHelper.js
    ├── bitmaps_reference/          committed reference screenshots
    ├── bitmaps_test/               generated, already ignored
    ├── html_report/                generated, already ignored
    └── ci_report/                  generated, already ignored
```

`backstop_data/bitmaps_reference/` is the only generated directory that should
normally be committed. Reference images are the actual visual baselines; the
test and report folders stay out of Git.

## `backstop.config.js`

The config should be JavaScript rather than JSON so it can read the base URL
from the environment and import the scenario modules.

Required values:

- `id`: `cooked`
- `engine`: `playwright`
- `engineOptions.browser`: `chromium`
- `onBeforeScript`: `playwright/onBefore.js`
- `onReadyScript`: `playwright/onReady.js`
- `scenarioDefaults`: shared waits and selectors
- `report`: `["browser", "ci"]`
- `paths.bitmaps_reference`: `tests/backstop/backstop_data/bitmaps_reference`
- `paths.bitmaps_test`: `tests/backstop/backstop_data/bitmaps_test`
- `paths.html_report`: `tests/backstop/backstop_data/html_report`
- `paths.ci_report`: `tests/backstop/backstop_data/ci_report`
- `paths.engine_scripts`: `tests/backstop/backstop_data/engine_scripts`

Base URL rules:

```js
const baseUrl =
  process.env.COOKED_BASE_URL ||
  (process.env.DDEV_HOSTNAME || process.env.IS_DDEV_PROJECT
    ? 'https://cooked.ddev.site'
    : 'http://localhost:8888');
```

The suite must run from the repository root. BackstopJS resolves relative paths
from the process working directory, so every `paths` entry should start with
`tests/backstop/`.

Suggested viewports:

| Label | Size | Why |
|---|---:|---|
| `mobile-320` | 320 × 800 | smallest supported layout; tests wrapping and overflow |
| `mobile-390` | 390 × 844 | common phone width |
| `tablet-768` | 768 × 1024 | grid and filters switch layouts |
| `desktop-1440` | 1440 × 1000 | full recipe grid and admin frame |

The first pass can use 320, 768, and 1440; add 390 once the baselines are stable.

## Scenarios to cover

### Public frontend

| Scenario | URL / state | Selector or full page | Notes |
|---|---|---|---|
| Home recipe list | seeded home page with `[cooked-recipe-list]` | `.cooked-recipe-list` or `document` | proves shortcode spacing and grid columns |
| Browse recipes | `/browse-recipes/` | `.cooked-recipe-search`, `.cooked-recipe-grid` | full page and search UI separately |
| Browse with filters | browse page, filter panel open | `.cooked-recipe-search` | captures taxonomy controls and sorting |
| Browse search results | `/browse-recipes/search/cake/` | `.cooked-recipe-grid` | tests result count, card layout, no-results state |
| Browse category | `/browse-recipes/recipe-category/desserts/` | `.cooked-recipe-grid` | term heading and cards |
| Single recipe | seeded recipe permalink | `.cooked-recipe` | full recipe card in the theme |
| Single recipe, ingredient checked | same page, click first ingredient checkbox | `.cooked-recipe-ingredients` | checked state must survive |
| Single recipe, servings changed | same page, change servings select | `.cooked-recipe` | confirms live measurement scaling |
| Recipe gallery | seeded recipe with gallery | `.cooked-recipe-gallery` or gallery selector | lightbox/gallery layout |
| Print view | `...?print=1` | `#cooked-print-options`, `#printTitle` | use a print-media scenario if the styles differ |
| Allergen icons | recipe with allergens, setting on | `.cooked-allergens-info`, browse cards | both single and list variants |
| Dark mode, forced | `dark_mode = enabled` | same public selectors | checks the generated dark CSS |
| Dark mode, auto | `dark_mode = auto` plus page `[data-theme="dark"]` | same public selectors | checks the scoped selectors |
| No recipes | empty browse/search result | `.cooked-none-found` | empty-state spacing |

The public suite should hide or remove only genuinely dynamic elements:
admin-bar test notices, update nags, and the WordPress footer. Do not hide the
theme header/footer. Those are part of what Cooked has to fit into.

### Admin

| Scenario | URL / state | Selector | Notes |
|---|---|---|---|
| Settings frame | `/wp-admin/admin.php?page=cooked_settings` | `.cooked-admin-frame` | desktop and mobile header/rail |
| Settings: Recipe Settings | `...page=cooked_settings#recipe_settings` | `.cooked-admin-frame` | fields, switches, save button |
| Settings: Design | `...page=cooked_settings#design` | `.cooked-admin-frame` | dark-mode select and breakpoints |
| Settings: Advanced | `...page=cooked_settings#advanced` | `.cooked-admin-frame` | dense form state |
| Import screen | `/wp-admin/admin.php?page=cooked_import` | `#cooked_recipe_settings` | tabs and upload state |
| Recipe editor | `/wp-admin/post.php?post=<seed>&action=edit` | `#cooked_recipe_settings` or `.postbox` | ingredients, directions, nutrition, allergens |
| Recipe editor: tabs | editor, Directions or Nutrition tab | `#cooked-recipe-tabs` | tab styling and scroll behaviour |
| Welcome | `/wp-admin/admin.php?page=cooked_welcome` | `#cooked-welcome-panel` | banner and columns |
| Upgrade to Pro | `/wp-admin/admin.php?page=cooked_pro` | `#cooked-welcome-panel` | feature columns and coupon block |

Admin screenshots need a fixed admin user, a fixed site title, and notices
removed. BackstopJS can reuse a Playwright storage state through
`engineOptions.storageState` rather than logging in on every scenario.

### Responsive checks

Use the same canonical scenarios at the selected viewports, or add dedicated
entry points for the breakpoints Cooked actually changes:

- `responsive_breakpoint_1` and `responsive_breakpoint_2` from Settings.
- Browse search/filter controls wrapping.
- Recipe card grid columns.
- Single-recipe ingredients/directions columns.
- Settings frame header, page tabs, and section rail collapsing.
- Admin recipe editor at 782 px and 600 px widths.

## `onBefore.js`

Responsibilities:

- Load the generated storage state if the config does not use
  `engineOptions.storageState`.
- Set a fixed timezone, locale, and viewport (the config owns the viewport).
- Seed `localStorage` for any theme-level dark-mode toggle used in the auto
  scenario.
- Fail fast if the login/storage state has expired.

## `onReady.js`

Responsibilities:

- Wait for web fonts and images.
- Disable animations, transitions, and smooth scrolling so screenshots are
  deterministic.
- Close or hide update notices and other WordPress admin nags.
- Remove the admin footer and, for public pages, any test/debug bar.
- Click or focus stateful controls only when the scenario asks for it.
- Optionally emulate print media for the print scenario.

The default `clickAndHoverHelper.js` can be copied from BackstopJS; custom
behaviour belongs in `onReady.js` so the helper stays simple.

## Seed and auth helpers

`seed.php` should create a repeatable fixture set through WordPress APIs:

- a fixed admin user and author;
- one browse page, one home page, and a small number of recipes;
- recipe categories, cuisines, cooking methods, and tags;
- one recipe with a featured image, gallery, allergens, nutrition, notes, and
  substitutions;
- Cooked settings for the default browse page, sorting, allergens, dark mode,
  and print options;
- uploaded images with stable filenames and alt text.

`seed.sh` should detect wp-env or DDEV and call the same PHP file:

```bash
# wp-env
wp-env run cli wp eval-file wp-content/plugins/cooked/tests/backstop/seed.php

# DDEV
ddev wp eval-file /var/www/html/wp-content/plugins/cooked/tests/backstop/seed.php
```

Auth should be generated with a small Playwright helper or a one-off script
that logs in once and writes `tests/backstop/auth/admin.json`. Add
`tests/backstop/auth/*.json` to `.gitignore`; do not commit sessions, nonces, or
cookies.

## Workflow

```bash
bun run start:wp-env
bash tests/backstop/seed.sh
bun run test:visual:ref       # first run / intentional design change
bun run test:visual           # compare against references
bun run test:visual:approve   # accept new references after review
```

`test:visual:ref` should be run only when the visual change is intentional. A
normal pull request runs `test:visual` against the committed references.

## CI

Add a separate workflow or job rather than folding this into the Playwright
job. It needs the same WordPress environment, the seed step, browser install,
and access to the reference images. Upload `html_report` and the failing
`bitmaps_test` images as artifacts. Do not upload reference images as build
artifacts.

## Minimum files for a first useful suite

If the full plan is too much for one change, the first implementation only
needs:

1. `backstop.config.js`
2. `scenarios/frontend.js`
3. `scenarios/admin.js`
4. `backstop_data/engine_scripts/playwright/onReady.js`
5. `seed.sh` / `seed.php`
6. `README.md`

That is enough to cover browse, single recipe, print, settings, and the recipe
editor at desktop and mobile widths.
