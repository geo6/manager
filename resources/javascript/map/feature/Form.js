'use strict';

import Table from './Table';
import Input from './form/Input';

export default class Form {
    static getElement () {
        return document.getElementById('info-form');
    }

    static isActive () {
        return Form.getElement().hidden !== true;
    }

    static enable () {
        Table.getElement().hidden = true;
        Form.getElement().removeAttribute('hidden');

        document
            .getElementById('infos-details-btn-delete')
            .removeAttribute('hidden');
    }

    static disable () {
        Table.getElement().removeAttribute('hidden');
        Form.getElement().hidden = true;

        document.getElementById('infos-details-btn-delete').hidden = true;
    }

    static fill (feature) {
        const id = feature.getId();
        const properties = feature.getProperties();
        const keys = Object.keys(properties);

        Form.getElement().dataset.id = id;

        const readonly = window.app.cache.table.columns
            .filter(column => column.readonly === true)
            .map(column => column.name);

        keys.filter(key => {
            return key !== 'geometry' && readonly.indexOf(key) === -1;
        }).map(key => {
            Input.fill(key, properties[key]);
        });
    }
}
