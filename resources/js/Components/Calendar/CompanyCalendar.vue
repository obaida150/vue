<template>
    <div :class="[
        'w-full overflow-x-auto p-6 rounded-lg shadow-md transition-all duration-300',
        isDarkMode ? 'bg-gray-900 text-gray-100' : 'bg-white text-gray-800'
    ]">
        <!-- Calendar Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div class="flex items-center gap-4">
                <Button icon="pi pi pi-chevron-left" @click="previousPeriod" class="p-button-rounded p-button-text" />
                <h2 class="text-2xl font-semibold capitalize m-0">
                    <span v-if="calendarView === 'day'">{{ formatDate(currentDate) }}</span>
                    <span v-else-if="calendarView === 'week'">KW {{ currentWeekNumber }} ({{ formatDateRange(weekStart, weekEnd) }})</span>
                    <span v-else>{{ currentMonthName }} {{ currentYear }}</span>
                </h2>
                <Button icon="pi pi pi-chevron-right" @click="nextPeriod" class="p-button-rounded p-button-text" />
            </div>
            <div class="flex items-center gap-4 w-full md:w-auto">

                <div class="flex gap-1">
                    <Button
                        :class="{ 'p-button-primary': calendarView === 'day', 'p-button-outlined': calendarView !== 'day' }"
                        label="Tag"
                        @click="() => setCalendarView('day')"
                        class="p-button-rounded"
                    />
                    <Button
                        :class="{ 'p-button-primary': calendarView === 'week', 'p-button-outlined': calendarView !== 'week' }"
                        label="Woche"
                        @click="() => setCalendarView('week')"
                        class="p-button-rounded"
                    />
                    <Button
                        :class="{ 'p-button-primary': calendarView === 'month', 'p-button-outlined': calendarView !== 'month' }"
                        label="Monat"
                        @click="() => setCalendarView('month')"
                        class="p-button-rounded"
                    />
                </div>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="mb-4">
            <div class="flex flex-col md:flex-row justify-between items-center w-full gap-4">
                <div class="w-full md:flex-1">
                    <span class="p-input-icon-left w-full">
                        <InputText v-model="searchQuery" placeholder="Mitarbeiter suchen..." class="w-full" />
                    </span>
                </div>
                <div class="w-full md:flex-1">
                    <MultiSelect
                        v-model="selectedDepartments"
                        :options="availableDepartments"
                        optionLabel="name"
                        placeholder="Abteilungen filtern"
                        class="w-full"
                        :maxSelectedLabels="3"
                    />
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="flex flex-col gap-4 mb-6">
            <!-- Department Cards -->
            <div :class="[
                'grid gap-4',
                getDynamicGridClass(departmentSummary.length)
            ]">
                <div
                    v-for="department in departmentSummary"
                    :key="department.name"
                    class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md flex flex-col items-center text-center border-l-4 border-l-blue-500"
                    @click="openDepartmentDialog(department.name)"
                >
                    <div class="font-semibold mb-2">{{ department.name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ department.count }} Mitarbeiter</div>
                </div>
            </div>

            <!-- Status Cards -->
            <div :class="[
                'grid gap-4',
                getDynamicGridClass(statusSummary.length)
            ]">
                <div
                    v-for="status in statusSummary"
                    :key="status.type.value"
                    class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md flex flex-col items-center text-center"
                    :style="{ borderLeftWidth: '4px', borderLeftColor: status.type.color }"
                    @click="openStatusDialog(status.type)"
                >
                    <div class="w-6 h-6 rounded-full mb-2" :style="{ backgroundColor: status.type.color }"></div>
                    <div class="font-semibold mb-2">{{ status.type.name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ status.count }} Mitarbeiter</div>
                </div>
            </div>
        </div>

        <!-- Day View -->
        <div v-if="calendarView === 'day'" class="w-full">
            <h3 class="text-xl font-medium text-center mb-4">
                {{ formatDate(currentDate) }}
                <span v-if="isHoliday(currentDate)" class="ml-2 text-red-500 font-bold">({{ getHolidayName(currentDate) }})</span>
            </h3>

            <!-- Desktop View (md und größer) -->
            <div class="hidden md:flex md:flex-col w-full border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm">
                <!-- Table Header -->
                <div class="grid grid-cols-12 bg-gray-100 dark:bg-gray-800 font-bold p-3">
                    <div class="col-span-3">Mitarbeiter</div>
                    <div class="col-span-4">Status</div>
                    <div class="col-span-5">Notizen</div>
                </div>

                <!-- Table Body -->
                <div
                    v-for="employee in filteredEmployees"
                    :key="employee.id"
                    class="grid grid-cols-12 border-t border-gray-200 dark:border-gray-700 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 even:bg-gray-50 dark:even:bg-gray-800"
                >
                    <!-- Employee Column -->
                    <div class="col-span-3 p-3 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm" :style="{ backgroundColor: getInitialsColor(employee.name) }">
                            {{ employee.initials || getInitials(employee.name) }}
                        </div>
                        <div>
                            <div class="font-medium">{{ employee.name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ employee.department }}</div>
                        </div>
                    </div>

                    <!-- Status Column -->
                    <div class="col-span-4 p-3">
                        <div class="flex flex-col gap-2">
                            <div
                                v-for="(event, eventIndex) in getEmployeeEventsForDay(employee, currentDate)"
                                :key="eventIndex"
                                class="px-3 py-1.5 rounded text-sm text-white shadow-sm flex justify-between items-center"
                                :style="{ backgroundColor: event.type.color }"
                            >
                                <span>{{ event.type.name }}</span>
                                <span v-if="getEmployeeEventsForDay(employee, currentDate).length > 1" class="text-xs bg-white bg-opacity-20 px-1.5 py-0.5 rounded-full">
                                    {{ eventIndex + 1 }}/{{ getEmployeeEventsForDay(employee, currentDate).length }}
                                </span>
                            </div>
                            <div v-if="getEmployeeEventsForDay(employee, currentDate).length === 0" class="px-3 py-1.5 rounded text-sm bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                                Nicht eingetragen
                            </div>
                        </div>
                    </div>

                    <!-- Notes Column -->
                    <div class="col-span-5 p-3">
                        <div class="flex flex-col gap-2">
                            <div v-for="(event, eventIndex) in getEmployeeEventsForDay(employee, currentDate)" :key="eventIndex">
                                <div v-if="event.notes" class="border border-gray-200 dark:border-gray-700 rounded">
                                    <div class="text-xs font-medium text-gray-600 dark:text-gray-400 px-3 py-1 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                        {{ event.type.name }}:
                                    </div>
                                    <div class="px-3 py-1.5 text-sm">{{ event.notes }}</div>
                                </div>
                            </div>
                            <div v-if="getEmployeeEventsForDay(employee, currentDate).length === 0 || !getEmployeeEventsForDay(employee, currentDate).some(e => e.notes)" class="py-1.5 text-gray-400">
                                -
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile View (sm und kleiner) -->
            <div class="md:hidden flex flex-col gap-4">
                <div
                    v-for="employee in filteredEmployees"
                    :key="employee.id"
                    class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm"
                >
                    <!-- Employee Info -->
                    <div class="p-3 bg-gray-100 dark:bg-gray-800 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm" :style="{ backgroundColor: getInitialsColor(employee.name) }">
                            {{ employee.initials || getInitials(employee.name) }}
                        </div>
                        <div>
                            <div class="font-medium">{{ employee.name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ employee.department }}</div>
                        </div>
                    </div>

                    <!-- Status and Notes -->
                    <div class="p-3">
                        <div class="mb-3">
                            <div class="font-medium text-sm mb-1">Status:</div>
                            <div class="flex flex-col gap-2">
                                <div
                                    v-for="(event, eventIndex) in getEmployeeEventsForDay(employee, currentDate)"
                                    :key="eventIndex"
                                    class="px-3 py-1.5 rounded text-sm text-white shadow-sm flex justify-between items-center"
                                    :style="{ backgroundColor: event.type.color }"
                                >
                                    <span>{{ event.type.name }}</span>
                                    <span v-if="getEmployeeEventsForDay(employee, currentDate).length > 1" class="text-xs bg-white bg-opacity-20 px-1.5 py-0.5 rounded-full">
                                        {{ eventIndex + 1 }}/{{ getEmployeeEventsForDay(employee, currentDate).length }}
                                    </span>
                                </div>
                                <div v-if="getEmployeeEventsForDay(employee, currentDate).length === 0" class="px-3 py-1.5 rounded text-sm bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                                    Nicht eingetragen
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="font-medium text-sm mb-1">Notizen:</div>
                            <div class="flex flex-col gap-2">
                                <div v-for="(event, eventIndex) in getEmployeeEventsForDay(employee, currentDate)" :key="eventIndex">
                                    <div v-if="event.notes" class="border border-gray-200 dark:border-gray-700 rounded">
                                        <div class="text-xs font-medium text-gray-600 dark:text-gray-400 px-3 py-1 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                            {{ event.type.name }}:
                                        </div>
                                        <div class="px-3 py-1.5 text-sm">{{ event.notes }}</div>
                                    </div>
                                </div>
                                <div v-if="getEmployeeEventsForDay(employee, currentDate).length === 0 || !getEmployeeEventsForDay(employee, currentDate).some(e => e.notes)" class="py-1.5 text-gray-400">
                                    -
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Week View -->
        <div v-else-if="calendarView === 'week'" class="w-full">
            <!-- Desktop View (lg und größer) -->
            <div class="hidden lg:block overflow-x-auto rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex min-w-[800px]">
                    <div class="w-[260px] min-w-[260px] p-3 bg-gray-100 dark:bg-gray-800 font-bold text-center">Mitarbeiter</div>
                    <div
                        v-for="(day, index) in weekDays"
                        :key="index"
                        class="flex-1 min-w-[100px] p-3 text-center font-bold border-l border-gray-200 dark:border-gray-700 transition-colors"
                        :class="{
                            'bg-blue-50 dark:bg-blue-900/20': day.isToday,
                            'bg-red-50 dark:bg-red-900/20': isHoliday(day.date),
                            'bg-gray-100 dark:bg-gray-800': !day.isToday && !day.isWeekend && !isHoliday(day.date),
                            'bg-gray-200 dark:bg-gray-700': day.isWeekend && !isHoliday(day.date)
                        }"
                    >
                        <div class="font-bold">{{ day.dayName }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ formatDayMonth(day.date) }}</div>
                        <div v-if="isHoliday(day.date)" class="text-xs text-red-500 font-semibold mt-1">{{ getHolidayName(day.date) }}</div>
                    </div>
                </div>

                <div class="flex flex-col min-w-[800px]">
                    <div
                        v-for="employee in filteredEmployees"
                        :key="employee.id"
                        class="flex border-t border-gray-200 dark:border-gray-700 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 even:bg-gray-50 dark:even:bg-gray-800"
                    >
                        <div class="w-[260px] min-w-[260px] p-3 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm" :style="{ backgroundColor: getInitialsColor(employee.name) }">
                                {{ employee.initials || getInitials(employee.name) }}
                            </div>
                            <div>
                                <div class="font-medium">{{ employee.name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ employee.department }}</div>
                            </div>
                        </div>

                        <div
                            v-for="(day, index) in weekDays"
                            :key="index"
                            class="flex-1 min-w-[100px] p-3 flex flex-col items-center justify-center border-l border-gray-200 dark:border-gray-700 transition-colors"
                            :class="{
                                'bg-blue-50 dark:bg-blue-900/20': day.isToday,
                                'bg-red-50 dark:bg-red-900/20': isHoliday(day.date),
                                'bg-gray-200 dark:bg-gray-700': day.isWeekend && !isHoliday(day.date)
                            }"
                        >
                            <div class="flex flex-col gap-1 w-full">
                                <div
                                    v-for="(event, eventIndex) in getEmployeeEventsForDay(employee, day.date)"
                                    :key="eventIndex"
                                    class="px-2 py-1 rounded text-xs text-white shadow-sm text-center truncate"
                                    :style="{ backgroundColor: event.type.color }"
                                    :title="event.type.name + (event.notes ? ': ' + event.notes : '')"
                                >
                                    {{ event.type.name }}
                                </div>
                                <div v-if="getEmployeeEventsForDay(employee, day.date).length === 0" class="px-2 py-1 rounded text-xs bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-center">
                                    -
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile View (md und kleiner) - Tagesweise mit Swipe/Scroll -->
            <div class="lg:hidden flex flex-col gap-4">
                <!-- Day Navigation -->
                <div class="flex justify-between items-center mb-2">
                    <Button icon="pi pi-chevron-left" @click="mobileWeekDay = Math.max(0, mobileWeekDay - 1)" class="p-button-rounded p-button-text" :disabled="mobileWeekDay === 0" />

                    <div class="flex-1 text-center">
                        <div class="font-bold">{{ weekDays[mobileWeekDay]?.dayName }}</div>
                        <div class="text-sm">{{ formatDayMonth(weekDays[mobileWeekDay]?.date) }}</div>
                        <div v-if="isHoliday(weekDays[mobileWeekDay]?.date)" class="text-xs text-red-500 font-semibold">
                            {{ getHolidayName(weekDays[mobileWeekDay]?.date) }}
                        </div>
                    </div>

                    <Button icon="pi pi-chevron-right" @click="mobileWeekDay = Math.min(6, mobileWeekDay + 1)" class="p-button-rounded p-button-text" :disabled="mobileWeekDay === 6" />
                </div>

                <!-- Employees for the selected day -->
                <div
                    v-for="employee in filteredEmployees"
                    :key="employee.id"
                    class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm"
                    :class="{
                        'bg-blue-50/20 dark:bg-blue-900/10': weekDays[mobileWeekDay]?.isToday,
                        'bg-red-50/20 dark:bg-red-900/10': isHoliday(weekDays[mobileWeekDay]?.date)
                    }"
                >
                    <!-- Employee Info -->
                    <div class="p-3 bg-gray-100 dark:bg-gray-800 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm" :style="{ backgroundColor: getInitialsColor(employee.name) }">
                            {{ employee.initials || getInitials(employee.name) }}
                        </div>
                        <div>
                            <div class="font-medium">{{ employee.name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ employee.department }}</div>
                        </div>
                    </div>

                    <!-- Status for selected day -->
                    <div class="p-3">
                        <div class="flex flex-col gap-2">
                            <div
                                v-for="(event, eventIndex) in getEmployeeEventsForDay(employee, weekDays[mobileWeekDay]?.date)"
                                :key="eventIndex"
                                class="px-3 py-1.5 rounded text-sm text-white shadow-sm flex justify-between items-center"
                                :style="{ backgroundColor: event.type.color }"
                            >
                                <span>{{ event.type.name }}</span>
                                <span v-if="event.notes" class="text-xs bg-white bg-opacity-20 px-1.5 py-0.5 rounded-full cursor-pointer" @click="showEventDetails(event)">
                                    <i class="pi pi-comment"></i>
                                </span>
                            </div>
                            <div v-if="getEmployeeEventsForDay(employee, weekDays[mobileWeekDay]?.date).length === 0" class="px-3 py-1.5 rounded text-sm bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                                Nicht eingetragen
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Month View -->
        <div v-else-if="calendarView === 'month'" class="w-full">
            <!-- Desktop View (xl und größer) -->
            <div class="hidden xl:block overflow-x-auto rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex min-w-[1200px]">
                    <div class="w-[200px] min-w-[200px] p-2 bg-gray-100 dark:bg-gray-800 font-bold">Mitarbeiter</div>
                    <div
                        v-for="dayNum in daysInMonth"
                        :key="dayNum"
                        class="w-[50px] min-w-[30px] py-2 text-center text-sm font-bold border-l border-gray-200 dark:border-gray-700 transition-colors"
                        :class="{
                            'bg-blue-50 dark:bg-blue-900/20': isToday(dayNum),
                            'bg-red-50 dark:bg-red-900/20': isHolidayInMonth(dayNum),
                            'bg-gray-100 dark:bg-gray-800': !isToday(dayNum) && !isWeekend(dayNum) && !isHolidayInMonth(dayNum),
                            'bg-gray-200 dark:bg-gray-700': isWeekend(dayNum) && !isHolidayInMonth(dayNum)
                        }"
                    >
                        <div :class="{ 'text-red-500': isHolidayInMonth(dayNum) }">{{ dayNum }}</div>
                        <div v-if="isHolidayInMonth(dayNum)" class="" :title="getHolidayNameInMonth(dayNum)"></div>
                    </div>
                </div>

                <div class="flex flex-col min-w-[1200px]">
                    <div
                        v-for="employee in filteredEmployees"
                        :key="employee.id"
                        class="flex border-t border-gray-200 dark:border-gray-700 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 even:bg-gray-50 dark:even:bg-gray-800"
                    >
                        <div class="w-[200px] min-w-[200px] p-2 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm" :style="{ backgroundColor: getInitialsColor(employee.name) }">
                                {{ employee.initials || getInitials(employee.name) }}
                            </div>
                            <div class="overflow-hidden">
                                <div class="font-medium">{{ employee.name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ employee.department }}</div>
                            </div>
                        </div>

                        <div
                            v-for="dayNum in daysInMonth"
                            :key="dayNum"
                            class="w-[50px] min-w-[30px] h-[30px] flex items-center justify-center border-l border-gray-200 dark:border-gray-700 transition-colors relative"
                            :class="{
                                'bg-blue-50 dark:bg-blue-900/20': isToday(dayNum),
                                'bg-red-50 dark:bg-red-900/20': isHolidayInMonth(dayNum),
                                'bg-gray-200 dark:bg-gray-700': isWeekend(dayNum) && !isHolidayInMonth(dayNum)
                            }"
                        >
                            <div class="relative w-full h-full flex items-center justify-center">
                                <div
                                    v-if="getEmployeeEventsForMonthDay(employee, dayNum).length > 0"
                                    class="flex flex-wrap gap-0.5 absolute top-0 left-0 right-0 bottom-0 items-center justify-center"
                                >
                                    <div
                                        v-for="(event, eventIndex) in getEmployeeEventsForMonthDay(employee, dayNum).slice(0, 3)"
                                        :key="eventIndex"
                                        class="w-2 h-2 rounded-full"
                                        :style="{ backgroundColor: event.type.color }"
                                        :title="event.type.name + (event.notes ? ': ' + event.notes : '')"
                                    ></div>
                                    <div
                                        v-if="getEmployeeEventsForMonthDay(employee, dayNum).length > 3"
                                        class="w-2 h-2 rounded-full bg-gray-500"
                                        :title="`${getEmployeeEventsForMonthDay(employee, dayNum).length} Ereignisse`"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile View (lg und kleiner) - Karten pro Mitarbeiter -->
            <div class="xl:hidden">
                <!-- Monats-Navigation -->
                <div class="mb-4 flex justify-center">
                    <div class="inline-flex p-2 rounded-lg bg-gray-100 dark:bg-gray-800">
                        <div class="text-center">
                            <div class="font-bold text-xl">{{ currentMonthName }} {{ currentYear }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ getDaysWithEvents() }} Tage mit Einträgen
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mitarbeiter-Karten -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="employee in filteredEmployees"
                        :key="employee.id"
                        class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm"
                    >
                        <!-- Mitarbeiter-Info -->
                        <div class="p-3 bg-gray-100 dark:bg-gray-800 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm" :style="{ backgroundColor: getInitialsColor(employee.name) }">
                                {{ employee.initials || getInitials(employee.name) }}
                            </div>
                            <div>
                                <div class="font-medium">{{ employee.name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ employee.department }}</div>
                            </div>
                        </div>

                        <!-- Kalender-Miniansicht -->
                        <div class="p-3">
                            <div class="grid grid-cols-7 gap-1 mb-1 text-center text-xs">
                                <div v-for="day in weekdaysShort" :key="day" class="font-medium">{{ day }}</div>
                            </div>

                            <div class="grid grid-cols-7 gap-1">
                                <!-- Leere Tage vor Monatsbeginn -->
                                <div
                                    v-for="i in getMonthStartDay()"
                                    :key="`empty-start-${i}`"
                                    class="w-8 h-8 opacity-0"
                                ></div>

                                <!-- Tage des Monats -->
                                <div
                                    v-for="dayNum in daysInMonth"
                                    :key="`day-${dayNum}`"
                                    class="w-8 h-8 flex items-center justify-center text-xs rounded-full relative cursor-pointer"
                                    :class="{
                                        'bg-blue-500 text-white': isToday(dayNum),
                                        'bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200': !isToday(dayNum) && isHolidayInMonth(dayNum),
                                        'bg-gray-200 dark:bg-gray-700': !isToday(dayNum) && isWeekend(dayNum) && !isHolidayInMonth(dayNum),
                                        'font-bold': getEmployeeEventsForMonthDay(employee, dayNum).length > 0
                                    }"
                                    @click="openDayDetailsDialog(employee, dayNum)"
                                >
                                    {{ dayNum }}
                                    <div
                                        v-if="getEmployeeEventsForMonthDay(employee, dayNum).length > 0"
                                        class="absolute -bottom-0.5 left-1/2 transform -translate-x-1/2 w-1.5 h-1.5 rounded-full"
                                        :style="{ backgroundColor: getEventColorForMonthDay(employee, dayNum) }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Zusammenfassung -->
                        <div class="p-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <div class="text-sm">
                                <div class="flex flex-wrap gap-2">
                                    <div
                                        v-for="(type, typeIndex) in getEmployeeMonthSummary(employee)"
                                        :key="typeIndex"
                                        class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded-full"
                                    >
                                        <div class="w-2 h-2 rounded-full" :style="{ backgroundColor: type.color }"></div>
                                        <span>{{ type.name }}: {{ type.count }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="mt-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
            <div class="font-bold mb-2">Legende:</div>
            <div :class="[
                'grid gap-2',
                getDynamicGridClass(allActiveEventTypes.length)
            ]">
                <div
                    v-for="type in allActiveEventTypes"
                    :key="type.value"
                    class="flex items-center gap-2 cursor-pointer px-2 py-1 rounded-md transition-colors hover:bg-gray-100 dark:hover:bg-gray-700"
                    @click="type.value !== 'holiday' ? openStatusDialog(type) : null"
                >
                    <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: type.color }"></div>
                    <div class="text-sm">{{ type.name }}</div>
                </div>
            </div>
        </div>

        <!-- Department Dialog -->
        <Dialog
            v-model:visible="departmentDialogVisible"
            :header="`Abteilung: ${selectedDepartment}`"
            :style="{ width: '90vw', maxWidth: '1000px' }"
            :modal="true"
            :closable="true"
            :closeOnEscape="true"
        >
            <div class="flex flex-col gap-6">
                <div class="text-xl font-medium text-center">
                    <span v-if="calendarView === 'day'">{{ formatDate(currentDate) }}</span>
                    <span v-else-if="calendarView === 'week'">KW {{ currentWeekNumber }} ({{ formatDateRange(weekStart, weekEnd) }})</span>
                    <span v-else>{{ currentMonthName }} {{ currentYear }}</span>
                </div>

                <div :class="[
                    'grid gap-2 justify-items-center',
                    getDynamicGridClass(departmentStatusSummary.length)
                ]">
                    <div
                        v-for="status in departmentStatusSummary"
                        :key="status.type.value"
                        class="flex items-center gap-2 px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-800 shadow-sm w-full"
                    >
                        <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: status.type.color }"></div>
                        <div class="text-sm font-medium">{{ status.type.name }}: {{ status.count }}</div>
                    </div>
                </div>

                <div class="flex flex-col gap-4">
                    <div
                        v-for="employee in departmentEmployees"
                        :key="employee.id"
                        class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-3 bg-gray-100 dark:bg-gray-800">
                            <div class="flex items-center gap-3 flex-1">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm" :style="{ backgroundColor: getInitialsColor(employee.name) }">
                                    {{ employee.initials || getInitials(employee.name) }}
                                </div>
                                <div class="font-medium">{{ employee.name }}</div>
                            </div>
                            <div
                                v-if="getEmployeeCurrentStatus(employee)"
                                class="px-2 py-1 rounded text-sm text-white shadow-sm self-start sm:self-auto"
                                :style="{ backgroundColor: getEmployeeCurrentStatus(employee).color }"
                            >
                                {{ getEmployeeCurrentStatus(employee).name }}
                            </div>
                        </div>
                        <div class="p-3">
                            <div v-if="calendarView === 'day'" class="text-sm mb-3">
                                <div v-for="(eventNote, noteIndex) in getEmployeeNotesForSpecificDay(employee, currentDate)" :key="noteIndex" class="mb-2">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: eventNote.type.color }"></div>
                                        <span class="font-medium">{{ eventNote.type.name }}:</span>
                                    </div>
                                    <div class="pl-5">{{ eventNote.notes }}</div>
                                </div>
                                <div v-if="getEmployeeNotesForSpecificDay(employee, currentDate).length === 0">
                                    Keine Notizen
                                </div>
                            </div>
                            <div v-else class="text-sm mb-3">
                                Notizen werden in der Tagesansicht angezeigt
                            </div>
                            <div v-if="calendarView !== 'day'">
                                <div class="text-sm font-medium mb-2">Tage:</div>
                                <div class="flex flex-wrap gap-1">
                                    <div
                                        v-for="(day, index) in getEmployeePeriodDays(employee)"
                                        :key="index"
                                        class="w-9 h-7 rounded border border-gray-200 dark:border-gray-700 flex items-center justify-center text-xs"
                                        :class="{
                                            'text-white font-bold': day.status,
                                            'border-red-500 dark:border-red-400 border-2': isHoliday(day.date)
                                        }"
                                        :style="{ backgroundColor: day.status ? day.status.color : 'transparent' }"
                                        :title="day.date.format('DD.MM.YYYY') + (day.status ? ' - ' + day.status.name : ' - Nicht eingetragen') + (isHoliday(day.date) ? ' - ' + getHolidayName(day.date) : '')"
                                    >
                                        {{ day.date.format('DD.MM') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- Status Dialog - auch hier responsiver machen -->
        <Dialog
            v-model:visible="statusDialogVisible"
            :header="`Status: ${selectedStatus ? selectedStatus.name : ''}`"
            :style="{ width: '90vw', maxWidth: '1000px' }"
            :modal="true"
            :closable="true"
            :closeOnEscape="true"
        >
            <!-- Inhalt bleibt gleich, nur Anpassungen für Grid-Layouts -->
            <div class="flex flex-col gap-6">
                <div class="text-xl font-medium text-center">
                    <span v-if="calendarView === 'day'">{{ formatDate(currentDate) }}</span>
                    <span v-else-if="calendarView === 'week'">KW {{ currentWeekNumber }} ({{ formatDateRange(weekStart, weekEnd) }})</span>
                    <span v-else>{{ currentMonthName }} {{ currentYear }}</span>
                </div>

                <div :class="[
                    'grid gap-2 justify-items-center',
                    getDynamicGridClass(statusDepartmentSummary.length)
                ]">
                    <div
                        v-for="dept in statusDepartmentSummary"
                        :key="dept.name"
                        class="px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-800 shadow-sm w-full"
                    >
                        <div class="text-sm font-medium">{{ dept.name }}: {{ dept.count }}</div>
                    </div>
                </div>

                <div class="flex flex-col gap-4">
                    <div
                        v-for="employee in statusEmployees"
                        :key="employee.id"
                        class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="flex items-center gap-4 p-3 bg-gray-100 dark:bg-gray-800">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm" :style="{ backgroundColor: getInitialsColor(employee.name) }">
                                {{ employee.initials || getInitials(employee.name) }}
                            </div>
                            <div class="font-medium flex-1">{{ employee.name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ employee.department }}</div>
                        </div>
                        <div class="p-3">
                            <div v-if="calendarView === 'day'" class="text-sm mb-3">
                                <div v-for="(eventNote, noteIndex) in getEmployeeNotesForSpecificDay(employee, currentDate)" :key="noteIndex" class="mb-2">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: eventNote.type.color }"></div>
                                        <span class="font-medium">{{ eventNote.type.name }}:</span>
                                    </div>
                                    <div class="pl-5">{{ eventNote.notes }}</div>
                                </div>
                                <div v-if="getEmployeeNotesForSpecificDay(employee, currentDate).length === 0">
                                    Keine Notizen
                                </div>
                            </div>
                            <div v-else class="text-sm mb-3">
                                Notizen werden in der Tagesansicht angezeigt
                            </div>
                            <div v-if="calendarView !== 'day'">
                                <div class="text-sm font-medium mb-3">Tage:</div>
                                <div class="flex flex-wrap gap-1">
                                    <div
                                        v-for="(day, index) in getEmployeePeriodDays(employee)"
                                        :key="index"
                                        class="w-9 h-7 rounded border border-gray-200 dark:border-gray-700 flex items-center justify-center text-xs"
                                        :class="{
                                                    'border-blue-500 dark:border-blue-400 border-2': day.status && day.status.value === selectedStatus.value,
                                                    'border-red-500 dark:border-red-400 border-2': isHoliday(day.date)
                                                }"
                                        :style="{ backgroundColor: day.status && day.status.value === selectedStatus.value ? day.status.color : 'transparent' }"
                                        :title="day.date.format('DD.MM.YYYY') + (isHoliday(day.date) ? ' - ' + getHolidayName(day.date) : '')"
                                    >
                                        {{ day.date.format('DD.MM') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- Mobile Event Details Dialog -->
        <Dialog
            v-model:visible="eventDetailsDialogVisible"
            :header="selectedEvent ? selectedEvent.type.name : 'Ereignisdetails'"
            modal
            :style="{ width: '95%', maxWidth: '500px' }"
            :closable="true"
            :closeOnEscape="true"
        >
            <div v-if="selectedEvent">
                <div class="p-3 mb-3 rounded-lg" :style="{ backgroundColor: selectedEvent.type.color + '20' }">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: selectedEvent.type.color }"></div>
                        <div class="font-medium">{{ selectedEvent.type.name }}</div>
                    </div>
                    <div v-if="selectedEvent.notes" class="mt-2 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        {{ selectedEvent.notes }}
                    </div>
                </div>

                <div class="flex justify-center">
                    <Button label="Schließen" @click="eventDetailsDialogVisible = false" class="p-button-sm" />
                </div>
            </div>
        </Dialog>

        <!-- Mobile Day Details Dialog -->
        <Dialog
            v-model:visible="dayDetailsDialogVisible"
            :header="`${selectedEmployee?.name || ''} - ${formatDayMonthYear(getDayInMonth(selectedDayNum))}`"
            modal
            :style="{ width: '95%', maxWidth: '500px' }"
            :closable="true"
            :closeOnEscape="true"
        >
            <div v-if="selectedEmployee && selectedDayNum">
                <div class="flex flex-col gap-3">
                    <div
                        v-for="(event, eventIndex) in getEmployeeEventsForMonthDay(selectedEmployee, selectedDayNum)"
                        :key="eventIndex"
                        class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                    >
                        <div class="p-2 font-medium" :style="{ backgroundColor: event.type.color, color: 'white' }">
                            {{ event.type.name }}
                        </div>
                        <div class="p-3">
                            <div v-if="event.notes" class="text-sm">{{ event.notes }}</div>
                            <div v-else class="text-sm text-gray-500 dark:text-gray-400">Keine Notizen</div>
                        </div>
                    </div>

                    <div v-if="getEmployeeEventsForMonthDay(selectedEmployee, selectedDayNum).length === 0" class="text-center p-4 text-gray-500 dark:text-gray-400">
                        Keine Einträge für diesen Tag
                    </div>
                </div>

                <div class="flex justify-center mt-4">
                    <Button label="Schließen" @click="dayDetailsDialogVisible = false" class="p-button-sm" />
                </div>
            </div>
        </Dialog>

        <!-- Loading Overlay für Feiertage -->
        <div v-if="isLoadingHolidays" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-red-500 mx-auto mb-4"></div>
                <p class="text-lg font-medium">Feiertage werden geladen...</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import weekOfYear from 'dayjs/plugin/weekOfYear';
import isoWeek from 'dayjs/plugin/isoWeek';
import isSameOrAfterPlugin from 'dayjs/plugin/isSameOrAfter';
import isSameOrBeforePlugin from 'dayjs/plugin/isSameOrBefore';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Dialog from 'primevue/dialog';
import VacationService from '@/Services/VacationService';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import HolidayService from '@/Services/holiday-service';

// Initialize dayjs plugins
dayjs.extend(weekOfYear);
dayjs.extend(isoWeek);
dayjs.extend(isSameOrBeforePlugin);
dayjs.extend(isSameOrAfterPlugin);
dayjs.locale('de');

// Dark mode state
const isDarkMode = ref(false);

// Filter state
const searchQuery = ref('');
const selectedDepartments = ref([]);

// Calendar view state
const calendarView = ref('week'); // 'day', 'week', 'month'
const currentDate = ref(dayjs());

// Dialog state
const departmentDialogVisible = ref(false);
const statusDialogVisible = ref(false);
const selectedDepartment = ref('');
const selectedStatus = ref(null);

// Sample employees data (would be fetched from API in real implementation)
const employees = ref([]);
const availableDepartments = ref([]);
const eventTypes = ref([]);

// Definiere Feiertage und Geburtstage als spezielle Ereignistypen
const holidayEventType = {
    name: 'Feiertag',
    value: 'holiday',
    color: '#FF0000' // Rot für Feiertage
};

// Ändere die Farbe für Geburtstage
const birthdayEventType = {
    name: 'Geburtstag',
    value: 'birthday',
    color: '#FFD700' // Gold für Geburtstage
};

const sicknessEventType = {
    name: 'Krankheit',
    value: 'sick',
    color: '#F44336' // Red color for sickness events
};

const toast = useToast();

// Initialize vacationResponse and vacationError refs
const vacationResponse = ref(null);
const vacationError = ref(null);

// Feiertage für das aktuelle Jahr
const holidays = ref([]);
const isLoadingHolidays = ref(false);

// Hilfsfunktion, um dynamische Grid-Spalten basierend auf der Anzahl der Elemente zu erhalten
const getDynamicGridClass = (count) => {
    if (count <= 0) return 'grid-cols-1';
    if (count === 1) return 'grid-cols-1';
    if (count === 2) return 'grid-cols-2';
    if (count === 3) return 'grid-cols-3';
    if (count === 4) return 'grid-cols-4';
    if (count === 5) return 'grid-cols-5';
    if (count === 6) return 'grid-cols-6';
    return 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5'; // Mehr als 6 Elemente
};

// Ersetze die fetchHolidays-Funktion mit:
const fetchHolidays = async (year) => {
    isLoadingHolidays.value = true;
    try {
        holidays.value = await HolidayService.getHolidays(year);
    } catch (error) {
        console.error('Fehler beim Laden der Feiertage:', error);
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Die Feiertage konnten nicht geladen werden.',
            life: 3000
        });
    } finally {
        isLoadingHolidays.value = false;
    }
};

// Ersetze die isHoliday-Funktion mit:
const isHoliday = (date) => {
    return HolidayService.isHoliday(date, holidays.value);
};

// Ersetze die getHolidayName-Funktion mit:
const getHolidayName = (date) => {
    return HolidayService.getHolidayName(date, holidays.value);
};

// Prüft, ob ein Tag im aktuellen Monat ein Feiertag ist
const isHolidayInMonth = (dayNum) => {
    const date = getDayInMonth(dayNum);
    return isHoliday(date);
};

// Gibt den Namen eines Feiertags im aktuellen Monat zurück
const getHolidayNameInMonth = (dayNum) => {
    const date = getDayInMonth(dayNum);
    return getHolidayName(date);
};

// Prüft, ob es Feiertage im aktuellen Zeitraum gibt
const hasHolidaysInCurrentPeriod = () => {
    if (calendarView.value === 'day') {
        return isHoliday(currentDate.value);
    } else if (calendarView.value === 'week') {
        return weekDays.value.some(day => isHoliday(day.date));
    } else {
        return daysInMonth.value.some(dayNum => isHolidayInMonth(dayNum));
    }
};

// Daten vom Server laden
const fetchCalendarData = async () => {
    let employeesData = [];

    try {
        // Reguläre Kalenderdaten laden
        const response = await VacationService.getCompanyCalendarData();

        // Mitarbeiter und Abteilungen aus der regulären Antwort extrahieren
        employeesData = response.data.employees || [];
        availableDepartments.value = Array.isArray(response.data.departments) ? response.data.departments : [];
        eventTypes.value = Array.isArray(response.data.eventTypes) ? response.data.eventTypes : [];

        // Sicherstellen, dass eventTypes ein Array ist und Urlaub und Geburtstag enthält
        if (!eventTypes.value.some(type => type.value === 'vacation')) {
            eventTypes.value.push({ name: 'Urlaub', value: 'vacation', color: '#9C27B0' });
        }

        // Ensure sickness event type is included
        if (!eventTypes.value.some(type => type.value === 'sick')) {
            eventTypes.value.push(sicknessEventType);
        }

        // Geburtstage mit der neuen Farbe aktualisieren
        const birthdayIndex = eventTypes.value.findIndex(type => type.value === 'birthday');
        if (birthdayIndex >= 0) {
            eventTypes.value[birthdayIndex] = birthdayEventType;
        } else {
            eventTypes.value.push(birthdayEventType);
        }
    } catch (error) {
        console.error('Fehler beim Laden der Kalenderdaten:', error);
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Die Kalenderdaten konnten nicht geladen werden.',
            life: 3000
        });

        // Keine Fallback-Daten mehr verwenden, stattdessen leere Arrays
        employeesData = [];
        availableDepartments.value = [];
        eventTypes.value = [
            { name: 'Homeoffice', value: 'homeoffice', color: '#4CAF50' },
            { name: 'Büro', value: 'office', color: '#2196F3' },
            { name: 'Außendienst', value: 'field', color: '#FF9800' },
            { name: 'Krank', value: 'sick', color: '#F44336' },
            { name: 'Urlaub', value: 'vacation', color: '#9C27B0' },
            birthdayEventType,
            { name: 'Sonstiges', value: 'other', color: '#607D8B' }
        ];
    }

    try {
        // Urlaubsanträge laden
        const response = await axios.get('/api/vacation/all-requests');
        vacationResponse.value = response;
        vacationError.value = null;

        // Urlaubsanträge in das richtige Format umwandeln
        const vacationEvents = vacationResponse.value.data
            .filter(vacation => vacation.status === 'approved') // Nur genehmigte Urlaubsanträge
            .map(vacation => {
                let vacationName = 'Urlaub';
                if (vacation.day_type === 'morning') {
                    vacationName = 'Urlaub Vormittag';
                } else if (vacation.day_type === 'afternoon') {
                    vacationName = 'Urlaub Nachmittag';
                }
                return {
                    user_id: vacation.user_id,
                    date: vacation.start_date,
                    start_date: vacation.start_date,
                    end_date: vacation.end_date,
                    type: {
                        name: vacationName,
                        value: 'vacation',
                        color: '#9C27B0'
                    },
                    notes: vacation.notes || 'Genehmigter Urlaub',
                    employee_name: vacation.employee_name,
                    department: vacation.department
                };
            });

        // Urlaubsanträge den entsprechenden Mitarbeitern zuordnen
        employeesData.forEach(employee => {
            // Finde alle Urlaubsanträge für diesen Mitarbeiter
            const employeeVacations = vacationEvents.filter(vacation =>
                vacation.user_id === employee.id
            );

            // Füge die Urlaubsanträge zu den Events des Mitarbeiters hinzu
            if (employeeVacations.length > 0) {
                employee.events = [...(employee.events || []), ...employeeVacations];
            }
        });
    } catch (error) {
        console.error('Fehler beim Laden der Urlaubsanträge:', error);
        vacationResponse.value = null;
        vacationError.value = error;
        // Wir setzen den Prozess fort, auch wenn die Urlaubsanträge nicht geladen werden konnten
    }

    // Aktualisiere die Mitarbeiterliste
    employees.value = employeesData;
};

// Computed properties for calendar display
const currentYear = computed(() => currentDate.value.year());
const currentMonthName = computed(() => currentDate.value.format('MMMM'));
const currentWeekNumber = computed(() => currentDate.value.isoWeek());

// Computed properties for month view
const daysInMonth = computed(() => {
    const firstDay = currentDate.value.startOf('month');
    const lastDay = currentDate.value.endOf('month');
    const daysCount = lastDay.date();

    return Array.from({ length: daysCount }, (_, i) => i + 1);
});

// Computed properties for week view
const weekStart = computed(() => {
    // Get the first day of the week (Monday)
    const day = currentDate.value.day();
    const diff = day === 0 ? 6 : day - 1; // Adjust for Monday start
    return currentDate.value.subtract(diff, 'day');
});

const weekEnd = computed(() => {
    return weekStart.value.add(6, 'day');
});

const weekDays = computed(() => {
    const days = [];
    const today = dayjs();

    for (let i = 0; i < 7; i++) {
        const date = weekStart.value.add(i, 'day');
        days.push({
            date: date,
            dayName: date.format('ddd'),
            dayNumber: date.date(),
            isToday: date.format('YYYY-MM-DD') === today.format('YYYY-MM-DD'),
            isWeekend: date.day() === 0 || date.day() === 6
        });
    }

    return days;
});

// Gefilterte Mitarbeiter basierend auf Suche und Abteilungsfilter
const filteredEmployees = computed(() => {
    let result = employees.value;

    // Filtern nach Suchbegriff
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(employee =>
            employee.name.toLowerCase().includes(query) ||
            employee.department.toLowerCase().includes(query)
        );
    }

    // Filtern nach ausgewählten Abteilungen
    if (selectedDepartments.value.length > 0) {
        const departments = selectedDepartments.value.map(d => d.name);
        result = result.filter(employee => departments.includes(employee.department));
    }

    // In der Tagesansicht nur Mitarbeiter mit Einträgen anzeigen
    if (calendarView.value === 'day') {
        result = result.filter(employee => getEmployeeStatusForDay(employee, currentDate.value) !== null);
    }

    return result;
});

// Zusammenfassung der Abteilungen mit Mitarbeiterzahlen für den aktuellen Zeitraum
const departmentSummary = computed(() => {
    const departments = {};

    // Gruppiere Mitarbeiter nach Abteilungen
    employees.value.forEach(employee => {
        if (!departments[employee.department]) {
            departments[employee.department] = {
                name: employee.department,
                count: 0,
                employees: []
            };
        }

        // Prüfe, ob der Mitarbeiter im aktuellen Zeitraum einen Eintrag hat
        if (hasEmployeeEventInCurrentPeriod(employee)) {
            departments[employee.department].count++;
            departments[employee.department].employees.push(employee);
        }
    });

    return Object.values(departments).filter(dept => dept.count > 0);
});

const renderCell = (employee, date) => {
    const status = getEmployeeStatusForDay(employee, date);
    const isBirthday = hasBirthdayOnDay(employee, date);

    if (isBirthday) {
        return {
            class: 'birthday-cell',
            style: {
                backgroundColor: '#FFF0F0',
                border: '2px solid #FFD700', // Neue Farbe für Geburtstage
                position: 'relative'
            },
            content: `<div class="birthday-icon" style="position: absolute; top: 2px; right: 2px; font-size: 12px;">🎂</div>`
        };
    }

    if (status) {
        return {
            class: `status-${status.value}`,
            style: { backgroundColor: status.color },
            content: ''
        };
    }

    return {
        class: '',
        style: {},
        content: ''
    };
};

// Zusammenfassung der Status mit Mitarbeiterzahlen für den aktuellen Zeitraum
const statusSummary = computed(() => {
    const statuses = {};

    // Zuerst alle Mitarbeiter durchgehen und ihre Status im aktuellen Zeitraum sammeln
    employees.value.forEach(employee => {
        if (calendarView.value === 'day') {
            // Für Tagesansicht
            const status = getEmployeeStatusForDay(employee, currentDate.value);
            if (status) {
                if (!statuses[status.value]) {
                    statuses[status.value] = {
                        type: status,
                        count: 0,
                        employees: []
                    };
                }
                statuses[status.value].count++;
                statuses[status.value].employees.push(employee);
            }
        } else if (calendarView.value === 'week') {
            // Für Wochenansicht - alle Tage der Woche prüfen
            for (const day of weekDays.value) {
                const status = getEmployeeStatusForDay(employee, day.date);
                if (status) {
                    if (!statuses[status.value]) {
                        statuses[status.value] = {
                            type: status,
                            count: 0,
                            employees: []
                        };
                    }
                    // Nur einmal pro Mitarbeiter zählen, auch wenn er mehrere Tage denselben Status hat
                    if (!statuses[status.value].employees.some(emp => emp.id === employee.id)) {
                        statuses[status.value].count++;
                        statuses[status.value].employees.push(employee);
                    }
                }
            }
        } else {
            // Für Monatsansicht - alle Tage des Monats prüfen
            for (const dayNum of daysInMonth.value) {
                const date = getDayInMonth(dayNum);
                const status = getEmployeeStatusForDay(employee, date);
                if (status) {
                    if (!statuses[status.value]) {
                        statuses[status.value] = {
                            type: status,
                            count: 0,
                            employees: []
                        };
                    }
                    // Nur einmal pro Mitarbeiter zählen, auch wenn er mehrere Tage denselben Status hat
                    if (!statuses[status.value].employees.some(emp => emp.id === employee.id)) {
                        statuses[status.value].count++;
                        statuses[status.value].employees.push(employee);
                    }
                }
            }
        }
    });

    return Object.values(statuses);
});

// Diese Eigenschaft gibt nur die Ereignistypen zurück, die in der aktuellen Ansicht tatsächlich verwendet werden
const activeEventTypes = computed(() => {
    // Verwende die bereits berechneten Statustypen aus statusSummary
    return statusSummary.value.map(status => status.type);
});

// Alle aktiven Ereignistypen inklusive Feiertage, wenn vorhanden
const allActiveEventTypes = computed(() => {
    const types = [...activeEventTypes.value];

    // Füge Feiertage hinzu, wenn es welche im aktuellen Zeitraum gibt
    if (hasHolidaysInCurrentPeriod()) {
        types.push(holidayEventType);
    }

    return types;
});

// Mitarbeiter für die ausgewählte Abteilung
const departmentEmployees = computed(() => {
    if (!selectedDepartment.value) return [];

    return employees.value.filter(employee =>
        employee.department === selectedDepartment.value &&
        hasEmployeeEventInCurrentPeriod(employee)
    );
});

// Status-Zusammenfassung für die ausgewählte Abteilung
const departmentStatusSummary = computed(() => {
    if (!selectedDepartment.value) return [];

    const statuses = {};

    // Initialisiere alle Status
    if (Array.isArray(eventTypes.value)) {
        eventTypes.value.forEach(type => {
            statuses[type.value] = {
                type: type,
                count: 0
            };
        });
    }

    // Zähle Mitarbeiter pro Status in der ausgewählten Abteilung
    departmentEmployees.value.forEach(employee => {
        const status = getEmployeeCurrentStatus(employee);
        if (status) {
            statuses[status.value].count++;
        }
    });

    return Object.values(statuses).filter(status => status.count > 0);
});

// Mitarbeiter für den ausgewählten Status
const statusEmployees = computed(() => {
    if (!selectedStatus.value) return [];

    return employees.value.filter(employee => {
        // Prüfen, ob der Mitarbeiter den ausgewählten Status im aktuellen Zeitraum hat
        if (calendarView.value === 'day') {
            const status = getEmployeeStatusForDay(employee, currentDate.value);
            return status && status.value === selectedStatus.value.value;
        } else if (calendarView.value === 'week') {
            // Für Wochenansicht - alle Tage der Woche prüfen
            for (const day of weekDays.value) {
                const status = getEmployeeStatusForDay(employee, day.date);
                if (status && status.value === selectedStatus.value.value) {
                    return true;
                }
            }
            return false;
        } else {
            // Für Monatsansicht - alle Tage des Monats prüfen
            for (const dayNum of daysInMonth.value) {
                const date = getDayInMonth(dayNum);
                const status = getEmployeeStatusForDay(employee, date);
                if (status && status.value === selectedStatus.value.value) {
                    return true;
                }
            }
            return false;
        }
    });
});

// Abteilungs-Zusammenfassung für den ausgewählten Status
const statusDepartmentSummary = computed(() => {
    if (!selectedStatus.value) return [];

    const departments = {};

    // Initialisiere alle Abteilungen
    if (Array.isArray(availableDepartments.value)) {
        availableDepartments.value.forEach(dept => {
            departments[dept.name] = {
                name: dept.name,
                count: 0
            };
        });
    }

    // Zähle Mitarbeiter pro Abteilung mit dem ausgewählten Status
    statusEmployees.value.forEach(employee => {
        if (departments[employee.department]) {
            departments[employee.department].count++;
        } else {
            // Falls die Abteilung nicht in der Liste ist, füge sie hinzu
            departments[employee.department] = {
                name: employee.department,
                count: 1
            };
        }
    });

    return Object.values(departments).filter(dept => dept.count > 0);
});

// Methods for calendar navigation
const previousPeriod = () => {
    if (calendarView.value === 'day') {
        currentDate.value = currentDate.value.subtract(1, 'day');
    } else if (calendarView.value === 'week') {
        currentDate.value = currentDate.value.subtract(1, 'week');
    } else {
        currentDate.value = currentDate.value.subtract(1, 'month');
    }
};

const nextPeriod = () => {
    if (calendarView.value === 'day') {
        currentDate.value = currentDate.value.add(1, 'day');
    } else if (calendarView.value === 'week') {
        currentDate.value = currentDate.value.add(1, 'week');
    } else {
        currentDate.value = currentDate.value.add(1, 'month');
    }
};

// Helper methods for month view
const isToday = (dayNum) => {
    const today = dayjs();
    return today.date() === dayNum &&
        today.month() === currentDate.value.month() &&
        today.year() === currentDate.value.year();
};

const isWeekend = (dayNum) => {
    const date = currentDate.value.startOf('month').date(dayNum);
    const day = date.day();
    return day === 0 || day === 6; // 0 is Sunday, 6 is Saturday
};

const getDayInMonth = (dayNum) => {
    return currentDate.value.startOf('month').date(dayNum);
};

// Helper methods
const formatDate = (date) => {
    return date.format('dddd, DD. MMMM YYYY');
};

const formatDayMonth = (date) => {
    return date.format('DD.MM.');
};

const formatDateRange = (start, end) => {
    return `${start.format('DD.MM.')} - ${end.format('DD.MM.YYYY')}`;
};

const getInitials = (name) => {
    return name
        .split(' ')
        .map(part => part.charAt(0))
        .join('')
        .toUpperCase();
};

const getInitialsColor = (name) => {
    // Generate a deterministic color based on the name
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }

    const hue = hash % 360;
    return `hsl(${hue}, 70%, 60%)`;
};

const getEmployeeEventsForDay = (employee, date) => {
    if (!employee || !employee.events || !Array.isArray(employee.events)) return [];

    const isWeekend = date.day() === 0 || date.day() === 6; // 0 = Sonntag, 6 = Samstag
    const dateStr = date.format('YYYY-MM-DD');
    const events = [];

    // Exakte Datumstreffer finden
    const exactEvents = employee.events.filter(e => e.date === dateStr);

    // Ereignisse mit Zeitspannen finden
    const rangeEvents = employee.events.filter(e => {
        if (e.start_date && e.end_date) {
            const startDate = dayjs(e.start_date);
            const endDate = dayjs(e.end_date);
            return date.isSameOrAfter(startDate, 'day') && date.isSameOrBefore(endDate, 'day');
        }
        return false;
    });

    // Alle gefundenen Ereignisse kombinieren und Duplikate entfernen
    const allEvents = [...exactEvents, ...rangeEvents];
    const uniqueEvents = [];

    // Duplikate entfernen (basierend auf event.id oder einer Kombination aus Typ und Datum)
    allEvents.forEach(event => {
        const isDuplicate = uniqueEvents.some(e =>
            (e.id && e.id === event.id) ||
            (e.type.value === event.type.value && e.date === event.date)
        );

        if (!isDuplicate) {
            // Für Wochenenden: Nur bestimmte Ereignistypen anzeigen
            if (isWeekend) {
                if (event.type.value === 'sick' ||
                    event.type.value === 'birthday' ||
                    event.type.value === 'other' ||
                    event.type.value === 'sonstiges') {
                    uniqueEvents.push(event);
                }
            } else {
                uniqueEvents.push(event);
            }
        }
    });

    return uniqueEvents;
};

// Behalte die alte Funktion für Kompatibilität, aber nutze die neue Implementierung
const getEmployeeStatusForDay = (employee, date) => {
    const events = getEmployeeEventsForDay(employee, date);
    return events.length > 0 ? events[0].type : null;
};

// Füge eine Methode hinzu, um zu prüfen, ob ein Mitarbeiter an einem bestimmten Tag Geburtstag hat
const hasBirthdayOnDay = (employee, date) => {
    if (!employee.birth_date) return false;

    const birthDate = dayjs(employee.birth_date);
    return birthDate.month() === date.month() && birthDate.date() === date.date();
};

const getEmployeeEventsForMonthDay = (employee, dayNum) => {
    const date = getDayInMonth(dayNum);
    return getEmployeeEventsForDay(employee, date);
};

const getEmployeeStatusForMonthDay = (employee, dayNum) => {
    const date = getDayInMonth(dayNum);
    return getEmployeeStatusForDay(employee, date);
};

const getEmployeeNotesForDay = (employee, date) => {
    const dateStr = date.format('YYYY-MM-DD');
    const event = employee.events.find(e => e.date === dateStr);
    return event ? event.notes : '';
};

const getEmployeeNotesForSpecificDay = (employee, date) => {
    const events = getEmployeeEventsForDay(employee, date);
    return events.map(event => ({
        type: event.type,
        notes: event.notes || ''
    })).filter(item => item.notes);
};

// Prüft, ob ein Mitarbeiter im aktuellen Zeitraum (Tag/Woche/Monat) einen Eintrag hat
const hasEmployeeEventInCurrentPeriod = (employee) => {
    if (calendarView.value === 'day') {
        return getEmployeeEventsForDay(employee, currentDate.value).length > 0;
    } else if (calendarView.value === 'week') {
        for (let i = 0; i < 7; i++) {
            const date = weekStart.value.add(i, 'day');
            if (getEmployeeEventsForDay(employee, date).length > 0) {
                return true;
            }
        }
        return false;
    } else {
        // Monatsansicht
        for (let dayNum of daysInMonth.value) {
            if (getEmployeeEventsForMonthDay(employee, dayNum).length > 0) {
                return true;
            }
        }
        return false;
    }
};

// Gibt den aktuellen Status eines Mitarbeiters zurück (abhängig von der Ansicht)
const getEmployeeCurrentStatus = (employee) => {
    if (calendarView.value === 'day') {
        const events = getEmployeeEventsForDay(employee, currentDate.value);
        return events.length > 0 ? events[0].type : null;
    } else if (calendarView.value === 'week') {
        // Für die Wochenansicht prüfen wir alle Tage
        for (let i = 0; i < 7; i++) {
            const date = weekStart.value.add(i, 'day');
            const events = getEmployeeEventsForDay(employee, date);
            if (events.length > 0) return events[0].type;
        }
        return null;
    } else {
        // Für die Monatsansicht prüfen wir alle Tage
        for (let dayNum of daysInMonth.value) {
            const events = getEmployeeEventsForMonthDay(employee, dayNum);
            if (events.length > 0) return events[0].type;
        }
        return null;
    }
};

// Gibt die aktuellen Notizen eines Mitarbeiters zurück (abhängig von der Ansicht)
const getEmployeeCurrentNotes = (employee) => {
    if (calendarView.value === 'day') {
        return getEmployeeNotesForDay(employee, currentDate.value);
    } else if (calendarView.value === 'week') {
        // Für die Wochenansicht nehmen wir die ersten verfügbaren Notizen
        for (let i = 0; i < 7; i++) {
            const date = weekStart.value.add(i, 'day');
            const notes = getEmployeeNotesForDay(employee, date);
            if (notes) return notes;
        }
        return '';
    } else {
        // Für die Monatsansicht nehmen wir die ersten verfügbaren Notizen
        for (let dayNum of daysInMonth.value) {
            const date = getDayInMonth(dayNum);
            const notes = getEmployeeNotesForDay(employee, date);
            if (notes) return notes;
        }
        return '';
    }
};

// Gibt die Tage für einen Mitarbeiter im aktuellen Zeitraum zurück
const getEmployeePeriodDays = (employee) => {
    if (calendarView.value === 'day') {
        return [{
            date: currentDate.value,
            status: getEmployeeStatusForDay(employee, currentDate.value)
        }];
    } else if (calendarView.value === 'week') {
        return weekDays.value.map(day => ({
            date: day.date,
            status: getEmployeeStatusForDay(employee, day.date)
        }));
    } else {
        // Monatsansicht
        return daysInMonth.value.map(dayNum => {
            const date = getDayInMonth(dayNum);
            return {
                date: date,
                status: getEmployeeStatusForDay(employee, date)
            };
        });
    }
};

// Dialog-Methoden
const openDepartmentDialog = (department) => {
    selectedDepartment.value = department;
    departmentDialogVisible.value = true;
};

const openStatusDialog = (status) => {
    selectedStatus.value = status;
    statusDialogVisible.value = true;
};

// Dark mode toggle
const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;

    // Save preference to localStorage
    localStorage.setItem('calendar-theme', isDarkMode.value ? 'dark' : 'light');

    // Apply dark mode to document if needed
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

// Beobachte Änderungen am Jahr und lade die Feiertage neu
watch(
    () => currentDate.value.year(),
    (newYear, oldYear) => {
        if (newYear !== oldYear) {
            fetchHolidays(newYear);
        }
    }
);

// Initialize component
onMounted(() => {
    // Check for saved theme preference
    const savedTheme = localStorage.getItem('calendar-theme');
    if (savedTheme === 'dark') {
        isDarkMode.value = true;
        document.documentElement.classList.add('dark');
    } else if (savedTheme === 'light') {
        isDarkMode.value = false;
        document.documentElement.classList.remove('dark');
    } else {
        // Check system preference if no saved preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            isDarkMode.value = true;
            document.documentElement.classList.add('dark');
        }
    }

    // Listen for system theme changes
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            // Only apply system preference if user hasn't set a preference
            const savedTheme = localStorage.getItem('calendar-theme');
            if (!savedTheme) {
                isDarkMode.value = e.matches;
                if (e.matches) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
        });
    }

    // Feiertage für das aktuelle Jahr laden
    fetchHolidays(currentDate.value.year());

    // Fetch data from the server
    fetchCalendarData();
});

const setCalendarView = (view) => {
    calendarView.value = view;
};

// Neue Variablen für mobile Ansicht
const mobileWeekDay = ref(0); // Für die mobile Wochenansicht
const selectedEvent = ref(null); // Für den Event-Details-Dialog
const eventDetailsDialogVisible = ref(false);
const dayDetailsDialogVisible = ref(false);
const selectedEmployee = ref(null);
const selectedDayNum = ref(null);

// Für die mobile Monatsansicht
const weekdaysShort = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

// Dialog für Event-Details in der mobilen Ansicht öffnen
const showEventDetails = (event) => {
    selectedEvent.value = event;
    eventDetailsDialogVisible.value = true;
};

// Dialog für Tag-Details in der mobilen Monatsansicht öffnen
const openDayDetailsDialog = (employee, dayNum) => {
    selectedEmployee.value = employee;
    selectedDayNum.value = dayNum;
    dayDetailsDialogVisible.value = true;
};

// Formatiert ein Datum zu Tag, Monat und Jahr
const formatDayMonthYear = (date) => {
    return date.format('DD.MM.YYYY');
};

// Gibt den Wochentag des ersten Tags im Monat zurück (0 = Montag, 6 = Sonntag)
const getMonthStartDay = () => {
    const firstDayOfMonth = currentDate.value.startOf('month');
    const dayOfWeek = firstDayOfMonth.day(); // 0 = Sonntag, 1 = Montag, ...
    return dayOfWeek === 0 ? 6 : dayOfWeek - 1; // Anpassung für Montag als ersten Tag der Woche
};

// Gibt die Farbe des ersten Events für einen Tag in der Monatsansicht zurück
const getEventColorForMonthDay = (employee, dayNum) => {
    const events = getEmployeeEventsForMonthDay(employee, dayNum);
    return events.length > 0 ? events[0].type.color : '#ccc';
};

// Gibt eine Zusammenfassung der Events eines Mitarbeiters für den aktuellen Monat zurück
const getEmployeeMonthSummary = (employee) => {
    const summary = {};

    for (const dayNum of daysInMonth.value) {
        const events = getEmployeeEventsForMonthDay(employee, dayNum);
        events.forEach(event => {
            const type = event.type;
            if (!summary[type.value]) {
                summary[type.value] = {
                    name: type.name,
                    color: type.color,
                    count: 0
                };
            }
            summary[type.value].count++;
        });
    }

    return Object.values(summary);
};

// Gibt die Anzahl der Tage mit Events im aktuellen Monat zurück
const getDaysWithEvents = () => {
    let count = 0;
    for (const dayNum of daysInMonth.value) {
        let hasEvents = false;
        for (const employee of filteredEmployees.value) {
            if (getEmployeeEventsForMonthDay(employee, dayNum).length > 0) {
                hasEvents = true;
                break;
            }
        }
        if (hasEvents) count++;
    }
    return count;
};
</script>

<style scoped>
/* Animation für Geburtstags-Icon */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.birthday-icon {
    animation: pulse 1.5s infinite;
}
</style>
