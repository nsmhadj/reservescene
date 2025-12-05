// recharge.js — interactions pour la page de rechargement de solde

document.addEventListener('DOMContentLoaded', () => {
  // Sélectionne le message de succès s'il existe
  const successMessage = document.querySelector('.recharge-message.success');
  if (successMessage) {
    // Cache doucement le message après quelques secondes
    setTimeout(() => {
      successMessage.style.opacity = '0';
      successMessage.style.transition = 'opacity 0.6s ease';
      setTimeout(() => {
        successMessage.style.display = 'none';
      }, 600);
    }, 6000);
  }
});