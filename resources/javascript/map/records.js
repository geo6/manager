'use strict';

export default function () {
    const promise = fetch('/app/manager/test/api/db/records')
        .then(response => {
            return response.json();
        });

    return promise;
}
