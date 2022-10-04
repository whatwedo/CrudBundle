const colors = require('tailwindcss/colors')

module.exports = {
    content: [
        './assets/**/*.js',
        './templates/**/*.{html,html.twig}',
        './var/cache/twig/**/*.php',
    ],
    safelist: [
        'sm:grid-cols-1', 'sm:grid-cols-2', 'sm:grid-cols-3', 'sm:grid-cols-4', 'sm:grid-cols-5', 'sm:grid-cols-6', 'sm:grid-cols-7', 'sm:grid-cols-8', 'sm:grid-cols-9', 'sm:grid-cols-10', 'sm:grid-cols-11', 'sm:grid-cols-12',
        'sm:grid-rows-1', 'sm:grid-rows-2', 'sm:grid-rows-3', 'sm:grid-rows-4', 'sm:grid-rows-5', 'sm:grid-rows-6',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#eeeeee',
                error: colors.red,
            }
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
}
