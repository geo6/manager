'use strict';

import { Select } from 'ol/interaction';
import Collection from 'ol/Collection';

import displayRecord from './feature/display';

export default function (map, layer) {
    const select = new Select({
        layers: [layer],
        multi: false,
        style: () => false,
        wrapX: false
    });

    select.on('select', event => {
        window.app.highlightLayer.getSource().clear();

        const features = new Collection();

        select.getFeatures().forEach(feature => {
            const properties = feature.getProperties();

            if (typeof properties.features !== 'undefined') {
                // Cluster
                features.extend(properties.features);
            } else {
                // Feature
                features.push(feature);
            }
        });

        const liElement = Array.prototype.filter.call(
            document.querySelectorAll('.sidebar-tabs > ul > li'),
            liElement => liElement.querySelector('a[href="#info"]') !== null
        )[0];

        if (features.getLength() > 0) {
            liElement.classList.remove('disabled');

            displayRecord(features, 0);

            window.app.sidebar.open('info');
        } else {
            liElement.classList.add('disabled');

            window.app.sidebar.close();
        }
    });

    map.addInteraction(select);

    return select;
}
