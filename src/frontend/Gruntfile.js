module.exports = function (grunt) {
  "use strict";

  require('load-grunt-tasks')(grunt);
  require('time-grunt')(grunt);

  grunt.config.merge({
    pkg: grunt.file.readJSON('package.json'),
    watch: {
      js: {
        files: ['Gruntfile.js', 'webapp/**/*.js', 'webapp/**/*.properties'],
        tasks: ['jshint'],
        options: {
          livereload: true
        }
      },

      livereload: {
        options: {
          livereload: true
        },
        files: [
          'webapp/**/*.html',
          'webapp/**/*.js',
          'webapp/**/*.css'
        ]
      }
    }
  });

  grunt.registerTask("default", [
    "clean",
    "lint",
    "build"
  ]);
};
