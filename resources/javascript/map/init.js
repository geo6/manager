'use strict';

import { defaults as defaultControls, Attribution, ScaleLine } from 'ol/control.js';
import Map from 'ol/Map';
import TileLayer from 'ol/layer/Tile';
import OSM from 'ol/source/OSM';
import View from 'ol/View';

import singleclick from './singleclick';
import initPermalink from './permalink';

export default function () {
    const attribution = new Attribution({
        collapsible: false
    });
    const scaleLine = new ScaleLine();

    const map = new Map({
        controls: defaultControls({ attribution: false }).extend([attribution, scaleLine]),
        layers: [
            new TileLayer({
                source: new OSM()
            })
        ],
        target: 'map',
        view: new View({
            center: [0, 0],
            zoom: 2
        })
    });

    map.on('singleclick', (event) => {
        return singleclick(event);
    });

    initPermalink(map);

    return map;
}
