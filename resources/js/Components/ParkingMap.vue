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
                {{ getReservationForDate(spot) ? (getReservationForDate(spot).user?.initials || getReservationForDate(spot).user?.name?.substring(0, 2).toUpperCase()) : spot.identifier }}
              </span>
                        </div>

                        <!-- Spot Label with User Info -->
                        <div
                            v-if="showLabels || getReservationForDate(spot)"
                            class="absolute top-14 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-90 text-white text-xs px-3 py-2 rounded-lg whitespace-nowrap shadow-lg z-10"
                        >
                            <div class="font-semibold">{{ spot.name || spot.identifier }}</div>
                            <div class="text-xs opacity-75">{{ getTypeLabel(spot.type) }}</div>
                            <div v-if="getReservationForDate(spot)" class="text-xs text-yellow-300 mt-1">
                                👤 {{ getReservationForDate(spot).user?.name }}
                                <br>🕐 {{ getReservationForDate(spot).start_time }} - {{ getReservationForDate(spot).end_time }}
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
                {{ getReservationForDate(spot) ? (getReservationForDate(spot).user?.initials || getReservationForDate(spot).user?.name?.substring(0, 2).toUpperCase()) : spot.identifier }}
              </span>
                        </div>

                        <!-- Spot Label with User Info -->
                        <div
                            v-if="showLabels || getReservationForDate(spot)"
                            class="absolute top-14 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-90 text-white text-xs px-3 py-2 rounded-lg whitespace-nowrap shadow-lg z-10"
                        >
                            <div class="font-semibold">{{ spot.name || spot.identifier }}</div>
                            <div class="text-xs opacity-75">{{ getTypeLabel(spot.type) }}</div>
                            <div v-if="getReservationForDate(spot)" class="text-xs text-yellow-300 mt-1">
                                👤 {{ getReservationForDate(spot).user?.name }}
                                <br>🕐 {{ getReservationForDate(spot).start_time }} - {{ getReservationForDate(spot).end_time }}
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
import { ref, computed, defineProps, defineEmits, watch } from 'vue'

const props = defineProps({
    parkingSpots: {
        type: Array,
        default: () => []
    },
    parkingImage: {
        type: String,
        default: '/placeholder.svg?height=400&width=800&query=parking+garage+courtyard'
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
        const reservation = getReservationForDate(spot)
        return !reservation
    })
})

const selectSpot = (spot) => {
    const reservation = getReservationForDate(spot)

    if (!spot.is_active) {
        console.log('Spot is not active')
        return
    }

    if (reservation) {
        console.log('Spot is already reserved')
        return
    }

    console.log('Selecting available spot:', spot)
    emit('spot-selected', spot)
}

const onDateChange = () => {
    emit('date-changed', selectedDate.value)
}

const getReservationForDate = (spot) => {
    console.log(`=== Checking spot ${spot.identifier} (ID: ${spot.id}) ===`)
    console.log('Selected date:', selectedDate.value)
    console.log('All reservations passed to component:', props.reservations)

    const reservation = props.reservations.find(reservation => {
        const spotMatch = reservation.parking_spot_id === spot.id
        // Extrahiere nur das Datum aus dem ISO-String (vor dem 'T')
        const reservationDate = reservation.reservation_date.split('T')[0]
        const dateMatch = reservationDate === selectedDate.value
        const statusMatch = ['pending', 'confirmed'].includes(reservation.status)

        console.log(`Reservation ${reservation.id}: spotMatch=${spotMatch} (${reservation.parking_spot_id} === ${spot.id}), dateMatch=${dateMatch} (${reservationDate} === ${selectedDate.value}), statusMatch=${statusMatch}`)

        return spotMatch && dateMatch && statusMatch
    })

    if (reservation) {
        console.log('✅ Found reservation:', reservation)
    } else {
        console.log('❌ No reservation found')
    }

    return reservation
}

const getSpotClasses = (spot) => {
    if (!spot.is_active) {
        return 'bg-gray-400 border-gray-500 cursor-not-allowed'
    }

    // Check if spot has reservation for selected date
    const reservation = getReservationForDate(spot)
    if (reservation) {
        return 'bg-yellow-500 border-yellow-600 cursor-not-allowed'
    }

    return 'bg-green-500 border-green-600 hover:bg-green-600 cursor-pointer'
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
