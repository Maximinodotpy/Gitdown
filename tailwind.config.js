/* eslint-disable no-undef */
/* eslint-disable linebreak-style */
/** @type {import('tailwindcss').Config} */
module.exports = {
    corePlugins: {
        preflight: false,
    },
    prefix: 'tw-',
    important: true,
    content: [
        'views/*.php',
        'views/**/*.php',
    ],
    theme: {
        extend: {},
    },
    plugins: [],
}
