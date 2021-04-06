import { map } from '../../map';
import { Baselayer } from './baselayer/overview';

export class Sidebar {
  constructor (private element: HTMLElement) {
    const view = map.getView();
    const overviews = [
      new Baselayer.Overview(document.getElementById('sidebar-baselayer-osm'), view, 1),
      new Baselayer.Overview(document.getElementById('sidebar-baselayer-cirb-urbis'), view, 2),
      new Baselayer.Overview(document.getElementById('sidebar-baselayer-cirb-ortho'), view, 3)
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
        handle.classList.remove('active');

        const tab = element.closest('.tab-pane');
        tab.classList.remove('show', 'active');

        map.updateSize();
      });
    });
  }
}
