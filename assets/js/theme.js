(function() {
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
