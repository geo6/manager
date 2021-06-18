import { Fill, RegularShape, Stroke } from 'ol/style';

export function triangle (stroke: Stroke, fill: Fill, radius: number, rotation: number): RegularShape {
  return new RegularShape({
    fill,
    stroke,
    points: 3,
    radius,
    rotation,
    angle: 0
  });
}
