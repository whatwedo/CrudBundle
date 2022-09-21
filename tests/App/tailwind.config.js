const colors = require('tailwindcss/colors')

module.exports = {
    content: [
        './assets/**/*.js',
        './templates/**/*.{html,html.twig}',
        './vendor/whatwedo/**/*.{html,html.twig,js,scss}',
        './var/cache/twig/**/*.php',
        './src/Definition/*.php',
    ],
    safelist: [
        {
            pattern: /grid-(cols|rows)-\d/,
            variants: ['lg', 'md', 'sm'],
        },
    ],
    theme: {
        extend: {
            colors: {
                // needs fixing
                'neutral-900': '#eeeeee',
                'neutral-700': '#eeeeee',
                'neutral-300': '#eeeeee',
                'neutral-500': '#eeeeee',
                'neutral-400': '#eeeeee',
                'neutral-200': '#eeeeee',
                'neutral-100': '#eeeeee',
                'neutral-50': '#eeeeee',
                primary: {
                    lightest: '#6EDBFF',
                    light: '#48C0E8',
                    DEFAULT: '#007EA8',
                    dark: '#336C80',
                    darkest: '#0F4152',
                },
                error: colors.red,
            }
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
}
