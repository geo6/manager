'use strict';

export default function (value) {
    const url = new URL(value);

    const a = document.createElement('a');
    a.href = url.href;
    a.target = '_blank';
    a.style.textDecoration = 'none';
    a.innerHTML = '<span class="text-nowrap"><i class="fas fa-external-link-alt"></i> ' + url.hostname + '</span>';

    return a.outerHTML;
}
