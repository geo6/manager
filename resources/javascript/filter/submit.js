'use strict';

import app from '../app';

import initLayer from '../map/layer/layer';
import SelectInteraction from '../map/interaction/Select';
import Records from '../Records';

function removeLayer () {
    app.map.removeLayer(app.layers.layer);

    app.interaction.select.remove();
    app.interaction.select = null;
}

function addFilteredLayer (filter) {
    Records.getAll(filter)
        .then(data => {
            app.layers.layer = initLayer(app.map, data);

            app.interaction.select = new SelectInteraction(app.map, app.layers.layer, app.layers.highlight, app.sidebar);
        });
}

export default function (form) {
    const data = Object.fromEntries(new FormData(form).entries());

    let filter = `${data.key} ${data.operation}`;

    if (typeof data.value !== 'undefined') {
        filter += ` ${data.value}`;
    }

    switch (app.mode) {
    case 'map':
        removeLayer();
        addFilteredLayer(filter);
        break;
    case 'table':
        window.location.href = document.location.origin + document.location.pathname + `?filter=${filter}`;
        break;
    }
}
