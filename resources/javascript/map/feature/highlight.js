'use strict';

import app from '../../app';

export default function (feature) {
    app.layers.highlight.getSource().addFeature(feature);
}
