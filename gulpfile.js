const { src, dest, watch, series } = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const uglify = require('gulp-uglify');
const rename = require('gulp-rename');
const concat = require('gulp-concat');

// SCSS entry points: essentials (everywhere), front end, print view and admin.
const styleEntries = ['assets/scss/essentials.scss', 'assets/scss/style.scss', 'assets/scss/colors.scss', 'assets/scss/print.scss', 'assets/scss/admin/admin.scss'];
const frontendScripts = ['assets/js/cooked-functions.js'];
const adminScripts = ['assets/js/cooked-admin-functions.js', 'assets/js/cooked-migration.js'];

// Compile SCSS entry points to minified CSS.
function buildStyles() {
  return src(styleEntries)
    .pipe(sass({ style: 'compressed' }).on('error', sass.logError))
    .pipe(rename({ suffix: '.min' }))
    .pipe(dest('assets/css'));
}

function buildFrontendScripts() {
  return src(frontendScripts)
    .pipe(uglify())
    .pipe(rename({ suffix: '.min' }))
    .pipe(dest('assets/js'));
}

// Admin scripts ship as a single bundle; COOKED_DEV loads the sources separately.
function buildAdminScripts() {
  return src(adminScripts)
    .pipe(concat('cooked-admin-functions.min.js'))
    .pipe(uglify())
    .pipe(dest('assets/js'));
}

function watchSources() {
  watch('assets/scss/**/*.scss', buildStyles);
  watch(frontendScripts, buildFrontendScripts);
  watch(adminScripts, buildAdminScripts);
}

// Task for building for production.
const build = series(buildStyles, buildFrontendScripts, buildAdminScripts);
exports.build = build;

// Export the default Gulp task.
exports.default = series(build, watchSources);
