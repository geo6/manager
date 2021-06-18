import { Fill, RegularShape, Stroke } from 'ol/style';

export function square (stroke: Stroke, fill: Fill, radius: number): RegularShape {
  return new RegularShape({
    fill,
    stroke,
    points: 4,
    radius,
    angle: Math.PI / 4
  });
}
