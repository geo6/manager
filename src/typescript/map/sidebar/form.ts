import { Tab } from 'bootstrap';
import { Feature } from 'ol';

import { Sidebar } from '.';

export class SidebarForm {
  private readonly tab!: Tab;
  private readonly form!: HTMLFormElement;
  private readonly inputs: Array<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>;

  constructor (private readonly handle: HTMLAnchorElement) {
    this.tab = new Tab(this.handle);
    this.form = document.querySelector(this.handle.getAttribute('href')).querySelector('.sidebar-content form');
    this.inputs = Array.from(this.form.querySelectorAll('input, select, textarea'));
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
      input.value = '';
      input.disabled = true;
    });

    return this;
  }

  load (feature: Feature): this {
    const properties = feature.getProperties();

    const { table } = this.form.dataset;

    this.inputs.forEach((element) => {
      const input = element;
      input.value = properties[`${table}_${input.name}`];
      input.disabled = false;
    });

    return this;
  }
}
