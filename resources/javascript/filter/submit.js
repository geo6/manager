'use strict';

import app from '../app';

import initLayer from '../map/layer/layer';
import { add as initSelect, remove as removeSelect } from '../map/select';
import Records from '../Records';

function removeLayer () {
    app.map.removeLayer(app.layers.layer);

    removeSelect(app.map);
}

function addFilteredLayer (filter) {
    Records.getAll(filter)
        .then(data => {
            app.layers.layer = initLayer(app.map, data);

            initSelect(app.map, app.layers.layer);
        });
}

export default function (form) {
    const data = Object.fromEntries(new FormData(form).entries());

    let filter = `${data.key} ${data.operation}`;

    if (typeof data.value !== 'undefined') {
        filter += ` ${data.value}`;
    }

    removeLayer();
    addFilteredLayer(filter);
}
