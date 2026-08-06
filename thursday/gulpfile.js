const gulp = require("gulp");
const browserSync = require("browser-sync").create();
function serve(done) {
browserSync.init({
proxy: "http://localhost/school-erp/public",
notify: false,
open: false,
port: 3000
});
gulp.watch("**/*.php").on("change", browserSync.reload);
gulp.watch("**/*.css").on("change", browserSync.reload);
gulp.watch("**/*.js").on("change", browserSync.reload);
done();
}
exports.default = serve;