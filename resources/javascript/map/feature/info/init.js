'use strict';

import app from '../../../app';

export default function () {
    document
        .getElementById('infos-details-btn-locate')
        .addEventListener('click', () => {
            app.map
                .getView()
                .fit(app.layers.highlight.getSource().getExtent(), {
                    maxZoom: 20
                });
        });
}
