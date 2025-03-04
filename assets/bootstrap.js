import { Application } from '@hotwired/stimulus';
import { definitionsFromContext } from '@hotwired/stimulus-webpack-helpers';

// Initialise l'application Stimulus
const app = Application.start();

// Charge les contrôleurs depuis le dossier "controllers"
const context = require.context('./controllers', true, /\.js$/);
app.load(definitionsFromContext(context));
import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
