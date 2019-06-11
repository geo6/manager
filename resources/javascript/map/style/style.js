'use strict';

import { Fill, Stroke, Circle, Style, Text } from 'ol/style';
import TextPlacement from 'ol/style/TextPlacement';

export default function (feature, labelColumn) {
    const type = feature.getGeometry().getType();
    const label = feature.get(labelColumn);

    const fill = new Fill({
        color: 'rgba(255,255,255,0.4)'
    });
    const stroke = new Stroke({
        color: '#3399CC',
        width: 1.25
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
            radius: 5,
            stroke: stroke
        }),
        stroke: stroke,
        text: text
    });
}
