'use strict';

export default function (value) {
    if (value === true) {
        return '<i class="far fa-fw fa-check-circle text-success"></i>';
    } else {
        return '<i class="far fa-fw fa-times-circle text-danger"></i>';
    }
}
