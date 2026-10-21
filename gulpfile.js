const { src, dest, watch, series } = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const uglify = require('gulp-uglify');
const rename = require('gulp-rename');

// Compile SCSS entry points to minified CSS. Watch mode also writes source maps
// (pointing at the SCSS partials) for debugging; the production build does not.
let sourcemaps = false;

function compile(entries) {
  return src(entries, { sourcemaps })
    .pipe(sass({ style: 'compressed' }).on('error', sass.logError))
    .pipe(rename({ suffix: '.min' }))
    .pipe(dest('assets/css', { sourcemaps: sourcemaps ? '.' : false }));
}

// CSS Task: essentials (everywhere), front end, print view and admin.
function css() {
  return compile(['assets/scss/essentials.scss', 'assets/scss/style.scss', 'assets/scss/print.scss', 'assets/scss/admin/admin.scss']);
}

function enableSourcemaps(done) {
  sourcemaps = true;
  done();
}

// JavaScript Task.
function js() {
  return src('assets/js/cooked-functions.js')
    .pipe(uglify())
    .pipe(rename({ suffix: '.min' }))
    .pipe(dest('assets/js'));
}

function jsAdmin() {
  return src(['assets/js/cooked-admin-functions.js', 'assets/js/cooked-migration.js'])
    .pipe(uglify())
    .pipe(rename({ suffix: '.min' }))
    .pipe(dest('assets/js'));
}

// Watch Task.
function watchFiles() {
  watch('assets/scss/**/*.scss', css);
  watch('assets/js/cooked-functions.js', js);
  watch(['assets/js/cooked-admin-functions.js', 'assets/js/cooked-migration.js'], jsAdmin);
}

// Task for building for production.
const build = series(css, js, jsAdmin);
exports.build = build;

// Export the default Gulp task.
exports.default = series(enableSourcemaps, build, watchFiles);
