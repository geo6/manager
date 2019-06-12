'use strict';

import VectorLayer from 'ol/layer/Vector';
import GeoJSON from 'ol/format/GeoJSON';
import { Vector as VectorSource, Cluster } from 'ol/source';

import styleFunction from '../style/style';
import styleClusterFunction from '../style/cluster';
import Records from '../../Records';

export default function (map, geojson) {
    window.app.source = new VectorSource({
        features: new GeoJSON().readFeatures(geojson, {
            featureProjection: window.app.map.getView().getProjection()
        })
    });

    const count = window.app.source.getFeatures().length;
    const labelColumn = 'label';

    const layer = new VectorLayer({
        map: map,
        source: window.app.source,
        style: feature => {
            return styleFunction(feature, labelColumn);
        }
    });

    if (count > 1000) {
        const cluster = new Cluster({
            source: window.app.source
        });

        layer.setStyle(feature => {
            const features = feature.get('features');

            if (features.length > 1) {
                return styleClusterFunction(features.length);
            } else {
                return styleFunction(features[0], labelColumn);
            }
        });
        layer.setSource(cluster);
    }

    if (window.location.hash === '') {
        layer.once('render', () => {
            window.app.map.getView().fit(layer.getSource().getExtent());
        });
    }

    window.app.source.on('removefeature', event => {
        const feature = event.feature;
        const id = feature.getId();

        Records.delete(id).then(data => console.log(data));
    });

    return layer;
}
