'use strict';

import 'ol/ol.css';
import 'sidebar-v2/css/ol3-sidebar.css';

import '../sass/map.scss';

import Cache from './cache';
import initMap from './map/init';
import initSelect from './map/select';
import initLayer from './map/layer/layer';
import initHighlightLayer from './map/layer/highlight';
import initFeatureInfoUI from './map/feature/info';
import initFilter from './filter/init';
import Records from './Records';

require('sidebar-v2/js/jquery-sidebar.js');

window.app = {
    cache: null,
    custom: null,
    layers: {
        highlight: null,
    layer: null,
        new: null
    },
    map: null,
    sidebar: null,
    source: null,
    thematic: null
};

(function () {
    $('#map').height(
        $(window).height() - $('body > header > nav.navbar').outerHeight()
    );
    $(window).on('resize', () => {
        $('#map').height(
            $(window).height() - $('body > header > nav.navbar').outerHeight()
        );
    });

    window.app.custom = window.custom || 'default';
    delete window.custom;

    window.app.thematic = window.thematic;
    delete window.thematic;

    window.app.cache = new Cache();

    window.app.sidebar = $('#sidebar').sidebar();

    window.app.map = initMap();

    Promise.all([
        fetch(`/app/manager/test/api/db/table`).then(response =>
            response.json()
        ),
        Records.getAll()
    ]).then(data => {
        window.app.cache.setTable(data[0]);
        window.app.layers.layer = initLayer(window.app.map, data[1]);

        initSelect(window.app.map, window.app.layers.layer);
    });

    window.app.layers.highlight = initHighlightLayer(window.app.map);

    initFeatureInfoUI();
    initFilter();
})();
