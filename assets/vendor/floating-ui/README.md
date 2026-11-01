# Floating UI (vendored)

- Version: 1.8.0 (`@floating-ui/core` and `@floating-ui/dom`)
- Source: https://github.com/floating-ui/floating-ui
- Upstream files (copied verbatim):
  - `@floating-ui/core/dist/floating-ui.core.umd.min.js`
  - `@floating-ui/dom/dist/floating-ui.dom.umd.min.js`
- License: MIT (see `LICENSE`)
- Usage: UMD globals `window.FloatingUICore` / `window.FloatingUIDOM`. The DOM
  build depends on core, so core must load first. Cooked's tooltip logic lives
  in `assets/admin/js/cooked-functions.js`: add `data-tooltip="..."` (and
  optionally `data-positions="top,bottom"`) to any element.

Do not edit the `*.umd.min.js` files directly. To update, copy both new UMD builds
from upstream (keep core and dom on matching versions) and bump the version in
`includes/class.cooked-admin-enqueues.php` (`cooked-floating-ui-*` handles).
