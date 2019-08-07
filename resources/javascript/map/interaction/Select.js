'use strict';

import Collection from 'ol/Collection';
import Select from 'ol/interaction/Select';
import Cluster from 'ol/source/Cluster';

import app from '../../app';

import EditForm from '../feature/edit/Form';
import InfoTable from '../feature/info/Table';
import { updateListButtons } from '../feature/info/init';

export default class extends Select {
    constructor (map) {
        super({
            layers: [app.layers.layer],
            multi: true,
            wrapX: false
        });

        this.map = map;

        this.on('select', event => this.onselect(event, this.getFeatures()));

        this.add();
    }

    add () {
        this.map.addInteraction(this);
    }

    remove () {
        this.map.getInteractions().forEach(interaction => {
            if (interaction instanceof Select) {
                this.map.removeInteraction(interaction);
            }
        });
    }

    onselect (event, features) {
        const cluster = (app.layers.layer.getSource() instanceof Cluster);

        const selection = new Collection();

        if (cluster === true) {
            features.forEach(feature => {
                selection.extend(feature.get('features'));
            });
        } else {
            features.forEach(feature => {
                // ToDo: "hide" feature from initial layer and Select features

                selection.push(feature.clone());
            });
        }
        app.selection.setFeatures(selection);

        const sidebarIconElement = Array.prototype.filter.call(
            document.querySelectorAll('.sidebar-tabs > ul > li'),
            liElement => liElement.querySelector('a[href="#info"]') !== null
        )[0];

        updateListButtons();

        const count = app.selection.getFeatures().length;
        if (count > 0) {
            document.getElementById('info-list').innerText = `${app.selection.cursor + 1}/${count}`;

            InfoTable.fill(app.selection.current());
            EditForm.fill(app.selection.current());

            sidebarIconElement.classList.remove('disabled');
            app.sidebar.open('info');
        } else {
            document.getElementById('info-list').innerText = '';

            sidebarIconElement.classList.add('disabled');
            app.sidebar.close();
        }
    }
}
