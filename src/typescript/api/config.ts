namespace API {
  export class Config {
    static async get (key?: string): Promise<any> {
      const response = await fetch(typeof key === 'undefined' ? '/api/config' : `/api/config/${key}`);

      if (response.ok !== true) {
        throw response.statusText;
      }

      const json = await response.json();

      return json;
    }
  }
}
