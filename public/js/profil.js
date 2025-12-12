
document.addEventListener('DOMContentLoaded', () => {
  const successMsg = document.querySelector('.recharge-message.success');
  if (successMsg) {
    
    setTimeout(() => {
      successMsg.style.opacity = '0';
      successMsg.style.transition = 'opacity .6s';
      setTimeout(() => {
        successMsg.style.display = 'none';
      }, 600);
    }, 6000);
  }
});