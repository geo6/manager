import { Tab } from 'bootstrap';
import { Feature } from 'ol';

import { Sidebar } from '.';

export class SidebarInfo {
  private tab!: Tab;
  private table!: HTMLTableElement;

  constructor (private handle: HTMLAnchorElement) {
    this.tab = new Tab(this.handle);
    this.table = document.querySelector(this.handle.getAttribute('href')).querySelector('.sidebar-content table');
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

    this.table.querySelectorAll('tbody > td').forEach((td) => { td.innerHTML = ''; });

    return this;
  }

  load (feature: Feature): this {
    const id = feature.getId();
    const properties = feature.getProperties();

    (this.table.querySelector('caption > span') as HTMLSpanElement).innerText = id.toString();

    this.table.querySelectorAll('tbody > tr > td').forEach((td) => {
      const { name } = td.parentElement.dataset;
      console.log(name, properties[name]);

      if (typeof properties[name] !== 'undefined') {
        td.innerHTML = properties[name];
      }
    });

    return this;
  }
}
