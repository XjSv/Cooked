# Fotorama (vendored, patched)

- Version: 4.6.4 (last upstream release, 2015; unmaintained)
- Source: https://github.com/artpolikarpov/fotorama
- License: MIT (see `LICENSE`)
- Usage: recipe galleries (`cooked-fotorama` handles, front end).

## Local changes

`fotorama.js` is patched; `fotorama.min.js` is built from it by the `jsFotorama`
task in `gulpfile.js` (uglify-js). Edit `fotorama.js` only.

- Captions are rendered as text (`textContent` via `safeDiv()`), the fix for
  GHSA-9vfv-c966-jwrv.
- The Vimeo thumbnail request uses `json` instead of `jsonp`, so it no longer runs a
  script from vimeo.com.
- YouTube Shorts URLs are recognised.
- The retina sprite is `fotorama-2x.png` (upstream: `fotorama@2x.png`), and
  `fotorama.min.css` refers to it by that name. `FotoramaAssetsTest` checks this.

The CSS loads `fotorama.png` and `fotorama-2x.png` relative to itself, so keep them
in this folder. Do not replace these files with upstream without re-applying the
patches above.
