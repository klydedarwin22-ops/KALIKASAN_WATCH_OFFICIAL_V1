import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

const themeButtons = document.querySelectorAll('[data-theme-toggle]');
const updateThemeButtons = () => {
    const darkMode = document.documentElement.classList.contains('dark');
    themeButtons.forEach((button) => {
        button.setAttribute('aria-pressed', String(darkMode));
        button.querySelector('[data-theme-label]').textContent = darkMode ? 'Light mode' : 'Dark mode';
    });
};

themeButtons.forEach((button) => {
    button.addEventListener('click', () => {
        const darkMode = !document.documentElement.classList.contains('dark');
        document.documentElement.classList.toggle('dark', darkMode);
        localStorage.setItem('theme', darkMode ? 'dark' : 'light');
        updateThemeButtons();
    });
});
updateThemeButtons();

Alpine.start();
