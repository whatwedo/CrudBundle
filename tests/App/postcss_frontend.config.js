let tailwindcss = require('tailwindcss');

module.exports = {
    plugins: [
        tailwindcss('./tailwind_frontend.config.js'),
        require('autoprefixer'),
    ]
}
