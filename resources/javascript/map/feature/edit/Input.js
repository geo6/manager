'use strict';

import app from '../../../app';

import InfoForm from './Form';
import Table from '../info/Table';
import Records from '../../../Records';

export default class Input {
    static getElement (key) {
        return InfoForm.getElement().querySelector(`[name="${key}"]`);
    }

    static fill (key, value) {
        Input.getElement(key).value = '';

        if (value !== null) {
            if (Input.getElement(key).dataset.datatype === 'boolean') {
                Input.getElement(key).value = value === true ? 1 : 0;
            } else {
                Input.getElement(key).value = value;
            }
        }
    }

    static save (key) {
        const element = InfoForm.getElement().querySelector(`[name="${key}"]`);
        const id = InfoForm.getElement().dataset.id;

        const properties = {};
        properties[key] = element.value;

        Records.update(id, { properties }).then(data => {
            const feature = app.layers.highlight
                .getSource()
                .getFeatureById(id);

            feature.setProperties(data.properties);

            Table.fill(feature);
        });
    }

    static enableOnChange (element, save) {
        element.addEventListener('change', event => {
            const key = event.target.name;
            const value = event.target.value;
            const valid = event.target.checkValidity();

            const statusElement = {
                danger: event.target
                    .closest('.form-group')
                    .querySelector('i.fas.text-danger'),
                success: event.target
                    .closest('.form-group')
                    .querySelector('i.fas.text-success'),
                warning: event.target
                    .closest('.form-group')
                    .querySelector('i.fas.text-warning')
            };

            statusElement.danger.hidden = true;
            statusElement.danger.removeAttribute('title');
            statusElement.success.hidden = true;
            statusElement.success.removeAttribute('title');
            statusElement.warning.hidden = true;
            statusElement.warning.removeAttribute('title');

            if (valid !== true) {
                statusElement.danger.removeAttribute('hidden');
                statusElement.danger.title = event.target.validationMessage;
            } else {
                statusElement.success.removeAttribute('hidden');

                if (save === true) {
                    Input.save(key);
                }
            }

            console.log(event.type, valid, key, value);
        });
    }
}
