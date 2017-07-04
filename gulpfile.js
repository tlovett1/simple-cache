var gulp = require( 'gulp' );

var jshint = require( 'gulp-jshint' );
var sass = require( 'gulp-sass' );
var concat = require( 'gulp-concat' );
var uglify = require( 'gulp-uglify' );
var rename = require( 'gulp-rename' );
var wpPot = require('gulp-wp-pot');

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

gulp.task(
	'makepot',
	function () {
		return gulp.src(['*.php', 'inc/**/*.php'])
		.pipe(wpPot({
			domain: 'simple-cache',
			package: 'Simple Cache'
		}))
		.pipe(gulp.dest('languages/simple-cache.pot'));
	}
);

gulp.task( 'default', ['lint', 'sass', 'scripts', 'watch'] );
