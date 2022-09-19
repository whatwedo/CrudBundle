const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/admin')
    .setPublicPath('/build/admin')

    /*
     * ENTRY CONFIG BACKEND
     */
    .addEntry('admin', './assets/admin.js')

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    .enableStimulusBridge('./assets/controllers.json')
    .splitEntryChunks()
    .enableSingleRuntimeChunk()

    /*
    .copyFiles({
        from: './assets/favicon',
        to: 'favicon/[path][name].[ext]',
    })
    .copyFiles({
        from: './assets/images',
        to: 'images/[path][name].[ext]',
    })
    */

    /*
     * FEATURE CONFIG BACKEND
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })
    .enableSassLoader()
    .enablePostCssLoader()
    .enableIntegrityHashes(Encore.isProduction())

    .configureWatchOptions(function(watchOptions) {
        watchOptions.poll = 1000;
    })
;

const adminConfig = Encore.getWebpackConfig();
adminConfig.name = 'adminConfig';

// reset Encore to build the second config
Encore.reset();

Encore
    .setOutputPath('public/build/frontend/')
    .setPublicPath('/build/frontend')

    /*
     * ENTRY CONFIG FRONTEND
     */
    .addEntry('frontend', './assets/frontend.js')
    //.enableStimulusBridge('./assets/controllers_frontend.json') // doesn't work twice
    .splitEntryChunks()
    .enableSingleRuntimeChunk()

    /*
    .copyFiles({
        from: './assets/favicon',
        to: 'favicon/[path][name].[ext]',
    })
    .copyFiles({
        from: './assets/images',
        to: 'images/[path][name].[ext]',
    })
    */

    /*
     * FEATURE CONFIG FRONTEND
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })
    .enableSassLoader()
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            config: './postcss_frontend.config.js',
        }
    })

    /*
    .configureDevServerOptions(options => {
        options.allowedHosts = 'all';
        options.host = process.env.VIRTUAL_HOST;
        options.port = 443;
        //options.path = '/webpack-dev-server/wss';
    })
    */

    .enableIntegrityHashes(Encore.isProduction())
;

// build the second configuration
const frontendConfig = Encore.getWebpackConfig();

// Set a unique name for the config (needed later!)
frontendConfig.name = 'frontendConfig';

// devServer works only once - we use it for the frontend
delete adminConfig.devServer;

// export the final configuration as an array of multiple configurations
module.exports = [adminConfig, frontendConfig]
