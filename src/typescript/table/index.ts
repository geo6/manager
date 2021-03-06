import * as API from '../api';
import { FormModal } from './modal/FormModal';
import generateIcons from './icons';
import generateThumbnailLinks from './thumbnails';

export let table!: string;
export let theme!: Theme.Config;

(async () => {
  let id: number | string | null = null;

  const config = await API.Config.get();
  table = config.table;
  theme = config.theme;

  const buttonForm = document.getElementById('btn-modal-form-edit') as HTMLButtonElement;
  const buttonMap = document.getElementById('btn-link-to-map') as HTMLButtonElement;
  const modalForm = new FormModal(document.getElementById('modal-form'));

  document.querySelectorAll('.table-responsive > table > tbody > tr').forEach((element) => {
    const row = element as HTMLTableRowElement;

    row.addEventListener('click', () => {
      const activeRow = document.querySelector('.table-responsive > table > tbody > tr.table-warning');
      if (activeRow !== null) {
        activeRow.classList.remove('table-warning');
      }

      if (id !== row.dataset['id']) {
        row.classList.add('table-warning');
        id = row.dataset['id'];
        buttonForm.disabled = false;
        buttonMap.disabled = false;

        modalForm.setId(id);
      } else {
        id = null;
        buttonForm.disabled = true;
        buttonMap.disabled = true;
      }
    });
  });

  buttonMap.addEventListener('click', () => {
    if (id !== null) {
      document.location.href = `/map?id=${id}`;
    }
  });

  generateThumbnailLinks();
  await generateIcons();
})();
