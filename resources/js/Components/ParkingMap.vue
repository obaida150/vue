<template>
    <div class="relative bg-gray-100 rounded-lg overflow-hidden">
        <!-- Date Selector -->
        <div class="p-4 bg-white border-b">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <h3 class="text-lg font-semibold">{{ locationName }}</h3>
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700">Datum:</label>
                        <input
                            v-model="selectedDate"
                            type="date"
                            :min="today"
                            class="border border-gray-300 rounded-md px-3 py-1 text-sm"
                            @change="onDateChange"
                        />
                    </div>
                </div>
                <div class="text-sm text-gray-600">
                    {{ locationDescription }}
                </div>
            </div>
        </div>

        <!-- Parking Lot Image -->
        <div class="relative">
            <img
                :src="parkingImage"
                alt="Parkplatz Übersicht"
                class="w-full h-auto max-h-[600px] object-cover"
                @load="onImageLoad"
            />

            <!-- Parking Spots Grid Overlay -->
            <div class="absolute inset-0 flex items-center justify-center" style="padding-top: 120px;">
                <div class="grid grid-cols-3 gap-64 p-8">
                    <!-- Top Row (Hebebühnen Oben) -->
                    <div
                        v-for="spot in topRowSpots"
                        :key="spot.id"
                        class="relative cursor-pointer transition-all duration-200 hover:scale-110"
                        @click="selectSpot(spot)"
                    >
                        <!-- Spot Circle -->
                        <div
                            class="w-12 h-12 rounded-full border-3 flex items-center justify-center shadow-lg relative mx-auto"
                            :class="getSpotClasses(spot)"
                        >
              <span class="text-sm font-bold text-white">
                {{ getSpotDisplayText(spot) }}
              </span>
                        </div>

                        <!-- Spot Label with Reservation Info -->
                        <div
                            v-if="showLabels || getReservationsForDate(spot).length > 0"
                            class="absolute top-14 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-90 text-white text-xs px-3 py-2 rounded-lg whitespace-nowrap shadow-lg z-10 max-w-xs"
                        >
                            <div class="font-semibold">{{ spot.name || spot.identifier }}</div>
                            <div class="text-xs opacity-75">{{ getTypeLabel(spot.type) }}</div>

                            <!-- Show all reservations for this date -->
                            <div v-if="getReservationsForDate(spot).length > 0" class="text-xs text-yellow-300 mt-1 space-y-1">
                                <div class="font-semibold">Reserviert:</div>
                                <div v-for="reservation in getReservationsForDate(spot)" :key="reservation.id">
                                    👤 {{ reservation.user?.name }}
                                    <br>🕐 {{ reservation.start_time }} - {{ reservation.end_time }}
                                </div>
                            </div>

                            <!-- Show available time slots -->
                            <div v-if="getAvailableTimeSlots(spot).length > 0" class="text-xs text-green-300 mt-1">
                                <div class="font-semibold">Verfügbar:</div>
                                <div v-for="slot in getAvailableTimeSlots(spot)" :key="slot">
                                    🕐 {{ slot }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Row (Hebebühnen Unten) -->
                    <div
                        v-for="spot in bottomRowSpots"
                        :key="spot.id"
                        class="relative cursor-pointer transition-all duration-200 hover:scale-110"
                        @click="selectSpot(spot)"
                    >
                        <!-- Spot Circle -->
                        <div
                            class="w-12 h-12 rounded-full border-3 flex items-center justify-center shadow-lg relative mx-auto"
                            :class="getSpotClasses(spot)"
                        >
              <span class="text-sm font-bold text-white">
                {{ getSpotDisplayText(spot) }}
              </span>
                        </div>

                        <!-- Spot Label with Reservation Info -->
                        <div
                            v-if="showLabels || getReservationsForDate(spot).length > 0"
                            class="absolute top-14 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-90 text-white text-xs px-3 py-2 rounded-lg whitespace-nowrap shadow-lg z-10 max-w-xs"
                        >
                            <div class="font-semibold">{{ spot.name || spot.identifier }}</div>
                            <div class="text-xs opacity-75">{{ getTypeLabel(spot.type) }}</div>

                            <!-- Show all reservations for this date -->
                            <div v-if="getReservationsForDate(spot).length > 0" class="text-xs text-yellow-300 mt-1 space-y-1">
                                <div class="font-semibold">Reserviert:</div>
                                <div v-for="reservation in getReservationsForDate(spot)" :key="reservation.id">
                                    👤 {{ reservation.user?.name }}
                                    <br>🕐 {{ reservation.start_time }} - {{ reservation.end_time }}
                                </div>
                            </div>

                            <!-- Show available time slots -->
                            <div v-if="getAvailableTimeSlots(spot).length > 0" class="text-xs text-green-300 mt-1">
                                <div class="font-semibold">Verfügbar:</div>
                                <div v-for="slot in getAvailableTimeSlots(spot)" :key="slot">
                                    🕐 {{ slot }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="absolute top-4 right-4 bg-white bg-opacity-90 rounded-lg p-3 shadow-lg">
                <h4 class="font-semibold text-sm mb-2">Legende</h4>
                <div class="space-y-1 text-xs">
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-green-500 rounded-full mr-2"></div>
                        <span>Verfügbar</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-yellow-500 rounded-full mr-2"></div>
                        <span>Reserviert</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-red-500 rounded-full mr-2"></div>
                        <span>Belegt</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-gray-400 rounded-full mr-2"></div>
                        <span>Wartung</span>
                    </div>
                </div>

                <button
                    @click="showLabels = !showLabels"
                    class="mt-2 text-xs bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600"
                >
                    {{ showLabels ? 'Labels ausblenden' : 'Labels anzeigen' }}
                </button>
            </div>
        </div>

        <!-- Mobile Controls -->
        <div class="md:hidden p-4 bg-white border-t">
            <div class="flex justify-between items-center mb-2">
                <span class="font-medium">Parkplatz auswählen:</span>
                <button
                    @click="showMobileList = !showMobileList"
                    class="text-blue-500 text-sm"
                >
                    {{ showMobileList ? 'Karte anzeigen' : 'Liste anzeigen' }}
                </button>
            </div>

            <div v-if="showMobileList" class="space-y-2 max-h-40 overflow-y-auto">
                <button
                    v-for="spot in availableSpots"
                    :key="spot.id"
                    @click="selectSpot(spot)"
                    class="w-full text-left p-2 border rounded hover:bg-gray-50"
                >
                    <div class="font-medium">{{ spot.name || spot.identifier }}</div>
                    <div class="text-sm text-gray-500">{{ getTypeLabel(spot.type) }}</div>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
    parkingSpots: {
        type: Array,
        default: () => []
    },
    parkingImage: {
        type: String,
        default: '/placeholder.svg?height=400&width=800'
    },
    locationName: {
        type: String,
        default: 'Hauptgebäude'
    },
    locationDescription: {
        type: String,
        default: 'Hof • Hebebühnen'
    },
    reservations: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['spot-selected', 'date-changed'])

const showLabels = ref(false)
const showMobileList = ref(false)
const selectedDate = ref(new Date().toISOString().split('T')[0])

const today = computed(() => {
    return new Date().toISOString().split('T')[0]
})

// Separate spots into top and bottom rows
const topRowSpots = computed(() => {
    return props.parkingSpots
        .filter(spot => spot.type === 'lift_top')
        .sort((a, b) => (a.identifier || '').localeCompare(b.identifier || ''))
})

const bottomRowSpots = computed(() => {
    return props.parkingSpots
        .filter(spot => spot.type === 'lift_bottom')
        .sort((a, b) => (a.identifier || '').localeCompare(b.identifier || ''))
})

const availableSpots = computed(() => {
    return props.parkingSpots.filter(spot => {
        if (!spot.is_active) return false
        // Ein Spot ist verfügbar, wenn er mindestens ein freies Zeitfenster hat
        return getAvailableTimeSlots(spot).length > 0
    })
})

const selectSpot = (spot) => {
    if (!spot.is_active) {
        console.log('Spot is not active')
        return
    }

    // Prüfe, ob der Spot mindestens ein freies Zeitfenster hat
    if (getAvailableTimeSlots(spot).length === 0) {
        console.log('Spot has no available time slots')
        return
    }

    console.log('Selecting available spot:', spot)
    emit('spot-selected', spot)
}

const onDateChange = () => {
    emit('date-changed', selectedDate.value)
}

const getReservationsForDate = (spot) => {
    return props.reservations.filter(reservation => {
        const spotMatch = reservation.parking_spot_id === spot.id
        const reservationDate = reservation.reservation_date.split('T')[0]
        const dateMatch = reservationDate === selectedDate.value
        const statusMatch = ['pending', 'confirmed'].includes(reservation.status)

        return spotMatch && dateMatch && statusMatch
    }).sort((a, b) => a.start_time.localeCompare(b.start_time))
}

// Funktion zur Berechnung der verfügbaren Zeitfenster
const getAvailableTimeSlots = (spot) => {
    const reservations = getReservationsForDate(spot)

    if (reservations.length === 0) {
        return ['06:00 - 18:00']
    }

    const slots = []
    const dayStart = '06:00'
    const dayEnd = '18:00'

    // Sortiere Reservierungen nach Startzeit
    const sortedReservations = [...reservations].sort((a, b) =>
        a.start_time.localeCompare(b.start_time)
    )

    // Prüfe Slot vor der ersten Reservierung
    if (sortedReservations[0].start_time > dayStart) {
        slots.push(`${dayStart} - ${sortedReservations[0].start_time}`)
    }

    // Prüfe Slots zwischen Reservierungen
    for (let i = 0; i < sortedReservations.length - 1; i++) {
        const currentEnd = sortedReservations[i].end_time
        const nextStart = sortedReservations[i + 1].start_time

        if (currentEnd < nextStart) {
            slots.push(`${currentEnd} - ${nextStart}`)
        }
    }

    // Prüfe Slot nach der letzten Reservierung
    const lastReservation = sortedReservations[sortedReservations.length - 1]
    if (lastReservation.end_time < dayEnd) {
        slots.push(`${lastReservation.end_time} - ${dayEnd}`)
    }

    return slots
}

const getSpotDisplayText = (spot) => {
    const reservations = getReservationsForDate(spot)

    if (reservations.length === 0) {
        return spot.identifier
    }

    // Zeige Initialen des ersten Benutzers oder Anzahl der Reservierungen
    if (reservations.length === 1) {
        const user = reservations[0].user
        return user?.initials || user?.name?.substring(0, 2).toUpperCase() || spot.identifier
    } else {
        return reservations.length.toString() // Zeige Anzahl der Reservierungen
    }
}

const getSpotClasses = (spot) => {
    if (!spot.is_active) {
        return 'bg-gray-400 border-gray-500 cursor-not-allowed'
    }

    const reservations = getReservationsForDate(spot)
    const availableSlots = getAvailableTimeSlots(spot)

    if (reservations.length === 0) {
        // Komplett frei
        return 'bg-green-500 border-green-600 hover:bg-green-600 cursor-pointer'
    } else if (availableSlots.length > 0) {
        // Teilweise belegt (hat freie Zeitfenster)
        return 'bg-yellow-500 border-yellow-600 hover:bg-yellow-600 cursor-pointer'
    } else {
        // Komplett belegt
        return 'bg-red-500 border-red-600 cursor-not-allowed'
    }
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

const onImageLoad = () => {
    console.log('Parking map image loaded')
}

// Watch for date changes to update the display
watch(selectedDate, () => {
    onDateChange()
})
</script>

<style scoped>
.border-3 {
    border-width: 3px;
}
</style>
