'use strict';

import app from './app';

class Cache {
    constructor () {
        if (app.custom !== null) {
            this.storageKey = `manager.${app.custom}.cache`;
        } else {
            this.storageKey = 'manager.cache';
        }

        const storage = localStorage.getItem(this.storageKey);
        if (storage !== null) {
            $.extend(this, JSON.parse(storage));
        }
    }

    setBaselayer (name) {
        this.baselayer = name;
        this.save();
    }

    setMap (zoom, longitude, latitude) {
        this.map = {
            latitude: latitude,
            longitude: longitude,
            zoom: zoom
        };
        this.save();
    }

    setTable (table) {
        this.table = table;
        this.save();
    }

    save () {
        localStorage.setItem(
            this.storageKey,
            JSON.stringify({
                baselayer: this.baselayer || null,
                map: this.map || null,
                table: this.table || null
            })
        );
    }
}

export { Cache as default };
