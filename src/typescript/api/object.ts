import { Feature, FeatureCollection } from 'geojson';

export namespace API {
  export class Object {
    static async getAll (): Promise<FeatureCollection> {
      const response = await fetch('/api/object');

      if (response.ok !== true) {
        throw response.statusText;
      }

      const json = await response.json() as FeatureCollection;

      return json;
    }

    static async get (id: string | number): Promise<Feature> {
      const response = await fetch(`/api/object/${id}`);

      if (response.ok !== true) {
        throw response.statusText;
      }

      const json = await response.json() as Feature;

      return json;
    }

    static async delete (id: string | number): Promise<void> {
      const response = await fetch(`/api/object/${id}`, {
        method: 'DELETE'
      });

      if (response.ok !== true) {
        throw response.statusText;
      }
    }

    static async update (id: string | number, data = {}, patch = false): Promise<Feature> {
      const response = await fetch(`/api/object/${id}`, {
        body: JSON.stringify(data),
        headers: {
          'Content-Type': 'application/json'
        },
        method: patch === true ? 'PATCH' : 'PUT'
      });

      if (response.ok !== true) {
        throw response.statusText;
      }

      const json = await response.json() as Feature;

      return json;
    }

    static async insert (data = {}): Promise<Feature> {
      const response = await fetch('/api/object', {
        body: JSON.stringify(data),
        headers: {
          'Content-Type': 'application/json'
        },
        method: 'POST'
      });

      if (response.ok !== true) {
        throw response.statusText;
      }

      const json = await response.json() as Feature;

      return json;
    }
  }
}
