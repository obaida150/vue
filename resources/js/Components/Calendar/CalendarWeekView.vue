<template>
    <div class="w-full">
        <!-- Desktop Ansicht -->
        <div class="hidden lg:block overflow-x-auto rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <table class="w-full border-collapse min-w-[900px]">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-800">
                        <th class="p-3 text-left font-bold border-b border-r border-gray-200 dark:border-gray-700 w-[140px] min-w-[140px]">
                            Tag
                        </th>
                        <th
                            v-for="eventType in activeEventTypesForWeek"
                            :key="eventType.value"
                            class="p-3 text-center font-bold border-b border-r border-gray-200 dark:border-gray-700 min-w-[150px]"
                        >
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: eventType.color }"></div>
                                <span>{{ eventType.name }}</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="day in weekDays"
                        :key="day.date.format('YYYY-MM-DD')"
                        class="border-b border-gray-200 dark:border-gray-700"
                        :class="{
                            'bg-blue-50 dark:bg-blue-900/20': day.isToday,
                            'bg-red-50 dark:bg-red-900/20': isHoliday(day.date),
                            'bg-gray-100 dark:bg-gray-800/50': day.isWeekend && !isHoliday(day.date)
                        }"
                    >
                        <td class="p-3 border-r border-gray-200 dark:border-gray-700 align-top">
                            <div class="font-bold text-lg">{{ day.dayName }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ formatDayMonth(day.date) }}</div>
                            <div v-if="isHoliday(day.date)" class="text-xs text-red-500 font-semibold mt-1">
                                {{ getHolidayName(day.date) }}
                            </div>
                        </td>
                        <td
                            v-for="eventType in activeEventTypesForWeek"
                            :key="eventType.value"
                            class="p-2 border-r border-gray-200 dark:border-gray-700 align-top"
                        >
                            <div class="flex flex-col gap-2">
                                <div
                                    v-for="deptGroup in getEmployeesByEventTypeAndDay(eventType, day.date)"
                                    :key="deptGroup.department"
                                    class="mb-2"
                                >
                                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 border-b border-gray-200 dark:border-gray-700 pb-1">
                                        {{ deptGroup.department }}
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <div
                                            v-for="emp in deptGroup.employees"
                                            :key="emp.id"
                                            class="flex items-center gap-2 p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition-colors"
                                            @click="$emit('openEmployeeDialog', emp, day.date)"
                                        >
                                            <div
                                                class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold shadow-sm flex-shrink-0"
                                                :style="{ backgroundColor: getInitialsColor(emp.name) }"
                                            >
                                                {{ getInitials(emp.name) }}
                                            </div>
                                            <span class="text-sm truncate">{{ emp.name }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-if="getEmployeesByEventTypeAndDay(eventType, day.date).length === 0"
                                    class="text-xs text-gray-400 dark:text-gray-500 text-center py-2"
                                >
                                    -
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Ansicht -->
        <div class="lg:hidden flex flex-col gap-4">
            <div class="flex justify-between items-center mb-2 bg-gray-100 dark:bg-gray-800 p-3 rounded-lg">
                <button
                    @click="mobileWeekDay = Math.max(0, mobileWeekDay - 1)"
                    :disabled="mobileWeekDay === 0"
                    class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-50"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <div class="text-center">
                    <div class="font-bold text-lg">{{ weekDays[mobileWeekDay]?.dayName }}</div>
                    <div class="text-sm">{{ formatDayMonth(weekDays[mobileWeekDay]?.date) }}</div>
                    <div v-if="isHoliday(weekDays[mobileWeekDay]?.date)" class="text-xs text-red-500 font-semibold">
                        {{ getHolidayName(weekDays[mobileWeekDay]?.date) }}
                    </div>
                </div>
                <button
                    @click="mobileWeekDay = Math.min(6, mobileWeekDay + 1)"
                    :disabled="mobileWeekDay === 6"
                    class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-50"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>

            <div class="flex justify-center gap-2 mb-4">
                <button
                    v-for="(day, index) in weekDays"
                    :key="index"
                    @click="mobileWeekDay = index"
                    class="w-3 h-3 rounded-full transition-colors"
                    :class="{
                        'bg-emerald-500': mobileWeekDay === index,
                        'bg-gray-300 dark:bg-gray-600': mobileWeekDay !== index
                    }"
                ></button>
            </div>

            <div class="flex flex-col gap-4">
                <div
                    v-for="eventType in activeEventTypesForDay(weekDays[mobileWeekDay]?.date)"
                    :key="eventType.value"
                    class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm"
                >
                    <div
                        class="p-3 flex items-center gap-2"
                        :style="{ backgroundColor: eventType.color + '20' }"
                    >
                        <div class="w-5 h-5 rounded-full" :style="{ backgroundColor: eventType.color }"></div>
                        <span class="font-bold">{{ eventType.name }}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-auto">
                            {{ getEmployeesCountByEventTypeAndDay(eventType, weekDays[mobileWeekDay]?.date) }} Mitarbeiter
                        </span>
                    </div>
                    <div class="p-3">
                        <div
                            v-for="deptGroup in getEmployeesByEventTypeAndDay(eventType, weekDays[mobileWeekDay]?.date)"
                            :key="deptGroup.department"
                            class="mb-3 last:mb-0"
                        >
                            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2 border-b border-gray-200 dark:border-gray-700 pb-1">
                                {{ deptGroup.department }}
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <div
                                    v-for="emp in deptGroup.employees"
                                    :key="emp.id"
                                    class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
                                    @click="$emit('openEmployeeDialog', emp, weekDays[mobileWeekDay]?.date)"
                                >
                                    <div
                                        class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shadow-sm"
                                        :style="{ backgroundColor: getInitialsColor(emp.name) }"
                                    >
                                        {{ getInitials(emp.name) }}
                                    </div>
                                    <span class="text-sm font-medium">{{ emp.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    v-if="activeEventTypesForDay(weekDays[mobileWeekDay]?.date).length === 0"
                    class="text-center p-6 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-lg"
                >
                    Keine Einträge für diesen Tag
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
    weekDays: { type: Array, required: true },
    filteredEmployees: { type: Array, required: true },
    activeEventTypesForWeek: { type: Array, required: true },
    isHoliday: { type: Function, required: true },
    getHolidayName: { type: Function, required: true },
    getEmployeeEventsForDay: { type: Function, required: true },
    getEmployeesByEventTypeAndDay: { type: Function, required: true },
    getEmployeesCountByEventTypeAndDay: { type: Function, required: true },
    activeEventTypesForDay: { type: Function, required: true },
    getInitials: { type: Function, required: true },
    getInitialsColor: { type: Function, required: true },
    formatDayMonth: { type: Function, required: true }
})

defineEmits(['openEmployeeDialog'])

const mobileWeekDay = ref(0)
</script>
