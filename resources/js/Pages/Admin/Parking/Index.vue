<template>
    <AppLayout title="Parkplatz-Administration">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Parkplatz-Administration
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-[100rem] mx-auto sm:px-6 lg:px-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ totalSpots }}</div>
                            <div class="text-gray-600 dark:text-gray-400">Gesamt Plätze</div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">{{ activeSpots }}</div>
                            <div class="text-gray-600 dark:text-gray-400">Aktive Plätze</div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-orange-600">{{ locations.length }}</div>
                            <div class="text-gray-600 dark:text-gray-400">Standorte</div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600">{{ currentReservations }}</div>
                            <div class="text-gray-600 dark:text-gray-400">Aktive Reservierungen</div>
                        </div>
                    </div>
                </div>

                <!-- Reservations Overview (NEW) -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Reservierungsübersicht</h3>
                            <div class="space-x-2">
                                <button @click="showReservationCalendar = !showReservationCalendar"
                                        :class="showReservationCalendar ? 'bg-green-500 hover:bg-green-600' : 'bg-blue-500 hover:bg-blue-600'"
                                        class="text-white px-4 py-2 rounded-md transition-colors">
                                    {{ showReservationCalendar ? 'Kalender ausblenden' : 'Kalender anzeigen' }}
                                </button>
                            </div>
                        </div>

                        <!-- Calendar View -->
                        <div v-if="showReservationCalendar" class="mb-6">
                            <ParkingCalendar
                                :parking-spots="allParkingSpots"
                                :reservations="allReservations"
                                @spot-selected="showSpotDetails"
                                @date-changed="onDateChanged"
                                @quick-reserve="handleAdminQuickReserve"
                            />
                        </div>

                        <!-- Current Reservations Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Benutzer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Parkplatz</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Datum</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Zeit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aktionen</th>
                                </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="reservation in allReservations" :key="reservation.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ reservation.user?.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ reservation.parking_spot?.name || reservation.parking_spot?.identifier }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ formatDate(reservation.reservation_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ reservation.start_time }} - {{ reservation.end_time }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="getStatusColor(reservation.status)"
                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                        {{ getStatusLabel(reservation.status) }}
                      </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button @click="cancelReservationAdmin(reservation)"
                                                v-if="reservation.status === 'confirmed'"
                                                class="text-red-600 hover:text-red-900">
                                            Stornieren
                                        </button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Position Editor -->
                <div v-if="showPositionEditor" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Parkplatz-Positionen bearbeiten</h3>
                            <button @click="showPositionEditor = false"
                                    class="text-gray-500 hover:text-gray-700">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Standort auswählen</label>
                            <select v-model="selectedLocationForEditor"
                                    @change="loadSpotsForEditor"
                                    class="border border-gray-300 rounded-md px-3 py-2">
                                <option value="">Standort auswählen</option>
                                <option v-for="location in locations" :key="location.id" :value="location.id">
                                    {{ location.name }}
                                </option>
                            </select>
                        </div>

                        <ParkingPositionEditor
                            v-if="selectedLocationForEditor && editorSpots.length > 0"
                            :parking-spots="editorSpots"
                            :image-url="getEditorImageUrl()"
                            @positions-updated="updateSpotPositions"
                        />

                        <div v-else-if="selectedLocationForEditor" class="text-center py-8 text-gray-500">
                            Keine Parkplätze für diesen Standort gefunden.
                        </div>
                    </div>
                </div>

                <!-- Location Management -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Standorte verwalten</h3>
                            <div class="space-x-2">
                                <button @click="showLocationDialog = true"
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
                                    Neuer Standort
                                </button>
                                <button @click="showPositionEditor = true"
                                        class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-md">
                                    Positionen bearbeiten
                                </button>
                            </div>
                        </div>

                        <div v-if="loading" class="text-center py-4">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 mx-auto"></div>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Adresse</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Anzahl Plätze</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aktionen</th>
                                </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="location in locations" :key="location.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ location.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ location.address || 'Keine Adresse' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ location.parking_spots_count || 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button @click="editLocation(location)"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            Bearbeiten
                                        </button>
                                        <button @click="deleteLocation(location.id)"
                                                class="text-red-600 hover:text-red-900">
                                            Löschen
                                        </button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Parking Spots Management -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Parkplätze verwalten</h3>
                            <button @click="showSpotDialog = true"
                                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md">
                                Neuer Parkplatz
                            </button>
                        </div>

                        <div class="mb-4">
                            <select v-model="selectedLocationFilter"
                                    @change="filterByLocation"
                                    class="border border-gray-300 rounded-md px-3 py-2">
                                <option value="">Alle Standorte</option>
                                <option v-for="location in locations" :key="location.id" :value="location.id">
                                    {{ location.name }}
                                </option>
                            </select>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Identifier</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Standort</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Typ</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aktionen</th>
                                </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="spot in filteredSpots" :key="spot.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ spot.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ spot.identifier }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ spot.parking_location?.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="getTypeColor(spot.type)"
                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                        {{ getTypeLabel(spot.type) }}
                      </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="spot.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                        {{ spot.is_active ? 'Aktiv' : 'Inaktiv' }}
                      </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button @click="editSpot(spot)"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            Bearbeiten
                                        </button>
                                        <button @click="deleteSpot(spot.id)"
                                                class="text-red-600 hover:text-red-900">
                                            Löschen
                                        </button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Location Dialog -->
                <div v-if="showLocationDialog" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">
                            {{ editingLocation ? 'Standort bearbeiten' : 'Neuer Standort' }}
                        </h3>
                        <form @submit.prevent="saveLocation">
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                                <input v-model="locationForm.name"
                                       type="text"
                                       required
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Adresse</label>
                                <input v-model="locationForm.address"
                                       type="text"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Beschreibung</label>
                                <textarea v-model="locationForm.description"
                                          rows="3"
                                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button @click="showLocationDialog = false"
                                        type="button"
                                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2">
                                    Abbrechen
                                </button>
                                <button type="submit"
                                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Speichern
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Spot Dialog -->
                <div v-if="showSpotDialog" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">
                            {{ editingSpot ? 'Parkplatz bearbeiten' : 'Neuer Parkplatz' }}
                        </h3>
                        <form @submit.prevent="saveSpot">
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Standort</label>
                                <select v-model="spotForm.parking_location_id"
                                        required
                                        class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="">Standort auswählen</option>
                                    <option v-for="location in locations" :key="location.id" :value="location.id">
                                        {{ location.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                                <input v-model="spotForm.name"
                                       type="text"
                                       required
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Identifier</label>
                                <input v-model="spotForm.identifier"
                                       type="text"
                                       required
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Typ</label>
                                <select v-model="spotForm.type"
                                        required
                                        class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="regular">Standard</option>
                                    <option value="lift_top">Hebebühne Oben</option>
                                    <option value="lift_bottom">Hebebühne Unten</option>
                                    <option value="external">Extern</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input v-model="spotForm.is_active"
                                           type="checkbox"
                                           class="mr-2">
                                    <span class="text-gray-700 text-sm font-bold">Aktiv</span>
                                </label>
                            </div>
                            <div class="flex justify-end">
                                <button @click="showSpotDialog = false"
                                        type="button"
                                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2">
                                    Abbrechen
                                </button>
                                <button type="submit"
                                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    Speichern
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import ParkingPositionEditor from '@/Components/ParkingPositionEditor.vue'
import ParkingCalendar from '@/Components/ParkingCalendar.vue'

// Konfiguriere Axios für CSRF-Token
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.withCredentials = true

export default {
    components: {
        AppLayout,
        ParkingPositionEditor,
        ParkingCalendar,
    },
    setup() {
        const loading = ref(true)
        const locations = ref([])
        const parkingSpots = ref([])
        const allReservations = ref([])
        const selectedLocationFilter = ref('')
        const selectedDate = ref(new Date().toISOString().split('T')[0])
        const showReservationCalendar = ref(false)

        // Dialog states
        const showLocationDialog = ref(false)
        const showSpotDialog = ref(false)
        const editingLocation = ref(null)
        const editingSpot = ref(null)

        // Forms
        const locationForm = ref({
            name: '',
            address: '',
            description: ''
        })

        const spotForm = ref({
            parking_location_id: '',
            name: '',
            identifier: '',
            type: 'regular',
            is_active: true,
            requires_approval: false,
            sort_order: 0
        })

        // Computed
        const filteredSpots = computed(() => {
            if (!selectedLocationFilter.value) {
                return parkingSpots.value
            }
            return parkingSpots.value.filter(spot => spot.parking_location_id == selectedLocationFilter.value)
        })

        const allParkingSpots = computed(() => parkingSpots.value)
        const totalSpots = computed(() => parkingSpots.value.length)
        const activeSpots = computed(() => parkingSpots.value.filter(spot => spot.is_active).length)
        const currentReservations = computed(() => allReservations.value.length)

        // Add to data section
        const showPositionEditor = ref(false)
        const selectedLocationForEditor = ref('')
        const editorSpots = ref([])

        // Einfache API-Anfrage-Funktion mit Axios (gleich wie in der Benutzer-Oberfläche)
        const makeApiRequest = async (url, options = {}) => {
            try {
                const method = options.method || 'GET'

                if (method === 'GET') {
                    const response = await axios.get(url)
                    return response.data
                } else {
                    const response = await axios[method.toLowerCase()](
                        url,
                        options.body ? JSON.parse(options.body) : {}
                    )
                    return response.data
                }
            } catch (error) {
                console.error(`API-Anfrage fehlgeschlagen: ${url}`, error)

                // Detailliertere Fehlerbehandlung
                if (error.response) {
                    // Server hat mit einem Fehlercode geantwortet
                    const errorData = error.response.data
                    if (errorData && errorData.errors) {
                        // Laravel Validation Errors
                        const validationErrors = Object.values(errorData.errors).flat()
                        throw new Error(validationErrors.join(', '))
                    } else if (errorData && errorData.error) {
                        throw new Error(errorData.error)
                    } else if (errorData && errorData.message) {
                        throw new Error(errorData.message)
                    } else {
                        throw new Error(`HTTP ${error.response.status}: ${error.response.statusText}`)
                    }
                } else if (error.request) {
                    // Request wurde gesendet, aber keine Antwort erhalten
                    throw new Error('Keine Antwort vom Server erhalten')
                } else {
                    // Fehler beim Erstellen der Anfrage
                    throw new Error(error.message || 'Unbekannter Fehler')
                }
            }
        }

        // Methods
        const loadData = async () => {
            try {
                loading.value = true

                const [locationsData, spotsData, reservationsData] = await Promise.all([
                    makeApiRequest('/api/parking/locations'),
                    makeApiRequest('/api/parking/spaces'),
                    makeApiRequest('/api/parking/reservations/current')
                ])

                locations.value = locationsData
                parkingSpots.value = spotsData
                allReservations.value = reservationsData
            } catch (error) {
                console.error('Error loading data:', error)
                showNotification('Fehler beim Laden der Daten: ' + error.message, 'error')
            } finally {
                loading.value = false
            }
        }

        const saveLocation = async () => {
            try {
                const url = editingLocation.value
                    ? `/api/parking/locations/${editingLocation.value.id}`
                    : '/api/parking/locations'

                const method = editingLocation.value ? 'PUT' : 'POST'

                await makeApiRequest(url, {
                    method,
                    body: JSON.stringify(locationForm.value)
                })

                showNotification('Standort gespeichert!', 'success')
                showLocationDialog.value = false
                resetLocationForm()
                loadData()
            } catch (error) {
                console.error('Error saving location:', error)
                showNotification('Fehler beim Speichern: ' + error.message, 'error')
            }
        }

        const saveSpot = async () => {
            try {
                const url = editingSpot.value
                    ? `/api/parking/spaces/${editingSpot.value.id}`
                    : '/api/parking/spaces'

                const method = editingSpot.value ? 'PUT' : 'POST'

                await makeApiRequest(url, {
                    method,
                    body: JSON.stringify(spotForm.value)
                })

                showNotification('Parkplatz gespeichert!', 'success')
                showSpotDialog.value = false
                resetSpotForm()
                loadData()
            } catch (error) {
                console.error('Error saving spot:', error)
                showNotification('Fehler beim Speichern: ' + error.message, 'error')
            }
        }

        const editLocation = (location) => {
            editingLocation.value = location
            locationForm.value = { ...location }
            showLocationDialog.value = true
        }

        const editSpot = (spot) => {
            editingSpot.value = spot
            spotForm.value = { ...spot }
            showSpotDialog.value = true
        }

        const deleteLocation = async (id) => {
            if (confirm('Sind Sie sicher, dass Sie diesen Standort löschen möchten?')) {
                try {
                    await makeApiRequest(`/api/parking/locations/${id}`, {
                        method: 'DELETE'
                    })

                    showNotification('Standort gelöscht!', 'success')
                    loadData()
                } catch (error) {
                    console.error('Error deleting location:', error)
                    showNotification('Fehler beim Löschen: ' + error.message, 'error')
                }
            }
        }

        const deleteSpot = async (id) => {
            if (confirm('Sind Sie sicher, dass Sie diesen Parkplatz löschen möchten?')) {
                try {
                    await makeApiRequest(`/api/parking/spaces/${id}`, {
                        method: 'DELETE'
                    })

                    showNotification('Parkplatz gelöscht!', 'success')
                    loadData()
                } catch (error) {
                    console.error('Error deleting spot:', error)
                    showNotification('Fehler beim Löschen: ' + error.message, 'error')
                }
            }
        }

        const filterByLocation = () => {
            // Filter wird automatisch durch computed property angewendet
        }

        const resetLocationForm = () => {
            locationForm.value = {
                name: '',
                address: '',
                description: ''
            }
            editingLocation.value = null
        }

        const resetSpotForm = () => {
            spotForm.value = {
                parking_location_id: '',
                name: '',
                identifier: '',
                type: 'regular',
                is_active: true,
                requires_approval: false,
                sort_order: 0
            }
            editingSpot.value = null
        }

        const getTypeColor = (type) => {
            const colors = {
                regular: 'bg-gray-100 text-gray-800',
                lift_top: 'bg-blue-100 text-blue-800',
                lift_bottom: 'bg-green-100 text-green-800',
                external: 'bg-yellow-100 text-yellow-800'
            }
            return colors[type] || 'bg-gray-100 text-gray-800'
        }

        const getTypeLabel = (type) => {
            const labels = {
                regular: 'Standard',
                lift_top: 'Hebebühne Oben',
                lift_bottom: 'Hebebühne Unten',
                external: 'Extern'
            }
            return labels[type] || type
        }

        const loadSpotsForEditor = () => {
            if (selectedLocationForEditor.value) {
                editorSpots.value = parkingSpots.value.filter(spot =>
                    spot.parking_location_id == selectedLocationForEditor.value
                ).map(spot => ({
                    ...spot,
                    position_x: spot.position_x || Math.random() * 80 + 10,
                    position_y: spot.position_y || Math.random() * 60 + 20
                }))
            } else {
                editorSpots.value = []
            }
        }

        const getEditorImageUrl = () => {
            return '/images/park.jpg'
        }

        const updateSpotPositions = async (updates) => {
            try {
                for (const update of updates) {
                    await makeApiRequest(`/api/parking/spaces/${update.id}`, {
                        method: 'PUT',
                        body: JSON.stringify({
                            position_x: update.position_x,
                            position_y: update.position_y
                        })
                    })
                }

                showNotification('Positionen erfolgreich gespeichert!', 'success')
                loadData()
            } catch (error) {
                console.error('Error updating positions:', error)
                showNotification('Fehler beim Speichern der Positionen: ' + error.message, 'error')
            }
        }

        // New calendar-related methods
        const onDateChanged = (date) => {
            if (typeof date === 'string') {
                selectedDate.value = date
            } else if (date instanceof Date) {
                selectedDate.value = date.toISOString().split('T')[0]
            } else {
                selectedDate.value = new Date().toISOString().split('T')[0]
            }
        }

        const showSpotDetails = (spot) => {
            showNotification(`Parkplatz Details: ${spot.name || spot.identifier} - ${getTypeLabel(spot.type)} - ${spot.is_active ? 'Aktiv' : 'Inaktiv'}`, 'info')
        }

        const handleAdminQuickReserve = (reserveData) => {
            showNotification(`Admin-Schnellreservierung für ${reserveData.spot.name || reserveData.spot.identifier} am ${reserveData.date.toLocaleDateString('de-DE')} (${reserveData.timeSlot.start} - ${reserveData.timeSlot.end}) - Funktion noch nicht implementiert.`, 'info')
        }

        const cancelReservationAdmin = async (reservation) => {
            if (confirm(`Möchten Sie die Reservierung von ${reservation.user?.name} wirklich stornieren?`)) {
                try {
                    await makeApiRequest(`/api/parking/reservations/${reservation.id}`, {
                        method: 'DELETE'
                    })

                    showNotification('Reservierung erfolgreich storniert!', 'success')
                    loadData()
                } catch (error) {
                    console.error('Error cancelling reservation:', error)
                    showNotification('Fehler beim Stornieren: ' + error.message, 'error')
                }
            }
        }

        const formatDate = (dateString) => {
            return new Date(dateString).toLocaleDateString('de-DE')
        }

        const getStatusColor = (status) => {
            const colors = {
                confirmed: 'bg-green-100 text-green-800',
                pending: 'bg-yellow-100 text-yellow-800',
                cancelled: 'bg-red-100 text-red-800',
                completed: 'bg-gray-100 text-gray-800'
            }
            return colors[status] || 'bg-gray-100 text-gray-800'
        }

        const getStatusLabel = (status) => {
            const labels = {
                confirmed: 'Bestätigt',
                pending: 'Ausstehend',
                cancelled: 'Storniert',
                completed: 'Abgeschlossen'
            }
            return labels[status] || status
        }

        const showNotification = (message, type = 'info') => {
            const notification = document.createElement('div')
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full ${
                type === 'success' ? 'bg-green-500 text-white' :
                    type === 'error' ? 'bg-red-500 text-white' :
                        'bg-blue-500 text-white'
            }`
            notification.textContent = message

            document.body.appendChild(notification)

            setTimeout(() => {
                notification.classList.remove('translate-x-full')
            }, 100)

            setTimeout(() => {
                notification.classList.add('translate-x-full')
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification)
                    }
                }, 300)
            }, 4000)
        }

        onMounted(() => {
            loadData()
        })

        return {
            loading,
            locations,
            parkingSpots,
            allReservations,
            selectedLocationFilter,
            selectedDate,
            showReservationCalendar,
            showLocationDialog,
            showSpotDialog,
            editingLocation,
            editingSpot,
            locationForm,
            spotForm,
            filteredSpots,
            allParkingSpots,
            totalSpots,
            activeSpots,
            currentReservations,
            showPositionEditor,
            selectedLocationForEditor,
            editorSpots,
            makeApiRequest,
            loadData,
            saveLocation,
            saveSpot,
            editLocation,
            editSpot,
            deleteLocation,
            deleteSpot,
            filterByLocation,
            resetLocationForm,
            resetSpotForm,
            getTypeColor,
            getTypeLabel,
            loadSpotsForEditor,
            getEditorImageUrl,
            updateSpotPositions,
            onDateChanged,
            showSpotDetails,
            handleAdminQuickReserve,
            cancelReservationAdmin,
            formatDate,
            getStatusColor,
            getStatusLabel,
            showNotification
        }
    }
}
</script>
