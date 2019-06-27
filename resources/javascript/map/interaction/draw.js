'use strict';

import Draw from 'ol/interaction/Draw';
import NewForm from '../feature/new/Form';

function ondrawend (event) {
    remove(window.app.map);

    document
        .getElementById('new')
        .querySelector('.list-group > button.active')
        .classList.remove('active');

    NewForm.enable();
}

export function add (map, source, type) {
    const draw = new Draw({
        source: source,
        stopClick: true,
        type: type
    });

    draw.on('drawend', event => ondrawend(event));

    map.addInteraction(draw);

    return draw;
}

export function remove (map) {
    map.getInteractions().forEach(interaction => {
        if (interaction instanceof Draw) {
            map.removeInteraction(interaction);
        }
    });
}
