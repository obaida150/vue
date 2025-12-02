<template>
    <div class="w-full">
        <!-- Desktop Month View -->
        <div class="hidden xl:block overflow-x-auto rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex min-w-[1200px]">
                <div class="w-[200px] min-w-[200px] p-2 bg-gray-100 dark:bg-gray-800 font-bold">Mitarbeiter</div>
                <div
                    v-for="dayNum in daysInMonth"
                    :key="dayNum"
                    class="w-[50px] min-w-[30px] py-2 text-center text-sm font-bold border-l border-gray-200 dark:border-gray-700"
                    :class="{
                        'bg-blue-50 dark:bg-blue-900/20': isToday(dayNum),
                        'bg-red-50 dark:bg-red-900/20': isHolidayInMonth(dayNum),
                        'bg-gray-100 dark:bg-gray-800': !isToday(dayNum) && !isWeekendDay(dayNum) && !isHolidayInMonth(dayNum),
                        'bg-gray-200 dark:bg-gray-700': isWeekendDay(dayNum) && !isHolidayInMonth(dayNum)
                    }"
                >
                    <div :class="{ 'text-red-500': isHolidayInMonth(dayNum) }">{{ dayNum }}</div>
                </div>
            </div>

            <div class="flex flex-col min-w-[1200px]">
                <div
                    v-for="employee in filteredEmployees"
                    :key="employee.id"
                    class="flex border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
                >
                    <div class="w-[200px] min-w-[200px] p-2 flex items-center gap-3">
                        <div 
                            class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm" 
                            :style="{ backgroundColor: getInitialsColor(employee.name) }"
                        >
                            {{ getInitials(employee.name) }}
                        </div>
                        <div class="overflow-hidden">
                            <div class="font-medium truncate">{{ employee.name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ employee.department }}</div>
                        </div>
                    </div>

                    <div
                        v-for="dayNum in daysInMonth"
                        :key="dayNum"
                        class="w-[50px] min-w-[30px] h-[50px] flex items-center justify-center border-l border-gray-200 dark:border-gray-700"
                        :class="{
                            'bg-blue-50 dark:bg-blue-900/20': isToday(dayNum),
                            'bg-red-50 dark:bg-red-900/20': isHolidayInMonth(dayNum),
                            'bg-gray-200 dark:bg-gray-700': isWeekendDay(dayNum) && !isHolidayInMonth(dayNum)
                        }"
                    >
                        <div class="flex flex-wrap gap-0.5 justify-center">
                            <div
                                v-for="(event, eventIndex) in getEmployeeEventsForMonthDay(employee, dayNum).slice(0, 3)"
                                :key="eventIndex"
                                class="w-2 h-2 rounded-full"
                                :style="{ backgroundColor: event.type.color }"
                                :title="event.type.name"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Month View -->
        <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
                v-for="employee in filteredEmployees"
                :key="employee.id"
                class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm"
            >
                <div class="p-3 bg-gray-100 dark:bg-gray-800 flex items-center gap-3">
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

                <div class="p-3">
                    <div class="grid grid-cols-7 gap-1 mb-1 text-center text-xs">
                        <div v-for="day in weekdaysShort" :key="day" class="font-medium">{{ day }}</div>
                    </div>

                    <div class="grid grid-cols-7 gap-1">
                        <div v-for="i in getMonthStartDay()" :key="`empty-${i}`" class="w-8 h-8"></div>
                        <div
                            v-for="dayNum in daysInMonth"
                            :key="`day-${dayNum}`"
                            class="w-8 h-8 flex items-center justify-center text-xs rounded-full relative cursor-pointer"
                            :class="{
                                'bg-blue-500 text-white': isToday(dayNum),
                                'bg-red-100 dark:bg-red-900/20 text-red-800': !isToday(dayNum) && isHolidayInMonth(dayNum),
                                'bg-gray-200 dark:bg-gray-700': !isToday(dayNum) && isWeekendDay(dayNum) && !isHolidayInMonth(dayNum),
                                'font-bold': getEmployeeEventsForMonthDay(employee, dayNum).length > 0
                            }"
                        >
                            {{ dayNum }}
                            <div
                                v-if="getEmployeeEventsForMonthDay(employee, dayNum).length > 0"
                                class="absolute -bottom-0.5 left-1/2 transform -translate-x-1/2 w-1.5 h-1.5 rounded-full"
                                :style="{ backgroundColor: getEmployeeEventsForMonthDay(employee, dayNum)[0]?.type.color }"
                            ></div>
                        </div>
                    </div>
                </div>

                <div class="p-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <div class="flex flex-wrap gap-2 text-sm">
                        <div
                            v-for="summary in getEmployeeMonthSummary(employee)"
                            :key="summary.type"
                            class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded-full"
                        >
                            <div class="w-2 h-2 rounded-full" :style="{ backgroundColor: summary.color }"></div>
                            <span>{{ summary.name }}: {{ summary.count }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    currentDate: { type: Object, required: true },
    daysInMonth: { type: Array, required: true },
    filteredEmployees: { type: Array, required: true },
    isToday: { type: Function, required: true },
    isHolidayInMonth: { type: Function, required: true },
    isWeekendDay: { type: Function, required: true },
    getMonthStartDay: { type: Function, required: true },
    getEmployeeEventsForMonthDay: { type: Function, required: true },
    getEmployeeMonthSummary: { type: Function, required: true },
    getInitials: { type: Function, required: true },
    getInitialsColor: { type: Function, required: true },
    weekdaysShort: { type: Array, required: true }
})
</script>
