'use strict';

import GeoJSON from 'ol/format/GeoJSON';

import app from '../../../app';

import DrawInteraction from '../../interaction/Draw';
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

                    app.interaction.draw.remove();
                    app.interaction.draw = null;
                } else {
                    buttonElement.classList.add('active');

                    app.interaction.draw = new DrawInteraction(
                        app.map,
                        app.layers.new.getSource(),
                        type
                    );
                }
            });
        });

    NewForm.getElement()
        .querySelector('form')
        .querySelectorAll('input,select,textarea')
        .forEach(element => {
            const list = element.getAttribute('list');

            if (list !== null) {
                element.addEventListener('change', event => {
                    const key = event.target.name;
                    const value = event.target.value;
                    const optionElement = event.target.parentNode.querySelector(`datalist > option[value="${value}"]`);

                    NewForm.getElement().querySelector(`[name="${key}"] + .form-text`).innerText = '';

                    if (optionElement !== null && optionElement.innerText !== value) {
                        NewForm.getElement().querySelector(`[name="${key}"] + .form-text`).innerText = value + ' = ' + optionElement.innerText;
                    }
                });
            }
        });

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
