/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: ["./index.html", "./src/**/*.{vue,js,ts,jsx,tsx}"],
  safelist: ["badge-available", "badge-reserved", "badge-sold"],
  theme: {
    extend: {
      colors: {
        // Verde esmeralda fresco (Eco Fresh) — mismo key "brand" para no romper plantillas
        brand: {
          50:  '#ecfdf5',
          100: '#d1fae5',
          200: '#a7f3d0',
          300: '#6ee7b7',   // menta
          400: '#34d399',
          500: '#10b981',   // esmeralda principal
          600: '#059669',
          700: '#047857',
          800: '#065f46',   // bosque profundo
          900: '#064e3b',
          DEFAULT: '#10b981',
          dark: '#047857',
        },
        // Acento lima/menta para brillos y CTAs frescos
        lime: {
          50:  '#f7fee7',
          100: '#ecfccb',
          200: '#d9f99d',
          300: '#bef264',
          400: '#a3e635',   // lima (acento)
          500: '#84cc16',
          600: '#65a30d',
          DEFAULT: '#a3e635',
        },
        mint: {
          DEFAULT: '#6ee7b7',
          light: '#a7f3d0',
        },
        // Crema para superficies cálidas (light)
        cream: {
          50:  '#fbf7ec',
          100: '#f2e8cf',
          200: '#e9dbb4',
          DEFAULT: '#f2e8cf',
        },
        // Rojo/coral para acciones destructivas / errores
        danger: {
          50:  '#fef2f2',
          100: '#fee2e2',
          200: '#fecaca',
          300: '#fca5a5',
          400: '#f87171',
          500: '#ef4444',
          600: '#dc2626',
          700: '#b91c1c',
          DEFAULT: '#ef4444',
        },
        ink: { DEFAULT: '#0b1512', soft: '#33413a', muted: '#5c6b60', faint: '#8a978c' },
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        display: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
      },
      borderRadius: { xl: '0.875rem', '2xl': '1.125rem', '3xl': '1.5rem', '4xl': '2rem' },
      boxShadow: {
        card: '0 1px 2px rgba(6,78,59,.06), 0 8px 24px -8px rgba(6,78,59,.14)',
        'card-hover': '0 18px 44px -12px rgba(6,78,59,.28)',
        soft: '0 1px 3px rgba(6,78,59,.08)',
        glow: '0 0 0 1px rgba(16,185,129,.12), 0 12px 40px -8px rgba(16,185,129,.45)',
        'glow-lime': '0 0 32px -6px rgba(163,230,53,.55)',
      },
      backdropBlur: { xs: '2px' },
      keyframes: {
        'float': {
          '0%,100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-10px)' },
        },
        'gradient-shift': {
          '0%,100%': { backgroundPosition: '0% 50%' },
          '50%': { backgroundPosition: '100% 50%' },
        },
        'glow-pulse': {
          '0%,100%': { opacity: '.55' },
          '50%': { opacity: '.9' },
        },
      },
      animation: {
        'float': 'float 6s ease-in-out infinite',
        'float-slow': 'float 9s ease-in-out infinite',
        'gradient-shift': 'gradient-shift 8s ease infinite',
        'glow-pulse': 'glow-pulse 4s ease-in-out infinite',
      },
    },
  },
  plugins: [],
}
