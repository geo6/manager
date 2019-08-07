'use strict';

import Collection from 'ol/Collection';

import initSelectionLayer from './layer/selection';

export default class {
    constructor (map) {
        this.layer = initSelectionLayer(map);

        this.cursor = 0;
    }

    setFeatures (features) {
        this.clear();

        if (features instanceof Collection) {
            features = features.getArray();
        }

        return this.layer.getSource().addFeatures(features);
    }

    getFeatures () {
        return this.layer.getSource().getFeatures();
    }

    getFeature (i) {
        const features = this.layer.getSource().getFeatures();
        return features[i];
    }

    clear () {
        this.cursor = 0;
        this.layer.getSource().clear();

        return this.getFeatures();
    }

    prev () {
        if (this.cursor <= 0) {
            return null;
        }

        this.cursor--;
        return this.current();
    }

    current () {
        return this.getFeature(this.cursor);
    }

    next () {
        if (this.cursor >= this.getFeatures().length) {
            return null;
        }

        this.cursor++;
        return this.current();
    }
}
