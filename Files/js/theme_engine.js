/**
 * Sudarshan Fitness v2.0 Theme Engine
 * Persistent Theme Switcher (Dark / Light / System Mode + Primary Accent Customizer)
 */

(function () {
    const THEME_KEY = 'sf_v2_theme_mode';
    const ACCENT_KEY = 'sf_v2_accent_color';

    function applyThemeMode(theme) {
        if (!theme) theme = localStorage.getItem(THEME_KEY) || 'dark';

        let effectiveTheme = theme;
        if (theme === 'system') {
            effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        if (effectiveTheme === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }

        localStorage.setItem(THEME_KEY, theme);
    }

    function applyAccentColor(color) {
        if (!color) color = localStorage.getItem(ACCENT_KEY) || '#ff003c';
        document.documentElement.style.setProperty('--accent-primary', color);
        localStorage.setItem(ACCENT_KEY, color);
    }

    // Initialize immediately to prevent FOUC
    applyThemeMode();
    applyAccentColor();

    // Listen to system preference changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (localStorage.getItem(THEME_KEY) === 'system') {
            applyThemeMode('system');
        }
    });

    // Expose global controller
    window.SFThemeEngine = {
        setThemeMode: function (mode) {
            applyThemeMode(mode);
        },
        getThemeMode: function () {
            return localStorage.getItem(THEME_KEY) || 'dark';
        },
        setAccentColor: function (hexColor) {
            applyAccentColor(hexColor);
        },
        getAccentColor: function () {
            return localStorage.getItem(ACCENT_KEY) || '#00f0ff';
        }
    };
})();
