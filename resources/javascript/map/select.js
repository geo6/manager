'use strict';

import { Select } from 'ol/interaction';
import Collection from 'ol/Collection';

import displayRecord from './feature/display';

function onselect (event, features) {
    window.app.highlightLayer.getSource().clear();

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
        liElement.classList.remove('disabled');

        displayRecord(collection, 0);

        window.app.sidebar.open('info');
    } else {
        liElement.classList.add('disabled');

        window.app.sidebar.close();
    }
}

export default function (map, layer) {
    const select = new Select({
        layers: [layer],
        multi: false,
        style: () => false,
        wrapX: false
    });

    select.on('select', event => onselect(event, select.getFeatures()));

    map.addInteraction(select);

    return select;
}
