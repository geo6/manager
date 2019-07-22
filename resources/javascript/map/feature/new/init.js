'use strict';

import GeoJSON from 'ol/format/GeoJSON';

import app from '../../../app';

import { add, remove } from '../../interaction/draw';
// import Input from '../form/Input';
import NewForm from './Form';

export default function () {
    document
        .getElementById('new')
        .querySelectorAll('.list-group > button')
        .forEach(buttonElement => {
            buttonElement.addEventListener('click', () => {
                const type = buttonElement.dataset.type;

                app.layers.new.getSource().clear();

                if (buttonElement.classList.contains('active') === true) {
                    buttonElement.classList.remove('active');

                    remove(app.map);
                } else {
                    buttonElement.classList.add('active');

                    add(
                        app.map,
                        app.layers.new.getSource(),
                        type
                    );
                }
            });
        });

    // NewForm.getElement()
    //     .querySelector('form')
    //     .querySelectorAll('input,select,textarea')
    //     .forEach(element => {
    //         Input.enableOnChange(element, false);
    //     });

    document.getElementById('new-btn-cancel').addEventListener('click', () => {
        app.layers.new.getSource().clear();

        NewForm.reset();
        NewForm.disable();
    });

    NewForm.getElement()
        .querySelector('form')
        .addEventListener('submit', event => {
            event.preventDefault();

            const feature = app.layers.new
                .getSource()
                .getFeaturesCollection()
                .item(0);
            const geometry = feature.getGeometry();
            const geojson = new GeoJSON().writeGeometry(geometry, {
                decimals: 6,
                featureProjection: app.map.getView().getProjection()
            });

            NewForm.save(geojson).then(json => {
                const feature = new GeoJSON().readFeature(json, {
                    featureProjection: app.map.getView().getProjection()
                });

                app.source.addFeature(feature);

                NewForm.reset();
                NewForm.disable();
            });
        });
}
