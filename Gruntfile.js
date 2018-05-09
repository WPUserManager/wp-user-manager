module.exports = function( grunt ) {

	// load all grunt tasks in package.json matching the `grunt-*` pattern
	require( 'load-grunt-tasks' )( grunt );

	// Project configuration
	grunt.initConfig( {
		pkg:    grunt.file.readJSON( 'package.json' ),
		concat: {
			options: {
				stripBanners: true,
				banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
					' * <%= pkg.homepage %>\n' +
					' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
					' * Licensed GPLv2+' +
					' */\n'
			},
			wp_user_manager: {
				src: [
					'assets/js/src/wp-user-manager.js'
				],
				dest: 'assets/js/wp-user-manager.js'
			}
		},
		jshint: {
			all: [
				'Gruntfile.js',
				'assets/js/src/**/*.js',
				'assets/js/test/**/*.js'
			],
			options: {
				curly:   true,
				eqeqeq:  true,
				immed:   true,
				latedef: true,
				newcap:  true,
				noarg:   true,
				sub:     true,
				undef:   true,
				boss:    true,
				eqnull:  true,
				globals: {
					exports: true,
					module:  false
				}
			}
		},
		uglify: {
			all: {
				files: {
					'assets/js/admin/tinymce/mce-plugin.min.js': ['assets/js/src/admin/tinymce/mce-plugin.js'],
					'assets/js/admin/admin-shortcodes.min.js': ['assets/js/src/admin/admin-shortcodes.js'],
					'assets/js/admin/admin-email-customizer-preview.min.js': ['assets/js/src/admin/admin-email-customizer-preview.js'],
					'assets/js/admin/admin-email-customizer-controls.min.js': ['assets/js/src/admin/admin-email-customizer-controls.js'],
					'assets/js/admin/admin-email-customizer.min.js': ['assets/js/src/admin/admin-email-customizer.js'],
					'assets/js/admin/admin-menus.min.js': ['assets/js/src/admin/admin-menus.js'],
					'assets/js/admin/admin-upgrades.min.js': ['assets/js/src/admin/admin-upgrades.js'],
					'assets/js/wp-user-manager.min.js': ['assets/js/src/wp-user-manager.js']
				},
				options: {
					banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
						' * <%= pkg.homepage %>\n' +
						' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
						' * Licensed GPLv2+' +
						' */\n',
					mangle: {
						except: ['jQuery']
					}
				}
			}
		},
		test:   {
			files: ['assets/js/test/**/*.js']
		},

		sass:   {
			all: {
				files: {
					'assets/css/admin/email-editor-control.css': 'assets/css/src/admin/email-editor-control.scss',
					'assets/css/admin/shortcodes.css': 'assets/css/src/admin/shortcodes.scss',
					'assets/css/admin/registration-status.css': 'assets/css/src/admin/registration-status.scss',
					'assets/css/admin/fields-editor.css': 'assets/css/src/admin/fields-editor.scss',
					'assets/css/admin/wpum-logo.css': 'assets/css/src/admin/wpum-logo.scss',
					'assets/css/admin/logo-font.css': 'assets/css/src/admin/logo-font.scss',
					'assets/css/admin/addons.css': 'assets/css/src/admin/addons.scss',
					'assets/css/admin/upgrades.css': 'assets/css/src/admin/upgrades.scss',
					'assets/css/admin/licensing.css': 'assets/css/src/admin/licensing.scss',
					'assets/css/wpum.css': 'assets/css/src/wpum.scss',
				}
			}
		},
		addtextdomain: {
	        target: {
	            files: {
	                src: [
	                    '*.php',
	                    '**/*.php',
	                    '!.sass-cache/**',
	                    '!assets/**',
	                    '!images/**',
	                    '!node_modules/**',
	                    '!tests/**'
	                ]
	            }
	        }
	    },
	    other: {
			changelog: 'changelog.md'
		},
		makepot: {
            target: {
                options: {
                	exclude: [
	                    'assets/.*', 'images/.*', 'node_modules/.*', 'tests/.*', 'release/.*', 'build/.*'
	                ],
                    domainPath: '/languages',
                    mainFile: 'wp-user-manager.php',
                    potFilename: 'wpum.pot',
                    potHeaders: {
                        poedit: true,                 // Includes common Poedit headers.
                        'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
                    },
                    type: 'wp-plugin'
                }
            }
        },
		cssmin: {
			options: {
				banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
					' * <%= pkg.homepage %>\n' +
					' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
					' * Licensed GPLv2+' +
					' */\n'
			},
			minify: {
				expand: true,

				cwd: 'assets/css/',
				src: ['*.css', '!*.min.css'],

				dest: 'assets/css/',
				ext: '.min.css'
			}
		},
		watch:  {

			sass: {
				files: ['assets/css/src/**/*.scss'],
				tasks: ['sass', 'cssmin'],
				options: {
					debounceDelay: 500
				}
			},

			scripts: {
				files: ['assets/js/src/**/*.js', 'assets/js/vendor/**/*.js'],
				tasks: ['concat', 'uglify'],
				options: {
					debounceDelay: 500
				}
			}
		},
		clean: {
			main: ['release'],
			post_build: [
               'build'
           	]
		},
		gittag: {
           addtag: {
               options: {
                   tag: '<%= pkg.version %>',
                   message: 'Version <%= pkg.version %>'
               }
           }
		},
		gitcommit: {
		    commit: {
		        options: {
		            message: 'Version <%= pkg.version %>',
		            noVerify: true,
		            noStatus: false,
		            allowEmpty: true
		        },
		        files: {
		            src: [ 'readme.txt', 'wp-user-manager.php', 'package.json' ]
		        }
		    }
		},
		gitpush: {
		    push: {
		        options: {
		            tags: true,
		            remote: 'origin',
		            branch: 'origin/master'
		        }
		    }
		},
		copy: {
			// Copy the plugin to a versioned release directory
			main: {
				src:  [
					'**',
					'!node_modules/**',
					'!release/**',
					'!.git/**',
					'!.sass-cache/**',
					'!css/src/**',
					'!js/src/**',
					'!img/src/**',
					'!Gruntfile.js',
					'!package.json',
					'!.gitignore',
					'!.gitmodules'
				],
				dest: 'release/<%= pkg.version %>/'
			},
			svn_trunk: {
               options : {
                   mode :true
               },
               src:  [
                   '**',
					'!node_modules/**',
					'!release/**',
					'!.git/**',
					'!.sass-cache/**',
					'!css/src/**',
					'!js/src/**',
					'!img/src/**',
					'!Gruntfile.js',
					'!package.json',
					'!.gitignore',
					'!.gitmodules'
               ],
               dest: 'build/<%= pkg.name %>/trunk/'
           },
           svn_tag: {
               options : {
                   mode :true
               },
               src:  [
                   '**',
					'!node_modules/**',
					'!release/**',
					'!.git/**',
					'!.sass-cache/**',
					'!css/src/**',
					'!js/src/**',
					'!img/src/**',
					'!Gruntfile.js',
					'!package.json',
					'!.gitignore',
					'!.gitmodules'
               ],
               dest: 'build/<%= pkg.name %>/tags/<%= pkg.version %>/'
           }
		},
		svn_checkout: {
           make_local: {
               repos: [
                   {
                       path: [ 'build' ],
                       repo: 'http://plugins.svn.wordpress.org/wp-user-manager'
                   }
               ]
           }
       	},
		push_svn: {
		    options: {
		        remove: true
		    },
		    main: {
		        src: 'build/<%= pkg.name %>',
		        dest: 'http://plugins.svn.wordpress.org/wp-user-manager',
		        tmp: 'build/make_svn'
		    }
		},
		compress: {
			main: {
				options: {
					mode: 'zip',
					archive: './release/wp-user-manager.<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'release/<%= pkg.version %>/',
				src: ['**/*'],
				dest: 'wp-user-manager/'
			}
		},
		replace: {
			readme_txt: {
				src: [ 'readme.txt' ],
				overwrite: true,
				replacements: [{
					from: /Stable tag: (.*)/,
					to: "Stable tag: <%= pkg.version %>"
				}]
			},
			init_php: {
				src: [ 'wp-user-manager.php' ],
				overwrite: true,
				replacements: [{
					from: /Version:\s*(.*)/,
					to: "Version: <%= pkg.version %>"
				}, {
					from: /define\(\s*'WPUM_VERSION',\s*'(.*)'\s*\);/,
					to: "define( 'WPUM_VERSION', '<%= pkg.version %>' );"
				}]
			}
		},
		git_changelog: {
		    extended: {
		      options: {
		        app_name : 'WP User Manager Changelog',
		        file : 'changelog.md',
		        grep_commits: '^fix|^feat|^docs|^refactor|^chore|BREAKING|^updated|^adjusted',
        		tag : '1.2.3' //False for commits since the beggining
		      }
		    }
		  }
	} );

	grunt.loadNpmTasks('git-changelog');

	// Default task.
	grunt.registerTask( 'default', ['concat', 'uglify', 'sass', 'cssmin'] );
	grunt.registerTask( 'textdomain', ['addtextdomain'] );
	grunt.registerTask( 'do_pot', ['makepot'] );
	grunt.registerTask( 'do_changelog', ['git_changelog'] );
	grunt.registerTask( 'version_number', [ 'replace:readme_txt', 'replace:init_php' ] );
	grunt.registerTask( 'pre_vcs', [ 'version_number' ] );
	grunt.registerTask( 'do_svn', [ 'svn_checkout', 'copy:svn_trunk', 'copy:svn_tag', 'push_svn' ] );
	grunt.registerTask( 'do_git', [  'gitcommit', 'gittag', 'gitpush' ] );
	grunt.registerTask( 'release', [ 'pre_vcs', 'do_svn', 'do_git', 'clean:post_build' ] );

	grunt.registerTask( 'build', ['clean', 'copy', 'compress'] );

	grunt.util.linefeed = '\n';
};
