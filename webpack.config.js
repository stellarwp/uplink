const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

const isProduction = process.env.NODE_ENV === 'production';
const buildMode    = process.env.BUILD_MODE || ( isProduction ? 'prod' : 'dev' );
const outputPath   = path.resolve(
    process.cwd(),
    buildMode === 'prod' ? 'build' : 'build-dev'
);

module.exports = {
    ...defaultConfig,
    entry: {
        index: path.resolve( process.cwd(), 'resources', 'js', 'index.tsx' ),
    },
    output: {
        path:          outputPath,
        filename:      '[name].js',
        chunkFilename: '[name].js?ver=[contenthash]',
    },
    resolve: {
        ...defaultConfig.resolve,
        alias: {
            '@':           path.resolve( process.cwd(), 'resources', 'js' ),
            '@components': path.resolve( process.cwd(), 'resources', 'js', 'components' ),
            '@lib':        path.resolve( process.cwd(), 'resources', 'js', 'lib' ),
            '@css':        path.resolve( process.cwd(), 'resources', 'css' ),
        },
    },
    optimization: {
        ...defaultConfig.optimization,
        ...( buildMode === 'prod' && { minimize: true, usedExports: true } ),
    },
    devtool: buildMode === 'prod' ? false : 'source-map',
};
