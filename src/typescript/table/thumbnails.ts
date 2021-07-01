import Overlay from '@geo6/overlay-image-preview';

export default function (): void {
  document.querySelectorAll('a.thumbnail-link').forEach((element) => {
    const id = element.closest('tr').dataset['id'];

    if (typeof id !== 'undefined') {
      element.addEventListener('click', async (event) => {
        event.preventDefault();

        const response = await fetch(`/api/file/info/${id}/photo`);
        const info = await response.json();

        const overlay = new Overlay(element as HTMLAnchorElement, () => {
          return info.filename;
        });
        overlay.open();
      });
    }
  });
}
