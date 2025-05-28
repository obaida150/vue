<template>
    <div class="space-y-4">
        <!-- Week Header -->
        <div class="grid grid-cols-8 gap-2 mb-4">
            <div class="text-sm font-medium text-gray-500 p-2">Parkplatz</div>
            <div
                v-for="day in weekDays"
                :key="day.date"
                class="text-center p-2"
                :class="isToday(day.date) ? 'bg-blue-100 rounded' : ''"
            >
                <div class="text-sm font-medium text-gray-900">{{ day.name }}</div>
                <div class="text-xs text-gray-500">{{ day.date }}</div>
            </div>
        </div>

        <!-- Parking Spots Rows -->
        <div class="space-y-2 max-h-96 overflow-y-auto">
            <div
                v-for="spot in parkingSpots"
                :key="spot.id"
                class="grid grid-cols-8 gap-2 items-center border-b border-gray-100 py-2"
            >
                <!-- Spot Info -->
                <div class="p-2">
                    <div class="text-sm font-medium text-gray-900">{{ spot.name || spot.identifier }}</div>
                    <div class="text-xs text-gray-500">{{ getTypeLabel(spot.type) }}</div>
                </div>

                <!-- Week Days -->
                <div
                    v-for="day in weekDays"
                    :key="`${spot.id}-${day.date}`"
                    class="p-1 relative group"
                >
                    <div
                        class="h-12 rounded border-2 cursor-pointer transition-all relative"
                        :class="getDaySpotClasses(spot, day.date)"
                        @click="selectSpotForDay(spot, day.date)"
                    >
                        <div v-if="getReservationForDay(spot, day.date)" class="p-1 text-xs">
                            <div class="font-medium truncate">{{ getReservationForDay(spot, day.date).user?.name }}</div>
                            <div class="text-gray-600">{{ getReservationForDay(spot, day.date).start_time }}</div>
                        </div>

                        <!-- Quick Reserve Button for Week View -->
                        <button
                            v-else-if="spot.is_active"
                            @click.stop="quickReserveSpotForDay(spot, day.date)"
                            class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity text-xs hover:bg-green-200 rounded px-1"
                            title="Schnell reservieren"
                        >
                            ⚡
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="flex justify-center space-x-4 text-xs text-gray-600 pt-4 border-t">
            <div class="flex items-center">
                <div class="w-3 h-3 bg-green-200 border-2 border-green-400 rounded mr-1"></div>
                Verfügbar
            </div>
            <div class="flex items-center">
                <div class="w-3 h-3 bg-yellow-200 border-2 border-yellow-400 rounded mr-1"></div>
                Reserviert
            </div>
            <div class="flex items-center">
                <div class="w-3 h-3 bg-gray-200 border-2 border-gray-400 rounded mr-1"></div>
                Inaktiv
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    startDate: {
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

const weekDays = computed(() => {
    const days = []
    const start = new Date(props.startDate)

    for (let i = 0; i < 7; i++) {
        const date = new Date(start)
        date.setDate(start.getDate() + i)

        days.push({
            name: date.toLocaleDateString('de-DE', { weekday: 'short' }),
            date: date.toISOString().split('T')[0],
            fullDate: date
        })
    }

    return days
})

const getReservationForDay = (spot, dateStr) => {
    return props.reservations.find(res =>
        res.parking_spot_id === spot.id &&
        res.reservation_date.split('T')[0] === dateStr
    )
}

const getDaySpotClasses = (spot, dateStr) => {
    if (!spot.is_active) {
        return 'border-gray-300 bg-gray-100 cursor-not-allowed'
    }

    const reservation = getReservationForDay(spot, dateStr)
    if (reservation) {
        return 'border-yellow-400 bg-yellow-100'
    }

    return 'border-green-400 bg-green-100 hover:bg-green-200'
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

const isToday = (dateStr) => {
    const today = new Date().toISOString().split('T')[0]
    return dateStr === today
}

const selectSpotForDay = (spot, dateStr) => {
    if (!spot.is_active || getReservationForDay(spot, dateStr)) {
        return
    }

    // Erst das Datum setzen und dann mit einem kleinen Delay den Spot auswählen
    emit('date-changed', dateStr) // String statt Date Object senden

    // Verwende nextTick oder setTimeout um sicherzustellen, dass das Datum zuerst verarbeitet wird
    setTimeout(() => {
        emit('spot-selected', spot)
    }, 10)
}

const quickReserveSpotForDay = (spot, dateStr) => {
    if (!spot.is_active) {
        return
    }

    // Prüfe, ob bereits eine Reservierung existiert
    const existingReservation = getReservationForDay(spot, dateStr)
    if (existingReservation) {
        alert('Dieser Parkplatz ist bereits für diesen Tag reserviert.')
        return
    }

    emit('quick-reserve', {
        spot,
        timeSlot: { start: '06:00', end: '18:00' },
        date: new Date(dateStr + 'T00:00:00') // Explizit lokale Zeit verwenden
    })
}
</script>
