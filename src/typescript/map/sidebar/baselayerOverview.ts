import { Map, View } from 'ol';
import TileLayer from 'ol/layer/Tile';
import OSM from 'ol/source/OSM';
import TileSource from 'ol/source/Tile';
import TileWMS from 'ol/source/TileWMS';

import { map } from '../index';

export default class BaselayerOverview {
  private readonly map: Map;
  private readonly source: TileSource;

  constructor (private readonly element: HTMLElement, view: View, source: number) {
    if (source === 2) {
      this.source = new TileWMS({
        url: 'https://geoservices-urbis.irisnet.be/geoserver/ows/',
        params: {
          LAYERS: 'urbis'
        }
      });
    } else if (source === 3) {
      this.source = new TileWMS({
        url: 'https://geoservices-urbis.irisnet.be/geoserver/ows/',
        params: {
          LAYERS: 'Urbis:Ortho'
        }
      });
    } else {
      this.source = new OSM();
    }

    this.map = new Map({
      controls: [],
      layers: [new TileLayer({ source: this.source })],
      target: element.querySelector('.overview-map') as HTMLElement,
      view
    });

    this.element.addEventListener('click', () => {
      map.getLayers().removeAt(0);
      map.getLayers().insertAt(0, new TileLayer({ source: this.source }));

      this.element.parentElement.querySelector('.overview.active').classList.remove('active');
      this.element.classList.toggle('active');
    });
  }

  getMap (): Map {
    return this.map;
  }
}
