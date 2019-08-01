'use strict';

import '../sass/table.scss';

import app from './app';

import Cache from './cache';
import initFilter from './filter/init';

export function init (custom, thematic) {
    app.mode = 'table';

    app.custom = custom || 'default';
    app.thematic = thematic;

    app.cache = new Cache();

    document
        .getElementById('table-wrapper')
        .addEventListener('scroll', function () {
            this.querySelector('thead').style.transform =
                'translateY(' + (this.scrollTop - 1) + 'px)';
        });

    initFilter();
}
