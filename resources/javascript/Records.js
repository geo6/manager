'use strict';

export default class Records {
    static async getAll () {
        const response = await fetch('/app/manager/test/api/db/records');

        return response.json();
    }

    static async get (id) {
        const response = await fetch(`/app/manager/test/api/db/records/${id}`);

        return response.json();
    }

    static async update (id, data) {
        const response = await fetch(`/app/manager/test/api/db/records/${id}`, {
            body: JSON.stringify(data),
            headers: {
                'Content-Type': 'application/json'
            },
            method: 'PUT'
        });

        return response.json();
    }

    static async delete (id) {
        const response = await fetch(`/app/manager/test/api/db/records/${id}`, {
            method: 'DELETE'
        });

        return response.json();
    }
}
