'use strict';

import createOverlayBackground from './background';
import createOverlayLoading from './loading';
import createOverlayImage from './image';

/**
 * This group of "overlay" functions is based on the Image Overlay plugin for FilePond
 *
 * @see https://nielsboogaard.github.io/filepond-plugin-image-overlay/
 */
export default function () {
    document.querySelectorAll('[data-toggle="overlay"]').forEach(element => {
        element.addEventListener('click', event => {
            event.preventDefault();

            const href = event.target.getAttribute('href');

            createOverlayBackground();
            createOverlayLoading();

            const image = new Image();
            image.src = href;
            image.onload = () => {
                const width = image.naturalWidth;
                const height = image.naturalHeight;

                document.querySelector('.overlay-loading').remove();

                createOverlayImage(image.src, width, height);
            };
        });
    });
}
