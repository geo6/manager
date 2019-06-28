'use strict';

import InfoForm from './Form';
import Input from './Input';
import { add as addModify, remove as removeModify } from '../../modify';

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

                removeModify(window.app.map);

                InfoForm.disable();
            } else {
                event.target.classList.add('active');

                InfoForm.enable();

                addModify(
                    window.app.map,
                    window.app.layers.highlight.getSource()
                );
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
                const feature = window.app.source.getFeatureById(id);

                window.app.source.removeFeature(feature);
                window.app.layers.highlight.getSource().clear();

                const liElement = Array.prototype.filter.call(
                    document.querySelectorAll('.sidebar-tabs > ul > li'),
                    liElement =>
                        liElement.querySelector('a[href="#info"]') !== null
                )[0];
                liElement.classList.add('disabled');

                window.app.sidebar.close();
            }
        });
}

function formInput () {
    const form = InfoForm.getElement().querySelector('form');

    form.querySelectorAll('input,select,textarea').forEach(element => {
        Input.enableOnChange(element, true);
    });
}