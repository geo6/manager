import Overlay from '@geo6/overlay-image-preview';
import GeoJSON from 'ol/format/GeoJSON';
import Style from 'ol/style/Style';

import * as API from '../api';
import { styleFeature } from '../map/style';
import { FormModal } from '../modal/form';

(async function () {
  let id: number | string | null = null;

  const buttonForm = document.getElementById('btn-modal-form-edit') as HTMLButtonElement;
  const modalForm = new FormModal(document.getElementById('modal-form'));

  const theme = await API.Config.get('theme');
  const collection = await API.Object.getAll();

  document.querySelectorAll('.table-responsive > table > tbody > tr').forEach((element) => {
    const row = element as HTMLTableRowElement;

    const feature = collection.features.find(feature => feature.id.toString() === row.dataset.id);

    const style = styleFeature(theme, (new GeoJSON()).readFeature(feature), 0);
    let icon!: HTMLCanvasElement | HTMLVideoElement | HTMLImageElement;
    if (Array.isArray(style) === true) {
      icon = style[0].getImage().clone().getImage(1);
    } else {
      icon = (style as Style).getImage().clone().getImage(1);
    }
    row.querySelectorAll('td')[0].innerHTML = '';
    row.querySelectorAll('td')[0].append(icon);

    row.addEventListener('click', () => {
      const activeRow = document.querySelector('.table-responsive > table > tbody > tr.table-warning');
      if (activeRow !== null) {
        activeRow.classList.remove('table-warning');
      }

      if (id !== row.dataset.id) {
        row.classList.add('table-warning');
        id = row.dataset.id;
        buttonForm.disabled = false;

        modalForm.setId(id);
      } else {
        id = null;
        buttonForm.disabled = true;
      }
    });
  });

  document.querySelectorAll('a.thumbnail-link').forEach((element) => {
    const id = element.closest('tr').dataset.id;

    element.addEventListener('click', async (event) => {
      event.preventDefault();

      const response = await fetch(`/api/file/info/${id}/photo`);
      const info = await response.json();

      const overlay = new Overlay(element as HTMLAnchorElement, () => {
        return info.filename;
      });
      overlay.open();
    });
  });
})();
