'use strict';

import app from '../app';

export function eventKey (element) {
    return element.addEventListener('change', event => {
        const selectElement = event.target;
        const optionElements = selectElement.options;
        const index = selectElement.selectedIndex;
        const { column, datatype } = optionElements[index].dataset;
        const divElement = element.closest('.row');
        const inputValueElement = divElement.querySelector('input[name=value]');

        resetValue(divElement);

        if (typeof datatype !== 'undefined') {
            inputValueElement.setAttribute('placeholder', datatype);

            updateOperationList(divElement, datatype);

            if (column === app.thematic.column) {
                inputValueElement.setAttribute('type', 'text');
                inputValueElement.setAttribute('list', 'filter-value-thematic');
            } else if (datatype === 'integer') {
                inputValueElement.setAttribute('type', 'number');
            } else if (datatype === 'boolean') {
                displayValueBoolean(divElement);
            } else {
                inputValueElement.setAttribute('type', 'text');
            }
        } else {
            divElement
                .querySelector('input[name=value]')
                .removeAttribute('placeholder');

            inputValueElement.setAttribute('type', 'text');
        }
    });
}

export function eventOperation (element) {
    return element.addEventListener('change', event => {
        const selectOpElement = event.target;
        const divRowElement = element.closest('.row');
        const inputValueElement = divRowElement.querySelector('input[name=value]');
        const divValueColElement = inputValueElement.closest('.col');

        if (['null', 'nnull'].indexOf(selectOpElement.value) !== -1) {
            inputValueElement.setAttribute('value', '');
            inputValueElement.setAttribute('disabled', '');

            divValueColElement.style.display = 'none';
            element.parentElement.className = 'col-8';
        } else {
            inputValueElement.removeAttribute('disabled');

            divValueColElement.style.display = null;
            element.parentElement.className = 'col-3';
        }
    });
}

function updateOperationList (div, datatype) {
    if (
        ['character varying', 'varchar', 'character', 'char', 'text'].indexOf(
            datatype
        ) !== -1
    ) {
        div.querySelectorAll(
            'select[name=operation] > option[value=like], select[name=operation] > option[value=nlike]'
        ).forEach(option => {
            option.removeAttribute('disabled');
        });
    } else {
        div.querySelectorAll(
            'select[name=operation] > option[value=like], select[name=operation] > option[value=nlike]'
        ).forEach(option => {
            option.setAttribute('disabled', '');
        });
    }
}

function resetValue (div) {
    const input = div.querySelector('input[name=value]');

    div.querySelectorAll('input[name=value], select[name=value]').forEach(
        element => {
            element.removeAttribute('list');
            element.setAttribute('hidden', '');
            element.setAttribute('disabled', '');
        }
    );

    input.removeAttribute('hidden');
    input.removeAttribute('disabled');
}

function displayValueBoolean (div) {
    const input = div.querySelector('input[name=value]');
    const select = div.querySelector('.filter-value-boolean');

    resetValue(div);

    input.setAttribute('hidden', '');
    input.setAttribute('disabled', '');

    select.removeAttribute('hidden');
    select.removeAttribute('disabled');
}
