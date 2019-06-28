'use strict';

import link from './varchar-link';

export default function (value) {
    try {
        // eslint-disable-next-line no-unused-vars
        const url = new URL(value);
        return link(value);
    } catch (_) {
        return value;
    }
}
