const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		admin: './assets/admin-src/index.tsx',
		frontend: './assets/frontend-src/index.ts',
	},
};
