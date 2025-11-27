<template>
    <div class="w-full overflow-x-auto p-6 rounded-lg shadow-md transition-all duration-300 bg-white text-gray-800 dark:bg-gray-900 dark:text-gray-100">
        <!-- Calendar Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div class="flex items-center gap-4">
                <button @click="previousPeriod" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <h2 class="text-2xl font-semibold capitalize m-0">
                    <span v-if="calendarView === 'day'">{{ formatDate(currentDate) }}</span>
                    <span v-else-if="calendarView === 'week'">KW {{ currentWeekNumber }} ({{ formatDateRange(weekStart, weekEnd) }})</span>
                    <span v-else>{{ currentMonthName }} {{ currentYear }}</span>
                </h2>
                <button @click="nextPeriod" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
            <div class="flex gap-1">
                <button
                    v-for="view in ['day', 'week', 'month']"
                    :key="view"
                    :class="[
                        'px-4 py-2 rounded-full text-sm font-medium transition-colors',
                        calendarView === view
                            ? 'bg-emerald-500 text-white'
                            : 'border border-emerald-500 text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20'
                    ]"
                    @click="setCalendarView(view)"
                >
                    {{ view === 'day' ? 'Tag' : view === 'week' ? 'Woche' : 'Monat' }}
                </button>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="mb-4">
            <div class="flex flex-col md:flex-row justify-between items-center w-full gap-4">
                <div class="w-full md:flex-1">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Mitarbeiter suchen..."
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    />
                </div>
                <div class="w-full md:flex-1">
                    <select
                        v-model="selectedDepartmentFilter"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="">Alle Abteilungen</option>
                        <option v-for="dept in availableDepartments" :key="dept.name" :value="dept.name">
                            {{ dept.name }}
                        </option>
                    </select>
                </div>
                <!-- Added filter for event types -->
                <div class="w-full md:flex-1">
                    <select
                        v-model="selectedEventTypeFilter"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="">Alle Status</option>
                        <option v-for="eventType in eventTypes" :key="eventType.value" :value="eventType.value">
                            {{ eventType.name }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="flex flex-col gap-4 mb-6">
            <!-- Department Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <!-- Verbesserte Kachel-Markierung mit Häkchen-Icon und klareren aktiven Styles -->
                <div
                    v-for="department in departmentSummary"
                    :key="department.name"
                    @click="toggleDepartmentFilter(department.name)"
                    class="relative p-4 rounded-lg border shadow-sm cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md flex flex-col items-center text-center border-l-4 border-l-blue-500 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                    :class="selectedDepartmentFilter === department.name
                        ? 'ring-2 ring-blue-500 ring-offset-2'
                        : 'hover:border-blue-300'"
                >
                    <!-- Checkmark badge when selected -->
                    <div
                        v-if="selectedDepartmentFilter === department.name"
                        class="absolute -top-2 -right-2 w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center shadow-md"
                    >
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="font-semibold mb-2">{{ department.name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ department.count }} Mitarbeiter</div>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <!-- Verbesserte Kachel-Markierung mit Häkchen-Icon und klareren aktiven Styles -->
                <div
                    v-for="status in statusSummary"
                    :key="status.type.value"
                    @click="toggleEventTypeFilter(status.type.value)"
                    class="relative p-4 rounded-lg border shadow-sm cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md flex flex-col items-center text-center bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                    :class="selectedEventTypeFilter === status.type.value
                        ? 'ring-2 ring-offset-2'
                        : 'hover:border-gray-300'"
                    :style="{
                        borderLeftWidth: '4px',
                        borderLeftColor: status.type.color,
                        '--tw-ring-color': status.type.color
                    }"
                >
                    <!-- Checkmark badge when selected -->
                    <div
                        v-if="selectedEventTypeFilter === status.type.value"
                        class="absolute -top-2 -right-2 w-6 h-6 rounded-full flex items-center justify-center shadow-md"
                        :style="{ backgroundColor: status.type.color }"
                    >
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
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

            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm">
                <div class="grid grid-cols-12 bg-gray-100 dark:bg-gray-800 font-bold p-3">
                    <div class="col-span-3">Mitarbeiter</div>
                    <div class="col-span-4">Status</div>
                    <div class="col-span-5">Notizen</div>
                </div>

                <div
                    v-for="employee in filteredEmployeesForDay"
                    :key="employee.id"
                    class="grid grid-cols-12 border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
                >
                    <div class="col-span-3 p-3 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm" :style="{ backgroundColor: getInitialsColor(employee.name) }">
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
                            <div v-if="getEmployeeEventsForDay(employee, currentDate).length === 0" class="px-3 py-1.5 rounded text-sm bg-gray-200 dark:bg-gray-700 text-gray-500">
                                Nicht eingetragen
                            </div>
                        </div>
                    </div>

                    <div class="col-span-5 p-3">
                        <div v-for="(event, eventIndex) in getEmployeeEventsForDay(employee, currentDate)" :key="eventIndex">
                            <div v-if="event.notes" class="text-sm">{{ event.notes }}</div>
                        </div>
                        <div v-if="!getEmployeeEventsForDay(employee, currentDate).some(e => e.notes)" class="text-gray-400">-</div>
                    </div>
                </div>

                <div v-if="filteredEmployeesForDay.length === 0" class="p-6 text-center text-gray-500">
                    Keine Einträge für diesen Tag
                </div>
            </div>
        </div>

        <!-- Week View: Ereignisse als Spalten, Tage als Zeilen -->
        <div v-else-if="calendarView === 'week'" class="w-full">
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
                                            @click="openEmployeeDayDialog(emp, day.date)"
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
                                        @click="openEmployeeDayDialog(emp, weekDays[mobileWeekDay]?.date)"
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

        <!-- Month View -->
        <div v-else-if="calendarView === 'month'" class="w-full">
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
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm" :style="{ backgroundColor: getInitialsColor(employee.name) }">
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
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm" :style="{ backgroundColor: getInitialsColor(employee.name) }">
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

        <!-- Legend -->
        <div class="mt-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
            <div class="font-bold mb-2">Legende:</div>
            <div class="flex flex-wrap gap-4">
                <div
                    v-for="type in allActiveEventTypes"
                    :key="type.value"
                    class="flex items-center gap-2"
                >
                    <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: type.color }"></div>
                    <span class="text-sm">{{ type.name }}</span>
                </div>
            </div>
        </div>

        <!-- Employee Day Details Dialog -->
        <div v-if="employeeDayDialogVisible" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-bold">{{ selectedEmployeeForDay?.name }}</h3>
                    <button @click="employeeDayDialogVisible = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div v-if="selectedEmployeeForDay && selectedDateForDialog" class="p-4">
                    <div class="flex items-center gap-3 mb-4 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <div
                            class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold shadow-sm"
                            :style="{ backgroundColor: getInitialsColor(selectedEmployeeForDay.name) }"
                        >
                            {{ getInitials(selectedEmployeeForDay.name) }}
                        </div>
                        <div>
                            <div class="font-bold">{{ selectedEmployeeForDay.name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ selectedEmployeeForDay.department }}</div>
                        </div>
                    </div>

                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                        {{ formatDate(selectedDateForDialog) }}
                    </div>

                    <div class="flex flex-col gap-3">
                        <div
                            v-for="(event, eventIndex) in getEmployeeEventsForDay(selectedEmployeeForDay, selectedDateForDialog)"
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

                        <div v-if="getEmployeeEventsForDay(selectedEmployeeForDay, selectedDateForDialog).length === 0" class="text-center p-4 text-gray-500">
                            Keine Einträge für diesen Tag
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-emerald-500 mx-auto mb-4"></div>
                <p class="text-lg font-medium">Daten werden geladen...</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import dayjs from 'dayjs'
import 'dayjs/locale/de'
import weekOfYear from 'dayjs/plugin/weekOfYear'
import isoWeek from 'dayjs/plugin/isoWeek'
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter'
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore'
import VacationService from '@/Services/VacationService'
import HolidayService from '@/Services/holiday-service'
import axios from 'axios'

dayjs.extend(weekOfYear)
dayjs.extend(isoWeek)
dayjs.extend(isSameOrAfter)
dayjs.extend(isSameOrBefore)
dayjs.locale('de')

// State
const calendarView = ref('week')
const currentDate = ref(dayjs())
const searchQuery = ref('')
const selectedDepartmentFilter = ref('')
const selectedEventTypeFilter = ref('')
const mobileWeekDay = ref(0)
const isLoading = ref(false)

// Dialog state
const employeeDayDialogVisible = ref(false)
const selectedEmployeeForDay = ref(null)
const selectedDateForDialog = ref(null)

// Data
const employees = ref([])
const availableDepartments = ref([])
const eventTypes = ref([
    { name: 'Homeoffice', value: 'homeoffice', color: '#4CAF50' },
    { name: 'Büro', value: 'office', color: '#2196F3' },
    { name: 'Außendienst', value: 'field', color: '#FF9800' },
    { name: 'Krank', value: 'sick', color: '#F44336' },
    { name: 'Urlaub', value: 'vacation', color: '#9C27B0' },
    { name: 'Geburtstag', value: 'birthday', color: '#FFD700' },
    { name: 'Sonstiges', value: 'other', color: '#607D8B' }
])
const holidays = ref([])
const weekdaysShort = ['Montag', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']

// Computed - Calendar
const currentYear = computed(() => currentDate.value.year())
const currentMonthName = computed(() => currentDate.value.format('MMMM'))
const currentWeekNumber = computed(() => currentDate.value.isoWeek())

const weekStart = computed(() => {
    const day = currentDate.value.day()
    const diff = day === 0 ? 6 : day - 1
    return currentDate.value.subtract(diff, 'day')
})

const weekEnd = computed(() => weekStart.value.add(6, 'day'))

const weekDays = computed(() => {
    const days = []
    const today = dayjs()
    for (let i = 0; i < 7; i++) {
        const date = weekStart.value.add(i, 'day')
        days.push({
            date,
            dayName: date.format('ddd'),
            dayNumber: date.date(),
            isToday: date.format('YYYY-MM-DD') === today.format('YYYY-MM-DD'),
            isWeekend: date.day() === 0 || date.day() === 6
        })
    }
    return days
})

const daysInMonth = computed(() => {
    const lastDay = currentDate.value.endOf('month')
    return Array.from({ length: lastDay.date() }, (_, i) => i + 1)
})

// Filtered employees
const filteredEmployees = computed(() => {
    let result = employees.value
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(emp =>
            emp.name.toLowerCase().includes(query) ||
            emp.department.toLowerCase().includes(query)
        )
    }
    if (selectedDepartmentFilter.value) {
        result = result.filter(emp => emp.department === selectedDepartmentFilter.value)
    }
    if (selectedEventTypeFilter.value) {
        result = result.filter(emp => {
            // Check if employee has any event of the selected type in the current view period
            if (calendarView.value === 'week') {
                for (let i = 0; i < 7; i++) {
                    const date = weekStart.value.add(i, 'day')
                    const events = getEmployeeEventsForDay(emp, date)
                    if (events.some(e => e.type.value === selectedEventTypeFilter.value)) {
                        return true
                    }
                }
            } else if (calendarView.value === 'day') {
                const events = getEmployeeEventsForDay(emp, currentDate.value)
                return events.some(e => e.type.value === selectedEventTypeFilter.value)
            } else {
                // Month view - check all days in month
                const daysCount = currentDate.value.daysInMonth()
                for (let i = 1; i <= daysCount; i++) {
                    const date = currentDate.value.date(i)
                    const events = getEmployeeEventsForDay(emp, date)
                    if (events.some(e => e.type.value === selectedEventTypeFilter.value)) {
                        return true
                    }
                }
            }
            return false
        })
    }
    return result
})

const filteredEmployeesForDay = computed(() => {
    return filteredEmployees.value.filter(emp =>
        getEmployeeEventsForDay(emp, currentDate.value).length > 0
    )
})

// Active event types for week
const activeEventTypesForWeek = computed(() => {
    const activeTypes = new Set()
    weekDays.value.forEach(day => {
        filteredEmployees.value.forEach(employee => {
            const events = getEmployeeEventsForDay(employee, day.date)
            events.forEach(event => activeTypes.add(event.type.value))
        })
    })
    return eventTypes.value.filter(type => activeTypes.has(type.value))
})

const allActiveEventTypes = computed(() => {
    const types = [...activeEventTypesForWeek.value]
    if (hasHolidaysInCurrentPeriod()) {
        types.push({ name: 'Feiertag', value: 'holiday', color: '#FF0000' })
    }
    return types
})

// Summaries - Always show ALL departments and status types, not filtered ones
const departmentSummary = computed(() => {
    const departments = {}
    // Use employees.value instead of filteredEmployees to always show all departments
    employees.value.forEach(emp => {
        if (!departments[emp.department]) {
            departments[emp.department] = { name: emp.department, count: 0 }
        }
        // Count employees who have any event in the current week view
        const hasEvent = weekDays.value.some(day =>
            getEmployeeEventsForDay(emp, day.date).length > 0
        )
        if (hasEvent) departments[emp.department].count++
    })
    return Object.values(departments).filter(d => d.count > 0)
})

const statusSummary = computed(() => {
    const statuses = {}
    // Use employees.value instead of filteredEmployees to always show all status types
    weekDays.value.forEach(day => {
        employees.value.forEach(emp => {
            const events = getEmployeeEventsForDay(emp, day.date)
            events.forEach(event => {
                if (!statuses[event.type.value]) {
                    statuses[event.type.value] = { type: event.type, count: 0, employeeIds: new Set() }
                }
                if (!statuses[event.type.value].employeeIds.has(emp.id)) {
                    statuses[event.type.value].count++
                    statuses[event.type.value].employeeIds.add(emp.id)
                }
            })
        })
    })
    return Object.values(statuses)
})

// Methods - Events
const getEmployeeEventsForDay = (employee, date) => {
    if (!employee?.events || !Array.isArray(employee.events)) return []

    const dateStr = date.format('YYYY-MM-DD')
    const isWeekend = date.day() === 0 || date.day() === 6
    const uniqueEvents = []

    // Exact date matches
    const exactEvents = employee.events.filter(e => e.date === dateStr)

    // Range events
    const rangeEvents = employee.events.filter(e => {
        if (e.start_date && e.end_date) {
            const startDate = dayjs(e.start_date)
            const endDate = dayjs(e.end_date)
            return date.isSameOrAfter(startDate, 'day') && date.isSameOrBefore(endDate, 'day')
        }
        return false
    })

    const allEvents = [...exactEvents, ...rangeEvents]

    allEvents.forEach(event => {
        const isDuplicate = uniqueEvents.some(e =>
            (e.id && e.id === event.id) ||
            (e.type.value === event.type.value && e.date === event.date)
        )

        if (!isDuplicate) {
            // Apply weekend logic here for filtering
            if (isWeekend) {
                // Only include certain types on weekends
                if (['sick', 'birthday', 'other', 'sonstiges'].includes(event.type.value)) {
                    uniqueEvents.push(event)
                }
            } else {
                uniqueEvents.push(event)
            }
        }
    })

    return uniqueEvents
}

const getEmployeeEventsForMonthDay = (employee, dayNum) => {
    const date = currentDate.value.startOf('month').date(dayNum)
    return getEmployeeEventsForDay(employee, date)
}

// Group employees by event type and day, sorted by department
const getEmployeesByEventTypeAndDay = (eventType, date) => {
    if (!date) return []

    const departmentGroups = {}

    filteredEmployees.value.forEach(employee => {
        const events = getEmployeeEventsForDay(employee, date)
        const hasEventType = events.some(e => e.type.value === eventType.value)

        if (hasEventType) {
            const dept = employee.department || 'Keine Abteilung'
            if (!departmentGroups[dept]) {
                departmentGroups[dept] = { department: dept, employees: [] }
            }
            departmentGroups[dept].employees.push(employee)
        }
    })

    // Sort employees within each department
    Object.values(departmentGroups).forEach(group => {
        group.employees.sort((a, b) => a.name.localeCompare(b.name))
    })

    // Return sorted by department name
    return Object.values(departmentGroups).sort((a, b) =>
        a.department.localeCompare(b.department)
    )
}

const getEmployeesCountByEventTypeAndDay = (eventType, date) => {
    if (!date) return 0
    const groups = getEmployeesByEventTypeAndDay(eventType, date)
    return groups.reduce((sum, group) => sum + group.employees.length, 0)
}

const activeEventTypesForDay = (date) => {
    if (!date) return []
    const typesSet = new Set()
    const types = []

    filteredEmployees.value.forEach(employee => {
        const events = getEmployeeEventsForDay(employee, date)
        events.forEach(event => {
            if (!typesSet.has(event.type.value)) {
                typesSet.add(event.type.value)
                types.push(event.type)
            }
        })
    })

    // Define a custom order for event types
    const order = ['homeoffice', 'office', 'field', 'sick', 'vacation', 'birthday', 'other']
    types.sort((a, b) => {
        const indexA = order.indexOf(a.value)
        const indexB = order.indexOf(b.value)
        // Handle cases where a type might not be in the predefined order
        return (indexA === -1 ? 999 : indexA) - (indexB === -1 ? 999 : indexB)
    })

    return types
}

const getEmployeeMonthSummary = (employee) => {
    const summary = {}
    daysInMonth.value.forEach(dayNum => {
        const date = currentDate.value.startOf('month').date(dayNum)
        const events = getEmployeeEventsForDay(employee, date)
        events.forEach(event => {
            if (!summary[event.type.value]) {
                summary[event.type.value] = { name: event.type.name, color: event.type.color, count: 0 }
            }
            summary[event.type.value].count++
        })
    })
    return Object.values(summary)
}

// Holiday methods
const isHoliday = (date) => HolidayService.isHoliday(date, holidays.value)
const getHolidayName = (date) => HolidayService.getHolidayName(date, holidays.value)
const isHolidayInMonth = (dayNum) => isHoliday(currentDate.value.startOf('month').date(dayNum))

const hasHolidaysInCurrentPeriod = () => {
    if (calendarView.value === 'day') return isHoliday(currentDate.value)
    if (calendarView.value === 'week') return weekDays.value.some(day => isHoliday(day.date))
    return daysInMonth.value.some(dayNum => isHolidayInMonth(dayNum))
}

// Month helpers
const isToday = (dayNum) => {
    const today = dayjs()
    return today.date() === dayNum &&
        today.month() === currentDate.value.month() &&
        today.year() === currentDate.value.year()
}

const isWeekendDay = (dayNum) => {
    const date = currentDate.value.startOf('month').date(dayNum)
    const day = date.day()
    return day === 0 || day === 6
}

const getMonthStartDay = () => {
    const firstDayOfMonth = currentDate.value.startOf('month')
    const dayOfWeek = firstDayOfMonth.day()
    // If Sunday (0), it should be the 7th day of the week for alignment
    return dayOfWeek === 0 ? 6 : dayOfWeek - 1
}

// Format helpers
const formatDate = (date) => date.format('dddd, DD. MMMM YYYY')
const formatDayMonth = (date) => date.format('DD.MM.')
const formatDateRange = (start, end) => `${start.format('DD.MM.')} - ${end.format('DD.MM.YYYY')}`

const getInitials = (name) => name.split(' ').map(part => part.charAt(0)).join('').toUpperCase()

const getInitialsColor = (name) => {
    let hash = 0
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash)
    }
    // Use HSL for better color distribution
    return `hsl(${hash % 360}, 70%, 60%)`
}

// Navigation
const setCalendarView = (view) => { calendarView.value = view }

const previousPeriod = () => {
    if (calendarView.value === 'day') currentDate.value = currentDate.value.subtract(1, 'day')
    else if (calendarView.value === 'week') currentDate.value = currentDate.value.subtract(1, 'week')
    else currentDate.value = currentDate.value.subtract(1, 'month')
}

const nextPeriod = () => {
    if (calendarView.value === 'day') currentDate.value = currentDate.value.add(1, 'day')
    else if (calendarView.value === 'week') currentDate.value = currentDate.value.add(1, 'week')
    else currentDate.value = currentDate.value.add(1, 'month')
}

// Filter toggles
const toggleDepartmentFilter = (departmentName) => {
    if (selectedDepartmentFilter.value === departmentName) {
        selectedDepartmentFilter.value = ''
    } else {
        selectedDepartmentFilter.value = departmentName
    }
}

const toggleEventTypeFilter = (eventTypeValue) => {
    if (selectedEventTypeFilter.value === eventTypeValue) {
        selectedEventTypeFilter.value = ''
    } else {
        selectedEventTypeFilter.value = eventTypeValue
    }
}

// Dialog
const openEmployeeDayDialog = (employee, date) => {
    selectedEmployeeForDay.value = employee
    selectedDateForDialog.value = date
    employeeDayDialogVisible.value = true
}

// Data fetching
const fetchCalendarData = async () => {
    isLoading.value = true
    try {
        const response = await VacationService.getCompanyCalendarData()
        employees.value = response.data.employees || []
        availableDepartments.value = response.data.departments || []

        // Dynamically update event types if provided by backend
        if (response.data.eventTypes) {
            eventTypes.value = response.data.eventTypes
        }

        // Load vacation requests and merge them with employee events
        try {
            const vacationResponse = await axios.get('/api/vacation/all-requests')
            const vacationEvents = vacationResponse.data
                .filter(v => v.status === 'approved') // Only consider approved vacations
                .map(v => ({
                    // Use user_id to link to employee, assuming 'id' in response is user_id
                    user_id: v.user_id,
                    date: v.start_date, // For day view display
                    start_date: v.start_date,
                    end_date: v.end_date,
                    type: { name: 'Urlaub', value: 'vacation', color: '#9C27B0' }, // Hardcoded vacation type
                    notes: v.notes || 'Genehmigter Urlaub' // Default notes if none provided
                }))

            employees.value.forEach(emp => {
                const empVacations = vacationEvents.filter(v => v.user_id === emp.id)
                if (empVacations.length > 0) {
                    // Ensure emp.events is an array before spreading
                    emp.events = [...(emp.events || []), ...empVacations]
                }
            })
        } catch (e) {
            console.error('Error loading vacation requests:', e)
        }
    } catch (error) {
        console.error('Error loading calendar data:', error)
    } finally {
        isLoading.value = false
    }
}

const fetchHolidays = async (year) => {
    try {
        holidays.value = await HolidayService.getHolidays(year)
    } catch (error) {
        console.error('Error loading holidays:', error)
    }
}

// Watch year changes to fetch holidays for the correct year
watch(() => currentDate.value.year(), (newYear, oldYear) => {
    if (newYear !== oldYear) fetchHolidays(newYear)
})

// Initialize data when the component mounts
onMounted(() => {
    fetchHolidays(currentDate.value.year())
    fetchCalendarData()
})
</script>
