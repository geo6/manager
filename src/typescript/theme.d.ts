/* eslint-disable no-unused-vars */

declare namespace Theme {
  type Style = {
    'marker-color'?: string;
    'marker-size'?: number;
    'marker-symbol'?: string;
    'stroke'?: string;
    'stroke-width'?: number;
    'stroke-opacity'?: number;
    'fill'?: string;
    'fill-opacity'?: number;
  };

  type Config = {
    [column: string]: {
      [value: string]: Theme.Style
    }
  }
}
