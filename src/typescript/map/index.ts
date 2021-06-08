import { Feature, Map, View } from 'ol';
import { ScaleLine, defaults as defaultControls } from 'ol/control';
import GeoJSON from 'ol/format/GeoJSON';
import TileLayer from 'ol/layer/Tile';
import VectorLayer from 'ol/layer/Vector';
import OSM from 'ol/source/OSM';
import VectorSource from 'ol/source/Vector';

import { Sidebar } from './sidebar';
import { SidebarForm } from './sidebar/form';
import { SidebarInfo } from './sidebar/info';

export let map!: Map;
export let sidebar !: Sidebar;
export let sidebarInfo !: SidebarInfo;
export let sidebarForm !: SidebarForm;

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

  const layer = new VectorLayer({
    source: new VectorSource({
      url: '/api/object',
      format: new GeoJSON()
    })
  });
  layer.once('postrender', () => {
    const extent = layer.getSource().getExtent();
    console.log('postrender', extent);

    map.getView().fit(extent, {
      size: map.getSize(),
      padding: [50, 50, 50, 50]
    });
  });
  map.addLayer(layer);

  map.on('click', async (event) => {
    const features = map.getFeaturesAtPixel(event.pixel);

    if (features.length > 0) {
      sidebarInfo
        .load(features[0] as Feature)
        .enable()
        .open();
      sidebarForm
        .load(features[0] as Feature)
        .enable();
    } else {
      sidebarInfo.reset();
      sidebarForm.reset();
    }
  });

  sidebar = new Sidebar(document.getElementById('sidebar'));
  sidebarInfo = new SidebarInfo(document.getElementById('sidebar-info-tab') as HTMLAnchorElement);
  sidebarForm = new SidebarForm(document.getElementById('sidebar-form-tab') as HTMLAnchorElement);
})();
