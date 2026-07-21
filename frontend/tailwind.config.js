/** @type {import('tailwindcss').Config} */
export default {
  content: ["./index.html", "./src/**/*.{vue,js,ts,jsx,tsx}"],
  safelist: ["badge-available", "badge-reserved", "badge-sold"],
  theme: {
    extend: {
      colors: {
        // Verde corporativo (bosque -> lima)
        brand: {
          50:  '#f3f7ee',
          100: '#e4eecd',
          200: '#cfe0a8',
          300: '#a7c957',   // lima (paleta)
          400: '#8bb552',
          500: '#6a994e',   // verde medio (paleta)
          600: '#557d3f',
          700: '#386641',   // bosque (paleta)
          800: '#2c5133',
          900: '#24422b',
          DEFAULT: '#386641',
          dark: '#2c5133',
        },
        // Crema (paleta) para superficies cálidas
        cream: {
          50:  '#fbf7ec',
          100: '#f2e8cf',   // crema (paleta)
          200: '#e9dbb4',
          DEFAULT: '#f2e8cf',
        },
        // Rojo corporativo (paleta) para acciones destructivas / errores
        danger: {
          50:  '#fbecec',
          100: '#f6d7d7',
          200: '#eab3b4',
          300: '#dd8a8b',
          400: '#cc6365',
          500: '#bc4749',   // rojo (paleta)
          600: '#a5393b',
          700: '#872f31',
          DEFAULT: '#bc4749',
        },
        ink: { DEFAULT: '#1a2e21', soft: '#33413a', muted: '#5c6b60', faint: '#8a978c' },
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        display: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
      },
      borderRadius: { xl: '0.875rem', '2xl': '1.125rem', '3xl': '1.5rem' },
      boxShadow: {
        card: '0 1px 2px rgba(24,46,33,.05), 0 4px 16px -4px rgba(24,46,33,.10)',
        'card-hover': '0 10px 30px -6px rgba(24,46,33,.20)',
        soft: '0 1px 3px rgba(24,46,33,.07)',
      },
    },
  },
  plugins: [],
}
