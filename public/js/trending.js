
const TRENDING_API_URL = 'events.php?classificationName=music';
const trendingList = document.getElementById('trendingList');


if (trendingList && trendingList.children.length) {
  console.log('[trending.js] contenu déjà rendu côté serveur — script arrêté pour préserver la structure.');
} else {
 
  function shuffle(arr) {
    if(!Array.isArray(arr)) return [];
    const a = arr.slice();
    for (let i = a.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
  }

  function getArtistName(ev) {
    const attr = ev._embedded && ev._embedded.attractions;
    if (Array.isArray(attr) && attr.length) {
      return (attr[0].name || '').trim();
    }
    return (ev.name || '').trim();
  }

  function pickEventImage(event) {
    const images = event.images;
    if (!Array.isArray(images) || !images.length) return null;
    const candidate = images.find(img =>
      (img.ratio || '').toLowerCase().includes('16') && img.width >= 800
    );
    return candidate ? candidate.url : images[0].url;
  }

  function isMusicEvent(ev) {
    const classifications = ev.classifications || [];
    if (!classifications.length) return true;
    const first = classifications[0];
    const seg = (first.segment && first.segment.name || '').toLowerCase();
    const genre = (first.genre && first.genre.name || '').toLowerCase();
    const sub = (first.subGenre && first.subGenre.name || '').toLowerCase();
    return (
      seg.includes('music') ||
      genre.includes('music') ||
      sub.includes('music')
    );
  }

  function createTrendingCard(ev) {
    const imgUrl = pickEventImage(ev);
    const title = ev.name || 'Concert';
    const desc = ev.info || ev.pleaseNote || "Concert à venir.";

    const card = document.createElement('article');
    card.className = 'trending-card';

    card.innerHTML = `
      <div class="trending-card__imgwrap">
        ${imgUrl ? `<img src="${imgUrl}" alt="concert image" class="trending-card__img">` : ''}
      </div>
      <div class="trending-card__body">
        <h3 class="trending-card__title">${title}</h3>
        <p class="trending-card__desc">${desc}</p>
        <button class="trending-card__btn" type="button">voir</button>
      </div>
    `;

    return card;
  }

  async function loadTrending() {
    if (!trendingList) return;

    try {
      const res = await fetch(TRENDING_API_URL);
      if (!res.ok) {
        console.error('[trending.js] fetch status', res.status);
        trendingList.innerHTML = `<p class="trending__empty">Impossible de charger les concerts.</p>`;
        return;
      }
      const data = await res.json();

      const events = Array.isArray(data?.events) ? data.events : [];

     
      const musicEvents = events.filter(isMusicEvent);

     
      const shuffled = shuffle(musicEvents);

  
      const seenArtists = new Set();
      const uniqueByArtist = [];

      for (const ev of shuffled) {
        const artist = getArtistName(ev).toLowerCase();
        if (!artist) continue;
        if (seenArtists.has(artist)) {
          continue;
        }
        seenArtists.add(artist);
        uniqueByArtist.push(ev);
        if (uniqueByArtist.length >= 3) break;
      }

      trendingList.innerHTML = '';

      const finalEvents =
        uniqueByArtist.length ? uniqueByArtist : shuffled.slice(0, 3);

      if (!finalEvents.length) {
        trendingList.innerHTML = `<p class="trending__empty">Aucun concert trouvé.</p>`;
        return;
      }

      finalEvents.forEach(ev => {
        trendingList.appendChild(createTrendingCard(ev));
      });

    } catch (err) {
      console.error('Erreur trending:', err);
      if (trendingList) trendingList.innerHTML = `<p class="trending__empty">Impossible de charger les concerts.</p>`;
    }
  }

  document.addEventListener('DOMContentLoaded', loadTrending);
}