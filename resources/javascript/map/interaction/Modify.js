'use strict';

import Collection from 'ol/Collection';
import Modify from 'ol/interaction/Modify';
import GeoJSON from 'ol/format/GeoJSON';

import app from '../../app';

import Table from '../feature/info/Table';
import Records from '../../Records';

export default class extends Modify {
    constructor (map) {
        super({
            features: new Collection([app.selection.current()])
        });

        this.map = map;

        this.on('modifyend', event => this.onmodifyend(event));
    }

    add () {
        app.interaction.select.remove();

        this.map.addInteraction(this);
    }

    remove () {
        app.interaction.select.add();

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

            this.setActive(false);

            Records.update(app.custom, id, {
                geometry: JSON.parse(geojson)
            }).then(data => {
                const feature = app.source.getFeatureById(id);

                const geometry = new GeoJSON().readGeometry(data.geometry, {
                    featureProjection: this.map.getView().getProjection()
                });

                feature.setProperties(data.properties);
                feature.setGeometry(geometry);

                Table.fill(feature);

                this.setActive(true);
            });
        });
    }
}
