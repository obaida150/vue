<template>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-bold">{{ employee?.name }}</h3>
                <button @click="$emit('close')" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div v-if="employee && date" class="p-4">
                <div class="flex items-center gap-3 mb-4 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold shadow-sm"
                        :style="{ backgroundColor: getInitialsColor(employee.name) }"
                    >
                        {{ getInitials(employee.name) }}
                    </div>
                    <div>
                        <div class="font-bold">{{ employee.name }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ employee.department }}</div>
                    </div>
                </div>

                <div class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                    {{ formatDate(date) }}
                </div>

                <div class="flex flex-col gap-3">
                    <div
                        v-for="(event, eventIndex) in getEmployeeEventsForDay(employee, date)"
                        :key="eventIndex"
                        class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden"
                    >
                        <div class="p-2 font-medium text-white" :style="{ backgroundColor: event.type.color }">
                            {{ event.type.name }}
                        </div>
                        <div class="p-3">
                            <div v-if="event.notes" class="text-sm">{{ event.notes }}</div>
                            <div v-else class="text-sm text-gray-500 dark:text-gray-400">Keine Notizen</div>
                        </div>
                    </div>

                    <div 
                        v-if="getEmployeeEventsForDay(employee, date).length === 0" 
                        class="text-center p-4 text-gray-500"
                    >
                        Keine Einträge für diesen Tag
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    employee: { type: Object, default: null },
    date: { type: Object, default: null },
    getEmployeeEventsForDay: { type: Function, required: true },
    getInitials: { type: Function, required: true },
    getInitialsColor: { type: Function, required: true },
    formatDate: { type: Function, required: true }
})

defineEmits(['close'])
</script>
