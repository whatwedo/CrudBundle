const defaultTheme = require('tailwindcss/defaultTheme')
const colors = require('tailwindcss/colors')

module.exports = {
    content: [
        './assets/**/*.js',
        './templates/**/*.{html,html.twig}',
        './vendor/whatwedo/**/*.{html,html.twig,js}',
        './var/cache/twig/**/*.php',
        './src/Definition/*.php',
    ],
    safelist: [
        {
            pattern: /grid-(cols|rows)-\d/,
            variants: ['2xl', 'xl', 'lg', 'md', 'sm'],
        },
        {
            pattern: /col-span-\d+/,
            variants: ['lg', 'md', 'sm'],
        },
        'border-red-700',
    ],
    media: false,
    theme: {
        extend: {
            fontFamily: {
                sans: [...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                sm: ['12px', '16px'],
                base: ['14px', '20px'],
                '2xl': ['20px', '28px'],
            },
            colors: {
                primary: colors.sky,
                neutral: colors.slate,
                error: colors.red,
                warning: colors.amber,
                success: colors.green,
            },
            flexBasis: {
                '3/6-gap': 'calc(50% - 0.5rem)', // this is an ugly hack to float blocks
            }
        },
    },
    variants: {},
    plugins: [
        require('@tailwindcss/forms'),
    ],
}
