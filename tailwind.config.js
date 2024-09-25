const plugin = require('tailwindcss/plugin');

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
    "./vendor/tales-from-a-dev/flowbite-bundle/templates/**/*.html.twig",
    "./src/Twig/Components/**/*.php",
    "./src/Form/**/*.php",
  ],
  theme: {
    extend: {
      colors: {
        primary: {"50":"#eff6ff","100":"#dbeafe","200":"#bfdbfe","300":"#93c5fd","400":"#60a5fa","500":"#3b82f6","600":"#2563eb","700":"#1d4ed8","800":"#1e40af","900":"#1e3a8a","950":"#172554"},        supplier1: {
          "50": "#ecfeff",
          "100": "#cffafe",
          "200": "#a5f3fc",
          "300": "#67e8f9",
          "400": "#22d3ee",
          "500": "#06b6d4",
          "600": "#0891b2",
          "700": "#0e7490",
          "800": "#155e75",
          "900": "#164e63",
          "950": "#083344"
        },
        supplier2: {
          "50": "#fdf2f8",
          "100": "#fce7f3",
          "200": "#fbcfe8",
          "300": "#f9a8d4",
          "400": "#f472b6",
          "500": "#ec4899",
          "600": "#db2777",
          "700": "#be185d",
          "800": "#9d174d",
          "900": "#831843",
          "950": "#70173a"
        },
        supplier3: { // Earthy Green Theme
          "50": "#f3faf7",
          "100": "#def7ec",
          "200": "#bcf0da",
          "300": "#84e1bc",
          "400": "#31c48d",
          "500": "#0e9f6e",
          "600": "#057a55",
          "700": "#046c4e",
          "800": "#03543f",
          "900": "#014737",
          "950": "#013428"
        },
        supplier4: { // Warm Beige Theme
          "50": "#fafaf4",
          "100": "#f1e6dc",
          "200": "#e3d1c1",
          "300": "#d1bba6",
          "400": "#bfa691",
          "500": "#ae927d",
          "600": "#8f7362",
          "700": "#715947",
          "800": "#544033",
          "900": "#382a22",
          "950": "#1d1511"
        }

      },
      animation: {
        'fade-in': 'fadeIn .5s ease-out;',
        wiggle: 'wiggle 0.5s ease-in-out infinite;',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: 0 },
          '100%': { opacity: 1 },
        },
      },
    },
  },
  plugins: [
    plugin(function({ addVariant }) {
      addVariant('turbo-frame', 'turbo-frame[src] &');
      addVariant('modal', 'dialog &');
    }),
  ],
  options: {
    safelist: [
      /^border-supplier/,   // Ensures all supplier border styles are included
      /^bg-supplier/,       // Ensures all supplier background styles are included
    ],
  },
  darkMode: 'class',
}