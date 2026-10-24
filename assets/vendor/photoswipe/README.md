# PhotoSwipe (vendored)

- Version: 5.4.4
- Source: https://github.com/dimsemenov/PhotoSwipe
- Upstream files (copied verbatim): `dist/umd/photoswipe.umd.min.js`,
  `dist/umd/photoswipe-lightbox.umd.min.js`, `dist/photoswipe.css`
- License: MIT (see `LICENSE`)
- Usage: fullscreen image viewer for the recipe gallery (`cooked-photoswipe*`
  handles, front end). The slider around it is Cooked's own:
  `assets/js/cooked-gallery.js` and `assets/css/cooked-gallery.css`.

The UMD builds expose `window.PhotoSwipe` and `window.PhotoSwipeLightbox`, so no build
step is needed. To update, copy the same three files from the new release and bump the
version in `src/Frontend/Enqueues.php`.
