import { Application } from '@hotwired/stimulus';
import { definitionsFromContext } from '@hotwired/stimulus-webpack-helpers';

// Initialise l'application Stimulus
const app = Application.start();

// Charge les contrôleurs depuis le dossier "controllers"
const context = require.context('./controllers', true, /\.js$/);
app.load(definitionsFromContext(context));