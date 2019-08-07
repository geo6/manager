'use strict';

import VectorSource from 'ol/source/Vector';
import VectorLayer from 'ol/layer/Vector';

import highlightStyleFunction from '../style/highlight';
import styleFunction from '../style/style';

export default function (map) {
    const layer = new VectorLayer({
        map: map,
        source: new VectorSource({
            // useSpatialIndex: false
        }),
        style: (feature, resolution) => {
            return [
                highlightStyleFunction(feature, resolution),
                styleFunction(feature, 'label', resolution)
            ];
        }
    });

    return layer;
}
