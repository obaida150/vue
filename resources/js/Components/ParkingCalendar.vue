<template>
    <div class="space-y-6">
        <!-- Calendar Header -->
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Parkplatz-Kalender</h3>
            <div class="flex space-x-2">
                <button
                    @click="currentView = 'day'"
                    :class="[
            currentView === 'day'
              ? 'bg-green-500 text-white'
              : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
            'px-3 py-1 rounded text-sm font-medium transition-colors'
          ]"
                >
                    Tag
                </button>
                <button
                    @click="currentView = 'week'"
                    :class="[
            currentView === 'week'
              ? 'bg-green-500 text-white'
              : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
            'px-3 py-1 rounded text-sm font-medium transition-colors'
          ]"
                >
                    Woche
                </button>
                <button
                    @click="goToToday"
                    class="bg-green-100 text-green-700 hover:bg-green-200 px-3 py-1 rounded text-sm font-medium transition-colors"
                >
                    Heute
                </button>
            </div>
        </div>

        <!-- Date Navigation -->
        <div class="flex items-center justify-center space-x-4">
            <button
                @click="previousPeriod"
                class="p-2 hover:bg-gray-100 rounded-full transition-colors"
            >
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>

            <h2 class="text-xl font-semibold text-gray-900 min-w-[200px] text-center">
                {{ formatPeriodTitle() }}
            </h2>

            <button
                @click="nextPeriod"
                class="p-2 hover:bg-gray-100 rounded-full transition-colors"
            >
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>

        <!-- Day View -->
        <div v-if="currentView === 'day'" class="space-y-4">
            <!-- Current Date Display -->
            <div class="text-center">
                <h3 class="text-lg font-medium text-gray-900">
                    {{ formatDayTitle(currentDate) }}
                </h3>
            </div>

            <!-- Quick Time Selection -->
            <div class="flex justify-center space-x-2 mb-6">
                <button
                    v-for="timeSlot in timeSlots"
                    :key="timeSlot.key"
                    @click="quickReserveTimeSlot(timeSlot.key)"
                    :class="[
            timeSlot.color,
            'px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center space-x-2'
          ]"
                >
                    <span>{{ timeSlot.icon }}</span>
                    <div class="text-left">
                        <div>{{ timeSlot.label }}</div>
                        <div class="text-xs opacity-75">{{ timeSlot.time }}</div>
                    </div>
                </button>
            </div>

            <!-- Parking Spots Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="spot in parkingSpots"
                    :key="spot.id"
                    class="border rounded-lg p-4 transition-all duration-200 hover:shadow-md cursor-pointer"
                    :class="getSpotCardClasses(spot)"
                    @click="selectSpot(spot)"
                >
                    <!-- Spot Header -->
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-2">
                            <div
                                class="w-3 h-3 rounded-full"
                                :class="getSpotStatusColor(spot)"
                            ></div>
                            <div>
                                <h4 class="font-medium text-gray-900">{{ spot.name || spot.identifier }}</h4>
                                <p class="text-sm text-gray-500">{{ getTypeLabel(spot.type) }}</p>
                            </div>
                        </div>
                        <div class="text-right">
              <span
                  class="px-2 py-1 text-xs font-medium rounded-full"
                  :class="getAvailabilityBadgeClasses(spot)"
              >
                {{ getAvailabilityText(spot) }}
              </span>
                        </div>
                    </div>

                    <!-- Current Reservations -->
                    <div v-if="getSpotReservationsForDate(spot).length > 0" class="mb-3">
                        <div class="space-y-2">
                            <div
                                v-for="reservation in getSpotReservationsForDate(spot)"
                                :key="reservation.id"
                                class="flex items-center justify-between text-xs bg-yellow-50 border border-yellow-200 rounded p-2"
                            >
                                <div class="flex items-center space-x-2">
                                    <svg class="w-3 h-3 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="font-medium text-yellow-800">{{ reservation.user?.name || 'Unbekannt' }}</span>
                                </div>
                                <span class="font-mono text-yellow-700">
                  {{ reservation.start_time.substring(0, 5) }} - {{ reservation.end_time.substring(0, 5) }}
                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Available Time Slots -->
                    <div v-if="getAvailableTimeSlots(spot).length > 0" class="mb-3">
                        <div class="text-xs font-medium text-green-700 mb-1">Verfügbare Zeiten:</div>
                        <div class="flex flex-wrap gap-1">
              <span
                  v-for="slot in getAvailableTimeSlots(spot)"
                  :key="slot"
                  class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded"
              >
                {{ slot }}
              </span>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="mt-3">
                        <button
                            v-if="getAvailableTimeSlots(spot).length > 0"
                            @click.stop="selectSpot(spot)"
                            class="w-full px-3 py-2 bg-green-500 text-white text-sm font-medium rounded hover:bg-green-600 transition-colors"
                        >
                            Reservieren
                        </button>
                        <div
                            v-else
                            class="w-full px-3 py-2 bg-gray-300 text-gray-500 text-sm font-medium rounded text-center cursor-not-allowed"
                        >
                            Nicht verfügbar
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-center space-x-4 mt-6">
                <button
                    @click="previousDay"
                    class="flex items-center space-x-2 px-4 py-2 text-blue-600 hover:text-blue-800 transition-colors"
                >
                    <span>←</span>
                    <span>Vorheriger Tag</span>
                </button>
                <button
                    @click="nextDay"
                    class="flex items-center space-x-2 px-4 py-2 text-blue-600 hover:text-blue-800 transition-colors"
                >
                    <span>Nächster Tag</span>
                    <span>→</span>
                </button>
            </div>

            <!-- Legend -->
            <div class="flex justify-center mt-4">
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="flex items-center space-x-4 text-xs">
                        <div class="flex items-center space-x-1">
                            <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                            <span>Enter/Space: Auswählen</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                            <span>←/→: Navigation</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
                            <span>⚡: Schnell-Reservierung</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Week View -->
        <div v-if="currentView === 'week'" class="space-y-4">
            <div class="text-center text-gray-500">
                Wochenansicht - Coming Soon
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, defineProps, defineEmits, watch } from 'vue'

const props = defineProps({
    parkingSpots: {
        type: Array,
        default: () => []
    },
    reservations: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['spot-selected', 'date-changed', 'quick-reserve'])

const currentView = ref('day')
const currentDate = ref(new Date())

const timeSlots = [
    {
        key: 'morning',
        label: 'Vormittag',
        time: '06:00-12:00',
        icon: '🌅',
        color: 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200'
    },
    {
        key: 'afternoon',
        label: 'Nachmittag',
        time: '12:00-18:00',
        icon: '🌞',
        color: 'bg-blue-100 text-blue-700 hover:bg-blue-200'
    },
    {
        key: 'fullday',
        label: 'Ganztags',
        time: '06:00-18:00',
        icon: '🌅🌙',
        color: 'bg-purple-100 text-purple-700 hover:bg-purple-200'
    }
]

const formatPeriodTitle = () => {
    if (currentView.value === 'day') {
        return currentDate.value.toLocaleDateString('de-DE', {
            month: 'long',
            year: 'numeric'
        })
    }
    return 'Woche'
}

const formatDayTitle = (date) => {
    return date.toLocaleDateString('de-DE', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    })
}

const goToToday = () => {
    currentDate.value = new Date()
    emitDateChange()
}

const previousPeriod = () => {
    if (currentView.value === 'day') {
        previousDay()
    }
}

const nextPeriod = () => {
    if (currentView.value === 'day') {
        nextDay()
    }
}

const previousDay = () => {
    const newDate = new Date(currentDate.value)
    newDate.setDate(newDate.getDate() - 1)
    currentDate.value = newDate
    emitDateChange()
}

const nextDay = () => {
    const newDate = new Date(currentDate.value)
    newDate.setDate(newDate.getDate() + 1)
    currentDate.value = newDate
    emitDateChange()
}

const emitDateChange = () => {
    const dateStr = currentDate.value.toISOString().split('T')[0]
    emit('date-changed', dateStr)
}

const getSpotReservationsForDate = (spot) => {
    const dateStr = currentDate.value.toISOString().split('T')[0]
    return props.reservations.filter(res =>
        res.parking_spot_id === spot.id &&
        res.reservation_date.split('T')[0] === dateStr &&
        ['pending', 'confirmed'].includes(res.status)
    ).sort((a, b) => a.start_time.localeCompare(b.start_time))
}

const getAvailableTimeSlots = (spot) => {
    const reservations = getSpotReservationsForDate(spot)

    if (!spot.is_active) return []

    if (reservations.length === 0) {
        return ['06:00 - 18:00']
    }

    const slots = []
    const dayStart = '06:00'
    const dayEnd = '18:00'

    const sortedReservations = [...reservations].sort((a, b) =>
        a.start_time.localeCompare(b.start_time)
    )

    // Slot vor der ersten Reservierung
    if (sortedReservations[0].start_time > dayStart) {
        slots.push(`${dayStart} - ${sortedReservations[0].start_time}`)
    }

    // Slots zwischen Reservierungen
    for (let i = 0; i < sortedReservations.length - 1; i++) {
        const currentEnd = sortedReservations[i].end_time
        const nextStart = sortedReservations[i + 1].start_time

        if (currentEnd < nextStart) {
            slots.push(`${currentEnd} - ${nextStart}`)
        }
    }

    // Slot nach der letzten Reservierung
    const lastReservation = sortedReservations[sortedReservations.length - 1]
    if (lastReservation.end_time < dayEnd) {
        slots.push(`${lastReservation.end_time} - ${dayEnd}`)
    }

    return slots
}

const getSpotStatusColor = (spot) => {
    if (!spot.is_active) return 'bg-gray-400'

    const reservations = getSpotReservationsForDate(spot)
    const availableSlots = getAvailableTimeSlots(spot)

    if (reservations.length === 0) return 'bg-green-500'
    if (availableSlots.length > 0) return 'bg-yellow-500'
    return 'bg-red-500'
}

const getSpotCardClasses = (spot) => {
    if (!spot.is_active) return 'border-gray-300 bg-gray-50'

    const reservations = getSpotReservationsForDate(spot)
    const availableSlots = getAvailableTimeSlots(spot)

    if (reservations.length === 0) return 'border-green-300 bg-green-50 hover:border-green-400'
    if (availableSlots.length > 0) return 'border-yellow-300 bg-yellow-50 hover:border-yellow-400'
    return 'border-red-300 bg-red-50'
}

const getAvailabilityText = (spot) => {
    if (!spot.is_active) return 'Inaktiv'

    const reservations = getSpotReservationsForDate(spot)
    const availableSlots = getAvailableTimeSlots(spot)

    if (reservations.length === 0) return 'Verfügbar'
    if (availableSlots.length > 0) return 'Teilweise verfügbar'
    return 'Belegt'
}

const getAvailabilityBadgeClasses = (spot) => {
    if (!spot.is_active) return 'bg-gray-100 text-gray-600'

    const reservations = getSpotReservationsForDate(spot)
    const availableSlots = getAvailableTimeSlots(spot)

    if (reservations.length === 0) return 'bg-green-100 text-green-700'
    if (availableSlots.length > 0) return 'bg-yellow-100 text-yellow-700'
    return 'bg-red-100 text-red-700'
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

const selectSpot = (spot) => {
    if (!spot.is_active) return

    const availableSlots = getAvailableTimeSlots(spot)
    if (availableSlots.length === 0) return

    emit('spot-selected', spot)
}

const quickReserveTimeSlot = (timeSlotKey) => {
    const timeSlotMap = {
        morning: { start: '06:00', end: '12:00' },
        afternoon: { start: '12:00', end: '18:00' },
        fullday: { start: '06:00', end: '18:00' }
    }

    const timeSlot = timeSlotMap[timeSlotKey]

    // Finde verfügbare Spots für dieses Zeitfenster
    const availableSpots = props.parkingSpots.filter(spot => {
        if (!spot.is_active) return false

        const reservations = getSpotReservationsForDate(spot)

        // Prüfe auf Zeitüberschneidungen
        for (const existing of reservations) {
            if (timeRangesOverlap(timeSlot.start, timeSlot.end, existing.start_time, existing.end_time)) {
                return false
            }
        }

        return true
    })

    if (availableSpots.length === 0) {
        alert('Keine verfügbaren Parkplätze für diesen Zeitraum.')
        return
    }

    // Nimm den ersten verfügbaren Spot
    const spot = availableSpots[0]

    emit('quick-reserve', {
        spot,
        timeSlot,
        date: currentDate.value
    })
}

// Hilfsfunktion für Zeitüberschneidungen
const timeRangesOverlap = (start1, end1, start2, end2) => {
    return (start1 < end2) && (start2 < end1)
}

// Emit initial date
watch(() => currentDate.value, () => {
    emitDateChange()
}, { immediate: true })
</script>
