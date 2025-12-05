// profil.js : interactions de la page profil
document.addEventListener('DOMContentLoaded', () => {
  const successMsg = document.querySelector('.recharge-message.success');
  if (successMsg) {
    // Masquer le message de succès après quelques secondes
    setTimeout(() => {
      successMsg.style.opacity = '0';
      successMsg.style.transition = 'opacity .6s';
      setTimeout(() => {
        successMsg.style.display = 'none';
      }, 600);
    }, 6000);
  }
});