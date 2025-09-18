/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './index.html',
    './src/**/*.{ts,tsx,js,jsx}'
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#eef9ff',
          100: '#d9f1ff',
          200: '#b6e6ff',
          300: '#83d7ff',
          400: '#3cc0ff',
          500: '#009fe6',
          600: '#007ec2',
          700: '#00639d',
          800: '#034f7d',
          900: '#063f63',
        }
      }
    },
  },
  plugins: [],
};
