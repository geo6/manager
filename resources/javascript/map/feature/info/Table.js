'use strict';

import { toLonLat } from 'ol/proj';

import app from '../../../app';

import valueNull from './value/null';
import valueBoolean from './value/boolean';
import valueVarchar from './value/varchar';

export default class Table {
    static getElement () {
        return document.getElementById('info-table');
    }

    static isActive () {
        return Table.getElement().hidden !== true;
    }

    static fill (feature) {
        const properties = feature.getProperties();
        const geometryName = feature.getGeometryName();

        Object.keys(properties).filter(key => {
            return [geometryName, 'updateuser', 'updatetime'].indexOf(key) === -1;
        }).map(key => {
            const td = Table.getElement().querySelector(
                `table > tbody > tr > td[data-column="${key}"]`
            );
            const column = app.cache.table.columns.find(column => column.name === key);

            if (td !== null) {
                td.innerText = '';

                if (key === app.thematic.column) {
                    td.innerHTML = Table.renderThematic(properties[key], td.dataset.datatype);
                } else if (key.indexOf('.') === -1 && column.reference !== null) {
                    td.innerHTML = Table.renderForeignKey(properties[key], td.dataset.datatype, column.reference);
                } else {
                    td.innerHTML = Table.renderValue(properties[key], td.dataset.datatype);
                }
            } else {
                throw new Error(`No row in table for properties "${key}".`);
            }
        });

        Table.displayId(feature.getId());
        Table.displayGeometry(feature.getGeometry());
        Table.displayLastUpdate(
            'updateuser' in properties ? properties.updateuser : null,
            'updatetime' in properties ? properties.updatetime : null
        );
    }

    static displayId (id) {
        document.getElementById('info-details-id').innerText = id;
    }

    static displayGeometry (geometry) {
        const type = geometry.getType();

        document.getElementById('info-details-geometry').innerText = type;

        switch (type) {
        case 'Point':
            const coordinates = toLonLat(geometry.getCoordinates());
            const lng = Math.round(coordinates[0] * 1000000) / 1000000;
            const lat = Math.round(coordinates[1] * 1000000) / 1000000;

            document.getElementById(
                'info-details-geometry'
            ).innerText += `: ${lng}, ${lat}`;
            break;
        case 'LineString':
        case 'MultiLineString':
            const length = geometry.getLength();

            document.getElementById(
                'info-details-geometry'
            ).innerText += `<br>Length: ${length} m.`;
            break;
        case 'Polygon':
        case 'MultiPolygon':
            const area = geometry.getArea();

            document.getElementById(
                'info-details-geometry'
            ).innerText += `<br>Area: ${area} m&sup2;`;
            break;
        }
    }

    static displayLastUpdate (user, time) {
        const userElement = document.getElementById('info-details-updateuser');
        const timeElement = document.getElementById('info-details-updatetime');

        userElement.hidden = true;
        userElement.querySelector('span').innerText = '';

        if (user !== null) {
            userElement.hidden = null;
            userElement.querySelector('span').innerText = user;
        }

        timeElement.hidden = true;
        timeElement.querySelector('time').innerText = '';
        timeElement.querySelector('time').dateTime = null;

        if (time !== null) {
            timeElement.hidden = null;
            timeElement.querySelector('time').innerText = time;
            timeElement.querySelector('time').dateTime = time;
        }
    }

    static renderValue (value, datatype) {
        if (value === null) {
            return valueNull();
        } else if (datatype === 'boolean') {
            return valueBoolean(value);
        } else {
            return valueVarchar(value);
        }
    }

    static renderThematic (value, datatype) {
        let color = app.thematic.default;

        if (Object.keys(app.thematic.values).indexOf(value) !== -1) {
            color = app.thematic.values[value].color;
        }

        return `<span style="color: ${color};"><i class="fas fa-circle"></i></span> ` + Table.renderValue(value, datatype);
    }

    static renderForeignKey (value, datatype, reference) {
        const aElement = document.createElement('a');
        aElement.innerHTML = Table.renderValue(value, datatype);
        aElement.setAttribute('href', `#info-table-${reference.table}`);
        aElement.addEventListener('click', event => {
            event.preventDefault();
            document.getElementById(`info-table-${reference.table}`).scrollIntoView();
        });

        return aElement.outerHTML;
    }
}
