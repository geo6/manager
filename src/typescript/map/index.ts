import Map from 'ol/Map';
import { ScaleLine, defaults as defaultControls } from 'ol/control';
import OSM from 'ol/source/OSM';
import TileLayer from 'ol/layer/Tile';
import View from 'ol/View';

import { Sidebar } from './sidebar';

export { Tab } from 'bootstrap';

export let map!: Map;
export let sidebar !: Sidebar;

(function () {
  const view = new View({
    center: [0, 0],
    zoom: 2
  });

  map = new Map({
    controls: defaultControls().extend([new ScaleLine()]),
    layers: [
      new TileLayer({
        source: new OSM()
      })],
    target: 'map',
    view
  });

  sidebar = new Sidebar(document.getElementById('sidebar'));
})();
