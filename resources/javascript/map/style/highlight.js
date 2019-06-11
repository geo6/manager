'use strict';

import { Fill, Stroke, Circle, Style } from 'ol/style';

export default function () {
    const fill = new Fill({
        color: 'rgba(255,0,0,0.4)'
    });
    const stroke = new Stroke({
        color: '#3399CC',
        width: 1.25
    });

    return new Style({
        fill: fill,
        image: new Circle({
            fill: fill,
            radius: 15,
            stroke: stroke
        }),
        stroke: stroke
    });
}
