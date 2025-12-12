

document.addEventListener("DOMContentLoaded", () => {
  const eventId = window.EVENT_ID;
  let eventData = window.EVENT_DATA;

  if (!eventId) {
    console.error("Aucun id d'événement fourni");
    return;
  }

  if (!eventData) {
    fetch(`/src/api/event.php?id=${eventId}`)
      .then(res => res.json())
      .then(data => {
        eventData = data;
        fillEventData(eventId, data);
      })
      .catch(err => {
        console.error("Erreur de récupération de l'événement", err);
      });
  } else {
    fillEventData(eventId, eventData);
  }

  setupReviews(eventId);
});


function fillEventData(eventId, data) {
  if (!data) return;

  const titleElt    = document.getElementById("event-title");
  const priceElt    = document.getElementById("event-price");
  const venueElt    = document.getElementById("event-venue");
  const dateElt     = document.getElementById("event-date");
  const descElt     = document.getElementById("event-description");
  const imgElt      = document.getElementById("event-image");
  const btnElt      = document.getElementById("reserve-btn");
  const invitedList = document.getElementById("invited-list");

  // --- Titre ---
  const name = data.name || "Événement";

  // --- Date brute ---
  const dates     = data?.dates?.start;
  const localDate = dates?.localDate || "";
  const localTime = dates?.localTime || "";

  // --- Lieu ---
  const venue = data?._embedded?.venues?.[0];
  let address = "";
  if (venue) {
    const parts = [];
    if (venue.name) parts.push(venue.name);
    if (venue.city?.name) parts.push(venue.city.name);
    if (venue.country?.name) parts.push(venue.country.name);
    address = parts.join(" · ");
  }


  const serverPrice = window.EVENT_PRICE;
  if (serverPrice && serverPrice.display) {
    if (priceElt) priceElt.textContent = serverPrice.display;
    
  } else {
 
    let priceText = "";
    const prArr = Array.isArray(data.priceRanges) ? data.priceRanges : [];
    if (prArr.length > 0) {
      const pr = prArr[0];
      const min = (typeof pr.min === 'number') ? pr.min : (pr.min ? Number(pr.min) : null);
      const max = (typeof pr.max === 'number') ? pr.max : (pr.max ? Number(pr.max) : null);
      const currency = pr.currency || 'EUR';

      if (min && max && min !== max) {
        priceText = `À partir de ${formatCurrency(min, currency)} (jusqu'à ${formatCurrency(max, currency)})`;
      } else if (min) {
        priceText = `À partir de ${formatCurrency(min, currency)}`;
      } else if (max) {
        priceText = `Jusqu'à ${formatCurrency(max, currency)}`;
      }
    } else {
      // offers fallback
      const offers = Array.isArray(data.offers) ? data.offers : [];
      if (offers.length > 0) {
        const off = offers[0];
        const price = off.price || off.minPrice || null;
        const currency = off.currency || off.priceCurrency || 'EUR';
        if (price) priceText = `À partir de ${formatCurrency(Number(price), currency)}`;
      }
    }
    if (priceText) {
      if (priceElt) priceElt.textContent = priceText;
    } else {
      // leave existing server content or show a neutral message
      if (priceElt && !priceElt.textContent) priceElt.textContent = 'Prix non renseigné';
    }
  }

  // --- Description ---
  let info = "";
  if (data.info) info = data.info;
  else if (data.pleaseNote) info = data.pleaseNote;
  else if (data.promoter?.description) info = data.promoter.description;

  // --- Image principale ---
  let imageUrl = "";
  if (Array.isArray(data.images) && data.images.length > 0) {
    const sorted = data.images.slice().sort((a, b) => (b.width || 0) - (a.width || 0));
    imageUrl = sorted[0].url;
  }

  // --- Date formatée ---
  const dateText = localDate
    ? formatDateFr(localDate) + (localTime ? `, ${localTime.substring(0, 5)}` : "")
    : "";

  // --- Injection dans le DOM ---
  if (titleElt) titleElt.textContent = name;
  if (venueElt) venueElt.textContent = address;
  if (dateElt)  dateElt.textContent  = dateText;
  if (descElt)  descElt.textContent  = info;
  if (imgElt && imageUrl) {
    imgElt.src = imageUrl;
    imgElt.alt = name;
  }


  if (btnElt) {
    const currentPath = window.location.pathname;
    const dir = currentPath.substring(0, currentPath.lastIndexOf('/') + 1); 
    const formUrl = window.location.origin + dir + 'form.php';

    const params = new URLSearchParams({
      id: eventId,
      title: name,
      date: dateText,
      venue: address
    });

    btnElt.href = formUrl + "?" + params.toString();
  }

  // --- Artistes invités ---
  if (invitedList) {
    invitedList.innerHTML = "";
    const attractions = data?._embedded?.attractions || [];

    if (!attractions.length) {
      const p = document.createElement("p");
      p.textContent = "Pas d’artistes renseignés pour cet événement.";
      invitedList.appendChild(p);
    } else {
      attractions.forEach(attr => {
        const card = document.createElement("div");
        card.className = "artist-card";

        const img = document.createElement("img");
        img.className = "artist-img";
        let aImg = "";
        if (Array.isArray(attr.images) && attr.images.length > 0) {
          aImg = attr.images[0].url;
        }
        img.src = aImg || "https://via.placeholder.com/150x150?text=Artiste";
        img.alt = attr.name || "Artiste";

        const nameEl = document.createElement("p");
        nameEl.className = "artist-name";
        nameEl.textContent = attr.name || "Artiste";

        card.appendChild(img);
        card.appendChild(nameEl);
        invitedList.appendChild(card);
      });
    }
  }
}


function formatDateFr(dateStr) {
  const d = new Date(dateStr);
  if (isNaN(d)) return dateStr;
  return d.toLocaleDateString("fr-FR", {
    year: "numeric",
    month: "long",
    day: "numeric"
  });
}

/**
 * Format currency helper for JS
 */
function formatCurrency(amount, currency) {
  try {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: currency, maximumFractionDigits: 2 }).format(amount);
  } catch (e) {
    return amount.toFixed(2) + ' ' + currency;
  }
}

/**
 * Avis clients (localStorage)
 * (la même que avant)
 */
function setupReviews(eventId) {
  const form = document.getElementById("comment-form");
  const list = document.getElementById("reviews-list");
  if (!form || !list) return;

  const storageKey = `reviews_${eventId}`;

  let reviews = [];
  try {
    const fromStorage = localStorage.getItem(storageKey);
    if (fromStorage) reviews = JSON.parse(fromStorage);
  } catch (e) {
    console.warn("Impossible de lire les reviews du localStorage", e);
  }

  function renderReviews() {
    list.innerHTML = "";
    if (!reviews.length) {
      const empty = document.createElement("p");
      empty.textContent = "Aucun avis pour le moment. Soyez le premier !";
      list.appendChild(empty);
      return;
    }

    reviews.forEach(rev => {
      const card = document.createElement("article");
      card.className = "review-card";

      const header = document.createElement("div");
      header.className = "review-header";

      const nameEl = document.createElement("span");
      nameEl.className = "review-name";
      nameEl.textContent = rev.name || "Anonyme";

      const starsEl = document.createElement("span");
      starsEl.className = "review-stars";
      const starsNum = parseInt(rev.stars, 10) || 0;
      starsEl.textContent = "★".repeat(starsNum) + "☆".repeat(5 - starsNum);

      const textEl = document.createElement("p");
      textEl.className = "review-text";
      textEl.textContent = rev.text || "";

      header.appendChild(nameEl);
      header.appendChild(starsEl);
      card.appendChild(header);
      card.appendChild(textEl);
      list.appendChild(card);
    });
  }

  renderReviews();

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const nameInput = document.getElementById("comment-name");
    const starSelect = document.getElementById("comment-stars");
    const textArea  = document.getElementById("comment-text");

    const name  = nameInput.value.trim() || "Anonyme";
    const stars = starSelect.value;
    const text  = textArea.value.trim();

    if (!text) {
      alert("Merci de saisir un avis.");
      return;
    }

    const newReview = {
      name,
      stars,
      text,
      createdAt: new Date().toISOString()
    };

    reviews.unshift(newReview);
    try {
      localStorage.setItem(storageKey, JSON.stringify(reviews));
    } catch (err) {
      console.warn("Impossible d'enregistrer l'avis dans localStorage", err);
    }

    textArea.value = "";
    renderReviews();
  });
}
// --- Google Maps state ---
let gMapsReady = false;
let gMapInstance = null;
let gMapMarker = null;
let pendingEventData = null;

// Appelée par le script Google Maps (callback=onGoogleMapsLoaded dans resultat.php)
window.onGoogleMapsLoaded = function onGoogleMapsLoaded() {
  gMapsReady = true;
  if (pendingEventData) {
    setupMapFromEvent(pendingEventData);
  }
};
document.addEventListener("DOMContentLoaded", () => {
  const eventId = window.EVENT_ID;
  let eventData = window.EVENT_DATA;

  if (!eventId) {
    console.error("Aucun id d'événement fourni");
    return;
  }

  if (!eventData) {
    fetch(`/src/api/event.php?id=${eventId}`)
      .then(res => res.json())
      .then(data => {
        eventData = data;
        fillEventData(eventId, data);

        // ➜ on mémorise pour la carte
        pendingEventData = data;
        if (gMapsReady) {
          setupMapFromEvent(data);
        }
      })
      .catch(err => {
        console.error("Erreur de récupération de l'événement", err);
      });
  } else {
    fillEventData(eventId, eventData);

    //  même chose si les données viennent du serveur
    pendingEventData = eventData;
    if (gMapsReady) {
      setupMapFromEvent(eventData);
    }
  }

  setupReviews(eventId);
});
/**
 * Configure la carte Google Maps à partir des données de l'événement
 */
function setupMapFromEvent(data) {
  if (!data) return;
  const mapContainer = document.getElementById("event-map");
  if (!mapContainer) return;
  if (!window.google || !google.maps) {
    console.warn("Google Maps pas encore prêt");
    return;
  }

  const venue = data?._embedded?.venues?.[0];
  if (!venue) {
    console.warn("Pas de lieu dans les données de l'événement");
    return;
  }

  // --- Adresse complète pour affichage / géocodage ---
  const addrParts = [];
  if (venue.address?.line1) addrParts.push(venue.address.line1);
  if (venue.postalCode) addrParts.push(venue.postalCode);
  if (venue.city?.name) addrParts.push(venue.city.name);
  if (venue.country?.name) addrParts.push(venue.country.name);
  const fullAddress = addrParts.join(", ");

  // --- On tente d'abord avec latitude/longitude Ticketmaster ---
  let lat = parseFloat(venue.location?.latitude);
  let lng = parseFloat(venue.location?.longitude);

  if (!isNaN(lat) && !isNaN(lng)) {
    initOrUpdateMap(lat, lng, fullAddress || venue.name || "Lieu du concert");
    return;
  }


  if (!fullAddress) {
    console.warn("Impossible de géocoder : adresse vide");
    return;
  }

  const geocoder = new google.maps.Geocoder();
  geocoder.geocode({ address: fullAddress }, (results, status) => {
    if (status === "OK" && results[0]) {
      const loc = results[0].geometry.location;
      initOrUpdateMap(loc.lat(), loc.lng(), fullAddress);
    } else {
      console.warn("Geocoding échoué :", status);
    }
  });
}


function initOrUpdateMap(lat, lng, title) {
  const mapContainer = document.getElementById("event-map");
  if (!mapContainer) return;

  const center = { lat, lng };

  if (!gMapInstance) {
    gMapInstance = new google.maps.Map(mapContainer, {
      center,
      zoom: 15,
    });
  } else {
    gMapInstance.setCenter(center);
  }

  if (!gMapMarker) {
    gMapMarker = new google.maps.Marker({
      position: center,
      map: gMapInstance,
      title: title || "",
    });
  } else {
    gMapMarker.setPosition(center);
    if (title) gMapMarker.setTitle(title);
  }
}
