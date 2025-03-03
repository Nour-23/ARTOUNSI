import { Controller } from '@hotwired/stimulus';

/**
 * Ce contrôleur Stimulus est un exemple simple.
 *
 * Il sera exécuté sur tout élément HTML ayant un attribut `data-controller="hello"`.
 * Le nom "hello" correspond au nom du fichier : `hello_controller.js` -> "hello".
 *
 * Vous pouvez modifier ce fichier pour l'adapter à vos besoins.
 */
export default class extends Controller {
    /**
     * Méthode appelée lorsque le contrôleur est connecté à un élément du DOM.
     */
    connect() {
        // Affiche un message dans l'élément
        this.element.textContent = 'Hello Stimulus! Edit me in assets/controllers/hello_controller.js';

        // Vous pouvez également ajouter des logs pour le débogage
        console.log('Hello controller connected!', this.element);
    }

    /**
     * Méthode appelée lorsque le contrôleur est déconnecté de l'élément du DOM.
     */
    disconnect() {
        // Vous pouvez ajouter du code de nettoyage ici si nécessaire
        console.log('Hello controller disconnected!', this.element);
    }
}