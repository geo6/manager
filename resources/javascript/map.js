'use strict';

import 'ol/ol.css';
import 'sidebar-v2/css/ol3-sidebar.css';

import '../sass/map.scss';

import Cache from './cache';
import initMap from './map/init';
import initLayer from './map/layer/layer';
import initHighlightLayer from './map/layer/highlight';
import initFeatureInfoUI from './map/feature/info';
import initFilter from './filter/init';
import Records from './Records';

require('sidebar-v2/js/jquery-sidebar.js');

window.app = {
    cache: null,
    custom: null,
    highlightLayer: null,
    layer: null,
    map: null,
    sidebar: null,
    source: null
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
        window.app.layer = initLayer(window.app.map, data[1]);
    });

    window.app.highlightLayer = initHighlightLayer(window.app.map);

    initFeatureInfoUI();
    initFilter();
})();
