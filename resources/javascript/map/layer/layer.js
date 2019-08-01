'use strict';

import VectorLayer from 'ol/layer/Vector';
import GeoJSON from 'ol/format/GeoJSON';
import { Vector as VectorSource/*, Cluster */ } from 'ol/source';

import app from '../../app';

import styleFunction from '../style/style';
// import styleClusterFunction from '../style/cluster';
import Records from '../../Records';

export default function (map, geojson) {
    app.source = new VectorSource({
        features: new GeoJSON().readFeatures(geojson, {
            featureProjection: app.map.getView().getProjection()
        })
    });

    const count = app.source.getFeatures().length;
    // const geometryColumn = app.cache.table.columns.find(column => column.name === app.cache.table.geometry);
    const labelColumn = 'label';

    const layer = new VectorLayer({
        renderMode: count > 500 ? 'image' : 'vector',
        source: app.source,
        style: (feature, resolution) => {
            return styleFunction(feature, labelColumn, resolution);
        }
    });

    map.addLayer(layer);

    // if (geometryColumn.type === 'point' && count > 1000) {
    //     const cluster = new Cluster({
    //         source: app.source
    //     });

    //     layer.setStyle((feature, resolution) => {
    //         const features = feature.get('features');

    //         if (features.length > 1) {
    //             return styleClusterFunction(features.length);
    //         } else {
    //             return styleFunction(features[0], labelColumn, resolution);
    //         }
    //     });
    //     layer.setSource(cluster);
    // }

    if (window.location.hash === '') {
        layer.once('render', () => {
            app.map.getView().fit(layer.getSource().getExtent());
        });
    }

    app.source.on('removefeature', event => {
        const feature = event.feature;
        const id = feature.getId();

        Records.delete(id).then(data => console.log(data));
    });

    return layer;
}
