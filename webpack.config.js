const Encore = require('@symfony/webpack-encore');
const ESLintPlugin = require('eslint-webpack-plugin');

// Configuration de base
Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/app.js')
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .enableSassLoader()
    .enablePostCssLoader()
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    });

// Ajouter le plugin ESLint
const config = Encore.getWebpackConfig();
config.plugins.push(new ESLintPlugin());

// Exporte la configuration finale
module.exports = config;