import { Tab } from 'bootstrap';
import * as FilePond from 'filepond';
import FilePondPluginFileMetadata from 'filepond-plugin-file-metadata';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginImageExifOrientation from 'filepond-plugin-image-exif-orientation';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import { Feature } from 'ol';

import { Sidebar } from '.';

import { table } from '..';

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
  }
  // onaddfilestart: () => {
  //   // Started file load
  //   (document.getElementById('modal-form-save') as HTMLButtonElement).disabled = true;
  // },
  // onaddfile: () => {
  //   // If no error, file has been succesfully loaded
  //   (document.getElementById('modal-form-save') as HTMLButtonElement).disabled = false;
  // },
  // onprocessfiles: () => {
  //   // Called when all files in the list have been processed
  //   (document.getElementById('modal-form-save') as HTMLButtonElement).disabled = false;
  // },
  // onerror: () => {
  //   (document.getElementById('modal-form-save') as HTMLButtonElement).disabled = false;
  // }
});

export class SidebarForm {
  private readonly tab!: Tab;
  private readonly form!: HTMLFormElement;
  private readonly inputs: Array<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>;
  private readonly files: FilePond.FilePondFile[] = [];

  constructor (private readonly handle: HTMLAnchorElement) {
    this.tab = new Tab(this.handle);
    this.form = document.querySelector(this.handle.getAttribute('href')).querySelector('.sidebar-content form');
    this.inputs = Array.from(this.form.querySelectorAll('input, select, textarea'));

    FilePond.parse(this.form);
  }

  open (): this {
    this.tab.show();

    return this;
  }

  enable (): this {
    this.handle.classList.remove('disabled');

    return this;
  }

  reset (): this {
    this.handle.classList.add('disabled');

    Sidebar.close(this.handle);

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

    return this;
  }

  load (feature: Feature): this {
    const id = feature.getId();
    const properties = feature.getProperties();

    this.inputs.forEach(async (element) => {
      const input = element;

      if (input.type === 'file') {
        const pond = FilePond.find(input);
        pond.setOptions({
          fileMetadataObject: { id }
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

        if (properties[`${table}_${input.name}`] !== null) {
          console.log(`${id.toString()}/${input.name}`);
          await pond.addFile(`${id.toString()}/${input.name}`, { type: 'local' });
        }
      } else {
        input.value = properties[`${table}_${input.name}`];
        input.disabled = false;
      }
    });

    return this;
  }
}
