let mix = require('laravel-mix')

mix
  .setPublicPath('dist')
  .js('resources/js/tool.js', 'js')
  .vue({ version: 3 })
  .css('resources/css/tool.css', 'css')
  .webpackConfig({
    externals: {
      vue: 'Vue'
    },
    output: {
      uniqueName: 'ucubix/role-manager'
    }
  })
  .version()
