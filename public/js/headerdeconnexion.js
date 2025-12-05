// Menu burger
document.addEventListener('DOMContentLoaded', () => {
    const burger = document.querySelector('.burger-btn');
    const nav = document.querySelector('.header-nav');

    burger.addEventListener('click', () => {
        nav.classList.toggle('active');
    });
});
