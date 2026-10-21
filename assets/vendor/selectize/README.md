# Selectize (vendored)

- Version: 0.15.2 (`selectize.min.js`); the stylesheet is from 0.12.4
- Source: https://github.com/selectize/selectize.js
- License: Apache 2.0 (see `LICENSE`)
- Usage: searchable select fields in the widget settings (`cooked-selectize`
  handles, admin). Depends on MicroPlugin (`assets/vendor/microplugin/`). The
  `drag_drop` plugin used by `assets/admin/js/cooked-widgets.js` is built into
  `selectize.min.js`.

The stylesheet (0.12.4) is older than the script (0.15.2). Cooked's own overrides are in
`assets/admin/css/cooked-selectize.css`, not here. To update, replace both files from
the same release and bump the versions in `src/Admin/Enqueues.php`.
