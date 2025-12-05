const API_URL = 'events.php'; // ton script PHP
const AUTOPLAY_MS = 2500; // 2.5 s entre chaque changement

const viewport = document.getElementById('heroViewport');
let slides = [];
let current = 0;

// 👉 Sélectionne UNE image par événement
function pickEventImage(event) {
  if (!Array.isArray(event.images) || !event.images.length) return null;
  // On prend une image au format 16:9 si possible
  const candidate = event.images.find(img =>
    (img.ratio || '').toLowerCase().includes('16') && img.width >= 1200
  );
  return candidate ? candidate.url : event.images[0].url;
}

function createSlide(url) {
  const slide = document.createElement('div');
  slide.className = 'hero__slide';
  const img = document.createElement('img');
  img.className = 'hero__img';
  img.src = url;
  slide.appendChild(img);
  return slide;
}

function showSlide(i) {
  slides.forEach((slide, index) => {
    slide.classList.toggle('active', index === i);
  });
}

async function initBanner() {
  try {
    const res = await fetch(API_URL);
    const data = await res.json();

    // 🔹 Une image par événement
    const events = Array.isArray(data?.events) ? data.events : [];
    const urls = [];
    for (const ev of events) {
      const url = pickEventImage(ev);
      if (url && !urls.includes(url)) urls.push(url);
      if (urls.length >= 10) break; // limite à 10 images max
    }

    // 🔹 Fallback si aucun résultat
    if (!urls.length) {
      urls.push('https://picsum.photos/1600/800?random=1');
      urls.push('https://picsum.photos/1600/800?random=2');
      urls.push('https://picsum.photos/1600/800?random=3');
    }

    // 🔹 Crée les slides
    viewport.innerHTML = '';
    for (const url of urls) viewport.appendChild(createSlide(url));
    slides = Array.from(document.querySelectorAll('.hero__slide'));

    // 🔹 Affiche la première
    showSlide(0);

    // 🔹 Lance l’autoplay
    setInterval(() => {
      current = (current + 1) % slides.length;
      showSlide(current);
    }, AUTOPLAY_MS);

  } catch (err) {
    console.error('Erreur bannière:', err);
    viewport.innerHTML = `
      <div class="hero__slide active">
        <img class="hero__img" src="https://picsum.photos/1600/800?blur=3" alt="Erreur">
      </div>`;
  }
}

initBanner();
