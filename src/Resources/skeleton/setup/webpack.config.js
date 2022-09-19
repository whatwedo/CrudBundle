const Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
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

// You could add the configuration for your frontend here

// export the final configuration as an array of multiple configurations
module.exports = [adminConfig];
