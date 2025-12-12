

const API_URL = '/src/api/banners.php?source=music&per_page=8';
const AUTOPLAY_MS = 3500;

document.addEventListener('DOMContentLoaded', () => {
  const viewport = document.getElementById('heroViewport');
  if (!viewport) return;

  fetch(API_URL, { cache: 'no-store' })
    .then(res => {
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res.json();
    })
    .then(json => {
      const banners = Array.isArray(json?.banners) ? json.banners : [];
      buildSlider(viewport, banners);
    })
    .catch(err => {
      console.error('banner fetch error:', err);

      const fallback = [
        { url: 'https://cdn.paris.fr/paris/2025/03/21/huge-81b73d782c506c2af02be61142afe290.jpg', title: 'Concert', source: 'fallback' },
        { url: 'https://parisjetaime.com/data/layout_image/24553_Foule-concert--630x405--%C2%A9-DR-Pixhere_panoramic_2-1_l.jpg?ver=1700702737', title: 'Stage', source: 'fallback' },
      ];
      buildSlider(viewport, fallback);
    });
});

function buildSlider(viewport, banners) {
  viewport.innerHTML = '';
  viewport.classList.add('hero__viewport');

  const slides = [];
  banners.forEach((b, i) => {
    const slide = document.createElement('div');
    slide.className = 'hero__slide' + (i === 0 ? ' active' : '');

    const img = document.createElement('img');
    img.className = 'hero__img';
    img.src = b.url;
    img.alt = b.title || 'Banner';
    img.loading = 'lazy';
    slide.appendChild(img);

    if (b.title) {
      const caption = document.createElement('div');
      caption.className = 'hero__caption';
      caption.textContent = b.title;
      slide.appendChild(caption);
    }

    viewport.appendChild(slide);
    slides.push(slide);
  });

  if (!slides.length) return;

  let current = 0;
  let timer = null;

  function show(index) {
    slides.forEach((s, idx) => s.classList.toggle('active', idx === index));
    current = index;
  }

  function next() { show((current + 1) % slides.length); }
  function prev() { show((current - 1 + slides.length) % slides.length); }

  viewport.addEventListener('mouseenter', () => { if (timer) { clearInterval(timer); timer = null; } });
  viewport.addEventListener('mouseleave', () => { if (!timer && slides.length > 1) timer = setInterval(next, AUTOPLAY_MS); });

  viewport.tabIndex = 0;
  viewport.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') { prev(); restart(); }
    if (e.key === 'ArrowRight') { next(); restart(); }
  });

  function restart() {
    if (timer) clearInterval(timer);
    timer = setInterval(next, AUTOPLAY_MS);
  }

  if (slides.length > 1) timer = setInterval(next, AUTOPLAY_MS);
}