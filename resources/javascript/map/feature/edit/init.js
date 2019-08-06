'use strict';

import app from '../../../app';

import InfoForm from './Form';
import Input from './Input';
import ModifyInteraction from '../../interaction/Modify';

export default function () {
    editButton();
    deleteButton();

    formInput();
}

function editButton () {
    document
        .getElementById('infos-details-btn-edit')
        .addEventListener('click', () => {
            if (InfoForm.isActive() === true) {
                event.target.classList.remove('active');

                InfoForm.disable();

                app.interaction.modify.remove();
                app.interaction.modify = null;
            } else {
                event.target.classList.add('active');

                InfoForm.enable();

                app.interaction.modify = new ModifyInteraction(app.map, app.source, app.interaction.select.getFeatures());
                app.interaction.modify.add();
            }
        });
}

function deleteButton () {
    document
        .getElementById('infos-details-btn-delete')
        .addEventListener('click', () => {
            const id = InfoForm.getElement().dataset.id;

            const confirm = window.confirm(
                `Are you sure you want to delete feature ${id} ?`
            );

            if (confirm === true) {
                const feature = app.source.getFeatureById(id);

                app.source.removeFeature(feature);
                app.layers.highlight.getSource().clear();

                const liElement = Array.prototype.filter.call(
                    document.querySelectorAll('.sidebar-tabs > ul > li'),
                    liElement =>
                        liElement.querySelector('a[href="#info"]') !== null
                )[0];
                liElement.classList.add('disabled');

                app.sidebar.close();
            }
        });
}

function formInput () {
    const form = InfoForm.getElement().querySelector('form');

    form.querySelectorAll('input,select,textarea').forEach(element => {
        Input.enableOnChange(element);

        const list = element.getAttribute('list');

        if (list !== null) {
            element.addEventListener('change', event => {
                const key = event.target.name;
                const value = event.target.value;
                const optionElement = event.target.parentNode.querySelector(`datalist > option[value="${value}"]`);

                Input.getHelpText(key).innerText = '';

                if (optionElement !== null && optionElement.innerText !== value) {
                    Input.getHelpText(key).innerText = value + ' = ' + optionElement.innerText;
                }
            });
        }
    });
}
