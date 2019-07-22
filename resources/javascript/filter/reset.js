'use strict';

import initLayer from '../map/layer/layer';
import { add as initSelect, remove as removeSelect } from '../map/select';
import Records from '../Records';

function removeLayer () {
    window.app.map.removeLayer(window.app.layers.layer);

    removeSelect(window.app.map);
}

function addLayer () {
    Records.getAll()
        .then(data => {
            window.app.layers.layer = initLayer(window.app.map, data);

            initSelect(window.app.map, window.app.layers.layer);
        });
}

export default function () {
    removeLayer();
    addLayer();
}
