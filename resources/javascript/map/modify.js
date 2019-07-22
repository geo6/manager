'use strict';

import Modify from 'ol/interaction/Modify';
import GeoJSON from 'ol/format/GeoJSON';

import app from '../app';

import Table from './feature/info/Table';
import Records from '../Records';

function onmodifyend (event) {
    event.features.forEach(feature => {
        const id = feature.getId();
        const geometry = feature.getGeometry();
        const geojson = new GeoJSON().writeGeometry(geometry, {
            decimals: 6,
            featureProjection: app.map.getView().getProjection()
        });

        Records.update(id, { geometry: JSON.parse(geojson) }).then(data => {
            const feature = app.layers.highlight
                .getSource()
                .getFeatureById(id);

            const geometry = new GeoJSON().readGeometry(data.geometry, {
                featureProjection: app.map.getView().getProjection()
            });

            feature.setProperties(data.properties);
            feature.setGeometry(geometry);

            Table.fill(feature);
        });
    });
}

export function add (map, source) {
    const modify = new Modify({
        source
    });

    modify.on('modifyend', event => onmodifyend(event));

    map.addInteraction(modify);

    return modify;
}

export function remove (map) {
    map.getInteractions().forEach(interaction => {
        if (interaction instanceof Modify) {
            map.removeInteraction(interaction);
        }
    });
}
