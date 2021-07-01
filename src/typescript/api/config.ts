export const Config = {
  async get (key?: string): Promise<any> {
    const response = await fetch(typeof key === 'undefined' ? '/api/config' : `/api/config/${key}`);

    if (!response.ok) {
      throw new Error(response.statusText);
    }

    const json = await response.json();

    return json;
  }
};
