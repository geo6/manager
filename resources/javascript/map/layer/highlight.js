'use strict';

import VectorSource from 'ol/source/Vector';
import VectorLayer from 'ol/layer/Vector';

import styleHighlightFunction from '../style/highlight';

export default function (map) {
    const layer = new VectorLayer({
        map: map,
        source: new VectorSource({
            // useSpatialIndex: false
        }),
        style: feature => {
            return styleHighlightFunction(feature);
        }
    });

    return layer;
}
