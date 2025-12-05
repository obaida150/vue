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
                <div
                    v-for="department in departmentSummary"
                    :key="department.name"
                    @click="toggleDepartmentFilter(department.name)"
                    class="relative p-4 rounded-lg border shadow-sm cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md flex flex-col items-center text-center border-l-4 border-l-blue-500 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                    :class="selectedDepartmentFilter === department.name
                        ? 'ring-2 ring-blue-500 ring-offset-2'
                        : 'hover:border-blue-300'"
                >
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

        <!-- View Components -->
        <CalendarDayView
            v-if="calendarView === 'day'"
            :current-date="currentDate"
            :filtered-employees="filteredEmployeesForDay"
            :is-holiday="isHoliday"
            :get-holiday-name="getHolidayName"
            :get-employee-events-for-day="getEmployeeEventsForDay"
            :get-initials="getInitials"
            :get-initials-color="getInitialsColor"
            :format-date="formatDate"
        />

        <CalendarWeekView
            v-else-if="calendarView === 'week'"
            :week-days="weekDays"
            :filtered-employees="filteredEmployees"
            :active-event-types-for-week="activeEventTypesForWeek"
            :is-holiday="isHoliday"
            :get-holiday-name="getHolidayName"
            :get-employee-events-for-day="getEmployeeEventsForDay"
            :get-employees-by-event-type-and-day="getEmployeesByEventTypeAndDay"
            :get-employees-count-by-event-type-and-day="getEmployeesCountByEventTypeAndDay"
            :active-event-types-for-day="activeEventTypesForDay"
            :get-initials="getInitials"
            :get-initials-color="getInitialsColor"
            :format-day-month="formatDayMonth"
            @open-employee-dialog="openEmployeeDayDialog"
        />

        <CalendarMonthView
            v-else-if="calendarView === 'month'"
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
        <CalendarEmployeeDialog
            v-if="employeeDayDialogVisible"
            :employee="selectedEmployeeForDay"
            :date="selectedDateForDialog"
            :get-employee-events-for-day="getEmployeeEventsForDay"
            :get-initials="getInitials"
            :get-initials-color="getInitialsColor"
            :format-date="formatDate"
            @close="employeeDayDialogVisible = false"
        />

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

// Child components
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
const weekdaysShort = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']

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

const getEmployeeMonthSummary = (employee) => {
    const daysCount = currentDate.value.daysInMonth()
    const summary = {
        totalDays: 0,
        eventsByType: {}
    }

    for (let i = 1; i <= daysCount; i++) {
        const date = currentDate.value.startOf('month').date(i)
        const events = getEmployeeEventsForDay(employee, date)

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
            if (calendarView.value === 'week') {
                for (let i = 0; i < 7; i++) {
                    const date = weekStart.value.add(i, 'day')
                    const events = getEmployeeEventsForDay(emp, date)
                    if (events.some(e => (e.type?.value || e.type) === selectedEventTypeFilter.value)) {
                        return true
                    }
                }
            } else if (calendarView.value === 'day') {
                const events = getEmployeeEventsForDay(emp, currentDate.value)
                return events.some(e => (e.type?.value || e.type) === selectedEventTypeFilter.value)
            } else {
                const daysCount = currentDate.value.daysInMonth()
                for (let i = 1; i <= daysCount; i++) {
                    const date = currentDate.value.date(i)
                    const events = getEmployeeEventsForDay(emp, date)
                    if (events.some(e => (e.type?.value || e.type) === selectedEventTypeFilter.value)) {
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

// Summary Cards
const departmentSummary = computed(() => {
    const deptCounts = {}

    if (calendarView.value === 'day') {
        // Im Tagesansicht: nur Abteilungen von Mitarbeitern mit Events an diesem Tag
        employees.value.forEach(emp => {
            const events = getEmployeeEventsForDay(emp, currentDate.value)
            if (events.length > 0) {
                const dept = emp.department || 'Keine Abteilung'
                deptCounts[dept] = (deptCounts[dept] || 0) + 1
            }
        })
    } else if (calendarView.value === 'week') {
        // Im Wochenansicht: nur Abteilungen von Mitarbeitern mit Events in dieser Woche
        employees.value.forEach(emp => {
            let hasEventInWeek = false
            weekDays.value.forEach(day => {
                const events = getEmployeeEventsForDay(emp, day.date)
                if (events.length > 0) hasEventInWeek = true
            })
            if (hasEventInWeek) {
                const dept = emp.department || 'Keine Abteilung'
                deptCounts[dept] = (deptCounts[dept] || 0) + 1
            }
        })
    } else {
        // Im Monatsansicht: nur Abteilungen von Mitarbeitern mit Events in diesem Monat
        const daysCount = currentDate.value.daysInMonth()
        employees.value.forEach(emp => {
            let hasEventInMonth = false
            for (let i = 1; i <= daysCount; i++) {
                const day = currentDate.value.startOf('month').add(i - 1, 'day')
                const events = getEmployeeEventsForDay(emp, day)
                if (events.length > 0) {
                    hasEventInMonth = true
                    break
                }
            }
            if (hasEventInMonth) {
                const dept = emp.department || 'Keine Abteilung'
                deptCounts[dept] = (deptCounts[dept] || 0) + 1
            }
        })
    }

    return Object.entries(deptCounts).map(([name, count]) => ({ name, count }))
        .sort((a, b) => a.name.localeCompare(b.name))
})

const statusSummary = computed(() => {
    const typeCounts = {}
    const relevantDates = []

    if (calendarView.value === 'day') {
        // Nur der aktuelle Tag
        relevantDates.push(currentDate.value)
    } else if (calendarView.value === 'week') {
        // Alle Tage der aktuellen Woche
        for (let i = 0; i < 7; i++) {
            relevantDates.push(weekStart.value.add(i, 'day'))
        }
    } else {
        // Alle Tage des aktuellen Monats
        const daysCount = currentDate.value.daysInMonth()
        for (let i = 1; i <= daysCount; i++) {
            relevantDates.push(currentDate.value.startOf('month').add(i - 1, 'day'))
        }
    }

    console.log('[v0] statusSummary - view:', calendarView.value, 'dates:', relevantDates.map(d => d.format('YYYY-MM-DD')))

    employees.value.forEach(emp => {
        relevantDates.forEach(date => {
            const events = getEmployeeEventsForDay(emp, date)
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

    console.log('[v0] statusSummary - typeCounts:', Object.keys(typeCounts))

    return Object.entries(typeCounts).map(([value, data]) => ({
        type: data.type,
        count: data.employees.size
    })).sort((a, b) => {
        const order = ['homeoffice', 'office', 'field', 'sick', 'vacation', 'birthday', 'other']
        return order.indexOf(a.type?.value || a.type) - order.indexOf(b.type?.value || b.type)
    })
})

const allActiveEventTypes = computed(() => {
    return eventTypes.value
})

// Active event types for week
const activeEventTypesForWeek = computed(() => {
    const activeTypes = new Set()

    weekDays.value.forEach(day => {
        employees.value.forEach(employee => {
            const events = getEmployeeEventsForDay(employee, day.date)
            events.forEach(event => {
                const typeValue = event.type?.value || event.type
                if (typeValue) {
                    activeTypes.add(typeValue)
                }
            })
        })
    })

    let result = eventTypes.value.filter(type => activeTypes.has(type.value))

    if (selectedEventTypeFilter.value) {
        result = result.filter(type => type.value === selectedEventTypeFilter.value)
    }

    return result
})

// Active event types for day
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

    const order = ['homeoffice', 'office', 'field', 'sick', 'vacation', 'birthday', 'other']
    types.sort((a, b) => {
        const indexA = order.indexOf(a.value)
        const indexB = order.indexOf(b.value)
        return (indexA === -1 ? 999 : indexA) - (indexB === -1 ? 999 : indexB)
    })

    return types
}

// Methods - Events
const getEmployeeEventsForDay = (employee, date) => {
    if (!employee?.events || !Array.isArray(employee.events)) return []

    const dateStr = date.format('YYYY-MM-DD')
    const isWeekend = date.day() === 0 || date.day() === 6
    const seenTypes = new Set()
    const uniqueEvents = []

    const vacationEvents = employee.events.filter(e => (e.type?.value || e.type) === 'vacation')
    if (vacationEvents.length > 0) {
        console.log('[v0] getEmployeeEventsForDay - checking', employee.name, 'for date', dateStr, '- has vacation events:', vacationEvents.map(v => ({ id: v.id, date: v.date, start: v.start_date, end: v.end_date })))
    }

    // Nur Events mit exaktem Datum ODER wo das Datum im Bereich liegt (aber nicht beides)
    employee.events.forEach(event => {
        const eventDate = event.date
        const hasDateRange = event.start_date && event.end_date

        let matchesDay = false

        if (eventDate === dateStr) {
            // Event mit exaktem Datum
            matchesDay = true
        } else if (hasDateRange) {
            // Event mit Datumsbereich - prüfe ob aktuelles Datum im Bereich liegt
            const startDate = dayjs(event.start_date)
            const endDate = dayjs(event.end_date)
            matchesDay = date.isSameOrAfter(startDate, 'day') && date.isSameOrBefore(endDate, 'day')
        }

        if (matchesDay) {
            const typeValue = event.type?.value || event.type

            if (typeValue === 'vacation') {
                console.log('[v0] MATCH! Vacation event for', employee.name, 'on', dateStr, ':', event.id)
            }

            // Deduplizierung: nur ein Event pro Typ pro Tag
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
        }
    })

    return uniqueEvents
}

const getEmployeeEventsForMonthDay = (employee, dayNum) => {
    const date = currentDate.value.startOf('month').date(dayNum)
    return getEmployeeEventsForDay(employee, date)
}

const getEmployeesByEventTypeAndDay = (eventType, date) => {
    if (!date) return []

    const departmentGroups = {}

    filteredEmployees.value.forEach(employee => {
        const events = getEmployeeEventsForDay(employee, date)
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

    return Object.values(departmentGroups).sort((a, b) =>
        a.department.localeCompare(b.department)
    )
}

const getEmployeesCountByEventTypeAndDay = (eventType, date) => {
    if (!date) return 0
    const groups = getEmployeesByEventTypeAndDay(eventType, date)
    return groups.reduce((sum, group) => sum + group.employees.length, 0)
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

        if (response.data.eventTypes) {
            eventTypes.value = response.data.eventTypes
        }

        try {
            const vacationResponse = await axios.get('/api/vacation/all-requests')
            console.log('[v0] Vacation API Response:', vacationResponse.data)

            const approvedVacations = vacationResponse.data.filter(v => v.status === 'approved')
            console.log('[v0] Approved vacations:', approvedVacations)

            const vacationType = eventTypes.value.find(t =>
                t.value === 'vacation' || t.value === 'urlaub' || t.name?.toLowerCase() === 'urlaub'
            ) || { name: 'Urlaub', value: 'vacation', color: '#9C27B0' }

            console.log('[v0] Using vacation type:', vacationType)

            approvedVacations.forEach(vacation => {
                const employee = employees.value.find(emp => emp.id === vacation.user_id)
                if (employee) {
                    if (!employee.events) {
                        employee.events = []
                    }

                    const startDate = dayjs(vacation.start_date)
                    const endDate = dayjs(vacation.end_date)
                    let loopDate = startDate

                    while (loopDate.isSameOrBefore(endDate, 'day')) {
                        const vacationEvent = {
                            id: `vacation-${vacation.id}-${loopDate.format('YYYY-MM-DD')}`,
                            user_id: vacation.user_id,
                            date: loopDate.format('YYYY-MM-DD'),
                            start_date: vacation.start_date,
                            end_date: vacation.end_date,
                            type: vacationType,
                            notes: vacation.notes || `Urlaub (${startDate.format('DD.MM.')} - ${endDate.format('DD.MM.YYYY')})`
                        }

                        const isDuplicate = employee.events.some(e =>
                            e.id === vacationEvent.id ||
                            ((e.type?.value === 'vacation' || e.type?.value === 'urlaub') && e.date === vacationEvent.date)
                        )

                        if (!isDuplicate) {
                            employee.events.push(vacationEvent)
                        }

                        loopDate = loopDate.add(1, 'day')
                    }

                    console.log('[v0] Added vacation events for employee:', employee.name, employee.events.filter(e => e.type?.value === 'vacation' || e.type?.value === 'urlaub'))
                }
            })

            console.log('[v0] All employees with events:', employees.value.map(e => ({
                id: e.id,
                name: e.name,
                eventsCount: e.events?.length || 0,
                vacationEvents: e.events?.filter(ev => ev.type?.value === 'vacation' || ev.type?.value === 'urlaub') || []
            })))

        } catch (e) {
            console.error('[v0] Error loading vacation requests:', e)
        }
    } catch (error) {
        console.error('[v0] Error loading calendar data:', error)
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
