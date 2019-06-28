'use strict';

import highlight from './highlight';
import InfoForm from './edit/Form';
import Table from './info/Table';
import list from './list';

export default function (features, current) {
    if (typeof features.item(current) === 'undefined') {
        throw new Error(
            `Invalid feature index ${current} (length: ${features.getLength()})`
        );
    }

    const feature = features.item(current);

    list(features, current);

    Table.fill(feature);
    InfoForm.fill(feature);

    highlight(feature);
}
