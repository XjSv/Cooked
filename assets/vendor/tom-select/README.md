# Tom Select (vendored)

- Version: 2.6.2
- Source: https://github.com/orchidjs/tom-select
- Upstream files: `dist/js/tom-select.complete.js`, `dist/js/tom-select.complete.min.js`,
  `dist/css/tom-select.default.css`, `dist/css/tom-select.default.min.css`, unchanged
  except that the trailing `sourceMappingURL` comments are removed (the `.map` files
  are not shipped).
- License: Apache 2.0 (see `LICENSE`)
- Usage: the recipe finder in the Cooked widgets (`cooked-tom-select` handles, admin;
  `assets/js/cooked-widgets.js`). The complete build includes the `drag_drop`
  plugin, which uses native drag and drop (no jQuery UI). Cooked's own overrides
  are in `assets/scss/admin/components/_tom-select.scss`.

To update, copy the same four files from the new release's `dist/` and bump the version
in `src/Admin/Enqueues.php` (`cooked-tom-select` handles).
