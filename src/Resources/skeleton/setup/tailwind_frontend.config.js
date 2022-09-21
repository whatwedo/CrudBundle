const colors = require('tailwindcss/colors')

module.exports = {
    content: [
        './assets/**/*.js',
        './templates/**/*.{html,html.twig}',
        './var/cache/twig/**/*.php',
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
