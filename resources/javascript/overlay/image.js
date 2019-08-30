'use strict';

export default function (href, width, height) {
    const div = document.createElement('div');

    div.className = 'overlay-image';
    div.style.backgroundImage = 'url(' + href + ')';

    determineImageOverlaySize(width, height, div);

    document.querySelector('.overlay').appendChild(div);
}

function determineImageOverlaySize (width, height, element) {
    const w = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
    const h = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);

    if (width > w || height > h) {
        element.style.backgroundSize = 'contain';
    }
}
