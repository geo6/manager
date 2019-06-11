'use strict';

import Collection from 'ol/Collection';

import displayRecord from './feature/display';

export default function (event) {
    window.app.highlightLayer.getSource().clear();

    if (
        window.app.map.hasFeatureAtPixel(
            event.pixel,
            l => l === window.app.layer
        ) === true
    ) {
        $('.sidebar-tabs > ul > li:has(a[href="#info"])')
            .get(0)
            .classList.remove('disabled');

        let features = new Collection();

        window.app.map.forEachFeatureAtPixel(
            event.pixel,
            feature => {
                const properties = feature.getProperties();

                if (typeof properties.features !== 'undefined') {
                    // Cluster
                    features = features.extend(properties.features);
                } else {
                    // Feature
                    features.push(feature);
                }
            },
            l => l === window.app.layer
        );

        displayRecord(features, 0);

        window.app.sidebar.open('info');
    } else {
        $('.sidebar-tabs > ul > li:has(a[href="#info"])')
            .get(0)
            .classList.add('disabled');

        window.app.sidebar.close();
    }
}
