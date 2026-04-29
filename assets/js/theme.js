(function () {
    const currentTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', currentTheme);

    window.addEventListener('DOMContentLoaded', () => {
        const themeSwitch = document.getElementById('theme-switch-checkbox');

        // Si el checkbox existe en la página actual (ej. perfil.php), ajustamos su estado
        if (themeSwitch) {
            themeSwitch.checked = currentTheme === 'dark';

            themeSwitch.addEventListener('change', (e) => {
                const newTheme = e.target.checked ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
            });
        }
    });
})();

// Registro de Service Worker para PWA (para vistas logueadas)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/nutriassist/sw.js')
            .then(reg => console.log('Service Worker registrado en vista interna!', reg.scope))
            .catch(err => console.log('Error registrando Service Worker:', err));
    });
}
