document.addEventListener('DOMContentLoaded', () => {
  const lightbox = document.querySelector('.artopia-gallery-lightbox');

  if (!lightbox) {
    return;
  }

  // Move lightbox to <body> so theme/layout containers cannot constrain it.
  document.body.appendChild(lightbox);

  const image = lightbox.querySelector('.artopia-gallery-lightbox-image');
  const title = lightbox.querySelector('.artopia-gallery-lightbox-title');
  const medium = lightbox.querySelector('.artopia-gallery-lightbox-medium');
  const year = lightbox.querySelector('.artopia-gallery-lightbox-year');
  const dimensions = lightbox.querySelector('.artopia-gallery-lightbox-dimensions');
  const price = lightbox.querySelector('.artopia-gallery-lightbox-price');
  const status = lightbox.querySelector('.artopia-gallery-lightbox-status');
  const description = lightbox.querySelector('.artopia-gallery-lightbox-description');
  const closeButton = lightbox.querySelector('.artopia-gallery-lightbox-close');
  const backdrop = lightbox.querySelector('.artopia-gallery-lightbox-backdrop');

  const openLightbox = (card) => {
    const fullImage = card.dataset.fullImage || '';
    const titleText = card.dataset.title || '';

    if (fullImage) {
      image.src = fullImage;
      image.alt = titleText;
      image.style.display = '';
    } else {
      image.src = '';
      image.alt = '';
      image.style.display = 'none';
    }

    title.textContent = titleText;
    medium.textContent = card.dataset.medium ? `Medium: ${card.dataset.medium}` : '';
    year.textContent = card.dataset.year ? `Year: ${card.dataset.year}` : '';
    dimensions.textContent = card.dataset.dimensions ? `Dimensions: ${card.dataset.dimensions}` : '';
    price.textContent = card.dataset.price ? `Price: $${card.dataset.price}` : '';
    status.textContent = card.dataset.status ? `Status: ${card.dataset.status}` : '';
    description.textContent = card.dataset.description || '';

    lightbox.hidden = false;
    document.body.style.overflow = 'hidden';
  };

  const closeLightbox = () => {
    lightbox.hidden = true;
    image.src = '';
    image.alt = '';
    image.style.display = '';
    document.body.style.overflow = '';
  };

  document.querySelectorAll('.artopia-gallery-card').forEach((card) => {
    const button = card.querySelector('.artopia-gallery-card-button');

    if (button) {
      button.addEventListener('click', () => openLightbox(card));
    }
  });

  closeButton?.addEventListener('click', closeLightbox);
  backdrop?.addEventListener('click', closeLightbox);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !lightbox.hidden) {
      closeLightbox();
    }
  });
});