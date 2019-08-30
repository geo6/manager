'use strict';

import 'ol/ol.css';
import 'sidebar-v2/css/ol3-sidebar.css';

import '../sass/map.scss';

import app from './app';

import Cache from './cache';
import initMap from './map/init';
import SelectInteraction from './map/interaction/Select';
import initLayer from './map/layer/layer';
import initNewLayer from './map/layer/new';
import initInfo from './map/feature/info/init';
import initEdit from './map/feature/edit/init';
import initNew from './map/feature/new/init';
import Selection from './map/selection';
import initFilter from './filter/init';
import initOverlay from './overlay/init';
import Records from './Records';

require('sidebar-v2/js/jquery-sidebar.js');

export function init (custom, baselayers, thematic) {
    app.mode = 'map';

    $('#map').height(
        $(window).height() - $('body > header > nav.navbar').outerHeight()
    );
    $(window).on('resize', () => {
        $('#map').height(
            $(window).height() - $('body > header > nav.navbar').outerHeight()
        );
    });

    app.baselayers = baselayers || [];
    app.custom = custom || 'default';
    app.thematic = thematic;

    app.cache = new Cache();

    app.sidebar = $('#sidebar').sidebar();

    app.map = initMap();

    Promise.all([
        fetch(`/app/manager/${app.custom}/api/db/table`).then(response =>
            response.json()
        ),
        Records.getAll(app.custom)
    ]).then(data => {
        app.cache.setTable(data[0]);
        app.layers.layer = initLayer(app.map, data[1]);

        app.interaction.select = new SelectInteraction(app.map);
        app.selection = new Selection(app.map);

        initEdit();
    });

    app.layers.new = initNewLayer(app.map);

    initFilter();
    initOverlay();
    initInfo();
    initNew();
}
