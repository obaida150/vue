<template>
    <AppLayout title="Parkplatzverwaltung">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Parkplatzverwaltung
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <h1 class="text-2xl font-medium text-gray-900 dark:text-white">
                            Parkplatz-Reservierung
                        </h1>
                        <p class="mt-6 text-gray-500 dark:text-gray-400 leading-relaxed">
                            Wählen Sie einen verfügbaren Parkplatz aus der Kalenderansicht. Ihre Reservierung wird sofort bestätigt.
                        </p>
                    </div>

                    <div class="p-6 lg:p-8">
                        <!-- Location Tabs -->
                        <div class="mb-6">
                            <div class="border-b border-gray-200">
                                <nav class="-mb-px flex space-x-8">
                                    <button
                                        v-for="location in locations"
                                        :key="location.id"
                                        @click="selectedLocation = location"
                                        :class="[
                      selectedLocation?.id === location.id
                        ? 'border-green-500 text-green-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                      'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                    ]"
                                    >
                                        {{ location.name }}
                                        <span class="ml-2 text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                      {{ getAvailableCount(location.id) }}
                    </span>
                                    </button>
                                </nav>
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-12">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-500 mx-auto"></div>
                            <p class="mt-4 text-gray-500">Lade Parkplätze...</p>
                        </div>

                        <!-- Parking Calendar -->
                        <div v-else-if="selectedLocation" class="mb-8">
                            <ParkingCalendar
                                :parking-spots="getLocationSpots(selectedLocation.id)"
                                :reservations="allReservations"
                                @spot-selected="showReservationDialog"
                                @date-changed="onDateChanged"
                                @quick-reserve="handleQuickReserve"
                            />
                        </div>

                        <!-- My Reservations -->
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Meine Reservierungen
                            </h3>
                            <div v-if="myReservations.length === 0" class="text-gray-500 dark:text-gray-400 text-center py-8">
                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                Keine aktiven Reservierungen.
                            </div>
                            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="reservation in myReservations" :key="reservation.id"
                                     class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h4 class="font-medium text-gray-900">
                                                {{ reservation.parking_spot?.name || reservation.parking_spot?.identifier }}
                                            </h4>
                                            <p class="text-sm text-gray-500">{{ reservation.parking_spot?.parking_location?.name }}</p>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                      Bestätigt
                    </span>
                                    </div>
                                    <div class="text-sm text-gray-600 mb-3">
                                        <div>📅 {{ formatDate(reservation.reservation_date) }}</div>
                                        <div v-if="reservation.start_time && reservation.end_time">
                                            🕐 {{ reservation.start_time }} - {{ reservation.end_time }}
                                        </div>
                                        <div v-if="reservation.vehicle_info">
                                            🚗 {{ reservation.vehicle_info }}
                                        </div>
                                    </div>
                                    <button
                                        v-if="canCancelReservation(reservation)"
                                        @click="cancelReservation(reservation)"
                                        class="w-full bg-red-50 text-red-600 hover:bg-red-100 px-3 py-2 rounded text-sm font-medium transition-colors">
                                        Stornieren
                                    </button>
                                    <div v-else class="w-full bg-gray-50 text-gray-400 px-3 py-2 rounded text-sm text-center">
                                        Vergangene Termine können nicht storniert werden
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Admin Link -->
                        <div class="mt-8 text-center">
                            <a href="/admin/parking"
                               class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Parkplatz-Administration
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reservation Dialog mit verbesserter UX -->
        <div v-if="showDialog" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="closeDialog">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" @click.stop>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center">
                        🅿️ Parkplatz reservieren
                    </h3>
                    <button @click="closeDialog" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="mb-4 p-3 bg-green-50 rounded-lg border border-green-200">
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-green-500 rounded-full mr-3 animate-pulse"></div>
                        <div>
                            <p class="font-medium text-gray-900">{{ selectedSpot?.name || selectedSpot?.identifier }}</p>
                            <p class="text-sm text-gray-600">{{ selectedSpot?.parking_location?.name }}</p>
                            <p class="text-xs text-gray-500">{{ getTypeLabel(selectedSpot?.type) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Time Presets -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Schnell-Auswahl</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            @click="setQuickTime('morning')"
                            type="button"
                            class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 hover:bg-yellow-200 rounded transition-colors"
                        >
                            🌅 Vormittag<br><span class="text-xs">06:00-12:00</span>
                        </button>
                        <button
                            @click="setQuickTime('afternoon')"
                            type="button"
                            class="px-2 py-1 text-xs bg-blue-100 text-blue-700 hover:bg-blue-200 rounded transition-colors"
                        >
                            🌞 Nachmittag<br><span class="text-xs">12:00-18:00</span>
                        </button>
                        <button
                            @click="setQuickTime('fullday')"
                            type="button"
                            class="px-2 py-1 text-xs bg-purple-100 text-purple-700 hover:bg-purple-200 rounded transition-colors"
                        >
                            🌅🌙 Ganztags<br><span class="text-xs">06:00-18:00</span>
                        </button>
                    </div>
                </div>

                <form @submit.prevent="createReservation">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Datum</label>
                        <input v-model="reservationForm.reservation_date"
                               type="date"
                               :min="today"
                               required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500 transition-colors">
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Von</label>
                            <input v-model="reservationForm.start_time"
                                   type="time"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Bis</label>
                            <input v-model="reservationForm.end_time"
                                   type="time"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500 transition-colors">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Fahrzeug-Info (optional)</label>
                        <input v-model="reservationForm.vehicle_info"
                               type="text"
                               placeholder="z.B. BMW X3, Kennzeichen ABC-123"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500 transition-colors">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Notizen (optional)</label>
                        <textarea v-model="reservationForm.notes"
                                  rows="3"
                                  placeholder="Zusätzliche Informationen..."
                                  class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500 transition-colors"></textarea>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button @click="closeDialog"
                                type="button"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition-colors">
                            Abbrechen
                        </button>
                        <button type="submit"
                                :disabled="submitting"
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition-colors flex items-center disabled:opacity-50">
                            <svg v-if="submitting" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ submitting ? 'Reserviere...' : 'Sofort reservieren' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import ParkingCalendar from '@/Components/ParkingCalendar.vue'

// Konfiguriere Axios für CSRF-Token
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.withCredentials = true

export default {
    components: {
        AppLayout,
        ParkingCalendar,
    },
    setup() {
        const loading = ref(true)
        const submitting = ref(false)
        const locations = ref([])
        const parkingSpots = ref([])
        const myReservations = ref([])
        const allReservations = ref([])
        const selectedLocation = ref(null)
        const showDialog = ref(false)
        const selectedSpot = ref(null)
        const selectedDate = ref(new Date().toISOString().split('T')[0])

        const reservationForm = ref({
            parking_spot_id: null,
            reservation_date: '',
            start_time: '06:00',
            end_time: '18:00',
            vehicle_info: '',
            notes: ''
        })

        const today = computed(() => {
            return new Date().toISOString().split('T')[0]
        })

        // Hilfsfunktion für korrektes Datumsformat
        const formatDateForApi = (date) => {
            if (typeof date === 'string') {
                // Wenn es bereits ein String ist, prüfe das Format
                if (date.match(/^\d{4}-\d{2}-\d{2}$/)) {
                    return date
                }
                // Versuche es als Date zu parsen
                date = new Date(date)
            }

            if (date instanceof Date && !isNaN(date)) {
                // Verwende lokale Zeitzone um Timezone-Probleme zu vermeiden
                const year = date.getFullYear()
                const month = String(date.getMonth() + 1).padStart(2, '0')
                const day = String(date.getDate()).padStart(2, '0')
                return `${year}-${month}-${day}`
            }

            // Fallback auf heute
            return new Date().toISOString().split('T')[0]
        }

        // Hilfsfunktion für verfügbare Zeitslots
        const calculateAvailableTimeSlots = (reservations) => {
            if (reservations.length === 0) {
                return ['06:00 - 18:00']
            }

            const slots = []
            const dayStart = '06:00'
            const dayEnd = '18:00'

            const sortedReservations = [...reservations].sort((a, b) =>
                a.start_time.localeCompare(b.start_time)
            )

            if (sortedReservations[0].start_time > dayStart) {
                slots.push(`${dayStart} - ${sortedReservations[0].start_time}`)
            }

            for (let i = 0; i < sortedReservations.length - 1; i++) {
                const currentEnd = sortedReservations[i].end_time
                const nextStart = sortedReservations[i + 1].start_time

                if (currentEnd < nextStart) {
                    slots.push(`${currentEnd} - ${nextStart}`)
                }
            }

            const lastReservation = sortedReservations[sortedReservations.length - 1]
            if (lastReservation.end_time < dayEnd) {
                slots.push(`${lastReservation.end_time} - ${dayEnd}`)
            }

            return slots
        }

        // Einfache API-Anfrage-Funktion mit Axios
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

        const loadData = async () => {
            try {
                loading.value = true

                const [locationsData, spotsData, reservationsData, allReservationsData] = await Promise.all([
                    makeApiRequest('/api/parking/locations'),
                    makeApiRequest('/api/parking/spots'),
                    makeApiRequest('/api/parking/my-reservations'),
                    makeApiRequest('/api/parking/reservations/current')
                ])

                locations.value = locationsData
                parkingSpots.value = spotsData
                myReservations.value = reservationsData
                allReservations.value = allReservationsData

                // Select first location by default
                if (locations.value.length > 0) {
                    selectedLocation.value = locations.value[0]
                }

            } catch (error) {
                console.error('Fehler beim Laden der Daten:', error)
                showNotification('Fehler beim Laden der Daten: ' + error, 'error')
            } finally {
                loading.value = false
            }
        }

        // Hilfsfunktion für Zeitüberschneidungen
        const timeRangesOverlap = (start1, end1, start2, end2) => {
            return (start1 < end2) && (start2 < end1)
        }

        const createReservation = async () => {
            if (submitting.value) return

            try {
                submitting.value = true

                // Überprüfe vor der Reservierung auf Zeitüberschneidungen
                const dateStr = formatDateForApi(reservationForm.value.reservation_date)
                const conflictingReservations = allReservations.value.filter(res =>
                    res.parking_spot_id === reservationForm.value.parking_spot_id &&
                    res.reservation_date.split('T')[0] === dateStr &&
                    ['pending', 'confirmed'].includes(res.status)
                )

                // Prüfe auf Zeitüberschneidungen
                for (const existing of conflictingReservations) {
                    if (timeRangesOverlap(
                        reservationForm.value.start_time,
                        reservationForm.value.end_time,
                        existing.start_time,
                        existing.end_time
                    )) {
                        showNotification(`Zeitüberschneidung mit bestehender Reservierung (${existing.start_time} - ${existing.end_time})`, 'error')
                        return
                    }
                }

                // Stelle sicher, dass das Datum korrekt formatiert ist
                const formData = {
                    ...reservationForm.value,
                    reservation_date: dateStr
                }

                console.log('Sending reservation form:', formData)

                const response = await makeApiRequest('/api/parking/reserve', {
                    method: 'POST',
                    body: JSON.stringify(formData)
                })

                showNotification('Parkplatz erfolgreich reserviert! 🎉', 'success')
                closeDialog()
                await loadData()
            } catch (error) {
                console.error('Fehler bei der Reservierung:', error)

                // Bessere Fehlerbehandlung
                if (error.message.includes('Zeitüberschneidung')) {
                    showNotification('Zeitüberschneidung: ' + error.message, 'error')
                } else if (error.message.includes('already reserved')) {
                    showNotification('Dieser Parkplatz ist bereits für das gewählte Datum reserviert.', 'error')
                } else if (error.message.includes('Validation')) {
                    showNotification('Bitte überprüfen Sie Ihre Eingaben.', 'error')
                } else {
                    showNotification('Fehler bei der Reservierung: ' + error.message, 'error')
                }
            } finally {
                submitting.value = false
            }
        }

        const cancelReservation = async (reservation) => {
            if (confirm('Möchten Sie diese Reservierung wirklich stornieren?')) {
                try {
                    await makeApiRequest(`/api/parking/reservations/${reservation.id}`, {
                        method: 'DELETE'
                    })

                    showNotification('Reservierung erfolgreich storniert!', 'success')
                    loadData()
                } catch (error) {
                    console.error('Fehler beim Stornieren:', error)
                    showNotification('Fehler beim Stornieren: ' + error, 'error')
                }
            }
        }

        const onDateChanged = (date) => {
            if (typeof date === 'string') {
                selectedDate.value = date
            } else if (date instanceof Date) {
                selectedDate.value = date.toISOString().split('T')[0]
            } else {
                selectedDate.value = new Date().toISOString().split('T')[0]
            }
        }

        const getLocationSpots = (locationId) => {
            return parkingSpots.value.filter(spot => spot.parking_location_id === locationId)
        }

        const getAvailableCount = (locationId) => {
            const spots = getLocationSpots(locationId)
            let availableCount = 0

            spots.forEach(spot => {
                if (!spot.is_active) return

                // Prüfe ob der Spot verfügbare Zeitslots hat
                const reservations = allReservations.value.filter(r =>
                    r.parking_spot_id === spot.id &&
                    r.reservation_date.split('T')[0] === selectedDate.value &&
                    ['pending', 'confirmed'].includes(r.status)
                )

                const availableSlots = calculateAvailableTimeSlots(reservations)

                if (availableSlots.length > 0) {
                    availableCount++
                }
            })

            return availableCount
        }

        const showReservationDialog = (spot) => {
            selectedSpot.value = spot
            reservationForm.value.parking_spot_id = spot.id
            // Verwende das aktuell ausgewählte Datum
            reservationForm.value.reservation_date = selectedDate.value || today.value
            showDialog.value = true
        }

        const closeDialog = () => {
            showDialog.value = false
            selectedSpot.value = null
            submitting.value = false
            reservationForm.value = {
                parking_spot_id: null,
                reservation_date: '',
                start_time: '06:00',
                end_time: '18:00',
                vehicle_info: '',
                notes: ''
            }
        }

        const handleQuickReserve = async (reserveData) => {
            const dateStr = formatDateForApi(reserveData.date)

            // Validiere das Datum
            const reservationDate = new Date(dateStr)
            const today = new Date()
            today.setHours(0, 0, 0, 0)

            if (reservationDate < today) {
                showNotification('Reservierungen können nur für heute oder zukünftige Tage erstellt werden.', 'error')
                return
            }

            // Aktualisiere die Daten vor der Reservierung
            await loadData()

            // Überprüfe auf Zeitüberschneidungen statt kompletter Verfügbarkeit
            const conflictingReservations = allReservations.value.filter(res =>
                res.parking_spot_id === reserveData.spot.id &&
                res.reservation_date.split('T')[0] === dateStr &&
                ['pending', 'confirmed'].includes(res.status)
            )

            // Prüfe auf Zeitüberschneidungen
            for (const existing of conflictingReservations) {
                if (timeRangesOverlap(
                    reserveData.timeSlot.start,
                    reserveData.timeSlot.end,
                    existing.start_time,
                    existing.end_time
                )) {
                    showNotification(`Zeitüberschneidung mit bestehender Reservierung (${existing.start_time} - ${existing.end_time})`, 'error')
                    return
                }
            }

            // Überprüfe, ob der Parkplatz aktiv ist
            const currentSpot = parkingSpots.value.find(spot => spot.id === reserveData.spot.id)
            if (!currentSpot || !currentSpot.is_active) {
                showNotification('Dieser Parkplatz ist nicht verfügbar.', 'error')
                return
            }

            if (confirm(`Möchten Sie ${reserveData.spot.name || reserveData.spot.identifier} schnell reservieren?

Zeit: ${reserveData.timeSlot.start} - ${reserveData.timeSlot.end}
Datum: ${reserveData.date.toLocaleDateString('de-DE')}`)) {
                try {
                    const reservationData = {
                        parking_spot_id: reserveData.spot.id,
                        reservation_date: dateStr,
                        start_time: reserveData.timeSlot.start,
                        end_time: reserveData.timeSlot.end,
                        vehicle_info: '',
                        notes: 'Schnell-Reservierung'
                    }

                    console.log('Sending reservation data:', reservationData)

                    const response = await makeApiRequest('/api/parking/reserve', {
                        method: 'POST',
                        body: JSON.stringify(reservationData)
                    })

                    showNotification('Parkplatz erfolgreich reserviert! 🎉', 'success')
                    await loadData()
                } catch (error) {
                    console.error('Fehler bei der Schnell-Reservierung:', error)

                    if (error.message && error.message.includes('Zeitüberschneidung')) {
                        showNotification('Zeitüberschneidung mit bestehender Reservierung.', 'error')
                        await loadData()
                    } else if (error.message && error.message.includes('already reserved')) {
                        showNotification('Dieser Parkplatz ist bereits reserviert. Die Ansicht wird aktualisiert.', 'error')
                        await loadData()
                    } else {
                        showNotification('Fehler bei der Reservierung: ' + (error.message || 'Unbekannter Fehler'), 'error')
                    }
                }
            }
        }

        const formatDate = (dateString) => {
            return new Date(dateString).toLocaleDateString('de-DE')
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

        const setQuickTime = (timeSlot) => {
            const timeSlots = {
                morning: { start: '06:00', end: '12:00' },
                afternoon: { start: '12:00', end: '18:00' },
                fullday: { start: '06:00', end: '18:00' }
            }

            const slot = timeSlots[timeSlot]
            reservationForm.value.start_time = slot.start
            reservationForm.value.end_time = slot.end
        }

        const canCancelReservation = (reservation) => {
            const reservationDate = new Date(reservation.reservation_date)
            const today = new Date()
            today.setHours(0, 0, 0, 0)

            return reservationDate >= today
        }

        onMounted(() => {
            loadData()
        })

        return {
            loading,
            submitting,
            locations,
            parkingSpots,
            myReservations,
            allReservations,
            selectedLocation,
            showDialog,
            selectedSpot,
            selectedDate,
            reservationForm,
            today,
            onDateChanged,
            getLocationSpots,
            getAvailableCount,
            showReservationDialog,
            closeDialog,
            createReservation,
            canCancelReservation,
            cancelReservation,
            formatDate,
            getTypeLabel,
            handleQuickReserve,
            showNotification,
            setQuickTime,
            formatDateForApi,
            timeRangesOverlap,
            calculateAvailableTimeSlots
        }
    }
}
</script>
