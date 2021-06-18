import { Fill, RegularShape, Stroke } from 'ol/style';

export function plus (stroke: Stroke, fill: Fill, radius: number): RegularShape {
  return new RegularShape({
    fill,
    stroke,
    points: 4,
    radius,
    radius2: 0,
    angle: 0
  });
}
