// showcases.js - early-exit if server rendered content exists
// (Remplace ton showcases.js existant dans nassim)
console.log('[showcases.js] chargé');

// Petit helper d'échappement
function escapeHtml(s) {
  if (!s) return '';
  return String(s).replace(/[&<>"']/g, function (c) {
    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
  });
}

async function loadShowcaseRow(rowId, category, size = 6) {
  const container = document.getElementById(rowId);
  if (!container) {
    console.error(`[showcases.js] container #${rowId} introuvable`);
    return;
  }

  // Si le serveur a déjà rendu des éléments (showcase-item), on ne réécrit rien.
  if (container.children.length) {
    console.log(`[showcases.js] #${rowId} contient déjà du HTML (rendu serveur) — skipping fetch.`);
    return;
  }

  container.innerHTML = '<div>Chargement…</div>';

  try {
    const url = `/tm-json.php?category=${encodeURIComponent(category)}&size=${encodeURIComponent(size)}`;
    console.log('[showcases.js] fetch', url);
    const resp = await fetch(url, {cache: 'no-cache'});
    console.log('[showcases.js] status', resp.status);
    if (!resp.ok) {
      const text = await resp.text();
      console.error('[showcases.js] fetch error body:', text);
      container.innerHTML = `<div>Erreur serveur: ${resp.status}</div>`;
      return;
    }
    const data = await resp.json();
    console.log('[showcases.js] data', data);

    // Supporte format { events: [...] } ou raw _embedded.events
    let events = [];
    if (Array.isArray(data.events)) events = data.events;
    else if (data && data._embedded && Array.isArray(data._embedded.events)) events = data._embedded.events;
    else if (Array.isArray(data)) events = data;

    container.innerHTML = '';
    if (!events.length) {
      container.innerHTML = '<div>Aucun événement</div>';
      return;
    }

    // Build items
    for (const ev of events) {
      const name = ev.name || '—';
      const img = (ev.images && ev.images[0]) ? ev.images[0].url : null;
      const card = document.createElement('div');
      card.className = 'showcase-item';
      card.innerHTML = img ? `<img src="${escapeHtml(img)}" alt="${escapeHtml(name)}">` : `<div style="padding:8px;color:#666;text-align:center">${escapeHtml(name)}</div>`;
      container.appendChild(card);
    }

  } catch (err) {
    console.error('[showcases.js] erreur', err);
    container.innerHTML = '<div>Erreur de chargement</div>';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  console.log('[showcases.js] DOMContentLoaded');
  loadShowcaseRow('rowMusic', 'music', 6);
  loadShowcaseRow('rowComedy', 'comedy', 6);
  loadShowcaseRow('rowTheatre', 'theatre', 6);
});