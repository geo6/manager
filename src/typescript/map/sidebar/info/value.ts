export default function convertToHTML (type: string, value: string|number|boolean): string | HTMLElement {
  if (value === null) {
    return valueNull();
  } else if (type === 'boolean' && typeof value === 'boolean') {
    return valueBoolean(value);
  } else if (typeof value === 'string' && checkURL(value)) {
    return valueLink(value);
  } else {
    return value.toString();
  }
}

export function valueNull (): HTMLElement {
  const element = document.createElement('span');
  element.className = 'text-muted fst-italic';
  element.innerText = 'NULL';

  return element;
}

function valueBoolean (value: boolean): HTMLElement {
  if (value) {
    const element = document.createElement('i');
    element.className = 'far fa-fw fa-check-circle text-success';

    return element;
  } else {
    const element = document.createElement('i');
    element.className = 'far fa-fw fa-times-circle text-danger';

    return element;
  }
}

function valueLink (value: string): HTMLElement {
  const url = new URL(value);

  const element = document.createElement('a');
  element.className = 'text-decoration-none';
  element.href = value;
  element.target = '_blank';
  element.innerHTML = `<i class="fas fa-external-link-alt"></i> ${url.hostname}`;

  return element;
}

function checkURL (value: string): boolean {
  let url: URL;

  try {
    url = new URL(value);
  } catch (exception) {
    return false;
  }

  return url.protocol === 'http:' || url.protocol === 'https:';
}
