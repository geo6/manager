import GeoJSON from 'ol/format/GeoJSON';
import { Style } from 'ol/style';

import * as API from '../api';
import { styleFeature } from '../map/style';

import { table, theme } from '.';

export default async function (): Promise<void> {
  const collection = await API.Object.getAll();

  document.querySelectorAll('.table-responsive > table > tbody > tr').forEach((element) => {
    const row = element as HTMLTableRowElement;

    const feature = collection.features.find(feature => feature.id.toString() === row.dataset['id']);

    const style = styleFeature(theme, table, (new GeoJSON()).readFeature(feature), 0);
    let icon!: HTMLCanvasElement | HTMLVideoElement | HTMLImageElement;
    if (Array.isArray(style) && style.length > 0) {
      icon = style[0].getImage().clone().getImage(1);
    } else {
      icon = (style as Style).getImage().clone().getImage(1);
    }
    row.querySelectorAll('td')[0].innerHTML = '';
    row.querySelectorAll('td')[0].append(icon);
  });
}
