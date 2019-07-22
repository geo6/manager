'use strict';

import '../sass/table.scss';

import app from './app';

import Cache from './cache';
import initFilter from './filter/init';

(function () {
    app.custom = window.custom || 'default';
    delete window.custom;

    app.thematic = window.thematic;
    delete window.thematic;

    app.cache = new Cache();

    document
        .getElementById('table-wrapper')
        .addEventListener('scroll', function () {
            this.querySelector('thead').style.transform =
                'translateY(' + (this.scrollTop - 1) + 'px)';
        });

    initFilter();
})();
