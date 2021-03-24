import { FormModal } from '../modal/form';

(function () {
  let id: number | string | null = null;

  const buttonForm = document.getElementById('btn-modal-form-edit') as HTMLButtonElement;
  const modalForm = new FormModal(document.getElementById('modal-form'));

  document.querySelectorAll('.table-responsive > table > tbody > tr').forEach((element) => {
    const row = element as HTMLTableRowElement;

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
})();
