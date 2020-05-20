const merge = require('webpack-merge');
const common = require('./webpack.config.common.js');

module.exports = merge(common, {
	mode: 'development',
	devtool: 'inline-cheap-module-source-map',
});
