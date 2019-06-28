'use strict';

export default function () {
    document
        .getElementById('infos-details-btn-locate')
        .addEventListener('click', () => {
            window.app.map
                .getView()
                .fit(window.app.layers.highlight.getSource().getExtent(), {
                    maxZoom: 20
                });
        });
}
