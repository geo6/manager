import { Fill, RegularShape, Stroke } from 'ol/style';

export function star (stroke: Stroke, fill: Fill, radius: number): RegularShape {
  return new RegularShape({
    fill,
    stroke,
    points: 5,
    radius,
    radius2: (radius / 2),
    angle: 0
  });
}
