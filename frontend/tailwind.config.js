/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        // ── Organic Palette ─────────────────────────────────────────────
        // moss: Primary brand greens — growth, vitality, CTAs
        moss: {
          50: '#f2f7f2',
          100: '#e1eddf',
          200: '#c4dbc0',
          300: '#9ac494',
          400: '#6aa862',
          500: '#4a8c42',
          600: '#3a7033',
          700: '#2d5829',
          800: '#254622',
          900: '#1c361a',
        },
        // harvest: Warm ambers — highlights, badges, financial figures
        harvest: {
          50: '#fdf9ee',
          100: '#f9f0d0',
          200: '#f2de9a',
          300: '#eaca6a',
          400: '#e0b23c',
          500: '#d49a20',
          600: '#b87e18',
          700: '#936212',
          800: '#6e490d',
          900: '#4a3009',
        },
        // soil: Warm browns — structural, sidebar, footer, borders
        soil: {
          50: '#faf6f2',
          100: '#f2e9df',
          200: '#e4cdb9',
          300: '#d4ae93',
          400: '#c08d6d',
          500: '#a86e4a',
          600: '#8a5438',
          700: '#6f4028',
          800: '#573020',
          900: '#3d2016',
        },
        // dew: Soft sky blues — weather, AI, informational
        dew: {
          50: '#f0f7ff',
          100: '#daeeff',
          200: '#b3dcff',
          300: '#80c5ff',
          400: '#4aa8f5',
          500: '#2a8de0',
          600: '#1e72c0',
          700: '#165898',
          800: '#104070',
          900: '#0a2a4a',
        },
        // stone: Warm off-white/linen neutrals — pages, cards, surfaces
        stone: {
          50: '#fafaf9',
          100: '#f4f3f0',
          200: '#e8e4de',
          300: '#d4cec5',
          400: '#b5aca0',
          500: '#918880',
          600: '#706760',
          700: '#524c46',
          800: '#3a3530',
          900: '#1e1a16',
        },
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        serif: ['Lora', 'Georgia', 'serif'],
      },
      borderRadius: {
        '4xl': '2rem',
        '5xl': '3rem',
      },
      boxShadow: {
        soft: '0 4px 6px -1px rgba(74, 140, 66, 0.12), 0 2px 4px -2px rgba(74, 140, 66, 0.08)',
        organic:
          '0 10px 15px -3px rgba(168, 110, 74, 0.15), 0 4px 6px -4px rgba(168, 110, 74, 0.1)',
        'harvest-glow': '0 4px 14px 0 rgba(212, 154, 32, 0.3)',
      },
    },
  },
  plugins: [],
}
