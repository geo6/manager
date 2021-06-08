import Geometry from 'ol/geom/Geometry';

export default function geometryToHTML (geometry: Geometry): HTMLElement[] {
  const type = geometry.getType();

  const span = document.createElement('span');
  span.innerHTML = `<i class="fas fa-pencil-ruler"></i> ${type}`;

  return [span];
}
