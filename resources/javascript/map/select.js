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

        if (features.getLength() > 0) {
            $('.sidebar-tabs > ul > li:has(a[href="#info"])')
                .get(0)
                .classList.remove('disabled');

            displayRecord(features, 0);

            window.app.sidebar.open('info');
        } else {
            $('.sidebar-tabs > ul > li:has(a[href="#info"])')
                .get(0)
                .classList.add('disabled');

            window.app.sidebar.close();
        }
    });

    map.addInteraction(select);

    return select;
}
