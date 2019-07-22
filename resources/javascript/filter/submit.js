'use strict';

import initLayer from '../map/layer/layer';
import { add as initSelect, remove as removeSelect } from '../map/select';
import Records from '../Records';

function removeLayer () {
    window.app.map.removeLayer(window.app.layers.layer);

    removeSelect(window.app.map);
}

function addFilteredLayer (filter) {
    Records.getAll(filter)
        .then(data => {
            window.app.layers.layer = initLayer(window.app.map, data);

            initSelect(window.app.map, window.app.layers.layer);
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
