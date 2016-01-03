module.exports = (grunt) ->
    require('load-grunt-config')(grunt)

    config =
        debug:
            enabled: true

    grunt.initConfig(
        'pkg': grunt.file.readJSON 'package.json'

        'constants':
            banner: '/* <%= pkg.name %> (<%= grunt.template.today("yyyy-mm-dd HH:mm:ss") %>) */'

        'clean':
            options:
                force: true
            'before-css': [
                'web/build/*.css'
            ]
            'before-js': [
                'web/build/*.js'
            ]
            'after': [
                'web/build/**/*'
            ]

        'sass':
            options:
                style: if config.debug.enabled then 'expanded' else 'compressed'
            css:
                files: [
                    expand: true
                    cwd:    'web/assets/styles'
                    src:    ['*.scss']
                    dest:   'web/build/'
                    ext:    '.css'
                ]

        'coffee':
            options:
                bare: true
                join: false
            all:
                src: [
                    'web/assets/js/all.coffee'
                ]
                dest: 'web/build/all.js'

        'watch':
            css:
                files: [
                    'Gruntfile.coffee'
                    'web/assets/styles/**/*.scss'
                ]
                tasks: ['build-css']
            js:
                files: [
                    'Gruntfile.coffee'
                    'web/assets/js/**/*.coffee'
                ]
                tasks: ['build-js']
    )

    grunt.registerTask 'build-all', [
        'build-css'
        'build-js'
    ]

    grunt.registerTask 'build-css', [
        'clean:before-css'
        'sass'
    ]

    grunt.registerTask 'build-js', [
        'clean:before-js'
        'coffee'
    ]

    if config.debug.enabled
        grunt.registerTask 'watch-all', ['build-all', 'watch']

    grunt.registerTask 'default', 'build-all'