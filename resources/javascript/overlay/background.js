'use strict';

export default function () {
    document.body.style.overflow = 'hidden';

    const div = document.createElement('div');

    div.className = 'overlay';
    div.addEventListener('click', () => {
        document.body.style.overflow = '';

        div.remove();
    });

    document.body.appendChild(div);
}
