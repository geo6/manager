'use strict';

export default function (feature) {
    window.app.layers.highlight.getSource().addFeature(feature);
}
