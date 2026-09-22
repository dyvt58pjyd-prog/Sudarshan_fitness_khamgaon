/**
 * Sudarshan Fitness v2.0 Theme Engine
 * Persistent Theme Switcher (Dark / Light / System Mode + Primary Accent Customizer)
 */

(function () {
    const THEME_KEY = 'sf_v2_theme_mode';
    const ACCENT_KEY = 'sf_v2_accent_color';

    // One-time automatic migration to reset stale dark/brown cache to Apple Minimalist Light
    if (!localStorage.getItem('sf_v2_apple_migrated')) {
        localStorage.setItem(THEME_KEY, 'light');
        localStorage.setItem('sf_v2_apple_migrated', '1');
    }

    function applyThemeMode(theme) {
        if (!theme) theme = localStorage.getItem(THEME_KEY) || 'light';

        let effectiveTheme = theme;
        if (theme === 'system') {
            effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        if (effectiveTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
        }

        localStorage.setItem(THEME_KEY, theme);
    }

    function applyAccentColor(color) {
        if (!color) color = localStorage.getItem(ACCENT_KEY) || '#007aff';
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
            return localStorage.getItem(THEME_KEY) || 'light';
        },
        setAccentColor: function (hexColor) {
            applyAccentColor(hexColor);
        },
        getAccentColor: function () {
            return localStorage.getItem(ACCENT_KEY) || '#007aff';
        }
    };
})();

