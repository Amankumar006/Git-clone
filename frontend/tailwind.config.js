/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.{js,jsx,ts,tsx}",
    "./public/index.html"
  ],
  theme: {
    extend: {
      fontFamily: {
        'serif': ['Georgia', 'Cambria', 'Times New Roman', 'Times', 'serif'],
        'sans': ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif']
      },
      colors: {
        primary: {
          50: '#f0f9ff',
          100: '#e0f2fe',
          500: '#0ea5e9',
          600: '#0284c7',
          700: '#0369a1',
          900: '#0c4a6e'
        },
        gray: {
          50: '#f9fafb',
          100: '#f3f4f6',
          200: '#e5e7eb',
          300: '#d1d5db',
          400: '#9ca3af',
          500: '#6b7280',
          600: '#4b5563',
          700: '#374151',
          800: '#1f2937',
          900: '#111827'
        }
      },
      typography: {
        DEFAULT: {
          css: {
            maxWidth: '65ch',
            color: '#374151',
            lineHeight: '1.75',
            fontSize: '1.125rem',
            h1: {
              fontSize: '2.25rem',
              fontWeight: '700',
              lineHeight: '1.2',
              marginBottom: '1rem'
            },
            h2: {
              fontSize: '1.875rem',
              fontWeight: '600',
              lineHeight: '1.3',
              marginTop: '2rem',
              marginBottom: '1rem'
            },
            h3: {
              fontSize: '1.5rem',
              fontWeight: '600',
              lineHeight: '1.4',
              marginTop: '1.5rem',
              marginBottom: '0.75rem'
            },
            p: {
              marginBottom: '1.5rem'
            },
            blockquote: {
              borderLeftColor: '#e5e7eb',
              borderLeftWidth: '4px',
              paddingLeft: '1rem',
              fontStyle: 'italic',
              color: '#6b7280'
            }
          }
        }
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography')
  ],
}