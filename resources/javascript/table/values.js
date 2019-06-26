'use strict';

import valueVarchar from '../value/varchar';

export default function () {
    const tds = document.querySelectorAll('#table-wrapper > table > tbody td');

    tds.forEach(td => {
        const value = td.innerText;

        if (value !== 'NULL') {
            const content = valueVarchar(value);
            if (typeof content === 'object') {
                td.innerHTML = null;
                td.append(content);
            } else {
                td.innerHTML = content;
            }
        }
    });
}
