'use strict';

import initLayer from '../../map/layer/layer';
import SelectInteraction from '../../map/interaction/Select';
import Records from '../../Records';

function removeLayer (app) {
    app.map.removeLayer(app.layers.layer);

    app.interaction.select.remove();
    app.interaction.select = null;
}

function addFilteredLayer (app, filter) {
    Records.getAll(app.custom, filter).then(data => {
        app.layers.layer = initLayer(app.map, data);

        app.interaction.select = new SelectInteraction(app.map);
    });
}

export default function (app, filter) {
    removeLayer(app);
    addFilteredLayer(app, filter);
}
