import { map } from '..';
import { BaselayerOverview } from './BaselayerOverview';

export class Sidebar {
  constructor (private readonly element: HTMLElement) {
    const view = map.getView();
    const overviews = [
      new BaselayerOverview(document.getElementById('sidebar-baselayer-osm'), view, 1),
      new BaselayerOverview(document.getElementById('sidebar-baselayer-cirb-urbis'), view, 2),
      new BaselayerOverview(document.getElementById('sidebar-baselayer-cirb-ortho'), view, 3)
    ];

    this.element.querySelectorAll('a[data-bs-toggle="tab"]').forEach((element) => {
      element.addEventListener('shown.bs.tab', (event) => {
        map.updateSize();

        if ((event.target as HTMLElement).id === 'sidebar-baselayers-tab') {
          overviews.forEach((overview) => {
            overview.getMap().updateSize();
          });
        }
      });
    });

    this.element.querySelectorAll('#sidebar .btn-close').forEach((element) => {
      element.addEventListener('click', () => {
        const handle = document.querySelector('#sidebar a[data-bs-toggle="tab"].active') as HTMLAnchorElement;

        Sidebar.close(handle);
      });
    });
  }

  static close (handle: HTMLAnchorElement): void {
    handle.classList.remove('active');

    const tab = document.querySelector(handle.getAttribute('href'));
    tab.classList.remove('show', 'active');

    map.updateSize();
  }
}
