import Overlay from '@geo6/overlay-image-preview';

import { valueNull } from './value';

export default async function thumbnail (id: string | number, column: string, value: string | null): Promise<string | HTMLElement> {
  if (value === null) {
    return valueNull();
  }

  if (value.length === 0) {
    return '';
  }

  const filename = value.split('/').reverse()[0];
  if (typeof filename === 'undefined') {
    return value;
  }

  const response = await fetch(`/api/file/thumbnail/${id}/${column}`);
  if (!response.ok) {
    const element = document.createElement('span');
    element.className = 'text-muted';
    element.innerHTML = `<i class="far fa-file-image"></i> ${filename}`;

    return element;
  }

  const element = document.createElement('a');
  element.className = 'text-decoration-none thumbnail-link';
  element.href = `/api/file/thumbnail/${id}/${column}`;
  element.innerHTML = `<i class="far fa-file-image"></i> ${filename}`;

  element.addEventListener('click', async (event) => {
    event.preventDefault();

    const response = await fetch(`/api/file/info/${id}/${column}`);
    const info = await response.json();

    const overlay = new Overlay(element, () => {
      return info.filename;
    });
    overlay.open();
  });

  return element;
}
