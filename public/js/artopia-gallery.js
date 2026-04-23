document.addEventListener('DOMContentLoaded', () => {
  const lightbox = document.querySelector('.artopia-gallery-lightbox');

  if (!lightbox) {
    return;
  }

  document.body.appendChild(lightbox);

  const cards = Array.from(document.querySelectorAll('.artopia-gallery-card'));

  const image = lightbox.querySelector('.artopia-gallery-lightbox-image');
  const title = lightbox.querySelector('.artopia-gallery-lightbox-title');
  const medium = lightbox.querySelector('.artopia-gallery-lightbox-medium');
  const year = lightbox.querySelector('.artopia-gallery-lightbox-year');
  const dimensions = lightbox.querySelector('.artopia-gallery-lightbox-dimensions');
  const price = lightbox.querySelector('.artopia-gallery-lightbox-price');
  const status = lightbox.querySelector('.artopia-gallery-lightbox-status');
  const description = lightbox.querySelector('.artopia-gallery-lightbox-description');
  const counter = lightbox.querySelector('.artopia-gallery-lightbox-counter');
  const closeButton = lightbox.querySelector('.artopia-gallery-lightbox-close');
  const prevButton = lightbox.querySelector('.artopia-gallery-lightbox-prev');
  const nextButton = lightbox.querySelector('.artopia-gallery-lightbox-next');
  const backdrop = lightbox.querySelector('.artopia-gallery-lightbox-backdrop');

  let currentIndex = -1;

  const renderCard = (card, index) => {
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
    counter.textContent = `${index + 1} / ${cards.length}`;

    currentIndex = index;

    if (prevButton) {
      prevButton.disabled = cards.length <= 1;
    }

    if (nextButton) {
      nextButton.disabled = cards.length <= 1;
    }
  };

  const openLightbox = (index) => {
    const card = cards[index];

    if (!card) {
      return;
    }

    renderCard(card, index);
    lightbox.hidden = false;
    document.body.style.overflow = 'hidden';
  };

  const closeLightbox = () => {
    lightbox.hidden = true;
    image.src = '';
    image.alt = '';
    image.style.display = '';
    document.body.style.overflow = '';
    currentIndex = -1;
  };

  const goToPrevious = () => {
    if (cards.length === 0 || currentIndex < 0) {
      return;
    }

    const newIndex = (currentIndex - 1 + cards.length) % cards.length;
    renderCard(cards[newIndex], newIndex);
  };

  const goToNext = () => {
    if (cards.length === 0 || currentIndex < 0) {
      return;
    }

    const newIndex = (currentIndex + 1) % cards.length;
    renderCard(cards[newIndex], newIndex);
  };

  cards.forEach((card, index) => {
    const button = card.querySelector('.artopia-gallery-card-button');

    if (button) {
      button.addEventListener('click', () => openLightbox(index));
    }
  });

  closeButton?.addEventListener('click', closeLightbox);
  backdrop?.addEventListener('click', closeLightbox);
  prevButton?.addEventListener('click', goToPrevious);
  nextButton?.addEventListener('click', goToNext);

  document.addEventListener('keydown', (event) => {
    if (lightbox.hidden) {
      return;
    }

    if (event.key === 'Escape') {
      closeLightbox();
    } else if (event.key === 'ArrowLeft') {
      goToPrevious();
    } else if (event.key === 'ArrowRight') {
      goToNext();
    }
  });
});