/**
 * Sudarshan Fitness v2.0 Theme Engine
 * Persistent Theme Switcher (Festive Navratri / Dark / Light / System Mode + Accent Customizer)
 */

(function () {
    const THEME_KEY = 'sf_v2_theme_mode';
    const ACCENT_KEY = 'sf_v2_accent_color';
    const FESTIVE_MIGRATION_KEY = 'sf_v2_festive_navratri_v1';

    // Set default theme to 'festive' for the upcoming Hindu festival season!
    if (!localStorage.getItem(FESTIVE_MIGRATION_KEY)) {
        localStorage.setItem(THEME_KEY, 'festive');
        localStorage.setItem(FESTIVE_MIGRATION_KEY, '1');
    }

    function applyThemeMode(theme) {
        if (!theme) theme = localStorage.getItem(THEME_KEY) || 'festive';

        let effectiveTheme = theme;
        if (theme === 'system') {
            effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        document.documentElement.setAttribute('data-theme', effectiveTheme);
        
        if (effectiveTheme === 'festive') {
            document.body && document.body.classList.add('festive-theme-active');
        } else {
            document.body && document.body.classList.remove('festive-theme-active');
        }

        localStorage.setItem(THEME_KEY, theme);

        // Keep dropdown select in sync if rendered
        const select = document.getElementById('sf-theme-select');
        if (select && select.value !== theme) {
            select.value = theme;
        }
    }

    function applyAccentColor(color) {
        if (!color) color = localStorage.getItem(ACCENT_KEY) || '#FF5722';
        document.documentElement.style.setProperty('--accent-primary', color);
        localStorage.setItem(ACCENT_KEY, color);
    }

    // Initialize immediately to prevent FOUC
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            applyThemeMode();
            applyAccentColor();
        });
    } else {
        applyThemeMode();
        applyAccentColor();
    }

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
            return localStorage.getItem(THEME_KEY) || 'festive';
        },
        setAccentColor: function (hexColor) {
            applyAccentColor(hexColor);
        },
        getAccentColor: function () {
            return localStorage.getItem(ACCENT_KEY) || '#FF5722';
        }
    };
})();


