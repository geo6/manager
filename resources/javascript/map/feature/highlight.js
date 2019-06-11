'use strict';

export default function (feature) {
    window.app.highlightLayer.getSource().addFeature(feature);
}
