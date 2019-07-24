'use strict';

import Collection from 'ol/Collection';
import Select from 'ol/interaction/Select';

import styleFunction from '../style/style';
import displayRecord from '../feature/display';

export default class extends Select {
    constructor (map, layer, hightlightLayer, sidebar) {
        super({
            layers: [layer],
            multi: false,
            style: (feature, resolution) => {
                const properties = feature.getProperties();

                if (typeof properties.features !== 'undefined') {
                    // Cluster
                    if (properties.features.length > 1) {
                        return false;
                    } else {
                        return styleFunction(properties.features[0], 'label', resolution);
                    }
                } else {
                    // Feature
                    return styleFunction(feature, 'label', resolution);
                }
            },
            wrapX: false
        });

        this.map = map;
        this.sidebar = sidebar;

        this.layer = layer;
        this.hightlightLayer = hightlightLayer;

        this.on('select', event => this.onselect(event, this.getFeatures()));

        this.map.addInteraction(this);
    }

    remove () {
        this.map.getInteractions().forEach(interaction => {
            if (interaction instanceof Select) {
                this.map.removeInteraction(interaction);
            }
        });
    }

    onselect (event, features) {
        this.hightlightLayer.getSource().clear();

        const collection = new Collection();

        features.forEach(feature => {
            const properties = feature.getProperties();

            if (typeof properties.features !== 'undefined') {
                // Cluster
                collection.extend(properties.features);
            } else {
                // Feature
                collection.push(feature);
            }
        });

        const liElement = Array.prototype.filter.call(
            document.querySelectorAll('.sidebar-tabs > ul > li'),
            liElement => liElement.querySelector('a[href="#info"]') !== null
        )[0];

        if (collection.getLength() > 0) {
            if (collection.getLength() > 1) {
                this.map.getView().fit(collection.item(0).getGeometry(), {
                    maxZoom: 18
                });
            }

            liElement.classList.remove('disabled');

            displayRecord(collection, 0);

            this.sidebar.open('info');
        } else {
            liElement.classList.add('disabled');

            this.sidebar.close();
        }
    }
}
