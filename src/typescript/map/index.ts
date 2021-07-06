import { Feature, Map, View } from 'ol';
import { ScaleLine, defaults as defaultControls } from 'ol/control';
import GeoJSON from 'ol/format/GeoJSON';
import TileLayer from 'ol/layer/Tile';
import VectorLayer from 'ol/layer/Vector';
import OSM from 'ol/source/OSM';
import VectorSource from 'ol/source/Vector';

import * as API from '../api';
import { Sidebar } from './sidebar';
import { SidebarForm } from './sidebar/form';
import { SidebarInfo } from './sidebar/info';
import { styleFeature } from './style';

export let table!: string;
export let theme!: Theme.Config;

export let map!: Map;
export let sidebar !: Sidebar;
export let sidebarInfo !: SidebarInfo;
export let sidebarForm !: SidebarForm;

(async () => {
  const href = new URL(window.location.href);

  const config = await API.Config.get();
  const extent = await API.Object.extent();

  table = config.table;
  theme = config.theme;

  const params = new URLSearchParams();
  if (href.searchParams.get('search') !== null) {
    params.set('search', href.searchParams.get('search'));
  }

  const view = new View({
    center: [0, 0],
    zoom: 2
  });

  map = new Map({
    controls: defaultControls().extend([new ScaleLine()]),
    layers: [
      new TileLayer({
        source: new OSM()
      })
    ],
    target: 'map',
    view
  });
  if (extent !== null) {
    view.fit(
      (new GeoJSON({ featureProjection: 'EPSG:3857' })).readGeometry(extent).getExtent(), {
        maxZoom: 18,
        padding: [50, 50, 50, 50],
        size: map.getSize()
      });
  }

  const layer = new VectorLayer({
    source: new VectorSource({
      url: `/api/object?${params.toString()}`,
      format: new GeoJSON()
    }),
    style: (feature, resolution) => styleFeature(theme, table, feature, resolution)
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
