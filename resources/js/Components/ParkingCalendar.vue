<template>
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Calendar Header -->
        <div class="p-4 bg-gray-50 border-b">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Parkplatz-Kalender</h3>
                <!-- View Buttons - Hide Week view on mobile -->
                <div class="flex space-x-2">
                    <button
                        v-for="view in availableViews"
                        :key="view.key"
                        @click="currentView = view.key"
                        :class="[
              currentView === view.key
                ? 'bg-green-500 text-white'
                : 'bg-white text-gray-700 hover:bg-green-50 hover:text-green-700',
              'px-3 py-1 text-sm font-medium rounded-md border border-green-300'
            ]"
                    >
                        {{ view.label }}
                    </button>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <button @click="previousPeriod" class="p-2 hover:bg-gray-200 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <h4 class="text-xl font-semibold text-gray-900 min-w-[200px] text-center">
                        {{ currentPeriodLabel }}
                    </h4>
                    <button @click="nextPeriod" class="p-2 hover:bg-gray-200 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>

                <button @click="goToToday" class="px-3 py-1 text-sm bg-green-100 text-green-700 hover:bg-green-200 rounded">
                    Heute
                </button>
            </div>
        </div>

        <!-- Calendar Content -->
        <div class="p-4">
            <!-- Day View -->
            <div v-if="currentView === 'day'" class="space-y-4">
                <DayView
                    :date="currentDate"
                    :parking-spots="parkingSpots"
                    :reservations="dayReservations"
                    @spot-selected="$emit('spot-selected', $event)"
                    @date-changed="setCurrentDate"
                    @quick-reserve="$emit('quick-reserve', $event)"
                />
            </div>

            <!-- Week View - Only on desktop -->
            <div v-else-if="currentView === 'week'" class="overflow-x-auto">
                <WeekView
                    :start-date="weekStartDate"
                    :parking-spots="parkingSpots"
                    :reservations="weekReservations"
                    @spot-selected="$emit('spot-selected', $event)"
                    @date-changed="setCurrentDate"
                    @quick-reserve="$emit('quick-reserve', $event)"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import DayView from './Parkingcalendar/DayView.vue'
import WeekView from './Parkingcalendar/WeekView.vue'

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

const allViews = [
    { key: 'day', label: 'Tag' },
    { key: 'week', label: 'Woche' }
]

// Replace the availableViews computed property with this:
const isMobile = ref(false)
const availableViews = computed(() => {
    if (isMobile.value) {
        // Force day view on mobile and only show day option
        if (currentView.value === 'week') {
            currentView.value = 'day'
        }
        return allViews.filter(view => view.key === 'day')
    }

    return allViews
})

const weekStartDate = computed(() => {
    const date = new Date(currentDate.value)
    const day = date.getDay()
    const diff = date.getDate() - day + (day === 0 ? -6 : 1) // Monday as first day
    return new Date(date.setDate(diff))
})

const currentPeriodLabel = computed(() => {
    const date = currentDate.value
    const options = {
        year: 'numeric',
        month: 'long',
        day: currentView.value === 'day' ? 'numeric' : undefined
    }

    if (currentView.value === 'week') {
        const endDate = new Date(weekStartDate.value)
        endDate.setDate(endDate.getDate() + 6)

        const startStr = weekStartDate.value.toLocaleDateString('de-DE', { day: 'numeric', month: 'short' })
        const endStr = endDate.toLocaleDateString('de-DE', { day: 'numeric', month: 'short', year: 'numeric' })

        return `${startStr} - ${endStr}`
    }

    return date.toLocaleDateString('de-DE', options)
})

// Filtered reservations for current view
const dayReservations = computed(() => {
    const dateStr = currentDate.value.toISOString().split('T')[0]
    return props.reservations.filter(res =>
        res.reservation_date.split('T')[0] === dateStr
    )
})

const weekReservations = computed(() => {
    const startDate = new Date(weekStartDate.value)
    const endDate = new Date(startDate)
    endDate.setDate(endDate.getDate() + 6)

    return props.reservations.filter(res => {
        const resDate = new Date(res.reservation_date)
        return resDate >= startDate && resDate <= endDate
    })
})

// Navigation methods
const previousPeriod = () => {
    const date = new Date(currentDate.value)

    switch (currentView.value) {
        case 'day':
            date.setDate(date.getDate() - 1)
            break
        case 'week':
            date.setDate(date.getDate() - 7)
            break
    }

    currentDate.value = date
}

const nextPeriod = () => {
    const date = new Date(currentDate.value)

    switch (currentView.value) {
        case 'day':
            date.setDate(date.getDate() + 1)
            break
        case 'week':
            date.setDate(date.getDate() + 7)
            break
    }

    currentDate.value = date
}

const goToToday = () => {
    currentDate.value = new Date()
}

const setCurrentDate = (date) => {
    if (typeof date === 'string') {
        currentDate.value = new Date(date)
    } else {
        currentDate.value = date
    }
}

// Watch for date changes and emit to parent
watch(currentDate, (newDate) => {
    emit('date-changed', newDate.toISOString().split('T')[0])
}, { immediate: true })

// Update the onMounted function to properly handle resize detection:
onMounted(() => {
    // Initial check
    const checkMobile = () => {
        isMobile.value = window.innerWidth < 768
    }

    checkMobile() // Check immediately

    const handleResize = () => {
        checkMobile()
    }

    window.addEventListener('resize', handleResize)

    // Cleanup function
    const cleanup = () => {
        window.removeEventListener('resize', handleResize)
    }

    // Return cleanup function for onUnmounted
    return cleanup
})

onUnmounted(() => {
    // Cleanup is handled in onMounted return
})
</script>
