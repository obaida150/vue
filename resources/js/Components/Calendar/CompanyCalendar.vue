<!--<template>-->
<!--    &lt;!&ndash; Calendar Header &ndash;&gt;-->
<!--    <div class="w-full overflow-visible p-4 rounded-lg shadow-md transition-all duration-300 bg-gradient-to-br from-white via-gray-50 to-blue-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 text-gray-800 dark:text-gray-100">-->
<!--        &lt;!&ndash; Calendar Header &ndash;&gt;-->
<!--        <div class="bg-white/90 dark:bg-gray-900/90 backdrop-blur-md border-b border-gray-200 dark:border-gray-700">-->
<!--            <div class="flex flex-col md:flex-row justify-between items-start md:items-center p-4 gap-3">-->
<!--                <div class="flex items-center gap-3">-->
<!--                    <button @click="previousPeriod" class="p-2 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 text-white hover:from-blue-600 hover:to-blue-700 transition-all duration-200 shadow-sm hover:shadow-md">-->
<!--                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>-->
<!--                    </button>-->
<!--                    <h2 class="text-xl font-bold capitalize m-0 bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">-->
<!--                        <span v-if="calendarView === 'day'">{{ formatDate(currentDate) }}</span>-->
<!--                        <span v-else-if="calendarView === 'week'">KW {{ currentWeekNumber }} ({{ formatDateRange(weekStart, weekEnd) }})</span>-->
<!--                        <span v-else>{{ currentMonthName }} {{ currentYear }}</span>-->
<!--                    </h2>-->
<!--                    <button @click="nextPeriod" class="p-2 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 text-white hover:from-blue-600 hover:to-blue-700 transition-all duration-200 shadow-sm hover:shadow-md">-->
<!--                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>-->
<!--                    </button>-->
<!--                </div>-->
<!--                <div class="flex gap-1 bg-white dark:bg-gray-800 p-1 rounded-lg shadow-sm">-->
<!--                    <button-->
<!--                        v-for="view in ['day', 'week', 'month']"-->
<!--                        :key="view"-->
<!--                        :class="[-->
<!--                'px-3 py-1.5 rounded-md text-sm font-medium transition-all duration-200',-->
<!--                calendarView === view-->
<!--                    ? 'bg-gradient-to-br from-blue-500 to-purple-600 text-white shadow-md'-->
<!--                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'-->
<!--            ]"-->
<!--                        @click="setCalendarView(view)"-->
<!--                    >-->
<!--                        {{ view === 'day' ? 'Tag' : view === 'week' ? 'Woche' : 'Monat' }}-->
<!--                    </button>-->
<!--                </div>-->
<!--            </div>-->

<!--            &lt;!&ndash; Filter Controls &ndash;&gt;-->
<!--            <div class="bg-white/90 dark:bg-gray-900/90 backdrop-blur-md border-b border-gray-200 dark:border-gray-700 p-4">-->
<!--                <div class="flex flex-col md:flex-row justify-between items-center w-full gap-2">-->
<!--                    <div class="w-full md:flex-1 relative">-->
<!--                        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">-->
<!--                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>-->
<!--                        </div>-->
<!--                        <input-->
<!--                            v-model="searchQuery"-->
<!--                            type="text"-->
<!--                            placeholder="Mitarbeiter suchen..."-->
<!--                            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200"-->
<!--                        />-->
<!--                    </div>-->
<!--                    <div class="w-full md:flex-1 relative">-->
<!--                        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">-->
<!--                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>-->
<!--                        </div>-->
<!--                        <select-->
<!--                            v-model="selectedDepartmentFilter"-->
<!--                            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200"-->
<!--                        >-->
<!--                            <option value="">Alle Abteilungen</option>-->
<!--                            <option v-for="dept in availableDepartments" :key="dept.name" :value="dept.name">-->
<!--                                {{ dept.name }}-->
<!--                            </option>-->
<!--                        </select>-->
<!--                    </div>-->
<!--                    <div class="w-full md:flex-1 relative">-->
<!--                        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">-->
<!--                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>-->
<!--                        </div>-->
<!--                        <select-->
<!--                            v-model="selectedEventTypeFilter"-->
<!--                            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200"-->
<!--                        >-->
<!--                            <option value="">Alle Status</option>-->
<!--                            <option v-for="eventType in eventTypes" :key="eventType.value" :value="eventType.value">-->
<!--                                {{ eventType.name }}-->
<!--                            </option>-->
<!--                        </select>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->

<!--            &lt;!&ndash; Summary Cards &ndash;&gt;-->
<!--            <div class="flex flex-col gap-3 mb-4 bg-white/90 dark:bg-gray-900/90 backdrop-blur-md p-4 border-b border-gray-200 dark:border-gray-700">-->
<!--                &lt;!&ndash; Department Cards &ndash;&gt;-->
<!--                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">-->
<!--                    <div-->
<!--                        v-for="department in departmentSummary"-->
<!--                        :key="department.name"-->
<!--                        @click="toggleDepartmentFilter(department.name)"-->
<!--                        class="relative p-3 rounded-lg border shadow-sm cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg flex flex-col items-center text-center border-l-4 border-l-blue-500 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700"-->
<!--                        :class="selectedDepartmentFilter === department.name-->
<!--                ? 'ring-2 ring-blue-500 ring-offset-2 shadow-md'-->
<!--                : 'hover:border-blue-300'"-->
<!--                    >-->
<!--                        <div-->
<!--                            v-if="selectedDepartmentFilter === department.name"-->
<!--                            class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center shadow-md"-->
<!--                        >-->
<!--                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">-->
<!--                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>-->
<!--                            </svg>-->
<!--                        </div>-->
<!--                        <div class="font-semibold text-sm mb-1">{{ department.name }}</div>-->
<!--                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ department.count }} MA</div>-->
<!--                    </div>-->
<!--                </div>-->

<!--                &lt;!&ndash; Status Cards &ndash;&gt;-->
<!--                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">-->
<!--                    <div-->
<!--                        v-for="status in statusSummary"-->
<!--                        :key="status.type.value"-->
<!--                        @click="toggleEventTypeFilter(status.type.value)"-->
<!--                        class="relative p-3 rounded-lg border shadow-sm cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg flex flex-col items-center text-center bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700"-->
<!--                        :class="selectedEventTypeFilter === status.type.value-->
<!--                ? 'ring-2 ring-offset-2 shadow-md'-->
<!--                : 'hover:border-gray-300'"-->
<!--                        :style="{-->
<!--                borderLeftWidth: '4px',-->
<!--                borderLeftColor: status.type.color,-->
<!--                '&#45;&#45;tw-ring-color': status.type.color-->
<!--            }"-->
<!--                    >-->
<!--                        <div-->
<!--                            v-if="selectedEventTypeFilter === status.type.value"-->
<!--                            class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full flex items-center justify-center shadow-md"-->
<!--                            :style="{ backgroundColor: status.type.color }"-->
<!--                        >-->
<!--                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">-->
<!--                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>-->
<!--                            </svg>-->
<!--                        </div>-->
<!--                        <div class="w-5 h-5 rounded-full mb-1.5" :style="{ backgroundColor: status.type.color }"></div>-->
<!--                        <div class="font-semibold text-sm mb-1">{{ status.type.name }}</div>-->
<!--                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ status.count }} MA</div>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->
<!--        &lt;!&ndash; View Components &ndash;&gt;-->
<!--        <CalendarDayView-->
<!--            v-if="calendarView === 'day'"-->
<!--            :current-date="currentDate"-->
<!--            :filtered-employees="filteredEmployeesForDay"-->
<!--            :is-holiday="isHoliday"-->
<!--            :get-holiday-name="getHolidayName"-->
<!--            :get-employee-events-for-day="getEmployeeEventsForDay"-->
<!--            :get-initials="getInitials"-->
<!--            :get-initials-color="getInitialsColor"-->
<!--            :format-date="formatDate"-->
<!--            :event-types="eventTypes"-->
<!--        />-->

<!--        &lt;!&ndash; Enhanced Week View with Compact User Display &ndash;&gt;-->
<!--        <div v-else-if="calendarView === 'week'" class="overflow-x-auto">-->
<!--            <table class= "w-full border-collapse min-w-[800px]">-->
<!--                <thead class="">-->
<!--                <tr>-->
<!--                    <th class="p-2 text-left font-semibold text-sm border border-gray-300 dark:border-gray-600 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-800 dark:to-gray-700">-->
<!--                        Tag-->
<!--                    </th>-->
<!--                    <th-->
<!--                        v-for="eventType in activeEventTypesForWeek"-->
<!--                        :key="eventType.value"-->
<!--                        class="p-2 text-center font-semibold text-sm border border-gray-300 dark:border-gray-600 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-800 dark:to-gray-700"-->
<!--                    >-->
<!--                        <div class="flex items-center justify-center gap-2">-->
<!--                            <div class="w-3 h-3 rounded-full shadow-sm" :style="{ backgroundColor: eventType.color }"></div>-->
<!--                            <span>{{ eventType.name }}</span>-->
<!--                        </div>-->
<!--                    </th>-->
<!--                </tr>-->
<!--                </thead>-->
<!--                <tbody>-->
<!--                <tr-->
<!--                    v-for="day in weekDays"-->
<!--                    :key="day.date.format('YYYY-MM-DD')"-->
<!--                    :class="[-->
<!--                            'transition-colors duration-150',-->
<!--                            day.isToday ? 'bg-blue-100 dark:bg-blue-900/30' : '',-->
<!--                            day.isWeekend ? 'bg-gray-100 dark:bg-gray-800/50' : ''-->
<!--                        ]"-->
<!--                >-->
<!--                    <td class="p-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900">-->
<!--                        <div class="flex flex-col">-->
<!--                            <span class="font-bold text-sm">{{ day.dayName }}</span>-->
<!--                            <span class="text-xs text-gray-600 dark:text-gray-400">{{ formatDayMonth(day.date) }}</span>-->
<!--                            <span-->
<!--                                v-if="isHoliday(day.date)"-->
<!--                                class="text-xs font-semibold text-red-600 dark:text-red-400 mt-1"-->
<!--                            >-->
<!--                                    {{ getHolidayName(day.date) }}-->
<!--                                </span>-->
<!--                        </div>-->
<!--                    </td>-->
<!--                    <td-->
<!--                        v-for="eventType in activeEventTypesForWeek"-->
<!--                        :key="eventType.value"-->
<!--                        class="p-2 border border-gray-300 dark:border-gray-600 align-top"-->
<!--                    >-->
<!--                        <div-->
<!--                            v-if="getEmployeesByEventTypeAndDay(eventType, day.date).length > 0"-->
<!--                            class="space-y-2"-->
<!--                        >-->
<!--                            &lt;!&ndash; Show department groups &ndash;&gt;-->
<!--                            <div-->
<!--                                v-for="(group, groupIndex) in getEmployeesByEventTypeAndDay(eventType, day.date)"-->
<!--                                :key="group.department"-->
<!--                            >-->
<!--                                &lt;!&ndash; Department label &ndash;&gt;-->
<!--                                <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">-->
<!--                                    {{ group.department }}-->
<!--                                </div>-->

<!--                                &lt;!&ndash; Compact employee display &ndash;&gt;-->
<!--                                <div class="flex flex-wrap gap-1 items-center">-->
<!--                                    &lt;!&ndash; Show first 3 employees &ndash;&gt;-->
<!--                                    <div-->
<!--                                        v-for="(employee, idx) in group.employees.slice(0, 2)"-->
<!--                                        :key="employee.id"-->
<!--                                        class="flex items-center gap-1 px-2 py-1 bg-white dark:bg-gray-800 rounded-md shadow-sm border border-gray-200 dark:border-gray-600 hover:shadow-md transition-shadow duration-150"-->
<!--                                    >-->
<!--                                        <div-->
<!--                                            class="w-5 h-5 rounded-full flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0"-->
<!--                                            :style="{ backgroundColor: getInitialsColor(employee.name) }"-->
<!--                                        >-->
<!--                                            {{ getInitials(employee.name) }}-->
<!--                                        </div>-->
<!--                                        <span class="text-xs whitespace-nowrap">{{ employee.name }}</span>-->
<!--                                    </div>-->

<!--                                    &lt;!&ndash; Show "+X mehr" button if more than 3 employees &ndash;&gt;-->
<!--                                    <button-->
<!--                                        v-if="group.employees.length > 2"-->
<!--                                        @click="toggleUserPopover($event, group, eventType, day.date, groupIndex)"-->
<!--                                        class="px-2 py-1 text-xs font-medium bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-md shadow-sm hover:from-blue-600 hover:to-blue-700 transition-all duration-150 hover:shadow-md"-->
<!--                                    >-->
<!--                                        +{{ group.employees.length - 2 }} mehr-->
<!--                                    </button>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                        </div>-->
<!--                    </td>-->
<!--                </tr>-->
<!--                </tbody>-->
<!--            </table>-->
<!--        </div>-->

<!--        <CalendarMonthView-->
<!--            v-else-if="calendarView === 'month'"-->
<!--            :current-date="currentDate"-->
<!--            :days-in-month="daysInMonth"-->
<!--            :filtered-employees="filteredEmployees"-->
<!--            :is-today="isToday"-->
<!--            :is-holiday-in-month="isHolidayInMonth"-->
<!--            :is-weekend-day="isWeekendDay"-->
<!--            :get-month-start-day="getMonthStartDay"-->
<!--            :get-employee-events-for-month-day="getEmployeeEventsForMonthDay"-->
<!--            :get-employee-month-summary="getEmployeeMonthSummary"-->
<!--            :get-initials="getInitials"-->
<!--            :get-initials-color="getInitialsColor"-->
<!--            :weekdays-short="weekdaysShort"-->
<!--        />-->

<!--        &lt;!&ndash; Legend &ndash;&gt;-->
<!--        <div class="mt-4 p-3 border border-gray-200 dark:border-gray-700 rounded-lg bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-800 dark:to-gray-800 shadow-sm">-->
<!--            <div class="font-bold text-sm mb-2">Legende:</div>-->
<!--            <div class="flex flex-wrap gap-3">-->
<!--                <div-->
<!--                    v-for="type in allActiveEventTypes"-->
<!--                    :key="type.value"-->
<!--                    class="flex items-center gap-2"-->
<!--                >-->
<!--                    <div class="w-3 h-3 rounded-full shadow-sm" :style="{ backgroundColor: type.color }"></div>-->
<!--                    <span class="text-xs">{{ type.name }}</span>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->

<!--        &lt;!&ndash; Employee Day Details Dialog &ndash;&gt;-->
<!--        <CalendarEmployeeDialog-->
<!--            v-if="employeeDayDialogVisible"-->
<!--            :employee="selectedEmployeeForDay"-->
<!--            :date="selectedDateForDialog"-->
<!--            :get-employee-events-for-day="getEmployeeEventsForDay"-->
<!--            :get-initials="getInitials"-->
<!--            :get-initials-color="getInitialsColor"-->
<!--            :format-date="formatDate"-->
<!--            @close="employeeDayDialogVisible = false"-->
<!--        />-->

<!--        &lt;!&ndash; Loading Overlay &ndash;&gt;-->
<!--        <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 backdrop-blur-sm">-->
<!--            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-2xl text-center">-->
<!--                <div class="animate-spin rounded-full h-12 w-12 border-4 border-gray-200 border-t-blue-500 mx-auto mb-4"></div>-->
<!--                <p class="text-base font-medium bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Daten werden geladen...</p>-->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->

<!--    &lt;!&ndash; Added OverlayPanel for showing all users &ndash;&gt;-->
<!--    <OverlayPanel ref="userPopover" :dismissable="true" class="w-80">-->
<!--        <div v-if="selectedGroupForPopover" class="space-y-3">-->
<!--            <div class="flex items-center justify-between pb-2 border-b border-gray-200 dark:border-gray-700">-->
<!--                <h3 class="font-bold text-sm text-gray-800 dark:text-gray-200">-->
<!--                    {{ selectedGroupForPopover.department }}-->
<!--                </h3>-->
<!--                <div class="text-xs text-gray-500 dark:text-gray-400">-->
<!--                    {{ selectedGroupForPopover.employees.length }} Mitarbeiter-->
<!--                </div>-->
<!--            </div>-->

<!--            <div class="max-h-64 overflow-y-auto space-y-2">-->
<!--                <div-->
<!--                    v-for="employee in selectedGroupForPopover.employees"-->
<!--                    :key="employee.id"-->
<!--                    class="flex items-center gap-2 p-2 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"-->
<!--                >-->
<!--                    <div-->
<!--                        class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"-->
<!--                        :style="{ backgroundColor: getInitialsColor(employee.name) }"-->
<!--                    >-->
<!--                        {{ getInitials(employee.name) }}-->
<!--                    </div>-->
<!--                    <div class="flex-1 min-w-0">-->
<!--                        <div class="text-sm font-medium text-gray-800 dark:text-gray-200">-->
<!--                            {{ employee.name }}-->
<!--                        </div>-->
<!--                        <div class="text-xs text-gray-500 dark:text-gray-400">-->
<!--                            {{ employee.department }}-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->
<!--    </OverlayPanel>-->
<!--</template>-->
<template>
     <!-- Calendar Header -->
     <div class="w-full overflow-visible p-4 rounded-lg shadow-md transition-all duration-300 bg-gradient-to-br from-white via-gray-50 to-blue-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 text-gray-800 dark:text-gray-100">
     <!-- Calendar Header -->
     <div class="bg-white/90 dark:bg-gray-900/90 backdrop-blur-md border-b border-gray-200 dark:border-gray-700">
     <div class="flex flex-col md:flex-row justify-between items-start md:items-center p-4 gap-3">
     <div class="flex items-center gap-3">
     <button @click="previousPeriod" class="p-2 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 text-white hover:from-blue-600 hover:to-blue-700 transition-all duration-200 shadow-sm hover:shadow-md">
         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
     </button>
     <h2 class="text-xl font-bold capitalize m-0 bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
     <span v-if="calendarView === 'day'">{{ formatDate(currentDate) }}</span>
     <span v-else-if="calendarView === 'week'">KW {{ currentWeekNumber }} ({{ formatDateRange(weekStart, weekEnd) }})</span>
     <span v-else>{{ currentMonthName }} {{ currentYear }}</span>
     </h2>
     <button @click="nextPeriod" class="p-2 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 text-white hover:from-blue-600 hover:to-blue-700 transition-all duration-200 shadow-sm hover:shadow-md">
         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
     </button>
     </div>
     <div class="flex gap-1 bg-white dark:bg-gray-800 p-1 rounded-lg shadow-sm">
     <button v-for="view in ['day', 'week', 'month']"
     :key="view"
     :class="[
     'px-3 py-1.5 rounded-md text-sm font-medium transition-all duration-200',
     calendarView === view ? 'bg-gradient-to-br from-blue-500 to-purple-600 text-white shadow-md'
     : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
     ]"
     @click="setCalendarView(view)"
     >
     {{ view === 'day' ? 'Tag' : view === 'week' ? 'Woche' : 'Monat' }}
     </button>

     <!-- Kompakt-Toggle -->
     <button class="px-3 py-1.5 rounded-md text-sm font-medium transition-all duration-200"
     :class="compactWeekView ? 'bg-gradient-to-br from-blue-500 to-purple-600 text-white shadow-md'
     : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
     @click="compactWeekView = !compactWeekView"
     >
     Kompakt </button>
     </div>
     </div>

     <!-- Filter Controls -->
     <div class="bg-white/90 dark:bg-gray-900/90 backdrop-blur-md border-b border-gray-200 dark:border-gray-700 p-4">
     <div class="flex flex-col md:flex-row justify-between items-center w-full gap-2">
     <div class="w-full md:flex-1 relative">
     <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="002424"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2121l-6-6m2-5a77011-14077001140z"/></svg>
     </div>
     <input v-model="searchQuery"
     type="text"
     placeholder="Mitarbeiter suchen..."
     class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200"
     />
     </div>
     <div class="w-full md:flex-1 relative">
     <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="002424"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1921V5a22000-2-2H7a22000-22v16m140h2m-20h-5m-90H3m20h5M97h1m-14h1m4-4h1m-14h1m-510v-5a110011-1h2a1100111v5m-40h4"/></svg>
     </div>
     <select v-model="selectedDepartmentFilter"
     class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200"
     >
     <option value="">Alle Abteilungen</option>
     <option v-for="dept in availableDepartments" :key="dept.name" :value="dept.name">
     {{ dept.name }}
     </option>
     </select>
     </div>
     <div class="w-full md:flex-1 relative">
     <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="002424"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M95H7a22000-22v12a2200022h10a220002-2V7a22000-2-2h-2M95a2200022h2a220002-2M95a220012-2h2a2200122"/></svg>
     </div>
     <select v-model="selectedEventTypeFilter"
     class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200"
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
     <div class="flex flex-col gap-3 mb-4 bg-white/90 dark:bg-gray-900/90 backdrop-blur-md p-4 border-b border-gray-200 dark:border-gray-700">
     <!-- Department Cards -->
     <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
     <div v-for="department in departmentSummary"
     :key="department.name"
     @click="toggleDepartmentFilter(department.name)"
     class="relative p-3 rounded-lg border shadow-sm cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg flex flex-col items-center text-center border-l-4 border-l-blue-500 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700"
     :class="selectedDepartmentFilter === department.name ? 'ring-2 ring-blue-500 ring-offset-2 shadow-md'
     : 'hover:border-blue-300'"
     >
     <div v-if="selectedDepartmentFilter === department.name"
     class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center shadow-md"
     >
     <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="002424">
     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M513l44L197"></path>
     </svg>
     </div>
     <div class="font-semibold text-sm mb-1">{{ department.name }}</div>
     <div class="text-xs text-gray-500 dark:text-gray-400">{{ department.count }} MA</div>
     </div>
     </div>

     <!-- Status Cards -->
     <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
     <div v-for="status in statusSummary"
     :key="status.type.value"
     @click="toggleEventTypeFilter(status.type.value)"
     class="relative p-3 rounded-lg border shadow-sm cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg flex flex-col items-center text-center bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700"
     :class="selectedEventTypeFilter === status.type.value ? 'ring-2 ring-offset-2 shadow-md'
     : 'hover:border-gray-300'"
     :style="{
     borderLeftWidth: '4px',
     borderLeftColor: status.type.color,
     '--tw-ring-color': status.type.color }"
     >
     <div v-if="selectedEventTypeFilter === status.type.value"
     class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full flex items-center justify-center shadow-md"
     :style="{ backgroundColor: status.type.color }"
     >
     <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="002424">
     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M513l44L197"></path>
     </svg>
     </div>
     <div class="w-5 h-5 rounded-full mb-1.5" :style="{ backgroundColor: status.type.color }"></div>
     <div class="font-semibold text-sm mb-1">{{ status.type.name }}</div>
     <div class="text-xs text-gray-500 dark:text-gray-400">{{ status.count }} MA</div>
     </div>
     </div>
     </div>
     </div>

     <!-- View Components -->
     <CalendarDayView v-if="calendarView === 'day'"
     :current-date="currentDate"
     :filtered-employees="filteredEmployeesForDay"
     :is-holiday="isHoliday"
     :get-holiday-name="getHolidayName"
     :get-employee-events-for-day="getEmployeeEventsForDay"
     :get-initials="getInitials"
     :get-initials-color="getInitialsColor"
     :format-date="formatDate"
     :event-types="eventTypes"
     />

     <!-- Enhanced Week View with Compact User Display -->
     <div v-else-if="calendarView === 'week'" class="overflow-x-auto">
     <table class="w-full border-collapse min-w-[800px]">
     <thead class="">
     <tr>
     <th class="p-2 text-left font-semibold text-sm border border-gray-300 dark:border-gray-600 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-800 dark:to-gray-700">
     Tag </th>
     <th v-for="eventType in activeEventTypesForWeek"
     :key="eventType.value"
     class="p-2 text-center font-semibold text-sm border border-gray-300 dark:border-gray-600 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-800 dark:to-gray-700"
     >
     <div class="flex items-center justify-center gap-2">
     <div class="w-3 h-3 rounded-full shadow-sm" :style="{ backgroundColor: eventType.color }"></div>
     <span>{{ eventType.name }}</span>
     </div>
     </th>
     </tr>
     </thead>
     <tbody>
     <tr v-for="day in weekDays"
     :key="day.date.format('YYYY-MM-DD')"
     :class="[
     'transition-colors duration-150',
     day.isToday ? 'bg-blue-100 dark:bg-blue-900/30' : '',
     day.isWeekend ? 'bg-gray-100 dark:bg-gray-800/50' : ''
     ]"
     >
     <td class="p-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900">
     <div class="flex flex-col">
     <span class="font-bold text-sm">{{ day.dayName }}</span>
     <span class="text-xs text-gray-600 dark:text-gray-400">{{ formatDayMonth(day.date) }}</span>
     <span v-if="isHoliday(day.date)"
     class="text-xs font-semibold text-red-600 dark:text-red-400 mt-1"
     >
     {{ getHolidayName(day.date) }}
     </span>
     </div>
     </td>
     <td v-for="eventType in activeEventTypesForWeek"
     :key="eventType.value"
     class="p-2 border border-gray-300 dark:border-gray-600 align-top"
     >
     <div v-if="compactWeekView">
     <div class="flex items-center gap-2 text-xs font-semibold">
     <span class="inline-flex w-3 h-3 rounded-full" :style="{ backgroundColor: eventType.color }"></span>
     {{ getEventCountByDay(eventType, day.date) }} Mitarbeiter </div>
     <button v-if="getEventCountByDay(eventType, day.date) >0"
     @click="openCompactPopover($event, eventType, day.date)"
     class="mt-1 px-2 py-1 text-xs font-medium bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-md shadow-sm hover:from-blue-600 hover:to-blue-700 transition-all duration-150 hover:shadow-md"
     >
     Details </button>
     </div>
     <div v-else>
     <div v-if="getEmployeesByEventTypeAndDay(eventType, day.date).length >0"
     class="space-y-2"
     >
     <div v-for="(group, groupIndex) in getEmployeesByEventTypeAndDay(eventType, day.date)"
     :key="group.department"
     >
     <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
     {{ group.department }}
     </div>
     <div class="flex flex-wrap gap-1 items-center">
     <div v-for="(employee, idx) in group.employees.slice(0,2)"
     :key="employee.id"
     class="flex items-center gap-1 px-2 py-1 bg-white dark:bg-gray-800 rounded-md shadow-sm border border-gray-200 dark:border-gray-600 hover:shadow-md transition-shadow duration-150"
     >
     <div class="w-5 h-5 rounded-full flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0"
     :style="{ backgroundColor: getInitialsColor(employee.name) }"
     >
     {{ getInitials(employee.name) }}
     </div>
     <span class="text-xs whitespace-nowrap">{{ employee.name }}</span>
     </div>
     <button v-if="group.employees.length >2"
     @click="toggleUserPopover($event, group, eventType, day.date, groupIndex)"
     class="px-2 py-1 text-xs font-medium bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-md shadow-sm hover:from-blue-600 hover:to-blue-700 transition-all duration-150 hover:shadow-md"
     >
     +{{ group.employees.length -2 }} mehr </button>
     </div>
     </div>
     </div>
     </div>
     </td>
     </tr>
     </tbody>
     </table>
     </div>

     <CalendarMonthView v-else-if="calendarView === 'month'"
     :current-date="currentDate"
     :days-in-month="daysInMonth"
     :filtered-employees="filteredEmployees"
     :is-today="isToday"
     :is-holiday-in-month="isHolidayInMonth"
     :is-weekend-day="isWeekendDay"
     :get-month-start-day="getMonthStartDay"
     :get-employee-events-for-month-day="getEmployeeEventsForMonthDay"
     :get-employee-month-summary="getEmployeeMonthSummary"
     :get-initials="getInitials"
     :get-initials-color="getInitialsColor"
     :weekdays-short="weekdaysShort"
     />

     <!-- Legende -->
     <div class="mt-4 p-3 border border-gray-200 dark:border-gray-700 rounded-lg bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-800 dark:to-gray-800 shadow-sm">
     <div class="font-bold text-sm mb-2">Legende:</div>
     <div class="flex flex-wrap gap-3">
     <div v-for="type in allActiveEventTypes"
     :key="type.value"
     class="flex items-center gap-2"
     >
     <div class="w-3 h-3 rounded-full shadow-sm" :style="{ backgroundColor: type.color }"></div>
     <span class="text-xs">{{ type.name }}</span>
     </div>
     </div>
     </div>

     <!-- Employee Day Details Dialog -->
     <CalendarEmployeeDialog v-if="employeeDayDialogVisible"
     :employee="selectedEmployeeForDay"
     :date="selectedDateForDialog"
     :get-employee-events-for-day="getEmployeeEventsForDay"
     :get-initials="getInitials"
     :get-initials-color="getInitialsColor"
     :format-date="formatDate"
     @close="employeeDayDialogVisible = false"
     />

     <!-- Loading Overlay -->
     <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 backdrop-blur-sm">
     <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-2xl text-center">
     <div class="animate-spin rounded-full h-12 w-12 border-4 border-gray-200 border-t-blue-500 mx-auto mb-4"></div>
     <p class="text-base font-medium bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Daten werden geladen...</p>
     </div>
     </div>
     </div>

     <!-- Added OverlayPanel for showing all users -->
     <OverlayPanel ref="userPopover" :dismissable="true" class="w-80">
     <div v-if="selectedGroupForPopover" class="space-y-3">
     <div class="flex items-center justify-between pb-2 border-b border-gray-200 dark:border-gray-700">
     <h3 class="font-bold text-sm text-gray-800 dark:text-gray-200">
     {{ selectedGroupForPopover.department }}
     </h3>
     <div class="text-xs text-gray-500 dark:text-gray-400">
     {{ selectedGroupForPopover.employees.length }} Mitarbeiter </div>
     </div>

     <div class="max-h-64 overflow-y-auto space-y-2">
     <div v-for="employee in selectedGroupForPopover.employees"
     :key="employee.id"
     class="flex items-center gap-2 p-2 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
     >
     <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
     :style="{ backgroundColor: getInitialsColor(employee.name) }"
     >
     {{ getInitials(employee.name) }}
     </div>
     <div class="flex-1 min-w-0">
     <div class="text-sm font-medium text-gray-800 dark:text-gray-200">
     {{ employee.name }}
     </div>
     <div class="text-xs text-gray-500 dark:text-gray-400">
     {{ employee.department }}
     </div>
     </div>
     </div>
     </div>
     </div>
     </OverlayPanel>
    </template>


<script setup>
import { ref, computed, onMounted, watch, shallowRef } from 'vue'
import dayjs from 'dayjs'
import 'dayjs/locale/de'
import weekOfYear from 'dayjs/plugin/weekOfYear'
import isoWeek from 'dayjs/plugin/isoWeek'
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter'
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore'
import VacationService from '@/Services/VacationService'
import HolidayService from '@/Services/holiday-service'
import axios from 'axios'
import OverlayPanel from 'primevue/overlaypanel'

import CalendarDayView from './CalendarDayView.vue'
import CalendarWeekView from './CalendarWeekView.vue'
import CalendarMonthView from './CalendarMonthView.vue'
import CalendarEmployeeDialog from './CalendarEmployeeDialog.vue'

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
const isLoading = ref(false)

const employeeDayDialogVisible = ref(false)
const selectedEmployeeForDay = ref(null)
const selectedDateForDialog = ref(null)

const userPopover = ref()
const selectedGroupForPopover = ref(null)

// Data - shallowRef für bessere Performance bei großen Arrays
const employees = shallowRef([])
const availableDepartments = shallowRef([])
const eventTypes = ref([
    { name: 'Homeoffice', value: 'homeoffice', color: '#4CAF50' },
    { name: 'Büro', value: 'office', color: '#2196F3' },
    { name: 'Außendienst', value: 'field', color: '#FF9800' },
    { name: 'Krank', value: 'sick', color: '#F44336' },
    { name: 'Urlaub', value: 'vacation', color: '#9C27B0' },
    { name: 'Geburtstag', value: 'birthday', color: '#FFD700' },
    { name: 'Sonstiges', value: 'other', color: '#607D8B' }
])
const holidays = shallowRef([])
const weekdaysShort = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']

// ===== OPTIMIERUNG: Event-Index für schnelle Lookups =====
const eventIndex = computed(() => {
    const index = new Map()

    employees.value.forEach(employee => {
        if (!employee.events) return

        employee.events.forEach(event => {
            const eventDate = event.date
            const hasDateRange = event.start_date && event.end_date

            if (hasDateRange) {
                const startDate = dayjs(event.start_date)
                const endDate = dayjs(event.end_date)
                let loopDate = startDate

                while (loopDate.isSameOrBefore(endDate, 'day')) {
                    const dateStr = loopDate.format('YYYY-MM-DD')
                    const key = `${employee.id}-${dateStr}`

                    if (!index.has(key)) {
                        index.set(key, [])
                    }
                    index.get(key).push(event)
                    loopDate = loopDate.add(1, 'day')
                }
            } else if (eventDate) {
                const key = `${employee.id}-${eventDate}`
                if (!index.has(key)) {
                    index.set(key, [])
                }
                index.get(key).push(event)
            }
        })
    })

    return index
})

// ===== OPTIMIERUNG: Vorberechneter Holiday-Set für O(1) Lookup =====
const holidaySet = computed(() => {
    const set = new Set()
    holidays.value.forEach(h => {
        set.add(dayjs(h.date || h).format('YYYY-MM-DD'))
    })
    return set
})

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
    const today = dayjs().format('YYYY-MM-DD')
    for (let i = 0; i < 7; i++) {
        const date = weekStart.value.add(i, 'day')
        const dateStr = date.format('YYYY-MM-DD')
        days.push({
            date,
            dateStr,
            dayName: date.format('ddd'),
            dayNumber: date.date(),
            isToday: dateStr === today,
            isWeekend: date.day() === 0 || date.day() === 6
        })
    }
    return days
})

const daysInMonth = computed(() => {
    const lastDay = currentDate.value.endOf('month')
    return Array.from({ length: lastDay.date() }, (_, i) => i + 1)
})

// ===== OPTIMIERUNG: Schnelle Event-Lookup Methode =====
const getEmployeeEventsForDay = (employee, date) => {
    if (!employee) return []

    const dateStr = typeof date === 'string' ? date : date.format('YYYY-MM-DD')
    const key = `${employee.id}-${dateStr}`
    const events = eventIndex.value.get(key) || []

    const dateObj = typeof date === 'string' ? dayjs(date) : date
    const isWeekend = dateObj.day() === 0 || dateObj.day() === 6

    const seenTypes = new Set()
    const uniqueEvents = []

    events.forEach(event => {
        const typeValue = event.type?.value || event.type

        if (!seenTypes.has(typeValue)) {
            if (isWeekend) {
                if (['sick', 'birthday', 'other', 'sonstiges', 'vacation', 'urlaub'].includes(typeValue)) {
                    seenTypes.add(typeValue)
                    uniqueEvents.push(event)
                }
            } else {
                seenTypes.add(typeValue)
                uniqueEvents.push(event)
            }
        }
    })

    return uniqueEvents
}

// ===== OPTIMIERUNG: Vorberechnete Wochendaten =====
const weekViewData = computed(() => {
    const data = new Map()

    weekDays.value.forEach(day => {
        const dayData = new Map()

        eventTypes.value.forEach(eventType => {
            const departmentGroups = {}

            filteredEmployees.value.forEach(employee => {
                const events = getEmployeeEventsForDay(employee, day.dateStr)
                const hasEventType = events.some(e => (e.type?.value || e.type) === eventType.value)

                if (hasEventType) {
                    const dept = employee.department || 'Keine Abteilung'
                    if (!departmentGroups[dept]) {
                        departmentGroups[dept] = { department: dept, employees: [] }
                    }
                    departmentGroups[dept].employees.push(employee)
                }
            })

            Object.values(departmentGroups).forEach(group => {
                group.employees.sort((a, b) => a.name.localeCompare(b.name))
            })

            const sortedGroups = Object.values(departmentGroups).sort((a, b) =>
                a.department.localeCompare(b.department)
            )

            dayData.set(eventType.value, sortedGroups)
        })

        data.set(day.dateStr, dayData)
    })

    return data
})

// Optimierte Methode für Template
const getEmployeesByEventTypeAndDay = (eventType, date) => {
    if (!date) return []
    const dateStr = typeof date === 'string' ? date : date.format('YYYY-MM-DD')
    return weekViewData.value.get(dateStr)?.get(eventType.value) || []
}

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

    // Event-Type Filter - optimiert
    if (selectedEventTypeFilter.value) {
        const relevantDates = calendarView.value === 'week'
            ? weekDays.value.map(d => d.dateStr)
            : calendarView.value === 'day'
                ? [currentDate.value.format('YYYY-MM-DD')]
                : daysInMonth.value.map(d => currentDate.value.startOf('month').date(d).format('YYYY-MM-DD'))

        result = result.filter(emp => {
            return relevantDates.some(dateStr => {
                const events = getEmployeeEventsForDay(emp, dateStr)
                return events.some(e => (e.type?.value || e.type) === selectedEventTypeFilter.value)
            })
        })
    }

    return result
})

const filteredEmployeesForDay = computed(() => {
    const dateStr = currentDate.value.format('YYYY-MM-DD')
    return filteredEmployees.value.filter(emp =>
        getEmployeeEventsForDay(emp, dateStr).length > 0
    )
})

const compactWeekView = ref(true)

const getEventCountByDay = (eventType, date) => {
 return getEmployeesByEventTypeAndDay(eventType, date)
 .reduce((sum, group) => sum + group.employees.length,0)
}

const openCompactPopover = (event, eventType, date) => {
 const groups = getEmployeesByEventTypeAndDay(eventType, date)
 const flatGroup = {
 department: `${eventType.name} • ${dayjs(date).format('DD.MM.')}`,
 employees: groups.flatMap(g => g.employees),
 }
 toggleUserPopover(event, flatGroup)
}

// Summary Cards - optimiert mit Memoization
const departmentSummary = computed(() => {
    const deptCounts = {}
    const relevantDates = calendarView.value === 'day'
        ? [currentDate.value.format('YYYY-MM-DD')]
        : calendarView.value === 'week'
            ? weekDays.value.map(d => d.dateStr)
            : daysInMonth.value.map(d => currentDate.value.startOf('month').date(d).format('YYYY-MM-DD'))

    employees.value.forEach(emp => {
        const hasEvent = relevantDates.some(dateStr =>
            getEmployeeEventsForDay(emp, dateStr).length > 0
        )

        if (hasEvent) {
            const dept = emp.department || 'Keine Abteilung'
            deptCounts[dept] = (deptCounts[dept] || 0) + 1
        }
    })

    return Object.entries(deptCounts)
        .map(([name, count]) => ({ name, count }))
        .sort((a, b) => a.name.localeCompare(b.name))
})

const statusSummary = computed(() => {
    const typeCounts = {}
    const relevantDates = calendarView.value === 'day'
        ? [currentDate.value.format('YYYY-MM-DD')]
        : calendarView.value === 'week'
            ? weekDays.value.map(d => d.dateStr)
            : daysInMonth.value.map(d => currentDate.value.startOf('month').date(d).format('YYYY-MM-DD'))

    employees.value.forEach(emp => {
        relevantDates.forEach(dateStr => {
            const events = getEmployeeEventsForDay(emp, dateStr)
            events.forEach(event => {
                const typeValue = event.type?.value || event.type
                if (typeValue) {
                    if (!typeCounts[typeValue]) {
                        typeCounts[typeValue] = { employees: new Set(), type: event.type }
                    }
                    typeCounts[typeValue].employees.add(emp.id)
                }
            })
        })
    })

    const order = ['homeoffice', 'office', 'field', 'sick', 'vacation', 'birthday', 'other']
    return Object.entries(typeCounts)
        .map(([value, data]) => ({ type: data.type, count: data.employees.size }))
        .sort((a, b) => order.indexOf(a.type?.value) - order.indexOf(b.type?.value))
})

const allActiveEventTypes = computed(() => eventTypes.value)

const activeEventTypesForWeek = computed(() => {
    const activeTypes = new Set()

    weekDays.value.forEach(day => {
        employees.value.forEach(employee => {
            const events = getEmployeeEventsForDay(employee, day.dateStr)
            events.forEach(event => {
                const typeValue = event.type?.value || event.type
                if (typeValue) activeTypes.add(typeValue)
            })
        })
    })

    let result = eventTypes.value.filter(type => activeTypes.has(type.value))

    if (selectedEventTypeFilter.value) {
        result = result.filter(type => type.value === selectedEventTypeFilter.value)
    }

    return result
})

const getEmployeeMonthSummary = (employee) => {
    const daysCount = currentDate.value.daysInMonth()
    const summary = { totalDays: 0, eventsByType: {} }

    for (let i = 1; i <= daysCount; i++) {
        const dateStr = currentDate.value.startOf('month').date(i).format('YYYY-MM-DD')
        const events = getEmployeeEventsForDay(employee, dateStr)

        if (events.length > 0) {
            summary.totalDays++
            events.forEach(event => {
                const typeValue = event.type?.value || event.type
                summary.eventsByType[typeValue] = (summary.eventsByType[typeValue] || 0) + 1
            })
        }
    }

    return summary
}

const getEmployeeEventsForMonthDay = (employee, dayNum) => {
    const dateStr = currentDate.value.startOf('month').date(dayNum).format('YYYY-MM-DD')
    return getEmployeeEventsForDay(employee, dateStr)
}

// Holiday methods - optimiert
const isHoliday = (date) => {
    const dateStr = typeof date === 'string' ? date : date.format('YYYY-MM-DD')
    return holidaySet.value.has(dateStr)
}

const getHolidayName = (date) => HolidayService.getHolidayName(date, holidays.value)
const isHolidayInMonth = (dayNum) => isHoliday(currentDate.value.startOf('month').date(dayNum))

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
    return dayOfWeek === 0 ? 6 : dayOfWeek - 1
}

// Format helpers
const formatDate = (date) => date.format('dddd, DD. MMMM YYYY')
const formatDayMonth = (date) => date.format('DD.MM.')
const formatDateRange = (start, end) => `${start.format('DD.MM.')} - ${end.format('DD.MM.YYYY')}`

const getInitials = (name) => {
    if (!name) return '?'
    return name.split(' ').map(part => part.charAt(0)).join('').toUpperCase()
}

// Cached color map
const colorCache = new Map()
const getInitialsColor = (name) => {
    if (!name) return 'hsl(0, 70%, 60%)'
    if (colorCache.has(name)) return colorCache.get(name)

    let hash = 0
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash)
    }
    const color = `hsl(${hash % 360}, 70%, 60%)`
    colorCache.set(name, color)
    return color
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
    selectedDepartmentFilter.value = selectedDepartmentFilter.value === departmentName ? '' : departmentName
}

const toggleEventTypeFilter = (eventTypeValue) => {
    selectedEventTypeFilter.value = selectedEventTypeFilter.value === eventTypeValue ? '' : eventTypeValue
}

// Dialog
const openEmployeeDayDialog = (employee, date) => {
    selectedEmployeeForDay.value = employee
    selectedDateForDialog.value = date
    employeeDayDialogVisible.value = true
}

const toggleUserPopover = (event, group) => {
    selectedGroupForPopover.value = group
    userPopover.value.toggle(event)
}

// Data fetching
const fetchCalendarData = async () => {
    isLoading.value = true
    try {
        const [calendarResponse, vacationResponse] = await Promise.all([
            VacationService.getCompanyCalendarData(),
            axios.get('/api/vacation/all-requests').catch(() => ({ data: [] }))
        ])

        const loadedEmployees = calendarResponse.data.employees || []
        availableDepartments.value = calendarResponse.data.departments || []

        if (calendarResponse.data.eventTypes) {
            eventTypes.value = calendarResponse.data.eventTypes
        }

        // Vacation data processing
        const approvedVacations = (vacationResponse.data || []).filter(v => v.status === 'approved')
        const vacationType = eventTypes.value.find(t =>
            t.value === 'vacation' || t.value === 'urlaub'
        ) || { name: 'Urlaub', value: 'vacation', color: '#9C27B0' }

        approvedVacations.forEach(vacation => {
            const employee = loadedEmployees.find(emp => emp.id === vacation.user_id)
            if (employee) {
                if (!employee.events) employee.events = []

                const vacationEvent = {
                    id: `vacation-${vacation.id}`,
                    user_id: vacation.user_id,
                    start_date: vacation.start_date,
                    end_date: vacation.end_date,
                    type: vacationType,
                    notes: vacation.notes || 'Urlaub'
                }

                const isDuplicate = employee.events.some(e => e.id === vacationEvent.id)
                if (!isDuplicate) {
                    employee.events.push(vacationEvent)
                }
            }
        })

        // Trigger reactivity mit neuem Array
        employees.value = [...loadedEmployees]

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

watch(() => currentDate.value.year(), (newYear, oldYear) => {
    if (newYear !== oldYear) fetchHolidays(newYear)
})

onMounted(() => {
    fetchHolidays(currentDate.value.year())
    fetchCalendarData()
})
</script>
