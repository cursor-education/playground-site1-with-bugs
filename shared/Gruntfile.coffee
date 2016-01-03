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

        'watch':
            css:
                files: [
                    'Gruntfile.coffee'
                    'web/assets/styles/**/*.scss'
                ]
                tasks: ['build-css']
    )

    grunt.registerTask 'build-all', [
        'build-css'
    ]

    grunt.registerTask 'build-css', [
        'clean:before-css'
        'sass'
    ]

    if config.debug.enabled
        grunt.registerTask 'watch-all', ['build-all', 'watch']

    grunt.registerTask 'default', 'build-all'