# Tests

The layout follows WooCommerce's test organization: a separate suite per tool,
tests grouped by feature, shared utilities and fixtures, and reusable sample data
at the plugin root.

```text
tests/
├── phpunit/                 PHPUnit (no WordPress install required)
│   ├── Admin/               settings, updates
│   ├── Core/                plugin API, deprecated include placeholders
│   ├── Frontend/            enqueues, shortcodes, gallery, template filters
│   ├── Import/              CSV import
│   ├── Integrations/        multilingual integrations
│   ├── Measurement/         measurements and unit conversion
│   ├── Recipe/              recipes, metadata, related recipes, SEO
│   ├── System/              AJAX and global functions
│   ├── User/                users
│   ├── framework/           shared FilterTestCase
│   ├── bootstrap.php        WordPress function stubs
│   └── .cache/              PHPUnit result cache (ignored)
├── playwright/              Playwright against wp-env or DDEV
│   ├── playwright.config.ts
│   ├── global-setup.ts      creates the test users before the run
│   ├── reporters/           accessibility HTML/JSON reporter
│   ├── tests/
│   │   ├── admin/           settings, recipes, CSV import, bulk add
│   │   ├── frontend/
│   │   │   ├── browse/      browse, search, filters, sorting
│   │   │   └── home/        home page
│   │   ├── accessibility/   axe checks for public and single-recipe pages
│   │   └── security/        XSS and import/migrate authorization
│   ├── test-data/images/    image fixtures
│   └── utils/               auth, fixtures, forms, settings, WP-CLI, accessibility
└── backstop/                BackstopJS workspace (configuration pending)
```

CSV import tests use the plugin's `sample-data/` directory instead of keeping a
duplicate copy under `tests/`.

## PHPUnit

PHPUnit stubs the WordPress functions the plugin needs, so it runs without a
WordPress install:

```bash
bun run test:php
```

Static analysis and coding standards:

```bash
bun run analyze:php
bun run lint:php
```

## Playwright

Start the local WordPress environment first:

```bash
bun run start:wp-env
```

The suite auto-detects `http://localhost:8888` for wp-env, or the DDEV site when
`DDEV_HOSTNAME` / `IS_DDEV_PROJECT` is set. Copy `tests/playwright/.env.example`
to `tests/playwright/.env` to override the URL or admin fixture.

```bash
bun run test:e2e
bun run test:e2e:a11y
bun run test:e2e:report
```

The Playwright suite is intentionally serial because the admin tests change
settings and import data that later tests depend on. Keep tests that mutate
shared state in the same `test.describe.configure({ mode: 'serial' })` block.

## Visual regression

The BackstopJS file layout and scenario plan are documented in
`cooked-notes/backstop-test-plan.md`.

```bash
bun run test:visual
bun run test:visual:ref
bun run test:visual:approve
```

## Future work

- [ ] Write an onboarding procedure for new contributors covering the local
  environment, plugin structure, test layout, and PR/release workflow.
