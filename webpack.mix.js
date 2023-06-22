const mix = require('laravel-mix');
const VuetifyLoaderPlugin = require('vuetify-loader/lib/plugin');

mix.disableNotifications();

mix.options({
    terser: {
        terserOptions: {
            format: {
                comments: false,
            },
        },
        extractComments: false
    },
    autoprefixer: {
        enabled: true
    }
});

mix.webpackConfig({
    plugins: [
        new VuetifyLoaderPlugin({
            match(tag, {camelTag}) {
                if([].indexOf(camelTag) >= 0) {
                    return [camelTag, `import ${camelTag} from '~/components/vuetify/${camelTag}/${camelTag}'`]
                }
            }
        })
    ],
    resolve: {
        alias: {
            '~': require('path').join(__dirname, './resources/frontend-reconstruction/js')
        }
    },
    devtool: !mix.inProduction() && 'eval-source-map'
});

Mix.listen('configReady', config => {
    const addVariables = isScss => {
        config.module.rules.find(
            r => r.test.toString() ===
                (isScss ? /\.scss$/ : /\.sass$/).toString()
        ).oneOf.flatMap(
            rule => rule.use.filter(
                rule => rule.loader === 'sass-loader'
            ).map(rule => rule.options)
        ).forEach(options =>
            options.prependData = `@import "./resources/frontend-reconstruction/sass/variables.scss"${isScss ? ';' : ''}`
        );
    };

    addVariables(true);
    addVariables();
});

mix.js('resources/frontend-reconstruction/js/app.js', 'public/js')
    .vue({
        extractStyles: !Mix.isUsing('hmr')
    });

mix.sass('resources/frontend-reconstruction/sass/app.scss', 'public/css');

if(!Mix.isUsing('hmr')) {
    mix.extract({to: 'js/vendor.js', test: /node_modules\/|decompiled\/|\/css\/vendor\.css/});
    mix.version();
}
