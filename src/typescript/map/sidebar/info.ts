import { Tab } from 'bootstrap';
import { Feature } from 'ol';

import { Sidebar } from '.';
import geometryToHTML from './info/geometry';
import thumbnail from './info/thumbnail';
import convertToHTML from './info/value';

export class SidebarInfo {
  private tab!: Tab;
  private table!: HTMLTableElement;
  private geometryElement!: HTMLElement;

  constructor (private handle: HTMLAnchorElement) {
    this.tab = new Tab(this.handle);
    this.table = document.querySelector(this.handle.getAttribute('href')).querySelector('.sidebar-content > table');
    this.geometryElement = document.querySelector(this.handle.getAttribute('href')).querySelector('.sidebar-content > div');
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
    const geometry = feature.getGeometry();

    (this.table.querySelector('caption > span') as HTMLSpanElement).innerText = id.toString();

    this.table.querySelectorAll('tbody > tr > td').forEach((td) => {
      const { name, datatype, file } = td.parentElement.dataset;

      if (typeof properties[name] !== 'undefined') {
        if (typeof file !== 'undefined') {
          td.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
          thumbnail(id, name, properties[name])
            .then(value => {
              if (typeof value === 'string') {
                (td as HTMLTableCellElement).innerText = value;
              } else {
                td.innerHTML = '';
                td.append(value);
              }
            })
            .catch(() => {
              (td as HTMLTableCellElement).innerText = properties[name];
            });
        } else {
          const value = convertToHTML(datatype, properties[name]);

          if (typeof value === 'string') {
            (td as HTMLTableCellElement).innerText = value;
          } else {
            td.innerHTML = '';
            td.append(value);
          }
        }
      }
    });

    this.geometryElement.innerHTML = '';
    this.geometryElement.append(...geometryToHTML(geometry));

    return this;
  }
}
