'use strict';

import Draw from 'ol/interaction/Draw';

import app from '../../app';

import NewForm from '../feature/new/Form';

export default class extends Draw {
    constructor (map, source, type) {
        super({
            source: source,
            stopClick: true,
            type: type
        });

        this.map = map;

        this.on('drawend', event => this.ondrawend(event));

        this.add();
    }

    add () {
        app.interaction.select.remove();

        this.map.addInteraction(this);
    }

    remove () {
        app.interaction.select.add();

        this.map.getInteractions().forEach(interaction => {
            if (interaction instanceof Draw) {
                this.map.removeInteraction(interaction);
            }
        });
    }

    ondrawend (event) {
        this.remove();

        document
            .getElementById('new')
            .querySelector('.list-group > button.active')
            .classList.remove('active');

        NewForm.enable();
    }
}
