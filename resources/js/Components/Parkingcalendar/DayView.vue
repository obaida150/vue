<template>
    <div class="space-y-4">
        <!-- Date Header -->
        <div class="text-center">
            <h4 class="text-lg font-medium text-gray-900">
                {{ formatDate(date) }}
            </h4>
            <p class="text-sm text-gray-500">{{ getDayName(date) }}</p>
        </div>

        <!-- Quick Actions Bar -->
        <div class="flex justify-center space-x-2 mb-4">
            <button
                @click="quickReserve('morning')"
                class="px-3 py-1 text-xs bg-green-100 text-green-700 hover:bg-green-200 rounded-full transition-colors"
                title="Schnell-Reservierung: 06:00-12:00"
            >
                🌅 Vormittag
            </button>
            <button
                @click="quickReserve('afternoon')"
                class="px-3 py-1 text-xs bg-blue-100 text-blue-700 hover:bg-blue-200 rounded-full transition-colors"
                title="Schnell-Reservierung: 12:00-18:00"
            >
                🌞 Nachmittag
            </button>
            <button
                @click="quickReserve('fullday')"
                class="px-3 py-1 text-xs bg-purple-100 text-purple-700 hover:bg-purple-200 rounded-full transition-colors"
                title="Schnell-Reservierung: 06:00-18:00"
            >
                🌅🌙 Ganztags
            </button>
        </div>

        <!-- Parking Spots Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="spot in parkingSpots"
                :key="spot.id"
                class="border rounded-lg p-4 transition-all duration-200 cursor-pointer relative group"
                :class="getSpotCardClasses(spot)"
                @click="selectSpot(spot)"
                @mouseenter="showTooltip(spot, $event)"
                @mouseleave="hideTooltip"
                :title="getSpotTooltip(spot)"
                tabindex="0"
                @keydown.enter="selectSpot(spot)"
                @keydown.space.prevent="selectSpot(spot)"
            >
                <!-- Spot Header -->
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h5 class="font-medium text-gray-900 flex items-center">
                            {{ spot.name || spot.identifier }}
                            <span v-if="spot.type === 'lift_top'" class="ml-1 text-xs">⬆️</span>
                            <span v-if="spot.type === 'lift_bottom'" class="ml-1 text-xs">⬇️</span>
                            <span v-if="spot.type === 'external'" class="ml-1 text-xs">🚗</span>
                        </h5>
                        <p class="text-sm text-gray-500">{{ getTypeLabel(spot.type) }}</p>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div
                            class="w-3 h-3 rounded-full transition-all duration-200"
                            :class="getStatusColor(spot)"
                        ></div>
                        <!-- Quick Reserve Button -->
                        <button
                            v-if="!getSpotReservation(spot) && spot.is_active"
                            @click.stop="quickReserveSpot(spot)"
                            class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 p-1 hover:bg-green-100 rounded"
                            title="Schnell reservieren"
                        >
                            ⚡
                        </button>
                    </div>
                </div>

                <!-- Reservation Info -->
                <div v-if="getSpotReservation(spot)" class="mt-2 p-2 bg-gray-50 rounded text-sm">
                    <div class="flex items-center text-gray-700">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        {{ getSpotReservation(spot).user?.name }}
                    </div>
                    <div class="flex items-center text-gray-600 mt-1">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ getSpotReservation(spot).start_time }} - {{ getSpotReservation(spot).end_time }}
                    </div>
                    <div v-if="getSpotReservation(spot).vehicle_info" class="flex items-center text-gray-600 mt-1">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12a3 3 0 006 0m-6 0a3 3 0 006 0m-6 0H6a2 2 0 00-2 2v3a2 2 0 002 2h12a2 2 0 002-2v-3a2 2 0 00-2-2h-3"></path>
                        </svg>
                        {{ getSpotReservation(spot).vehicle_info }}
                    </div>
                </div>

                <!-- Available indicator with animation -->
                <div v-else-if="spot.is_active" class="mt-2 text-sm text-green-600 font-medium flex items-center">
                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></div>
                    Verfügbar
                </div>

                <!-- Inactive indicator -->
                <div v-else class="mt-2 text-sm text-gray-400 font-medium flex items-center">
                    <div class="w-2 h-2 bg-gray-400 rounded-full mr-2"></div>
                    Nicht verfügbar
                </div>

                <!-- Hover overlay for better UX -->
                <div
                    v-if="spot.is_active && !getSpotReservation(spot)"
                    class="absolute inset-0 bg-green-50 opacity-0 group-hover:opacity-20 transition-opacity duration-200 rounded-lg pointer-events-none"
                ></div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex justify-center space-x-2 pt-4 border-t">
            <button
                @click="previousDay"
                class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded transition-colors"
                title="Vorheriger Tag (Pfeil links)"
            >
                ← Vorheriger Tag
            </button>
            <button
                @click="nextDay"
                class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded transition-colors"
                title="Nächster Tag (Pfeil rechts)"
            >
                Nächster Tag →
            </button>
        </div>

        <!-- Keyboard Shortcuts Info -->
        <div class="text-xs text-gray-500 text-center mt-2">
            <span class="inline-block mr-4">⌨️ Enter/Space: Auswählen</span>
            <span class="inline-block mr-4">←/→: Navigation</span>
            <span class="inline-block">⚡: Schnell-Reservierung</span>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    date: {
        type: Date,
        required: true
    },
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

const getSpotReservation = (spot) => {
    return props.reservations.find(res => res.parking_spot_id === spot.id)
}

const getSpotCardClasses = (spot) => {
    if (!spot.is_active) {
        return 'border-gray-300 bg-gray-50 cursor-not-allowed opacity-60'
    }

    const reservation = getSpotReservation(spot)
    if (reservation) {
        return 'border-yellow-300 bg-yellow-50 shadow-sm'
    }

    return 'border-green-300 bg-green-50 hover:border-green-400 hover:shadow-md hover:scale-105'
}

const getStatusColor = (spot) => {
    if (!spot.is_active) return 'bg-gray-400'

    const reservation = getSpotReservation(spot)
    if (reservation) return 'bg-yellow-500'

    return 'bg-green-500'
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

const getSpotTooltip = (spot) => {
    if (!spot.is_active) {
        return 'Parkplatz ist nicht verfügbar'
    }

    const reservation = getSpotReservation(spot)
    if (reservation) {
        return `Reserviert von ${reservation.user?.name} (${reservation.start_time} - ${reservation.end_time})`
    }

    return 'Klicken zum Reservieren oder ⚡ für Schnell-Reservierung'
}

const formatDate = (date) => {
    return date.toLocaleDateString('de-DE', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const getDayName = (date) => {
    const today = new Date()
    const tomorrow = new Date(today)
    tomorrow.setDate(tomorrow.getDate() + 1)

    if (date.toDateString() === today.toDateString()) {
        return 'Heute'
    } else if (date.toDateString() === tomorrow.toDateString()) {
        return 'Morgen'
    }

    return ''
}

const selectSpot = (spot) => {
    if (!spot.is_active || getSpotReservation(spot)) {
        return
    }

    emit('spot-selected', spot)
}

const quickReserve = (timeSlot) => {
    const availableSpots = props.parkingSpots.filter(spot => {
        if (!spot.is_active) return false

        // Prüfe, ob bereits eine Reservierung für diesen Spot existiert
        const hasReservation = props.reservations.some(res => res.parking_spot_id === spot.id)
        return !hasReservation
    })

    if (availableSpots.length === 0) {
        alert('Keine verfügbaren Parkplätze für diesen Tag.')
        return
    }

    // Take first available spot
    const spot = availableSpots[0]
    const timeSlots = {
        morning: { start: '06:00', end: '12:00' },
        afternoon: { start: '12:00', end: '18:00' },
        fullday: { start: '06:00', end: '18:00' }
    }

    emit('quick-reserve', {
        spot,
        timeSlot: timeSlots[timeSlot],
        date: props.date
    })
}

const quickReserveSpot = (spot) => {
    // Prüfe, ob der Spot bereits reserviert ist
    const hasReservation = props.reservations.some(res => res.parking_spot_id === spot.id)

    if (hasReservation) {
        alert('Dieser Parkplatz ist bereits reserviert.')
        return
    }

    emit('quick-reserve', {
        spot,
        timeSlot: { start: '06:00', end: '18:00' },
        date: props.date
    })
}

const previousDay = () => {
    const newDate = new Date(props.date)
    newDate.setDate(newDate.getDate() - 1)
    emit('date-changed', newDate)
}

const nextDay = () => {
    const newDate = new Date(props.date)
    newDate.setDate(newDate.getDate() + 1)
    emit('date-changed', newDate)
}

const showTooltip = (spot, event) => {
    // Could implement custom tooltip here if needed
}

const hideTooltip = () => {
    // Hide custom tooltip
}

// Keyboard navigation
const handleKeydown = (event) => {
    switch (event.key) {
        case 'ArrowLeft':
            event.preventDefault()
            previousDay()
            break
        case 'ArrowRight':
            event.preventDefault()
            nextDay()
            break
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown)
})
</script>
