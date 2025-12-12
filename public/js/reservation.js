

document.addEventListener("DOMContentLoaded", () => {
  const cards = document.querySelectorAll(".reservation-card-item");

  cards.forEach((card) => {
    card.addEventListener("mouseenter", () => {
      card.classList.add("hovered");
    });
    card.addEventListener("mouseleave", () => {
      card.classList.remove("hovered");
    });
  });
});
