'use strict';

import '../sass/table.scss';

import Cache from './cache';
import initFilter from './filter/init';

window.app = {
    cache: null,
    custom: null,
    thematic: null
};

(function () {
    window.app.custom = window.custom || 'default';
    delete window.custom;

    window.app.thematic = window.thematic;
    delete window.thematic;

    window.app.cache = new Cache();

    document
        .getElementById('table-wrapper')
        .addEventListener('scroll', function () {
            this.querySelector('thead').style.transform =
                'translateY(' + (this.scrollTop - 1) + 'px)';
        });

    initFilter();
})();
