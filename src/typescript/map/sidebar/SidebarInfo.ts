import { Tab } from 'bootstrap';
import { Feature } from 'ol';

import { Sidebar } from './Sidebar';
import geometryToHTML from './info/geometry';
import thumbnail from './info/thumbnail';
import convertToHTML from './info/value';

export class SidebarInfo {
  private readonly tab!: Tab;
  private readonly idElement!: HTMLElement;
  private readonly contentElement!: HTMLElement;
  private readonly geometryElement!: HTMLElement;

  constructor (private readonly handle: HTMLAnchorElement) {
    this.tab = new Tab(this.handle);
    this.idElement = document.getElementById('sidebar-info-id')?.querySelector('span');
    this.contentElement = document.getElementById('sidebar-info-content');
    this.geometryElement = document.getElementById('sidebar-info-geometry');
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

    this.contentElement.querySelectorAll('div > div').forEach((element) => { element.innerHTML = ''; });

    return this;
  }

  load (feature: Feature): this {
    const id = feature.getId();
    const properties = feature.getProperties();
    const geometry = feature.getGeometry();

    this.idElement.innerText = id.toString();

    this.contentElement.querySelectorAll('div > div').forEach((element) => {
      const { table, name, datatype, file } = element.parentElement.dataset;

      if (typeof properties[`${table}_${name}`] !== 'undefined') {
        if (typeof file !== 'undefined') {
          element.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
          thumbnail(id, name, properties[`${table}_${name}`])
            .then(value => {
              if (typeof value === 'string') {
                (element as HTMLSpanElement).innerText = value;
              } else {
                element.innerHTML = '';
                element.append(value);
              }
            })
            .catch(() => {
              (td as HTMLTableCellElement).innerText = properties[`${table}_${name}`];
            });
        } else {
          const value = convertToHTML(datatype, properties[`${table}_${name}`]);

          if (typeof value === 'string') {
            (element as HTMLSpanElement).innerText = value;
          } else {
            element.innerHTML = '';
            element.append(value);
          }
        }
      }
    });

    this.geometryElement.innerHTML = '';
    this.geometryElement.append(...geometryToHTML(geometry));

    return this;
  }
}
