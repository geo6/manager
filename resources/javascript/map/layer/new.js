'use strict';

import VectorSource from 'ol/source/Vector';
import VectorLayer from 'ol/layer/Vector';

export default function (map) {
    const layer = new VectorLayer({
        map: map,
        source: new VectorSource({
            useSpatialIndex: false
        })
    });

    return layer;
}
