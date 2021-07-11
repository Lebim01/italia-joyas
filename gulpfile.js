const del = require("del");
const gulp = require("gulp");
const copyfiles = require("copyfiles");

// Get paths
const build_paths = require("./build_paths.js");

// Build Task
exports.build = gulp.series(copyFiles, delFiles, copyConfig);
async function copyFiles() {
  return await new Promise(function (resolve, reject) {
    copyfiles(build_paths.sim, false, (err) => {
      if (err) {
        console.error(err);
      }
      resolve(true);
    });
  });
}

async function delFiles() {
  await del(build_paths.sim_del, { force: true }).then((del_paths) => {
    console.log("File Deleted:\n", del_paths.join("\n"));
  });
}
async function copyConfig() {
  await gulp
    .src(build_paths.sim_config[0])
    .pipe(gulp.dest(build_paths.sim_config[1]));
}
