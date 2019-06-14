'use strict';

import Form from './Form';
import Input from './form/Input';

export default function () {
    editButton();
    deleteButton();
    locateButton();

    formInput();
}

function editButton () {
    document
        .getElementById('infos-details-btn-edit')
        .addEventListener('click', () => {
            if (Form.isActive() === true) {
                event.target.classList.remove('active');

                Form.disable();
            } else {
                event.target.classList.add('active');

                Form.enable();
            }
        });
}

function deleteButton () {
    document
        .getElementById('infos-details-btn-delete')
        .addEventListener('click', () => {
            const id = Form.getElement().dataset.id;

            const confirm = window.confirm(
                `Are you sure you want to delete feature ${id} ?`
            );

            if (confirm === true) {
                const feature = window.app.source.getFeatureById(id);

                window.app.source.removeFeature(feature);
                window.app.highlightLayer.getSource().clear();

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

function locateButton () {
    document
        .getElementById('infos-details-btn-locate')
        .addEventListener('click', () => {
            window.app.map
                .getView()
                .fit(window.app.highlightLayer.getSource().getExtent(), {
                    maxZoom: 20
                });
        });
}

function formInput () {
    const form = Form.getElement().querySelector('form');

    form.querySelectorAll('input,select,textarea').forEach(element => {
        Input.enableOnChange(element);
    });
}
