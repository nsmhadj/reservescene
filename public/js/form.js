

document.addEventListener("DOMContentLoaded", () => {
  const nbSelect = document.getElementById("nb_places");
  const guestBlock = document.getElementById("guest-block");
  const form = document.getElementById("reservation-form");

  
  if (nbSelect && guestBlock) {
    const toggleGuest = () => {
      if (nbSelect.value === "2") {
        guestBlock.classList.add("guest-visible");
      } else {
        guestBlock.classList.remove("guest-visible");
        
        const guestInputs = guestBlock.querySelectorAll("input");
        guestInputs.forEach((input) => (input.value = ""));
      }
    };

    nbSelect.addEventListener("change", toggleGuest);
    toggleGuest(); 
  }

  
  if (form) {
    form.addEventListener("submit", (e) => {
      const lastname = document.getElementById("holder_lastname");
      const firstname = document.getElementById("holder_firstname");
      const email = document.getElementById("holder_email");

      let hasError = false;
      [lastname, firstname, email].forEach((field) => {
        if (!field) return;
        field.classList.remove("field-error");
        if (!field.value.trim()) {
          field.classList.add("field-error");
          hasError = true;
        }
      });

      if (email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        email.classList.add("field-error");
        hasError = true;
      }

      if (nbSelect && nbSelect.value === "2") {
        const gl = document.getElementById("guest_lastname");
        const gf = document.getElementById("guest_firstname");
        [gl, gf].forEach((field) => {
          if (!field) return;
          field.classList.remove("field-error");
          if (!field.value.trim()) {
            field.classList.add("field-error");
            hasError = true;
          }
        });
      }

      if (hasError) {
        e.preventDefault();
        alert("Merci de vérifier les champs obligatoires.");
      }
    });
  }
});
