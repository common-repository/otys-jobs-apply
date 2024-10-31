module.exports = function (grunt) {
    grunt.initConfig({

        // define source files and their destinations
        uglify: {
            files: {
                expand: true,    // allow dynamic building
                cwd: 'src/js',
                src: ['**/*.js'],  // source files mask
                dest: 'assets/js',    // destination folder
                ext: '.min.js',   // Dest filepaths will have this extension.
                extDot: 'first'   // Extensions in filenames begin after the first dot
            }
        },
        sass: {
            options: {
                implementation: sass,
                sourceMap: true
            },
            dist: {
                files: {
                    'main.css': 'main.scss'
                }
            }
        },
        watch: {
            js: { files: 'src/js/**/*.js', tasks: ['uglify'] },
        }
    });

    // load plugins
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-uglify');

    // register at least this one task
    grunt.registerTask('default', ['uglify']);
};