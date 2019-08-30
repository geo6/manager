'use strict';

export default function () {
    const div = document.createElement('div');

    div.className = 'overlay-loading';
    div.innerText = 'Loading...';

    document.querySelector('.overlay').appendChild(div);
}
