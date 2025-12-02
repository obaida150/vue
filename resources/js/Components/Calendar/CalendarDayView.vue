<template>
    <div class="w-full">
        <h3 class="text-xl font-medium text-center mb-4">
            {{ formatDate(currentDate) }}
            <span v-if="isHoliday(currentDate)" class="ml-2 text-red-500 font-bold">({{ getHolidayName(currentDate) }})</span>
        </h3>

        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm">
            <div class="grid grid-cols-12 bg-gray-100 dark:bg-gray-800 font-bold p-3">
                <div class="col-span-3">Mitarbeiter</div>
                <div class="col-span-4">Status</div>
                <div class="col-span-5">Notizen</div>
            </div>

            <div
                v-for="employee in filteredEmployees"
                :key="employee.id"
                class="grid grid-cols-12 border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
            >
                <div class="col-span-3 p-3 flex items-center gap-3">
                    <div 
                        class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm" 
                        :style="{ backgroundColor: getInitialsColor(employee.name) }"
                    >
                        {{ getInitials(employee.name) }}
                    </div>
                    <div>
                        <div class="font-medium">{{ employee.name }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ employee.department }}</div>
                    </div>
                </div>

                <div class="col-span-4 p-3">
                    <div class="flex flex-col gap-2">
                        <div
                            v-for="(event, eventIndex) in getEmployeeEventsForDay(employee, currentDate)"
                            :key="eventIndex"
                            class="px-3 py-1.5 rounded text-sm text-white shadow-sm"
                            :style="{ backgroundColor: event.type.color }"
                        >
                            {{ event.type.name }}
                        </div>
                        <div 
                            v-if="getEmployeeEventsForDay(employee, currentDate).length === 0" 
                            class="px-3 py-1.5 rounded text-sm bg-gray-200 dark:bg-gray-700 text-gray-500"
                        >
                            Nicht eingetragen
                        </div>
                    </div>
                </div>

                <div class="col-span-5 p-3">
                    <div v-for="(event, eventIndex) in getEmployeeEventsForDay(employee, currentDate)" :key="eventIndex">
                        <div v-if="event.notes" class="text-sm">{{ event.notes }}</div>
                    </div>
                    <div 
                        v-if="!getEmployeeEventsForDay(employee, currentDate).some(e => e.notes)" 
                        class="text-gray-400"
                    >
                        -
                    </div>
                </div>
            </div>

            <div v-if="filteredEmployees.length === 0" class="p-6 text-center text-gray-500">
                Keine Einträge für diesen Tag
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    currentDate: { type: Object, required: true },
    filteredEmployees: { type: Array, required: true },
    isHoliday: { type: Function, required: true },
    getHolidayName: { type: Function, required: true },
    getEmployeeEventsForDay: { type: Function, required: true },
    getInitials: { type: Function, required: true },
    getInitialsColor: { type: Function, required: true },
    formatDate: { type: Function, required: true }
})
</script>
