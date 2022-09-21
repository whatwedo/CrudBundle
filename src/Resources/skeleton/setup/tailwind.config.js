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
