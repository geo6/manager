import { Feature, FeatureCollection } from 'geojson';

export const Object = {
  async getAll (): Promise<FeatureCollection> {
    const response = await fetch('/api/object');

    if (!response.ok) {
      throw new Error(response.statusText);
    }

    const json = await response.json() as FeatureCollection;

    return json;
  },

  async get (id: string | number): Promise<Feature> {
    const response = await fetch(`/api/object/${id}`);

    if (!response.ok) {
      throw new Error(response.statusText);
    }

    const json = await response.json() as Feature;

    return json;
  },

  async delete (id: string | number): Promise<void> {
    const response = await fetch(`/api/object/${id}`, {
      method: 'DELETE'
    });

    if (!response.ok) {
      throw new Error(response.statusText);
    }
  },

  async update (id: string | number, data = {}, patch = false): Promise<Feature> {
    const response = await fetch(`/api/object/${id}`, {
      body: JSON.stringify(data),
      headers: {
        'Content-Type': 'application/json'
      },
      method: patch ? 'PATCH' : 'PUT'
    });

    if (!response.ok) {
      throw new Error(response.statusText);
    }

    const json = await response.json() as Feature;

    return json;
  },

  async insert (data = {}): Promise<Feature> {
    const response = await fetch('/api/object', {
      body: JSON.stringify(data),
      headers: {
        'Content-Type': 'application/json'
      },
      method: 'POST'
    });

    if (!response.ok) {
      throw new Error(response.statusText);
    }

    const json = await response.json() as Feature;

    return json;
  }
};
