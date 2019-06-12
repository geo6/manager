'use strict';

export function eventKey (element) {
    return element.addEventListener('change', event => {
        const selectElement = event.target;
        const optionElements = selectElement.options;
        const index = selectElement.selectedIndex;
        const { datatype } = optionElements[index].dataset;
        const divElement = element.closest('.row');
        const inputValueElement = divElement.querySelector('input[name=value]');

        if (typeof datatype !== 'undefined') {
            inputValueElement.placeholder = datatype;

            if (datatype === 'integer') {
                inputValueElement.type = 'number';
            } else {
                inputValueElement.type = 'text';
            }
        } else {
            divElement
                .querySelector('input[name=value]')
                .removeAttribute('placeholder');

            inputValueElement.type = 'text';
        }
    });
}

export function eventOperation (element) {
    return element.addEventListener('change', event => {
        const selectOpElement = event.target;
        const divRowElement = element.closest('.row');
        const divValueColElement = divRowElement
            .querySelector('input[name=value]')
            .closest('.col');

        if (['null', 'nnull'].indexOf(selectOpElement.value) !== -1) {
            divValueColElement.style.display = 'none';
            element.parentElement.className = 'col-8';
        } else {
            divValueColElement.style.display = null;
            element.parentElement.className = 'col-3';
        }
    });
}
