'use strict';

import displayRecord from './display';
import Form from './Form';

export default function (features, current) {
    const count = features.getLength();

    document.getElementById('info-list').innerText = `${current + 1}/${count}`;

    Form.disable();

    if (count === 1) {
        document.getElementById(
            'infos-list-btn-prev'
        ).parentElement.hidden = true;
    } else {
        document
            .getElementById('infos-list-btn-prev')
            .parentElement.removeAttribute('hidden');

        if (current === 0) {
            document
                .getElementById('infos-list-btn-prev')
                .classList.add('disabled');
            document.getElementById('infos-list-btn-prev').disabled = true;
        } else {
            document
                .getElementById('infos-list-btn-prev')
                .classList.remove('disabled');
            document.getElementById('infos-list-btn-prev').disabled = false;

            document.getElementById('infos-list-btn-prev').addEventListener(
                'click',
                () => {
                    window.app.highlightLayer.getSource().clear();

                    displayRecord(features, current - 1);
                },
                { once: true }
            );
        }

        if (current + 1 >= count) {
            document
                .getElementById('infos-list-btn-next')
                .classList.add('disabled');
            document.getElementById('infos-list-btn-next').disabled = true;
        } else {
            document
                .getElementById('infos-list-btn-next')
                .classList.remove('disabled');
            document.getElementById('infos-list-btn-next').disabled = false;

            document.getElementById('infos-list-btn-next').addEventListener(
                'click',
                () => {
                    window.app.highlightLayer.getSource().clear();

                    displayRecord(features, current + 1);
                },
                { once: true }
            );
        }
    }
}
