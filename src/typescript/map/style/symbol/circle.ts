import { Circle, Fill, RegularShape, Stroke } from 'ol/style';

export function circle (stroke: Stroke, fill: Fill, radius: number): RegularShape {
  return new Circle({
    fill: fill,
    stroke: stroke,
    radius
  });
}
