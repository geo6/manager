'use strict';

import { Fill, Stroke, Circle, Style, Text } from 'ol/style';

export default function (size) {
    return new Style({
        image: new Circle({
            fill: new Fill({
                color: '#3399CC'
            }),
            radius: 10,
            stroke: new Stroke({
                color: '#fff'
            })
        }),
        text: new Text({
            fill: new Fill({
                color: '#fff'
            }),
            stroke: new Stroke({
                color: 'rgba(0, 0, 0, 0.6)',
                width: 3
            }),
            text: size.toString()
        })
    });
}
