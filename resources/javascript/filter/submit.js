'use strict';

import app from '../app';

import SubmitForMap from './submit/map';

export default function (form) {
    const data = Object.fromEntries(new FormData(form).entries());

    let filter = `${data.key} ${data.operation}`;

    if (typeof data.value !== 'undefined') {
        filter += ` ${data.value}`;
    }

    switch (app.mode) {
    case 'map':
        SubmitForMap(app, filter);
        break;
    case 'table':
        window.location.href = document.location.origin + document.location.pathname + `?filter=${filter}`;
        break;
    }
}
