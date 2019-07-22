'use strict';

import { eventKey, eventOperation } from './events';
import reset from './reset';
import submit from './submit';

export default function () {
    document
        .querySelectorAll('#modal-filter select[name=key]')
        .forEach(element => {
            eventKey(element);
            element.dispatchEvent(new Event('change'));
        });

    document
        .querySelectorAll('#modal-filter select[name=operation]')
        .forEach(element => eventOperation(element));

    document
        .querySelector('#modal-filter form')
        .addEventListener('submit', event => {
            event.preventDefault();

            submit(event.target);
        });

    document
        .querySelector('#modal-filter form')
        .addEventListener('reset', event => {
            reset(event.target);
        });
}
