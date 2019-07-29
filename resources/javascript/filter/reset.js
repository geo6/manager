'use strict';

import app from '../app';

import ResetForMap from './submit/map';

export default function () {
    switch (app.mode) {
    case 'map':
        ResetForMap(app);
        break;
    case 'table':
        window.location.href = document.location.origin + document.location.pathname;
        break;
    }
}
