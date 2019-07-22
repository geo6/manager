'use strict';

import 'ol/ol.css';
import 'sidebar-v2/css/ol3-sidebar.css';

import '../sass/map.scss';

import app from './app';

import Cache from './cache';
import initMap from './map/init';
import { add as initSelect } from './map/interaction/select';
import initLayer from './map/layer/layer';
import initHighlightLayer from './map/layer/highlight';
import initNewLayer from './map/layer/new';
import initInfo from './map/feature/info/init';
import initEdit from './map/feature/edit/init';
import initNew from './map/feature/new/init';
import initFilter from './filter/init';
import Records from './Records';

require('sidebar-v2/js/jquery-sidebar.js');

(function () {
    app.mode = 'map';

    $('#map').height(
        $(window).height() - $('body > header > nav.navbar').outerHeight()
    );
    $(window).on('resize', () => {
        $('#map').height(
            $(window).height() - $('body > header > nav.navbar').outerHeight()
        );
    });

    app.baselayers = window.baselayers || [];
    delete window.baselayers;

    app.custom = window.custom || 'default';
    delete window.custom;

    app.thematic = window.thematic;
    delete window.thematic;

    app.cache = new Cache();

    app.sidebar = $('#sidebar').sidebar();

    app.map = initMap();

    Promise.all([
        fetch(`/app/manager/test/api/db/table`).then(response =>
            response.json()
        ),
        Records.getAll()
    ]).then(data => {
        app.cache.setTable(data[0]);
        app.layers.layer = initLayer(app.map, data[1]);

        initSelect(app.map, app.layers.layer);
    });

    app.layers.highlight = initHighlightLayer(app.map);
    app.layers.new = initNewLayer(app.map);

    initFilter();
    initInfo();
    initEdit();
    initNew();
})();
