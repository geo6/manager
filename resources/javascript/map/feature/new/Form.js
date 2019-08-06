'use strict';

import Records from '../../../Records';

export default class Form {
    static getElement () {
        return document.getElementById('new-form');
    }

    static isActive () {
        return Form.getElement().hidden !== true;
    }

    static enable () {
        document.getElementById('new-list-types').hidden = true;
        Form.getElement().removeAttribute('hidden');
    }

    static disable () {
        document.getElementById('new-list-types').removeAttribute('hidden');
        Form.getElement().hidden = true;
    }

    static reset () {
        const alertElement = Form.getElement().querySelector('.alert-danger');

        alertElement.innerText = null;
        alertElement.hidden = true;

        Form.getElement()
            .querySelector('form')
            .reset();
    }

    static async save (geojson) {
        const formData = new FormData(Form.getElement().querySelector('form'));

        const data = {
            geometry: JSON.parse(geojson),
            properties: {}
        };
        Array.from(formData).forEach(pair => {
            const key = pair[0];
            const value = pair[1];

            data.properties[key] = value;
        });

        document.getElementById('info-form-alert-error').hidden = true;
        document.getElementById('info-form-alert-error').querySelector('pre > code').innerText = '';

        try {
            return await Records.insert(data);
        } catch (error) {
            document.getElementById('new-form-alert-error').removeAttribute('hidden');
            document.getElementById('new-form-alert-error').querySelector('pre > code').innerText = error;

            return Promise.reject(new Error(error));
        }
    }
}
