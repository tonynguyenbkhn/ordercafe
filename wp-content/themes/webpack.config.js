const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const { PurgeCSSPlugin } = require('purgecss-webpack-plugin');
const glob = require('glob');

module.exports = (env, argv) => {
  const theme = env.theme;
  const isProd = argv.mode === 'production';

  const themePath = path.resolve(__dirname, theme);
  const srcPath = path.resolve(themePath, 'src');
  const assetsPath = path.resolve(themePath, 'assets');

	const entry = {
		frontend: path.resolve(srcPath, 'frontend.js'),
		critical_frontend: path.resolve(srcPath, 'critical_frontend.js'),
		admin: path.resolve(srcPath, 'admin.js')
	}

	if (env.woo === 'yes') {
		entry.woocommerce = path.resolve(srcPath, 'woocommerce.js');
	}

  return {
    entry,
    output: {
      path: assetsPath,
      filename: isProd ? './js/[name].min.js' : './js/[name].js',
      clean: true
    },
    resolve: {
      modules: [
        path.resolve(__dirname, '../../node_modules'),
        'node_modules',
      ],
      alias: {
        lib: path.resolve(srcPath, 'js/lib/'),
        blocks: path.resolve(srcPath, 'js/blocks/'),
      },
      extensions: ['.js']
    },
    module: {
      rules: [
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: 'babel-loader'
        },
        {
          test: /\.(scss|css)$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            {
              loader: 'sass-loader',
              options: {
                implementation: require('sass'),
                sassOptions: {
                  includePaths: [path.resolve(srcPath, 'scss')],
                }
              }
            }
          ]
        },
        {
          test: /\.(woff2?|ttf|eot|otf)$/,
          type: 'asset/resource',
          generator: {
            filename: 'fonts/[name][ext]'
          }
        },
        {
          test: /\.(png|jpe?g|gif|svg|webp)$/,
          type: 'asset/resource',
          generator: {
            filename: 'images/[name][ext]'
          }
        }
      ]
    },
    ignoreWarnings: [
      warning =>
        typeof warning.message === 'string' &&
        warning.message.toLowerCase().includes('deprecation')
    ],
    stats: 'minimal',
    plugins: [
      new CleanWebpackPlugin(),
      new MiniCssExtractPlugin({
        filename: isProd ? 'css/[name].min.css' : 'css/[name].css'
      }),
      ...(isProd ? [new PurgeCSSPlugin({
        paths: glob.sync(`${themePath}/**/*.php`, { nodir: true }),
        safelist: {
          standard: ['body', 'html', /^wp-/],
          deep: [/^is-/, /^has-/],
          greedy: [/^twmp-/, /^woocommerce-/, /^calendar-/, /^fc-/]
        }
      })] : []),
      new CopyWebpackPlugin({
        patterns: [
          { from: path.resolve(srcPath, 'fonts'), to: 'fonts' },
          { from: path.resolve(srcPath, 'images'), to: 'images' },
        ]
      })
    ],
    optimization: {
      minimize: true,
      minimizer: [
        new CssMinimizerPlugin({
          minimizerOptions: {
            preset: ['default', {
              discardComments: { removeAll: true }
            }]
          }
        })
      ],
    },
    devtool: isProd ? false : 'source-map'
  };
};
