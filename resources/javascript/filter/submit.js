'use strict';

export default function (form) {
    const data = Array.from(new FormData(form));
    console.log(data);
}
