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
            const confirm = window.confirm(
                'Are you sure you want to delete this feature ?'
            );

            console.log(confirm);
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
