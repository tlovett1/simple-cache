var gulp = require( 'gulp' );

var jshint = require( 'gulp-jshint' );
var sass = require( 'gulp-sass' );
var concat = require( 'gulp-concat' );
var uglify = require( 'gulp-uglify' );
var rename = require( 'gulp-rename' );

gulp.task(
	'lint', function() {
		return gulp.src( 'assets/js/src/*.js' )
		.pipe( jshint() )
		.pipe( jshint.reporter( 'default' ) );
	}
);

gulp.task(
	'sass', function() {
		return gulp.src( 'assets/css/scss/*.scss' )
		.pipe( sass() )
		.pipe( gulp.dest( 'assets/css' ) );
	}
);

gulp.task(
	'scripts', function() {
		return gulp.src( 'assets/js/src/settings.js' )
		.pipe( rename( 'settings.min.js' ) )
		.pipe( uglify() )
		.pipe( gulp.dest( 'assets/js' ) );
	}
);

gulp.task(
	'watch', function() {
		gulp.watch( 'assets/js/src/*.js', ['lint', 'scripts'] );
		gulp.watch( 'assets/css/scss/*.scss', ['sass'] );
	}
);

gulp.task( 'default', ['lint', 'sass', 'scripts', 'watch'] );
