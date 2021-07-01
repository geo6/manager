import { Modal } from 'bootstrap';
import * as FilePond from 'filepond';
import FilePondPluginFileMetadata from 'filepond-plugin-file-metadata';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginImageExifOrientation from 'filepond-plugin-image-exif-orientation';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';

import * as API from '../api';

FilePond.registerPlugin(
  FilePondPluginFileMetadata,
  FilePondPluginFileValidateType,
  FilePondPluginImageExifOrientation,
  FilePondPluginImagePreview
);
FilePond.setOptions({
  allowProcess: false,
  instantUpload: true,
  server: {
    load: '/api/file/upload/',
    process: '/api/file/upload',
    revert: '/api/file/upload'
  },
  onaddfilestart: () => {
    // Started file load
    (document.getElementById('modal-form-save') as HTMLButtonElement).disabled = true;
  },
  onaddfile: () => {
    // If no error, file has been succesfully loaded
    (document.getElementById('modal-form-save') as HTMLButtonElement).disabled = false;
  },
  onprocessfiles: () => {
    // Called when all files in the list have been processed
    (document.getElementById('modal-form-save') as HTMLButtonElement).disabled = false;
  },
  onerror: () => {
    (document.getElementById('modal-form-save') as HTMLButtonElement).disabled = false;
  }
});

export class FormModal {
  private id!: number | string;
  private readonly modal: Modal;
  private readonly inputs: Array<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>;
  private files: FilePond.FilePondFile[] = [];

  private readonly deleteButton: HTMLButtonElement;
  private readonly saveButton: HTMLButtonElement;

  constructor (element: HTMLElement) {
    this.modal = new Modal(element);
    this.inputs = Array.from(element.querySelectorAll('form input, form select, form textarea'));
    this.deleteButton = document.getElementById('modal-form-delete') as HTMLButtonElement;
    this.saveButton = document.getElementById('modal-form-save') as HTMLButtonElement;

    FilePond.parse(element);

    element.addEventListener('show.bs.modal', async () => {
      this.files = [];

      this.inputs.forEach((element) => {
        const input = element;

        if (input.type === 'file') {
          const pond = FilePond.find(input);
          pond.removeFiles();
          pond.disabled = true;
        } else {
          input.value = '';
          input.disabled = true;
        }
      });

      if (typeof this.id !== 'undefined' && this.id !== null) {
        const json = await API.Object.get(this.id);

        this.inputs.forEach((element) => {
          const input = element;

          if (input.type === 'file') {
            const pond = FilePond.find(input);
            pond.setOptions({
              fileMetadataObject: { id: this.id }
            });
            pond.on('processfilestart', (file: FilePond.FilePondFile) => {
              this.files.push(file);
            });
            pond.on('processfile', (error: FilePond.FilePondErrorDescription|null, file: FilePond.FilePondFile) => {
              const index = this.files.findIndex((f) => f.id === file.id);
              if (index > -1) {
                this.files.splice(index, 1);
              }

              if (error !== null) {
                console.error(error);
              }
            });
            pond.disabled = false;

            if (json.properties[input.name] !== null) {
              await pond.addFile(`${this.id}/${input.name}`, { type: 'local' });
            }
          } else {
            input.value = json.properties[input.name];
            input.disabled = false;
          }
        });
      }
    });

    this.deleteButton.addEventListener('click', async () => await this.delete());
    this.saveButton.addEventListener('click', async () => await this.submit());
  }

  setId (id: string | number): void {
    this.id = id;
  }

  private async submit (): Promise<void> {
    const data: { [key: string]: string | number } = {};

    this.inputs.forEach(async (input) => {
      if (input.type === 'file') {
        const pond = FilePond.find(input);

        data[input.name] = pond.getFiles().map((file) => file.filename).join(',');
      } else {
        switch (input.dataset['datatype']) {
          case 'integer':
            data[input.name] = input.value.length > 0 ? parseInt(input.value) : '';
            break;
          default:
            data[input.name] = input.value;
            break;
        }
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

  private async delete (): Promise<void> {
    if (confirm('Are you sure you want to delete this object ?')) {
      await API.Object.delete(this.id);

      this.modal.hide();

      location.reload();
    }
  }
}
