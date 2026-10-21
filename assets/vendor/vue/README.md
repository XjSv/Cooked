# Vue (vendored)

- Version: 2.7.16 (full build, including the template compiler)
- Source: https://github.com/vuejs/vue
- License: MIT (see `LICENSE`)
- Usage: none in Cooked itself since 2.0.0 (the settings and import screens use
  `assets/admin/js/cooked-conditions.js`). It is still registered as `cooked-vue` on
  every admin screen because Cooked Pro's Pending Recipes screen depends on that handle.

`vue.js` is the minified production build, not the development build, so
`SCRIPT_DEBUG` does not give Vue's development warnings.

Vue 2 reached end of life on 2023-12-31. Remove it once Cooked Pro no longer uses the
`cooked-vue` handle; do not build new features on it.
