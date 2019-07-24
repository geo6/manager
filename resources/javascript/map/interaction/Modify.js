'use strict';

import Modify from 'ol/interaction/Modify';
import GeoJSON from 'ol/format/GeoJSON';

import Table from '../feature/info/Table';
import Records from '../../Records';

export default class {
    constructor (map, source) {
        this.map = map;

        this.source = source;
    }

    add () {
        const modify = new Modify({
            source: this.source
        });

        modify.on('modifyend', event => this.onmodifyend(event));

        this.map.addInteraction(modify);

        return modify;
    }

    remove () {
        this.map.getInteractions().forEach(interaction => {
            if (interaction instanceof Modify) {
                this.map.removeInteraction(interaction);
            }
        });
    }

    onmodifyend (event) {
        event.features.forEach(feature => {
            const id = feature.getId();
            const geometry = feature.getGeometry();
            const geojson = new GeoJSON().writeGeometry(geometry, {
                decimals: 6,
                featureProjection: this.map.getView().getProjection()
            });

            Records.update(id, { geometry: JSON.parse(geojson) }).then(data => {
                const feature = this.source.getFeatureById(id);

                const geometry = new GeoJSON().readGeometry(data.geometry, {
                    featureProjection: this.map.getView().getProjection()
                });

                feature.setProperties(data.properties);
                feature.setGeometry(geometry);

                Table.fill(feature);
            });
        });
    }
}
