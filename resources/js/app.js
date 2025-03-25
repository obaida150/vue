// import './bootstrap';
// import '../css/app.css';
//
// import { createApp, h } from 'vue';
// import { createInertiaApp } from '@inertiajs/vue3';
// import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
// import { ZiggyVue } from '../../vendor/tightenco/ziggy';
//
// import PrimeVue from 'primevue/config';
// import Aura from '@primeuix/themes/aura';
// import "primeicons/primeicons.css"
//
// const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
//
// // Initialize dark mode before app creation
// const initializeDarkMode = () => {
//     // Check localStorage first
//     const savedTheme = localStorage.getItem('theme');
//     if (savedTheme === 'dark') {
//         document.documentElement.classList.add('dark');
//     } else if (savedTheme === 'light') {
//         document.documentElement.classList.remove('dark');
//     } else {
//         // Check system preference if no saved preference
//         if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
//             document.documentElement.classList.add('dark');
//         }
//     }
// };
//
// // Initialize dark mode
// initializeDarkMode();
//
// createInertiaApp({
//     title: (title) => `${title} - ${appName}`,
//     resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
//     setup({ el, App, props, plugin }) {
//         return createApp({ render: () => h(App, props) })
//             .use(plugin)
//             .use(ZiggyVue)
//             .use(PrimeVue, {
//                 theme: {
//                     preset: Aura
//                 }
//             })
//             .mount(el);
//     },
//     progress: {
//         color: window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? '#D1D5DB' : '#4B5563',
//     },
// });
//
// // Add event listener for system theme changes
// window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
//     const savedTheme = localStorage.getItem('theme');
//     // Only apply system preference if user hasn't set a preference
//     if (!savedTheme) {
//         if (e.matches) {
//             document.documentElement.classList.add('dark');
//         } else {
//             document.documentElement.classList.remove('dark');
//         }
//     }
// });
import "./bootstrap"
import "../css/app.css"

import { createApp, h } from "vue"
import { createInertiaApp } from "@inertiajs/vue3"
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers"
import { ZiggyVue } from "../../vendor/tightenco/ziggy"
// import route from "ziggy-js"

import PrimeVue from "primevue/config"
import Aura from "@primeuix/themes/aura"
import ToastService from "primevue/toastservice"

// PrimeVue CSS Importe
// import "primevue/resources/primevue.min.css"
import "primeicons/primeicons.css"

const appName = import.meta.env.VITE_APP_NAME || "Laravel"

// Initialize dark mode before app creation
const initializeDarkMode = () => {
    // Check localStorage first
    const savedTheme = localStorage.getItem("theme")
    if (savedTheme === "dark") {
        document.documentElement.classList.add("dark")
    } else if (savedTheme === "light") {
        document.documentElement.classList.remove("dark")
    } else {
        // Check system preference if no saved preference
        if (window.matchMedia("(prefers-color-scheme: dark)").matches) {
            document.documentElement.classList.add("dark")
        }
    }
}

// Initialize dark mode
initializeDarkMode()

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob("./Pages/**/*.vue")),
    setup({ el, App, props, plugin }) {
        // Stellen Sie sicher, dass route global verfügbar ist
        window.route = route

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                },
            })
            .use(ToastService) // Hier fügen wir den ToastService hinzu
            .mount(el)
    },
    progress: {
        color: window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches ? "#D1D5DB" : "#4B5563",
    },
})

// Add event listener for system theme changes
window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", (e) => {
    const savedTheme = localStorage.getItem("theme")
    // Only apply system preference if user hasn't set a preference
    if (!savedTheme) {
        if (e.matches) {
            document.documentElement.classList.add("dark")
        } else {
            document.documentElement.classList.remove("dark")
        }
    }
})

