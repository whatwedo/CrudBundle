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
                    200: '#f1f8fe',
                    300: '#b1d2f2',
                    400: '#70a1d3',
                    500: '#4682c3',
                    600: '#35689d',
                    700: '#25496e',
                    800: '#001c5c',
                },
                neutral: colors.slate,
                error: colors.red,
                warning: colors.orange,
                success: colors.green,
            }
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
}
