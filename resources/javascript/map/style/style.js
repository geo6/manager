'use strict';

import { Fill, Stroke, Circle, Style, Text } from 'ol/style';
import TextPlacement from 'ol/style/TextPlacement';

import app from '../../app';

export default function (feature, labelColumn, resolution) {
    const zoom = app.map.getView().getZoomForResolution(resolution);
    const type = feature.getGeometry().getType();
    const label = feature.get(labelColumn);

    let symbol = {
        color: app.thematic.default
    };
    if (app.thematic.column !== null) {
        const value = feature.get(app.thematic.column);

        if (typeof app.thematic.values[value] !== 'undefined') {
            symbol = app.thematic.values[value];
        }
    }

    const fill = new Fill({
        color: symbol.color
    });
    const stroke = new Stroke({
        color: '#fff',
        width: 2
    });

    const text = new Text({
        fill: new Fill({
            color: '#fff'
        }),
        stroke: new Stroke({
            color: 'rgba(0, 0, 0, 0.6)',
            width: 3
        }),
        text: zoom >= 16 ? (label || null) : null
    });

    switch (type) {
    case 'Point':
    case 'MultiPoint':
        text.setOffsetY(15);
        break;
    case 'LineString':
    case 'MultiLineString':
        text.setPlacement(TextPlacement.LINE);
        break;
    }

    return new Style({
        fill: fill,
        image: new Circle({
            fill: fill,
            radius: 6,
            stroke: stroke
        }),
        stroke: stroke,
        text: text
    });
}
