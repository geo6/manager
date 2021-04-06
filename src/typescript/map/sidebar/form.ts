import { Tab } from 'bootstrap';
import { Feature } from 'ol';

import { Sidebar } from '.';

export class SidebarForm {
  private tab!: Tab;
  private form!: HTMLFormElement;
  private inputs: (HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement)[];

  constructor (private handle: HTMLAnchorElement) {
    this.tab = new Tab(this.handle);
    this.form = document.querySelector(this.handle.getAttribute('href')).querySelector('.sidebar-content form');
    this.inputs = Array.from(this.form.querySelectorAll('input, select, textarea'));
  }

  open (): this {
    this.tab.show();

    return this;
  }

  enable () : this {
    this.handle.classList.remove('disabled');

    return this;
  }

  reset (): this {
    this.handle.classList.add('disabled');

    Sidebar.close(this.handle);

    this.inputs.forEach((element) => {
      const input = element as HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;
      input.value = '';
      input.disabled = true;
    });

    return this;
  }

  load (feature: Feature): this {
    const id = feature.getId();
    const properties = feature.getProperties();

    this.inputs.forEach((element) => {
      const input = element as HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;
      input.value = properties[input.name];
      input.disabled = false;
    });

    return this;
  }
}
