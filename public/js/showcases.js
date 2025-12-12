
console.log('[showcases.js] chargé (TM + SK fallback)');

function escapeHtml(s) {
  if (!s) return '';
  return String(s).replace(/[&<>"']/g, function (c) {
    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
  });
}

function normKey(name, date) {
  if (!name) name = '';
  name = name.toString().toLowerCase().trim();
  
  name = name.replace(/[\W_]+/g, ' ').replace(/\s+/g, ' ').trim();
  date = (date || '').toString().substr(0, 10);
  return name + '|' + date;
}

function skMatchesCategory(ev, category) {
  if (!category || category === 'all') return true;
  const cat = category.toLowerCase();
  const t = (ev.type || '').toString().toLowerCase();
  const allowed = {
    music: ['music', 'concert'],
    comedy: ['comedy', 'theater', 'theatre', 'theater_comedy'],
    theatre: ['theater', 'theatre', 'play', 'film', 'theatre', 'family'],
  }[cat] || [];
  for (const a of allowed) {
    if (t.includes(a)) return true;
  }
  
  const name = (ev.name || '').toString().toLowerCase();
  for (const a of allowed) {
    if (name.includes(a)) return true;
  }
  if (ev._embedded && Array.isArray(ev._embedded.attractions)) {
    for (const at of ev._embedded.attractions) {
      const an = (at && at.name) ? at.name.toString().toLowerCase() : '';
      for (const a of allowed) if (an.includes(a)) return true;
    }
  }
  return false;
}

async function fetchJson(url) {
  const resp = await fetch(url, {cache: 'no-cache'});
  if (!resp.ok) {
    const text = await resp.text();
    const err = new Error('HTTP ' + resp.status + ' ' + url);
    err.body = text;
    throw err;
  }
  return resp.json();
}

function renderEventsToContainer(events, container) {
  container.innerHTML = '';
  if (!events.length) {
    container.innerHTML = '<div>Aucun événement</div>';
    return;
  }
  for (const ev of events) {
    const name = ev.name || '—';
    const img = (ev.images && ev.images[0]) ? ev.images[0].url : null;
    const card = document.createElement('div');
    card.className = 'showcase-item';
    card.innerHTML = img
      ? `<img src="${escapeHtml(img)}" alt="${escapeHtml(name)}">`
      : `<div style="padding:8px;color:#666;text-align:center">${escapeHtml(name)}</div>`;
    container.appendChild(card);
  }
}

async function loadShowcaseRow(rowId, category, size = 6) {
  const container = document.getElementById(rowId);
  if (!container) {
    console.error(`[showcases.js] container #${rowId} introuvable`);
    return;
  }

  if (container.children.length) {
    console.log(`[showcases.js] #${rowId} contient déjà du HTML — skipping fetch.`);
    return;
  }

  container.innerHTML = '<div>Chargement…</div>';

  try {
    const tmUrl = `/src/api/tm_json.php?category=${encodeURIComponent(category)}&size=${encodeURIComponent(size)}`;
    console.log('[showcases.js] fetch TM', tmUrl);
    const tmData = await fetchJson(tmUrl);
    let tmEvents = Array.isArray(tmData.events) ? tmData.events : (Array.isArray(tmData._embedded?.events) ? tmData._embedded.events : (Array.isArray(tmData) ? tmData : []));
    console.log('[showcases.js] TM events', tmEvents.length);

    
    if (tmEvents.length >= size) {
      renderEventsToContainer(tmEvents.slice(0, size), container);
      return;
    }

    
    const need = size - tmEvents.length;
    console.log('[showcases.js] TM underflow, need', need, 'fetching SeatGeek fallback...');
    let skEvents = [];
    try {
      const skUrl = `/src/api/seatgeek-only.php?per_page=${encodeURIComponent(Math.max(need * 2, size))}`;
      console.log('[showcases.js] fetch SK', skUrl);
      const skData = await fetchJson(skUrl);
      if (Array.isArray(skData.events)) skEvents = skData.events;
      else if (Array.isArray(skData.events || skData)) skEvents = skData.events || skData;
      console.log('[showcases.js] SK events', skEvents.length);
    } catch (e) {
      console.warn('[showcases.js] seatgeek fetch failed', e, e.body || '');
    }

    
    const skFiltered = skEvents.filter(ev => skMatchesCategory(ev, category));
    console.log('[showcases.js] SK filtered by category', skFiltered.length);

    
    const seen = new Set();
    const merged = [];

    
    for (const ev of tmEvents) {
      const date = ev.dates?.start?.localDate || ev.dates?.start?.dateTime || '';
      const key = normKey(ev.name || ev.title || ev.id || '', date);
      if (seen.has(key)) continue;
      seen.add(key);
      merged.push(ev);
      if (merged.length >= size) break;
    }

    if (merged.length < size) {
      for (const ev of skFiltered) {
        const date = ev.dates?.start?.localDate || ev.dates?.start?.dateTime || '';
        const key = normKey(ev.name || ev.title || ev.id || '', date);
        if (seen.has(key)) continue;
        seen.add(key);
        merged.push(ev);
        if (merged.length >= size) break;
      }
    }

    
    console.log('[showcases.js] merged final count', merged.length);
    renderEventsToContainer(merged.slice(0, size), container);

  } catch (err) {
    console.error('[showcases.js] erreur', err);
    const msg = err.body ? (`${err.message} — ${err.body}`) : err.message;
    container.innerHTML = `<div>Erreur de chargement: ${escapeHtml(msg)}</div>`;
  }
}

document.addEventListener('DOMContentLoaded', function() {
  console.log('[showcases.js] DOMContentLoaded');
  loadShowcaseRow('rowMusic', 'music', 6);
  loadShowcaseRow('rowComedy', 'comedy', 6);
  loadShowcaseRow('rowTheatre', 'theatre', 6);
});