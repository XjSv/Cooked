const { src, dest, watch, series } = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const cleanCSS = require('gulp-clean-css');
const uglify = require('gulp-uglify');
const rename = require('gulp-rename');

// CSS Task.
function css() {
  return src(['assets/css/icons.css', 'assets/css/print-only.css', 'assets/css/print.css', 'assets/css/style.css'])
    .pipe(cleanCSS())
    .pipe(rename({ suffix: '.min' }))
    .pipe(dest('assets/css'));
}

function cssAdmin() {
  return src(['assets/admin/css/essentials.css', 'assets/admin/css/style.css'])
    .pipe(cleanCSS())
    .pipe(rename({ suffix: '.min' }))
    .pipe(dest('assets/admin/css'));
}

// JavaScript Task.
function js() {
  return src('assets/js/cooked-functions.js')
    .pipe(uglify())
    .pipe(rename({ suffix: '.min' }))
    .pipe(dest('assets/js'));
}

function jsFotorama() {
  return src('assets/js/fotorama/fotorama.js')
    .pipe(uglify())
    .pipe(rename({ suffix: '.min' }))
    .pipe(dest('assets/js/fotorama'));
}

function jsAdmin() {
  return src(['assets/admin/js/cooked-functions.js', 'assets/admin/js/cooked-migration.js'])
    .pipe(uglify())
    .pipe(rename({ suffix: '.min' }))
    .pipe(dest('assets/admin/js'));
}

// Watch Task.
function watchFiles() {
  watch(['assets/css/icons.css', 'assets/css/print-only.css', 'assets/css/print.css', 'assets/css/style.css'], css);
  watch(['assets/admin/css/essentials.css', 'assets/admin/css/style.css'], cssAdmin);
  watch('assets/js/cooked-functions.js', js);
  watch('assets/js/fotorama/fotorama.js', jsFotorama);
  watch(['assets/admin/js/cooked-functions.js', 'assets/admin/js/cooked-migration.js'], jsAdmin);
}

// Task for building for production.
const build = series(css, cssAdmin, js, jsFotorama, jsAdmin);
exports.build = build;

// Export the default Gulp task.
exports.default = series(css, cssAdmin, js, jsFotorama, jsAdmin, watchFiles);
