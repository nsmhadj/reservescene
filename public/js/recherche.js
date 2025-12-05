// recherche.js

document.addEventListener("DOMContentLoaded", () => {
  const keyword = window.SEARCH_KEYWORD || new URLSearchParams(window.location.search).get("q");
  const errorBox = document.getElementById("js-error");
  const cardsContainer = document.querySelector(".cards-grid");
  const titleElt = document.querySelector(".results-header h2");
  const sortButtons = document.querySelectorAll(".sort-tabs button");
  const citySelect = document.getElementById("filter-city");

  let allEvents = [];
  let currentSort = "dateAsc";

  if (!keyword) {
    if (titleElt) titleElt.textContent = "Aucun mot-clé fourni";
    return;
  }
  if (titleElt) {
    titleElt.textContent = `Résultats de recherche "${keyword}"`;
  }

  const apiUrl = `events.php?keyword=${encodeURIComponent(keyword)}`;
  console.log("🌐 Appel API :", apiUrl);

  fetch(apiUrl)
    .then((res) => {
      if (!res.ok) throw new Error("HTTP " + res.status);
      return res.json();
    })
    .then((data) => {
      let events = Array.isArray(data?.events) ? data.events : [];
      if (!events.length && data?._embedded?.events) {
        events = data._embedded.events;
      }

      if (!events.length) {
        cardsContainer.innerHTML = `<p style="grid-column:1/-1;">Aucun événement trouvé.</p>`;
        return;
      }

      allEvents = events;

      // remplir le select villes selon les événements
      populateCities(allEvents, citySelect);

      // premier rendu : tout, trié par date
      const initial = sortEvents(allEvents, currentSort);
      renderEvents(initial, cardsContainer);

      // brancher les filtres
      attachFilters(allEvents, cardsContainer);

      // tri boutons
      sortButtons.forEach((btn) => {
        btn.addEventListener("click", () => {
          sortButtons.forEach((b) => b.classList.remove("active"));
          btn.classList.add("active");

          if (btn.textContent.includes("proche")) currentSort = "dateAsc";
          else if (btn.textContent.includes("moins cher")) currentSort = "priceAsc";
          else currentSort = "dateAsc";

          const filteredSorted = applyFiltersAndSort(allEvents, currentSort);
          renderEvents(filteredSorted, cardsContainer);
        });
      });

    })
    .catch((err) => {
      console.error("❌ Erreur API :", err);
      if (errorBox) errorBox.style.display = "block";
    });
});

/**
 * Ajoute les villes trouvées dans les events dans le select
 */
function populateCities(events, selectElt) {
  if (!selectElt) return;
  const cities = new Set();

  events.forEach((ev) => {
    const city =
      ev?._embedded?.venues?.[0]?.city?.name ||
      ev?.place?.city ||
      ev?.venue?.city ||
      null;
    if (city) cities.add(city);
  });

  [...cities].sort().forEach((city) => {
    const opt = document.createElement("option");
    opt.value = city;
    opt.textContent = city;
    selectElt.appendChild(opt);
  });
}

function attachFilters(allEvents, cardsContainer) {
  const catFilters = document.querySelectorAll(".filter-cat");
  const dateFilters = document.querySelectorAll(".filter-date");
  const priceFilters = document.querySelectorAll(".filter-price");
  const accessFilters = document.querySelectorAll(".filter-access");
  const citySelect = document.getElementById("filter-city");

  const rerender = () => {
    const filteredSorted = applyFiltersAndSort(allEvents);
    renderEvents(filteredSorted, cardsContainer);
  };

  catFilters.forEach((input) => input.addEventListener("change", rerender));
  dateFilters.forEach((input) => input.addEventListener("change", rerender));
  priceFilters.forEach((input) => input.addEventListener("change", rerender));
  accessFilters.forEach((input) => input.addEventListener("change", rerender));
  if (citySelect) citySelect.addEventListener("change", rerender);
}

function applyFiltersAndSort(events, sortMode = "dateAsc") {
  const selectedCats = [...document.querySelectorAll(".filter-cat:checked")].map((i) => i.value);
  const selectedDate = document.querySelector(".filter-date:checked")?.value || "all";
  const selectedPrice = document.querySelector(".filter-price:checked")?.value || "all";
  const selectedCity = document.getElementById("filter-city")?.value || "all";
  const wantsPMR = document.querySelector(".filter-access[value='pmr']")?.checked || false;

  let result = events.filter((ev) => {
    // 1) Catégories
    if (selectedCats.length) {
      const segment = ev?.classifications?.[0]?.segment?.name?.toLowerCase() || "";
      const genre = ev?.classifications?.[0]?.genre?.name?.toLowerCase() || "";
      let evCat = "other";
      if (segment.includes("music")) evCat = "concert";
      else if (segment.includes("theatre") || genre.includes("theatre")) evCat = "theatre";
      else if (segment.includes("comedy") || genre.includes("comedy")) evCat = "comedy";

      if (!selectedCats.includes(evCat)) return false;
    }

    // 2) Date
    if (selectedDate !== "all") {
      const dateStr = ev?.dates?.start?.localDate;
      if (!dateStr) return false;
      if (!matchSingleDateFilter(dateStr, selectedDate)) return false;
    }

    // 3) Ville
    if (selectedCity !== "all") {
      const city =
        ev?._embedded?.venues?.[0]?.city?.name ||
        ev?.place?.city ||
        ev?.venue?.city ||
        null;
      if (!city || city !== selectedCity) return false;
    }

    // 4) Prix
    if (selectedPrice !== "all") {
      const minPrice = ev?.priceRanges?.[0]?.min;
      if (!minPrice) return false;
      if (selectedPrice === "low" && !(minPrice < 20)) return false;
      if (selectedPrice === "mid" && !(minPrice >= 20 && minPrice <= 50)) return false;
      if (selectedPrice === "high" && !(minPrice > 50)) return false;
    }

    // 5) Accessibilité (placeholder)
    if (wantsPMR) {
      // à compléter si ton API renvoie un champ d'accessibilité
    }

    return true;
  });

  result = sortEvents(result, sortMode);
  return result;
}

function sortEvents(events, sortMode = "dateAsc") {
  const copy = [...events];
  if (sortMode === "dateAsc") {
    copy.sort((a, b) => {
      const da = new Date(a?.dates?.start?.localDate || "2100-01-01");
      const db = new Date(b?.dates?.start?.localDate || "2100-01-01");
      return da - db;
    });
  } else if (sortMode === "priceAsc") {
    copy.sort((a, b) => {
      const pa = a?.priceRanges?.[0]?.min || 999999;
      const pb = b?.priceRanges?.[0]?.min || 999999;
      return pa - pb;
    });
  }
  return copy;
}

function matchSingleDateFilter(dateStr, filterValue) {
  const d = new Date(dateStr + "T00:00:00");
  const now = new Date();

  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const tomorrow = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);

  const day = now.getDay(); // 0 dimanche
  const saturday = new Date(now);
  saturday.setDate(now.getDate() + ((6 - day + 7) % 7));
  const sunday = new Date(saturday);
  sunday.setDate(saturday.getDate() + 1);

  const weekEnd = new Date(now);
  weekEnd.setDate(now.getDate() + 7);

  const monthEnd = new Date(now.getFullYear(), now.getMonth() + 1, 0);

  switch (filterValue) {
    case "today":
      return d >= today && d < tomorrow;
    case "weekend":
      return d >= saturday && d <= sunday;
    case "week":
      return d >= today && d <= weekEnd;
    case "month":
      return d >= today && d <= monthEnd;
    default:
      return true;
  }
}

// 🔁 ICI la boucle modifiée pour aller vers resultat.php
function renderEvents(events, container) {
  if (!container) return;
  container.innerHTML = "";

  events.forEach((event) => {
    const title = event?.name || "Événement";
    const date = event?.dates?.start?.localDate || "";

    const city =
      event?._embedded?.venues?.[0]?.city?.name ||
      event?.place?.city ||
      event?.venue?.city ||
      "";

    const priceRange = event?.priceRanges?.[0];
    let priceText = "Voir les billets";
    if (priceRange && typeof priceRange.min !== "undefined") {
      const currency = priceRange.currency || "€";
      priceText = `À partir de ${priceRange.min} ${currency}`;
    }

    const imageUrl = getPrimaryImage(event?.images);
    const eventId = event?.id; // 👈 très important

    const card = document.createElement("article");
    card.className = "card";

    // 👇 on enveloppe toute la carte dans un <a> vers resultat.php
    card.innerHTML = `
      <a href="resultat.php?id=${encodeURIComponent(eventId)}" class="card-link">
        <div class="card-image">
          ${
            imageUrl
              ? `<img src="${imageUrl}" alt="${title}">`
              : `<div class="no-image">Pas d'image</div>`
          }
        </div>
        <div class="card-body">
          <h3>${title}</h3>
          ${date ? `<p class="date">${date}</p>` : ""}
          ${city ? `<p class="city">${city}</p>` : ""}
          <p class="price">${priceText}</p>
        </div>
      </a>
    `;
    container.appendChild(card);
  });

  if (!events.length) {
    container.innerHTML = `<p style="grid-column:1/-1;">Aucun événement ne correspond aux filtres.</p>`;
  }
}


function getPrimaryImage(images) {
  if (!Array.isArray(images) || images.length === 0) return null;
  return images[0].url;
}
