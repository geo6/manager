'use strict';

import { Fill, Stroke, Circle, Style, Text } from 'ol/style';
import TextPlacement from 'ol/style/TextPlacement';

export default function (feature, labelColumn) {
    const type = feature.getGeometry().getType();
    const label = feature.get(labelColumn);

    let symbol = {
        color: window.app.thematic.default
    };
    if (window.app.thematic.column !== null) {
        const value = feature.get(window.app.thematic.column);

        if (typeof window.app.thematic.values[value] !== 'undefined') {
            symbol = window.app.thematic.values[value];
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
        text: label || null
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
