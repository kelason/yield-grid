/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        farm: {
          50: '#f2f9f1',
          100: '#e1f2df',
          200: '#c5e5c0',
          300: '#9ad191',
          400: '#69b75d',
          500: '#469b3a',
          600: '#347b2a',
          700: '#2a6223',
          800: '#254e20',
          900: '#1f401c',
        },
        earth: {
          50: '#fdf8f4',
          100: '#f9eee5',
          200: '#f2d8c3',
          300: '#e9bc9b',
          400: '#dd986d',
          500: '#d37b46',
          600: '#c56138',
          700: '#a44d2f',
          800: '#83402a',
          900: '#6a3625',
        }
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      }
    },
  },
  plugins: [],
}
