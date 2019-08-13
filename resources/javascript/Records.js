'use strict';

export default class Records {
    static async getAll (config, filter) {
        const response = await fetch(
            typeof filter === 'undefined'
                ? `/app/manager/${config}/api/db/records`
                : `/app/manager/${config}/api/db/records?filter=${filter}`
        );

        return response.json();
    }

    static async insert (config, data) {
        const response = await fetch(`/app/manager/${config}/api/db/records`, {
            body: JSON.stringify(data),
            headers: {
                'Content-Type': 'application/json'
            },
            method: 'POST'
        });

        if (response.ok !== true) {
            try {
                const json = await response.json();

                return Promise.reject(new Error(json.error));
            } catch (e) {
                return Promise.reject(new Error(response.statusText));
            }
        }

        return response.json();
    }

    static async get (config, id) {
        const response = await fetch(
            `/app/manager/${config}/api/db/records/${id}`
        );

        if (response.ok !== true) {
            try {
                const json = await response.json();

                return Promise.reject(new Error(json.error));
            } catch (e) {
                return Promise.reject(new Error(response.statusText));
            }
        }

        return response.json();
    }

    static async update (config, id, data) {
        const response = await fetch(
            `/app/manager/${config}/api/db/records/${id}`,
            {
                body: JSON.stringify(data),
                headers: {
                    'Content-Type': 'application/json'
                },
                method: 'PUT'
            }
        );

        if (response.ok !== true) {
            try {
                const json = await response.json();

                return Promise.reject(new Error(json.error));
            } catch (e) {
                return Promise.reject(new Error(response.statusText));
            }
        }

        return response.json();
    }

    static async delete (config, id) {
        const response = await fetch(
            `/app/manager/${config}/api/db/records/${id}`,
            {
                method: 'DELETE'
            }
        );

        if (response.ok !== true) {
            try {
                const json = await response.json();

                return Promise.reject(new Error(json.error));
            } catch (e) {
                return Promise.reject(new Error(response.statusText));
            }
        }

        return response.json();
    }
}
