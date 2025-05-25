import "./bootstrap"
import "../css/app.css"

import { createApp, h } from "vue"
import { createInertiaApp } from "@inertiajs/vue3"
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers"
import { ZiggyVue } from "../../vendor/tightenco/ziggy"
import { route } from "../../vendor/tightenco/ziggy"

// PrimeVue imports
import PrimeVue from "primevue/config"
import Aura from "@primeuix/themes/aura"
import ConfirmationService from "primevue/confirmationservice"
import ToastService from "primevue/toastservice"
import Tooltip from "primevue/tooltip"

// PrimeVue Komponenten für das Dashboard
import Avatar from "primevue/avatar"
import Dropdown from "primevue/dropdown"
import Chart from "primevue/chart"
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import Tag from "primevue/tag"

// Zusätzliche PrimeVue Komponenten für Reports
import Button from "primevue/button"
import Dialog from "primevue/dialog"
import InputText from "primevue/inputtext"
import Textarea from "primevue/textarea"
import Calendar from "primevue/calendar"
import Toast from "primevue/toast"
import ConfirmDialog from "primevue/confirmdialog"

// PrimeVue CSS Importe
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
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                },
            })
            .use(ToastService)
            .use(ConfirmationService)
            .directive("tooltip", Tooltip)

        // Make route function globally available
        app.config.globalProperties.route = route
        window.route = route

        // Registriere PrimeVue Komponenten für das Dashboard
        app.component("Avatar", Avatar)
        app.component("Dropdown", Dropdown)
        app.component("Chart", Chart)
        app.component("DataTable", DataTable)
        app.component("Column", Column)
        app.component("Tag", Tag)

        // Registriere zusätzliche PrimeVue Komponenten für Reports
        app.component("Button", Button)
        app.component("Dialog", Dialog)
        app.component("InputText", InputText)
        app.component("Textarea", Textarea)
        app.component("Calendar", Calendar)
        app.component("Toast", Toast)
        app.component("ConfirmDialog", ConfirmDialog)

        return app.mount(el)
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
