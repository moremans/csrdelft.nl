const path = require('path')

const PnpWebpackPlugin = require(`pnp-webpack-plugin`);

const contextPath = path.resolve(__dirname, 'assets');

module.exports = (env, argv) => ({
	mode: 'development',
	context: contextPath,
	entry: {
		'app': './js/entry/app.ts',
		'sentry': './js/entry/sentry.ts',
		'ledenmemory': './js/entry/ledenmemory.ts',
		'fxclouds': './js/entry/fxclouds.ts',
		'fxsneeuw': './js/entry/fxsneeuw.ts',
		'fxonontdekt': './js/entry/fxonontdekt.ts',
		'fxtrein': './js/entry/fxtrein.ts',
		'fxraket': './js/entry/fxraket.ts',
		'fxminion': './js/entry/fxminion.ts',
		'fxclippy': './js/entry/fxclippy.ts',
		'fxspace': './js/entry/fxspace.ts',
		'extern': ['./js/entry/extern.ts', './scss/extern.scss'],
		'bredeletters': './scss/bredeletters.scss',
		'common': './scss/common.scss',
		'extern-forum': './scss/extern-forum.scss',
		'extern-fotoalbum': './scss/extern-fotoalbum.scss',
		'extern-owee': ['./js/entry/extern-owee.ts', './scss/extern-owee.scss'],
		'maaltijdlijst': './scss/maaltijdlijst.scss',
		'thema-civitasia': './scss/thema/civitasia.scss',
		'thema-dies': './scss/thema/dies.scss',
		'thema-donker': './scss/thema/donker.scss',
		'thema-lustrum': './scss/thema/lustrum.scss',
		'thema-normaal': './scss/thema/normaal.scss',
		'thema-owee': './scss/thema/owee.scss',
		'thema-roze': './scss/thema/roze.scss',
		'thema-koevoet': './scss/thema/Koevoet.scss',
		'thema-sineregno': './scss/thema/sineregno.scss',
		'effect-civisaldo': './scss/effect/civisaldo.scss',
	},
	output: {
		// De map waarin alle bestanden geplaatst worden.
		path: path.resolve(__dirname, 'htdocs/dist'),
		// Alle javascript bestanden worden in de map js geplaatst.
		filename: argv.mode !== 'production' ? 'js/[name].bundle.js' : 'js/[name].[contenthash].bundle.js',
		chunkFilename: argv.mode !== 'production' ? 'js/[name].chunk.js' : 'js/[name].[contenthash].chunk.js',
		publicPath: '/dist/',
	},
	devtool: 'source-map',
	resolve: {
		// Vanuit javascript kun je automatisch .js en .ts bestanden includen.
		extensions: ['.ts', '.js', '.vue'],
		alias: {
			vue$: 'vue/dist/vue.esm.js',
		},
		plugins: [
			PnpWebpackPlugin,
		]
	},
	resolveLoader: {
		plugins: [
			PnpWebpackPlugin.moduleLoader(module),
		]
	},
	optimization: {
		minimizer: [
			new (require('optimize-css-assets-webpack-plugin'))({}),
			new (require('terser-webpack-plugin'))(),
		],
		splitChunks: {
			chunks: 'all',
		},
	},
	plugins: [
		new (require('mini-css-extract-plugin'))({
			// Css bestanden komen in de map css terecht.
			filename: argv.mode !== 'production' ? 'css/[name].css' : 'css/[name].[contenthash].css',
		}),
		new (require('vue-loader/lib/plugin'))(),
		new (require('webpack-manifest-plugin'))(),
		new (require('moment-locales-webpack-plugin'))({
			localesToKeep: ['nl'],
		}),
		new (require('./bin/dev/css-cleanup-webpack-plugin'))(),
	],
	module: {
		// Regels voor bestanden die webpack tegenkomt, als `test` matcht wordt de rule uitgevoerd.
		rules: [
			// Controleer .js bestanden met ESLint. Zie ook .eslintrc.yaml
			{
				enforce: 'pre',
				test: /\.(js|jsx)$/,
				exclude: [
					/node_modules/,
					/lib\/external/,
				],
				use: 'eslint-loader',
			},
			// Verwerk .ts (typescript) bestanden en maak er javascript van.
			{
				test: /\.ts$/,
				use: [
					'cache-loader',
					{
						loader: 'ts-loader',
						options: {appendTsSuffixTo: [/\.vue$/]}
					}
				],
			},
			{
				test: /\.vue$/,
				use: [
					'cache-loader',
					{
						loader: 'vue-loader',
						options: {
							loaders: {
								ts: 'ts-loader',
							},
						},
					}
				],
			},
			// Verwerk sass bestanden.
			// `sass-loader` >
			// Compileer naar css
			// `resolve-url-loader` >
			// Zorg ervoor dat verwijzingen naar externe bestanden kloppen (sass was meerdere bestanden, css één)
			// `css-loader` >
			// Trek alle afbeeldingen/fonts waar naar verwezen wordt naar de dist/images map
			// `MiniCssExtractPlugin` >
			// Normaal slaat webpack css op in javascript bestanden, zodat je ze makkelijk specifiek kan opvragen
			// hier zorgen we ervoor dat de css eruit wordt getrokken en in een los .css bestand wordt gestopt.
			// css-cleanup-webpack-plugin is verantwoordelijk voor het verwijderen van leeggetrokken js bestanden.
			{
				test: /\.scss$/,
				use: [
					{
						loader: require('mini-css-extract-plugin').loader,
						options: {
							// De css bestanden zitten in de css map, / is dus te vinden op ../
							publicPath: '../',
						},
					},
					'cache-loader',
					{
						loader: 'css-loader',
						options: {
							importLoaders: 3,
						},
					},
					{
						loader: 'postcss-loader',
						options: {
							ident: 'postcss',
							plugins: [require('autoprefixer')],
						},
					},
					{
						loader: 'resolve-url-loader',
						options: {},
					},
					{
						loader: 'sass-loader',
						options: {
							// Source maps moeten aan staan om `resolve-url-loader` te laten werken.
							sourceMap: true,
						},
					},
				],
			},
			{
				test: /\.css$/,
				use: ['cache-loader', 'style-loader', 'css-loader'],
			},
			// Sla fonts op in de fonts map.
			{
				test: /\.(woff|woff2|eot|ttf|otf)$/,
				use: [{
					loader: 'file-loader',
					options: {
						name: 'fonts/[name].[ext]',
					},
				}],
			},
			// Sla plaetjes op in de images map.
			{
				test: /\.(png|svg|jpg|gif)$/,
				use: [{
					loader: 'file-loader',
					options: {
						name: 'images/[name].[ext]',
					},
				}],
			},
		],
	},
});
