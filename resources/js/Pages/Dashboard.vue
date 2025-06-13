<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- PWA Status Card -->
                <div v-if="isPWA" class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-green-800 font-medium">App-Modus aktiv</span>
                        <span class="text-green-600 text-sm ml-2">Sie nutzen die installierte App-Version</span>
                    </div>
                </div>

                <!-- Offline Status -->
                <div v-if="!isOnline" class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <span class="text-yellow-800 font-medium">Offline-Modus</span>
                        <span class="text-yellow-600 text-sm ml-2">Einige Funktionen sind eingeschränkt verfügbar</span>
                    </div>
                </div>

                <!-- Quick Actions Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Urlaubsantrag -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Urlaubsantrag</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Neuen Antrag stellen</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <Link :href="route('vacation')" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                    Antrag stellen
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Kalender -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Kalender</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Termine verwalten</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <Link :href="route('calendar')" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                                    Öffnen
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Parkplatz -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Parkplatz</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Platz reservieren</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <Link :href="route('parking.index')" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 active:bg-purple-900 focus:outline-none focus:border-purple-900 focus:ring ring-purple-300 disabled:opacity-25 transition ease-in-out duration-150">
                                    Reservieren
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Berichtsheft (nur für Azubis) -->
                    <div v-if="$page.props.auth.user.is_apprentice" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Berichtsheft</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Berichte verwalten</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <Link :href="route('reports.index')" class="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700 active:bg-orange-900 focus:outline-none focus:border-orange-900 focus:ring ring-orange-300 disabled:opacity-25 transition ease-in-out duration-150">
                                    Öffnen
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Welcome Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <h1 class="text-2xl font-medium text-gray-900 dark:text-white">
                            Willkommen, {{ $page.props.auth.user.name }}!
                        </h1>

                        <p class="mt-6 text-gray-500 dark:text-gray-400 leading-relaxed">
                            Willkommen im Mitarbeiterportal. Hier können Sie Ihre Urlaubsanträge verwalten, 
                            Ihren Kalender einsehen, Parkplätze reservieren und vieles mehr.
                        </p>
                    </div>

                    <div class="bg-gray-200 dark:bg-gray-800 bg-opacity-25 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8 p-6 lg:p-8">
                        <div>
                            <div class="flex items-center">
                                <svg class="w-6 h-6 stroke-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                                <h2 class="ml-3 text-xl font-semibold text-gray-900 dark:text-white">
                                    Mobile App
                                </h2>
                            </div>

                            <p class="mt-4 text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                                Diese Anwendung funktioniert als Progressive Web App (PWA). Sie können sie auf Ihrem 
                                Smartphone installieren und wie eine native App nutzen - auch offline!
                            </p>

                            <p class="mt-4 text-sm">
                                <button @click="showInstallInstructions = !showInstallInstructions" class="text-blue-600 hover:text-blue-800 underline">
                                    Installationsanleitung anzeigen
                                </button>
                            </p>

                            <div v-if="showInstallInstructions" class="mt-4 p-4 bg-blue-50 rounded-lg">
                                <h3 class="font-semibold text-blue-900 mb-2">So installieren Sie die App:</h3>
                                <div class="text-sm text-blue-800 space-y-2">
                                    <p><strong>Android (Chrome):</strong></p>
                                    <ul class="list-disc list-inside ml-4 space-y-1">
                                        <li>Tippen Sie auf das Menü (⋮) in Chrome</li>
                                        <li>Wählen Sie "Zum Startbildschirm hinzufügen"</li>
                                        <li>Bestätigen Sie mit "Hinzufügen"</li>
                                    </ul>
                                    <p class="mt-3"><strong>iPhone (Safari):</strong></p>
                                    <ul class="list-disc list-inside ml-4 space-y-1">
                                        <li>Tippen Sie auf das Teilen-Symbol (□↗)</li>
                                        <li>Scrollen Sie nach unten und wählen Sie "Zum Home-Bildschirm"</li>
                                        <li>Bestätigen Sie mit "Hinzufügen"</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center">
                                <svg class="w-6 h-6 stroke-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                                <h2 class="ml-3 text-xl font-semibold text-gray-900 dark:text-white">
                                    Offline-Funktionen
                                </h2>
                            </div>

                            <p class="mt-4 text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                                Auch ohne Internetverbindung können Sie die App nutzen. Ihre Aktionen werden 
                                automatisch synchronisiert, sobald Sie wieder online sind.
                            </p>

                            <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                                <p><strong>Offline verfügbar:</strong></p>
                                <ul class="list-disc list-inside ml-4 mt-2 space-y-1">
                                    <li>Kalender anzeigen</li>
                                    <li>Urlaubsanträge vorbereiten</li>
                                    <li>Parkplatz-Reservierungen planen</li>
                                    <li>Bereits geladene Daten einsehen</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import { defineComponent } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

export default defineComponent({
    components: {
        AppLayout,
        Link,
    },

    data() {
        return {
            showInstallInstructions: false,
            isOnline: navigator.onLine,
            isPWA: false
        }
    },

    mounted() {
        // PWA-Status prüfen
        this.checkPWAStatus();
        
        // Online-Status überwachen
        window.addEventListener('online', this.updateOnlineStatus);
        window.addEventListener('offline', this.updateOnlineStatus);
    },

    beforeUnmount() {
        window.removeEventListener('online', this.updateOnlineStatus);
        window.removeEventListener('offline', this.updateOnlineStatus);
    },

    methods: {
        updateOnlineStatus() {
            this.isOnline = navigator.onLine;
        },

        checkPWAStatus() {
            // Prüfen ob die App im Standalone-Modus läuft (installiert)
            this.isPWA = window.matchMedia('(display-mode: standalone)').matches ||
                        window.navigator.standalone ||
                        document.referrer.includes('android-app://');
        }
    }
})
</script>