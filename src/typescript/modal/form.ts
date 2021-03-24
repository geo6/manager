import { Modal } from 'bootstrap';
import { API } from '../api/object';

export class FormModal {
  private id!: number | string;
  private modal: Modal;
  private inputs: (HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement)[];
  private deleteButton: HTMLButtonElement;
  private saveButton: HTMLButtonElement;

  constructor (element: HTMLElement) {
    this.modal = new Modal(element);
    this.inputs = Array.from(element.querySelectorAll('form input, form select, form textarea'));
    this.deleteButton = document.getElementById('modal-form-delete') as HTMLButtonElement;
    this.saveButton = document.getElementById('modal-form-save') as HTMLButtonElement;

    element.addEventListener('show.bs.modal', async () => {
      this.inputs.forEach((element) => {
        const input = element as HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;
        input.value = '';
      });

      if (typeof this.id !== 'undefined' && this.id !== null) {
        this.inputs.forEach((element) => {
          const input = element as HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;
          input.disabled = true;
        });

        const json = await API.Object.get(this.id);

        this.inputs.forEach((element) => {
          const input = element as HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;
          input.value = json.properties[input.name];
          input.disabled = false;
        });

        console.log(json);
      }
    });

    this.deleteButton.addEventListener('click', () => this.delete());
    this.saveButton.addEventListener('click', () => this.submit());
  }

  setId (id: string | number): void {
    this.id = id;
  }

  private async submit () {
    const data: { [key: string]: string | number; } = {};
    this.inputs.forEach((input) => {
      switch (input.dataset.datatype) {
        case 'integer':
          data[input.name] = input.value.length > 0 ? parseInt(input.value) : '';
          break;
        default:
          data[input.name] = input.value;
          break;
      }
    });

    if (typeof this.id !== 'undefined' && this.id !== null) {
      await API.Object.update(this.id, data, true);
    } else {
      await API.Object.insert(data);
    }

    this.modal.hide();

    location.reload();
  }

  private async delete () {
    if (confirm('Are you sure you want to delete this object ?') === true) {
      await API.Object.delete(this.id);

      this.modal.hide();

      location.reload();
    }
  }
}
