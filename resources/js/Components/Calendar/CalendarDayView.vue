<template>
    <div class="w-full flex flex-col">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 shadow-md">
            <h3 class="text-xl font-semibold text-white text-center">
                {{ formatDate(currentDate) }}
                <span v-if="isHoliday(currentDate)" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-500 text-white">
                    {{ getHolidayName(currentDate) }}
                </span>
            </h3>
        </div>

        <!-- Day View - Event Columns Layout - scrollable container -->
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm mt-4">
            <div v-if="getUniqueEventsForDay(currentDate).length === 0" class="p-8 text-center text-gray-500">
                Keine Einträge für diesen Tag
            </div>

            <div v-else class="overflow-x-auto h-[calc(100vh-280px)]">
                <div class="flex gap-4 p-4 h-full">
                    <!-- Jede Event-Spalte -->
                    <div
                        v-for="(eventType, eventIndex) in getUniqueEventsForDay(currentDate)"
                        :key="eventIndex"
                        class="flex flex-col border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-900 shadow-sm flex-1 min-w-[250px]"
                    >
                        <!-- Event Header -->
                        <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-800 dark:to-gray-700 p-3 border-b border-gray-200 dark:border-gray-700 font-semibold">
                            <div
                                class="px-3 py-1.5 rounded-lg text-sm text-white font-medium text-center"
                                :style="{ backgroundColor: eventType.color }"
                            >
                                {{ eventType.name }}
                            </div>
                        </div>

                        <!-- Abteilungen gruppiert -->
                        <div class="overflow-y-auto flex-1">
                            <div
                                v-for="department in getUniqueDepartmentsForEvent(currentDate, eventType)"
                                :key="department"
                                class="border-b border-gray-100 dark:border-gray-800 last:border-b-0"
                            >
                                <!-- Department Header -->
                                <div class="bg-gray-100 dark:bg-gray-800 px-3 py-2 font-semibold text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    {{ department || 'Keine Abteilung' }}
                                </div>

                                <!-- Mitarbeiter dieser Abteilung mit Event -->
                                <div
                                    v-for="employee in getEmployeesForDepartmentAndEvent(currentDate, eventType, department)"
                                    :key="employee.id"
                                    class="px-3 py-2 border-b border-gray-100 dark:border-gray-800 last:border-b-0 hover:bg-blue-50 dark:hover:bg-gray-800 transition-colors duration-150"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs flex-shrink-0"
                                            :style="{ backgroundColor: getInitialsColor(employee.name) }"
                                        >
                                            {{ getInitials(employee.name) }}
                                        </div>
                                        <span class="text-sm font-medium truncate">{{ employee.name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps } from 'vue';

const props = defineProps({
    currentDate: { type: Object, required: true },
    filteredEmployees: { type: Array, required: true },
    isHoliday: { type: Function, required: true },
    getHolidayName: { type: Function, required: true },
    getEmployeeEventsForDay: { type: Function, required: true },
    getInitials: { type: Function, required: true },
    getInitialsColor: { type: Function, required: true },
    formatDate: { type: Function, required: true }
})

// Neue Hilfsfunktionen für das neue Layout
const getUniqueEventsForDay = (currentDate) => {
    const events = new Map()
    props.filteredEmployees.forEach(employee => {
        props.getEmployeeEventsForDay(employee, currentDate).forEach(event => {
            if (!events.has(event.type.name)) {
                events.set(event.type.name, event.type)
            }
        })
    })
    return Array.from(events.values())
}

const getUniqueDepartmentsForEvent = (currentDate, eventType) => {
    const departments = new Set()
    props.filteredEmployees.forEach(employee => {
        const hasEvent = props.getEmployeeEventsForDay(employee, currentDate).some(
            event => event.type.name === eventType.name
        )
        if (hasEvent) {
            departments.add(employee.department)
        }
    })
    return Array.from(departments).sort()
}

const getEmployeesForDepartmentAndEvent = (currentDate, eventType, department) => {
    return props.filteredEmployees.filter(employee => {
        const hasEvent = props.getEmployeeEventsForDay(employee, currentDate).some(
            event => event.type.name === eventType.name
        )
        return hasEvent && employee.department === department
    })
}

const hasEmployeeEventType = (employee, currentDate, eventType) => {
    return props.getEmployeeEventsForDay(employee, currentDate).some(
        event => event.type.name === eventType.name
    )
}
</script>
