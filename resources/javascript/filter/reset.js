'use strict';

import app from '../app';

import initLayer from '../map/layer/layer';
import { add as initSelect, remove as removeSelect } from '../map/select';
import Records from '../Records';

function removeLayer () {
    app.map.removeLayer(app.layers.layer);

    removeSelect(app.map);
}

function addLayer () {
    Records.getAll()
        .then(data => {
            app.layers.layer = initLayer(app.map, data);

            initSelect(app.map, app.layers.layer);
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
