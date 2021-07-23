const del = require("del");
const gulp = require("gulp");
const sass = require("gulp-sass");
const concat = require("gulp-concat");
const uglify = require("gulp-uglify");
const cssmin = require("gulp-cssmin");
const copyfiles = require("copyfiles");
const purify = require("gulp-purifycss");
const imagemin = require("gulp-imagemin");
const sourcemaps = require("gulp-sourcemaps");

// Get libs
const libs = require("./libs.js");

// Concatenate & minify libs css
function libsCss() {
  return gulp
    .src(libs.css)
    .pipe(concat("styles.css"))
    .pipe(cssmin())
    .pipe(gulp.dest(libs.dist.css));
}
exports.libsCss = libsCss;

// Concatenate & minify libs
function libsLibs() {
  return gulp
    .src(libs.libs)
    .pipe(concat("libraries.min.js"))
    .pipe(uglify())
    .pipe(gulp.dest(libs.dist.js));
}
exports.libsLibs = libsLibs;

// Concatenate & minify libs
function libsPurchase() {
  return gulp
    .src(libs.purchase)
    .pipe(sourcemaps.init())
    .pipe(concat("purchases.min.js"))
    .pipe(uglify())
    .pipe(sourcemaps.write("./maps"))
    .pipe(gulp.dest(libs.dist.js));
}
exports.libsPurchase = libsPurchase;

// Concatenate & minify js
function libsJs() {
  return gulp
    .src(libs.js)
    .pipe(sourcemaps.init())
    .pipe(concat("scripts.min.js"))
    .pipe(uglify())
    .pipe(sourcemaps.write("./maps"))
    .pipe(gulp.dest(libs.dist.js));
}
exports.libsJs = libsJs;

function libsPosjs() {
  return gulp
    .src(libs.posjs)
    .pipe(sourcemaps.init())
    .pipe(concat("pos.min.js"))
    //.pipe(uglify())
    .pipe(sourcemaps.write("./maps"))
    .pipe(gulp.dest(libs.dist.js));
}
exports.libsPosjs = libsPosjs;

// Move Libs Fonts
function libsFonts() {
  return gulp.src(libs.fonts).pipe(gulp.dest(libs.dist.fonts));
}
exports.libsFonts = libsFonts;

// Move Libs Images
function libsImg() {
  return gulp.src(libs.img).pipe(gulp.dest(libs.dist.img));
}
exports.libsImg = libsImg;

// Purify CSS
function purifyCSS() {
  return gulp
    .src([libs.dist.css + "styles.css"])
    .pipe(purify(["./themes/default/views/**/*.php"]))
    .pipe(cssmin())
    .pipe(gulp.dest("./themes/default/assets/dist/styles/"));
}
exports.purifyCSS = purifyCSS;

// Watch
function watch() {
  gulp.watch(libs.js, gulp.series("libsJs"));
  gulp.watch(libs.css, gulp.series("libsCss"));
  gulp.watch(libs.posjs, gulp.series("libsPosjs"));
  gulp.watch(libs.purchase, gulp.series("libsPurchase"));
}
exports.watch = watch;

// default
exports.libs = gulp.series(
  libsLibs,
  libsPurchase,
  libsCss,
  libsJs,
  libsPosjs,
  libsFonts,
  libsImg
);
exports.default = watch;
