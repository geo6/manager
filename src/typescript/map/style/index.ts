import { Feature } from 'ol';
import { Color, fromString } from 'ol/color';
import { ColorLike } from 'ol/colorlike';
import { FeatureLike } from 'ol/Feature';
import { Fill, Stroke, Circle, Style } from 'ol/style';
import { createDefaultStyle } from 'ol/style/Style';

import { circle, plus, square, star, times, triangle } from './symbol';

export function styleFeature (theme: Theme.Config, feature: FeatureLike, resolution: number): Style | Style[] {
  const properties = feature.getProperties();
  const type = feature.getGeometry().getType();

  let s: Theme.Style = {};
  Object.keys(theme).forEach((column) => {
    Object.keys(theme[column]).forEach((value) => {
      if (typeof properties[column] !== 'undefined' && properties[column] === value) {
        s = Object.assign(s, theme[column][value]);
      }
    });
  });

  return createStyle(type, s, resolution);
}

function createStyle (type: string, style: Theme.Style, resolution: number = 0): Style | Style[] {
  const defaultStyles = createDefaultStyle(new Feature(), resolution);

  let strokeColor = defaultStyles[0].getStroke().getColor();
  let strokeWidth = defaultStyles[0].getStroke().getWidth();
  let fillColor = defaultStyles[0].getFill().getColor();
  let markerRadius = (defaultStyles[0].getImage() as Circle).getRadius();
  let markerSymbol = null;

  switch (type) {
    case 'Point':
    case 'MultiPoint': {
      if (typeof style['marker-color'] !== 'undefined') {
        strokeColor = fromString(style['marker-color']);

        fillColor = [...strokeColor];
        fillColor[3] = 0.4;
      }
      if (typeof style['marker-size'] !== 'undefined') {
        markerRadius = style['marker-size'];
      }
      if (typeof style['marker-symbol'] !== 'undefined') {
        markerSymbol = style['marker-symbol'];
      }

      return marker(strokeColor, strokeWidth, fillColor, markerRadius, markerSymbol);
    }
    case 'LineString':
    case 'MultiLineString': {
      if (typeof style.stroke !== 'undefined') {
        strokeColor = fromString(style.stroke);
      }
      if (typeof style['stroke-opacity'] !== 'undefined') {
        strokeColor[3] = style['stroke-opacity'];
      }
      if (typeof style['stroke-width'] !== 'undefined') {
        strokeWidth = style['stroke-width'];
      }

      return new Style({
        stroke: new Stroke({
          color: strokeColor,
          width: strokeWidth
        })
      });
    }
    case 'Polygon':
    case 'MultiPolygon': {
      if (typeof style.stroke !== 'undefined') {
        strokeColor = fromString(style.stroke);
      }
      if (typeof style['stroke-opacity'] !== 'undefined') {
        strokeColor[3] = style['stroke-opacity'];
      }
      if (typeof style['stroke-width'] !== 'undefined') {
        strokeWidth = style['stroke-width'];
      }
      if (typeof style.fill !== 'undefined') {
        fillColor = fromString(style.fill);
      }
      if (typeof style['fill-opacity'] !== 'undefined') {
        fillColor[3] = style['fill-opacity'];
      }

      return new Style({
        fill: new Fill({
          color: fillColor
        }),
        stroke: new Stroke({
          color: strokeColor,
          width: strokeWidth
        })
      });
    }
    default: {
      return defaultStyles;
    }
  }
}

function marker (strokeColor: Color | ColorLike, strokeWidth: number, fillColor: Color | ColorLike, radius: number, symbol: string | null): Style {
  const stroke = new Stroke({ color: strokeColor, width: strokeWidth });
  const fill = new Fill({ color: fillColor });

  switch (symbol) {
    case 'circle': return new Style({ image: circle(stroke, fill, radius) });
    case 'plus': return new Style({ image: plus(stroke, fill, radius) });
    case 'square': return new Style({ image: square(stroke, fill, radius) });
    case 'star': return new Style({ image: star(stroke, fill, radius) });
    case 'times': return new Style({ image: times(stroke, fill, radius) });
    case 'triangle': return new Style({ image: triangle(stroke, fill, radius, 0) });
    case 'triangle-down': return new Style({ image: triangle(stroke, fill, radius, Math.PI) });
    case 'triangle-up': return new Style({ image: triangle(stroke, fill, radius, 0) });
    default: return new Style({ image: circle(stroke, fill, radius) });
  }
}
