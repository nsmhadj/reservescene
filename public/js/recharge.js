

document.addEventListener('DOMContentLoaded', () => {
  
  const successMessage = document.querySelector('.recharge-message.success');
  if (successMessage) {
    
    setTimeout(() => {
      successMessage.style.opacity = '0';
      successMessage.style.transition = 'opacity 0.6s ease';
      setTimeout(() => {
        successMessage.style.display = 'none';
      }, 600);
    }, 6000);
  }
});