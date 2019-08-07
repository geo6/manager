'use strict';

import app from '../../../app';

import EditForm from '../edit/Form';
import InfoTable from './Table';

export default function () {
    document
        .getElementById('infos-details-btn-locate')
        .addEventListener('click', () => {
            app.map
                .getView()
                .fit(app.selection.current().getGeometry().getExtent(), {
                    maxZoom: 20
                });
        });

    document
        .getElementById('infos-list-btn-prev')
        .addEventListener('click', () => {
            if (app.interaction.modify !== null) {
                app.interaction.modify.remove();
                app.interaction.modify = null;
            }

            const feature = app.selection.prev();

            if (feature !== null) {
                InfoTable.fill(feature);
                EditForm.fill(feature);
            }

            updateListButtons();
        });
    document
        .getElementById('infos-list-btn-next')
        .addEventListener('click', () => {
            if (app.interaction.modify !== null) {
                app.interaction.modify.remove();
                app.interaction.modify = null;
            }

            const feature = app.selection.next();

            if (feature !== null) {
                InfoTable.fill(feature);
                EditForm.fill(feature);
            }

            updateListButtons();
        });
}

export function updateListButtons () {
    const cursor = app.selection.cursor;
    const count = app.selection.getFeatures().length;

    document.getElementById('info-list').innerText = `${cursor + 1}/${count}`;

    enableButton('prev');
    enableButton('next');

    if (cursor <= 0) {
        disableButton('prev');
    }

    if (cursor >= (count - 1)) {
        disableButton('next');
    }
}

function enableButton (state) {
    document
        .getElementById(`infos-list-btn-${state}`)
        .classList.remove('disabled');
    document
        .getElementById(`infos-list-btn-${state}`)
        .removeAttribute('disabled');
}

function disableButton (state) {
    document
        .getElementById(`infos-list-btn-${state}`)
        .classList.add('disabled');
    document
        .getElementById(`infos-list-btn-${state}`)
        .setAttribute('disabled', '');
}
