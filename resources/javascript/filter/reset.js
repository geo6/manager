'use strict';

import app from '../app';

import initLayer from '../map/layer/layer';
import ModifyInteraction from '../map/interaction/Modify';
import Records from '../Records';

function removeLayer () {
    app.map.removeLayer(app.layers.layer);

    app.interaction.select.remove();
    app.interaction.select = null;
}

function addLayer () {
    Records.getAll()
        .then(data => {
            app.layers.layer = initLayer(app.map, data);

            app.interaction.select = new ModifyInteraction(app.map, app.layers.layer, app.layers.highlight, app.sidebar);
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
