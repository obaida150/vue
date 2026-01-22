<template>
            <div class="overflow-x-auto">
                <!-- Wochentage Header -->
                <div class="grid grid-cols-8 gap-1 mb-2">
                    <div class="p-2 text-sm font-medium text-gray-500">Parkplatz</div>
                    <div
                        v-for="day in weekDays"
                        :key="day.date"
                        class="p-2 text-center text-sm font-medium"
                        :class="isToday(day.date) ? 'bg-green-100 text-green-700 rounded' : 'text-gray-700'"
                    >
                        <div>{{ day.dayName }}</div>
                        <div class="text-xs">{{ day.dateStr }}</div>
                    </div>
                </div>

                <!-- Parkplätze Zeilen -->
                <div class="space-y-1">
                    <div
                        v-for="spot in parkingSpots"
                        :key="spot.id"
                        class="grid grid-cols-8 gap-1"
                    >
                        <!-- Parkplatz Name -->
                        <div class="p-2 text-sm font-medium text-gray-900 bg-gray-50 rounded flex items-center">
                            {{ spot.name || spot.identifier }}
                        </div>

                        <!-- Tage -->
                        <div
                            v-for="day in weekDays"
                            :key="day.date"
                            class="p-2 border-2 rounded cursor-pointer text-center text-xs transition-all"
                            :class="getDaySpotClasses(spot, day.date)"
                            @click="selectSpotForDay(spot, day.date)"
                        >
                            <template v-if="!spot.is_active">
                                <span class="text-gray-400">—</span>
                            </template>
                            <template v-else-if="isDayFullyReserved(spot, day.date)">
                                <span class="text-red-600 font-medium">Belegt</span>
                            </template>
                            <template v-else-if="getReservationsForDay(spot, day.date).length > 0">
                                <span class="text-yellow-600 font-medium">Teilweise</span>
                            </template>
                            <template v-else>
                                <span class="text-green-600 font-medium">Frei</span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Legende -->
                <div class="flex justify-center mt-4 space-x-4 text-xs">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-100 border-2 border-green-400 rounded mr-1"></div>
                        Verfügbar
                    </div>
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-yellow-100 border-2 border-yellow-400 rounded mr-1"></div>
                        Teilweise
                    </div>
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-red-100 border-2 border-red-400 rounded mr-1"></div>
                        Belegt
                    </div>
                </div>
            </div>
        </template>

        <script setup>
        import { computed } from 'vue'

        const props = defineProps({
            parkingSpots: {
                type: Array,
                default: () => []
            },
            reservations: {
                type: Array,
                default: () => []
            },
            currentDate: {
                type: Date,
                default: () => new Date()
            }
        })

        const emit = defineEmits(['spot-selected', 'date-changed', 'quick-reserve'])

        const weekDays = computed(() => {
            const days = []
            const weekStart = getWeekStart(props.currentDate)

            for (let i = 0; i < 7; i++) {
                const date = new Date(weekStart)
                date.setDate(date.getDate() + i)
                days.push({
                    date: formatDate(date),
                    dayName: date.toLocaleDateString('de-DE', { weekday: 'short' }),
                    dateStr: date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' })
                })
            }

            return days
        })

        const getWeekStart = (date) => {
            const d = new Date(date)
            const day = d.getDay()
            const diff = d.getDate() - day + (day === 0 ? -6 : 1)
            return new Date(d.setDate(diff))
        }

        const formatDate = (date) => {
            const year = date.getFullYear()
            const month = String(date.getMonth() + 1).padStart(2, '0')
            const day = String(date.getDate()).padStart(2, '0')
            return `${year}-${month}-${day}`
        }

        const isToday = (dateStr) => {
            return dateStr === formatDate(new Date())
        }

        const getReservationsForDay = (spot, dateStr) => {
            return props.reservations.filter(res =>
                res.parking_spot_id === spot.id &&
                res.reservation_date.split('T')[0] === dateStr &&
                ['pending', 'confirmed'].includes(res.status)
            )
        }

        const normalizeTime = (time) => time.substring(0, 5)

        const isDayFullyReserved = (spot, dateStr) => {
            const reservations = getReservationsForDay(spot, dateStr)
            if (reservations.length === 0) return false

            const sorted = [...reservations].sort((a, b) =>
                a.start_time.localeCompare(b.start_time)
            )

            let coveredEnd = '06:00'
            for (const res of sorted) {
                const start = normalizeTime(res.start_time)
                const end = normalizeTime(res.end_time)

                if (start > coveredEnd) return false
                if (end > coveredEnd) coveredEnd = end
            }

            return coveredEnd >= '18:00'
        }

        const getDaySpotClasses = (spot, dateStr) => {
            if (!spot.is_active) {
                return 'border-gray-300 bg-gray-100 cursor-not-allowed'
            }

            const reservations = getReservationsForDay(spot, dateStr)
            if (reservations.length > 0) {
                if (isDayFullyReserved(spot, dateStr)) {
                    return 'border-red-400 bg-red-100 cursor-not-allowed'
                }
                return 'border-yellow-400 bg-yellow-100 hover:bg-yellow-200'
            }

            return 'border-green-400 bg-green-100 hover:bg-green-200'
        }

        const selectSpotForDay = (spot, dateStr) => {
            if (!spot.is_active || isDayFullyReserved(spot, dateStr)) {
                return
            }

            emit('date-changed', dateStr)
            setTimeout(() => {
                emit('spot-selected', spot)
            }, 10)
        }
        </script>
