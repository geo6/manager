'use strict';

import app from '../../../app';

import InfoForm from './Form';
import Table from '../info/Table';
import Records from '../../../Records';

export default class Input {
    static getElement (key) {
        return InfoForm.getElement().querySelector(`[name="${key}"]`);
    }

    static getHelpText (key) {
        return InfoForm.getElement().querySelector(`[name="${key}"] + .form-text`);
    }

    static fill (key, value) {
        const input = Input.getElement(key);

        if (input !== null) {
            input.value = '';

            if (value !== null) {
                if (input.dataset.datatype === 'boolean') {
                    input.value = value === true ? 1 : 0;
                } else {
                    input.value = value;
                }
            }
        } else {
            throw new Error(`No input for properties "${key}".`);
        }
    }

    static async save (key) {
        const element = InfoForm.getElement().querySelector(`[name="${key}"]`);
        const id = InfoForm.getElement().dataset.id;

        const properties = {};
        properties[key] = element.value;

        try {
            const data = await Records.update(id, { properties });

            const feature = app.layers.highlight
                .getSource()
                .getFeatureById(id);

            feature.setProperties(data.properties);

            Table.fill(feature);

            Input.changeStatus(element, 'success');

            return data;
        } catch (error) {
            Input.changeStatus(element, 'danger');

            document.getElementById('info-form-alert-error').removeAttribute('hidden');
            document.getElementById('info-form-alert-error').querySelector('pre > code').innerText = error;

            return Promise.reject(new Error(error));
        }
    }

    static enableOnChange (element) {
        element.addEventListener('change', event => {
            const key = event.target.name;
            const valid = event.target.checkValidity();

            Input.changeStatus(event.target, 'loading');

            document.getElementById('info-form-alert-error').hidden = true;
            document.getElementById('info-form-alert-error').querySelector('pre > code').innerText = '';

            if (valid !== true) {
                Input.changeStatus(event.target, 'warning', event.target.validationMessage);
            } else {
                Input.save(key);
            }
        });
    }

    static changeStatus (element, status, text) {
        if (['loading', 'success', 'warning', 'danger'].indexOf(status) === -1) {
            throw new Error(`Invalid status "${status}".`);
        }

        const statusElement = {
            danger: element
                .closest('.form-group')
                .querySelector('i.fas.text-danger'),
            loading: element
                .closest('.form-group')
                .querySelector('i.fas.fa-spin'),
            success: element
                .closest('.form-group')
                .querySelector('i.fas.text-success'),
            warning: element
                .closest('.form-group')
                .querySelector('i.fas.text-warning')
        };

        statusElement.danger.hidden = true;
        statusElement.danger.removeAttribute('title');
        statusElement.loading.hidden = true;
        statusElement.loading.removeAttribute('title');
        statusElement.success.hidden = true;
        statusElement.success.removeAttribute('title');
        statusElement.warning.hidden = true;
        statusElement.warning.removeAttribute('title');

        if (typeof statusElement[status] !== 'undefined') {
            statusElement[status].removeAttribute('hidden');

            if (typeof text === 'string' && text.length > 0) {
                statusElement[status].setAttribute('title', text);
            }
        }
    }
}
