'use strict';

import app from '../app';

import initLayer from '../map/layer/layer';
import Records from '../Records';

function removeLayer () {
    app.map.removeLayer(app.layers.layer);

    app.interaction.select.remove();
}

function addLayer () {
    Records.getAll()
        .then(data => {
            app.layers.layer = initLayer(app.map, data);

            app.interaction.select.add();
        });
}

export default function () {
    switch (app.mode) {
    case 'map':
        removeLayer();
        addLayer();
        break;
    case 'table':
        window.location.href = document.location.origin + document.location.pathname;
        break;
    }
}
