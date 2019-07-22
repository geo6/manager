'use strict';

import { Select } from 'ol/interaction';
import Collection from 'ol/Collection';

import app from '../../app';

import styleFunction from '../style/style';
import displayRecord from '../feature/display';

function onselect (event, features) {
    app.layers.highlight.getSource().clear();

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
            app.map.getView().fit(collection.item(0).getGeometry(), {
                maxZoom: 18
            });
        }

        liElement.classList.remove('disabled');

        displayRecord(collection, 0);

        app.sidebar.open('info');
    } else {
        liElement.classList.add('disabled');

        app.sidebar.close();
    }
}

export function add (map, layer) {
    const select = new Select({
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

    select.on('select', event => onselect(event, select.getFeatures()));

    map.addInteraction(select);

    return select;
}

export function remove (map) {
    map.getInteractions().forEach(interaction => {
        if (interaction instanceof Select) {
            map.removeInteraction(interaction);
        }
    });
}
