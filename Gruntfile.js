module.exports = function (grunt) {
	grunt.initConfig({
		less: {
			development: {
				options: {
					compress: false,
					yuicompress: true,
					optimization: 2
					},
				files: {
					"css/style.css": "css/style.less"
				}
			}
		},
		//scp -P22053 amazon-affiliate.zip dev3:/home/eugen/www/update.dev3.gringo.qix.sx/www/packages/
		compress: {
			main: {
				options: {
					archive: 'custom-crop.zip'
				},
				files: [
					{expand: true, src: ['**', '!node_modules/**', '!custom-crop.zip'], dest: '/custom-crop/'}
				]
			}
		},
		watch: {
			styles: {
				files: ['./**/*.less'], // which files to watch
				tasks: ['less'],
				options: {
					nospawn: true
				}
			}
		}
	});
	grunt.loadNpmTasks('grunt-contrib-compress');
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-watch');

	grunt.registerTask('default', ['watch']);
};