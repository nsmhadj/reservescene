
const accessibilityToggle = document.getElementById('accessibility-toggle');
const accessibilityDropdown = document.getElementById('accessibility-dropdown');

const fontDecreaseBtn = document.getElementById('font-decrease');
const fontDefaultBtn = document.getElementById('font-default');
const fontIncreaseBtn = document.getElementById('font-increase');
const contrastToggleBtn = document.getElementById('contrast-toggle');
const dyslexiaToggleBtn = document.getElementById('dyslexia-toggle');

let currentFontSize = 100; 


accessibilityToggle.addEventListener('click', () => {
    const expanded = accessibilityToggle.getAttribute('aria-expanded') === 'true';
    accessibilityToggle.setAttribute('aria-expanded', !expanded);
    accessibilityDropdown.style.display = expanded ? 'none' : 'block';
    accessibilityDropdown.setAttribute('aria-hidden', expanded);
});


function updateFontSize() {
    document.documentElement.style.fontSize = currentFontSize + '%';
}


fontDecreaseBtn.addEventListener('click', () => {
    currentFontSize = Math.max(70, currentFontSize - 10);
    updateFontSize();
});

fontDefaultBtn.addEventListener('click', () => {
    currentFontSize = 100;
    updateFontSize();
});

fontIncreaseBtn.addEventListener('click', () => {
    currentFontSize = Math.min(150, currentFontSize + 10);
    updateFontSize();
});


contrastToggleBtn.addEventListener('click', () => {
    document.body.classList.toggle('contrast-mode');
});


dyslexiaToggleBtn.addEventListener('click', () => {
    document.body.classList.toggle('dyslexia-font');
});


document.addEventListener('click', (e) => {
    if (!accessibilityToggle.contains(e.target) && !accessibilityDropdown.contains(e.target)) {
        accessibilityDropdown.style.display = 'none';
        accessibilityToggle.setAttribute('aria-expanded', 'false');
        accessibilityDropdown.setAttribute('aria-hidden', 'true');
    }
});
