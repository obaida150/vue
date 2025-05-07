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
                <div class="mr-2">
                    <Button
                        icon="pi pi-sun"
                        v-if="isDarkMode"
                        @click="toggleDarkMode"
                        class="p-button-rounded p-button-text"
                        aria-label="Light Mode"
                    />
                    <Button
                        icon="pi pi-moon"
                        v-else
                        @click="toggleDarkMode"
                        class="p-button-rounded p-button-text"
                        aria-label="Dark Mode"
                    />
                </div>
                <div class="flex gap-1">
                    <Button
                        :class="{ 'p-button-primary': calendarView === 'day', 'p-button-outlined': calendarView !== 'day' }"
                        label="Tag"
                        @click="calendarView = 'day'"
                        class="p-button-rounded"
                    />
                    <Button
                        :class="{ 'p-button-primary': calendarView === 'week', 'p-button-outlined': calendarView !== 'week' }"
                        label="Woche"
                        @click="calendarView = 'week'"
                        class="p-button-rounded"
                    />
                    <Button
                        :class="{ 'p-button-primary': calendarView === 'month', 'p-button-outlined': calendarView !== 'month' }"
                        label="Monat"
                        @click="calendarView = 'month'"
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
<!--                        <i class="pi pi-search" />-->
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
            <div class="flex flex-wrap gap-4">
                <div
                    v-for="department in departmentSummary"
                    :key="department.name"
                    class="flex-1 min-w-[150px] p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md flex flex-col items-center text-center border-l-4 border-l-blue-500"
                    @click="openDepartmentDialog(department.name)"
                >
                    <div class="font-semibold mb-2">{{ department.name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ department.count }} Mitarbeiter</div>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="flex flex-wrap gap-4">
                <div
                    v-for="status in statusSummary"
                    :key="status.type.value"
                    class="flex-1 min-w-[150px] p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md flex flex-col items-center text-center"
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
            <h3 class="text-xl font-medium text-center mb-4">{{ formatDate(currentDate) }}</h3>

            <div class="flex flex-col w-full border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm">
                <div class="flex bg-gray-100 dark:bg-gray-800 font-bold p-3">
                    <div class="flex-2 px-2">Mitarbeiter</div>
                    <div class="flex-1 px-2">Status</div>
                    <div class="flex-1 px-2">Notizen</div>
                </div>

                <div
                    v-for="employee in filteredEmployees"
                    :key="employee.id"
                    class="flex border-t border-gray-200 dark:border-gray-700 p-3 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 even:bg-gray-50 dark:even:bg-gray-800"
                >
                    <div class="flex-2 flex items-center gap-3 px-2">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm" :style="{ backgroundColor: getInitialsColor(employee.name) }">
                            {{ getInitials(employee.name) }}
                        </div>
                        <div>
                            <div class="font-medium">{{ employee.name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ employee.department }}</div>
                        </div>
                    </div>

                    <div class="flex-1 flex items-center px-2">
                        <div
                            v-if="getEmployeeStatusForDay(employee, currentDate)"
                            class="px-2 py-1 rounded text-sm text-white shadow-sm"
                            :style="{ backgroundColor: getEmployeeStatusForDay(employee, currentDate).color }"
                        >
                            {{ getEmployeeStatusForDay(employee, currentDate).name }}
                        </div>
                        <div v-else class="px-2 py-1 rounded text-sm bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400">Nicht eingetragen</div>
                    </div>

                    <div class="flex-1 flex items-center px-2">
                        {{ getEmployeeNotesForDay(employee, currentDate) || '-' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Week View -->
        <div v-else-if="calendarView === 'week'" class="w-full overflow-x-auto rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex min-w-[800px]">
                <div class="w-[200px] min-w-[200px] p-3 bg-gray-100 dark:bg-gray-800 font-bold">Mitarbeiter</div>
                <div
                    v-for="(day, index) in weekDays"
                    :key="index"
                    class="flex-1 min-w-[100px] p-3 text-center font-bold border-l border-gray-200 dark:border-gray-700 transition-colors"
                    :class="{
                        'bg-blue-50 dark:bg-blue-900/20': day.isToday,
                        'bg-gray-100 dark:bg-gray-800': !day.isToday && !day.isWeekend,
                        'bg-gray-200 dark:bg-gray-700': day.isWeekend
                    }"
                >
                    <div class="font-bold">{{ day.dayName }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ formatDayMonth(day.date) }}</div>
                </div>
            </div>

            <div class="flex flex-col min-w-[800px]">
                <div
                    v-for="employee in filteredEmployees"
                    :key="employee.id"
                    class="flex border-t border-gray-200 dark:border-gray-700 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 even:bg-gray-50 dark:even:bg-gray-800"
                >
                    <div class="w-[200px] min-w-[200px] p-3 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm" :style="{ backgroundColor: getInitialsColor(employee.name) }">
                            {{ getInitials(employee.name) }}
                        </div>
                        <div>
                            <div class="font-medium">{{ employee.name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ employee.department }}</div>
                        </div>
                    </div>

                    <div
                        v-for="(day, index) in weekDays"
                        :key="index"
                        class="flex-1 min-w-[100px] p-3 flex items-center justify-center border-l border-gray-200 dark:border-gray-700 transition-colors"
                        :class="{
                            'bg-blue-50 dark:bg-blue-900/20': day.isToday,
                            'bg-gray-200 dark:bg-gray-700': day.isWeekend
                        }"
                    >
                        <div
                            v-if="getEmployeeStatusForDay(employee, day.date)"
                            class="px-2 py-1 rounded text-sm text-white shadow-sm"
                            :style="{ backgroundColor: getEmployeeStatusForDay(employee, day.date).color }"
                        >
                            {{ getEmployeeStatusForDay(employee, day.date).name }}
                        </div>
                        <div v-else class="px-2 py-1 rounded text-sm bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Month View -->
        <div v-else-if="calendarView === 'month'" class="w-full overflow-x-auto rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex min-w-[1200px]">
                <div class="w-[200px] min-w-[200px] p-2 bg-gray-100 dark:bg-gray-800 font-bold">Mitarbeiter</div>
                <div
                    v-for="dayNum in daysInMonth"
                    :key="dayNum"
                    class="w-[30px] min-w-[30px] py-2 text-center text-sm font-bold border-l border-gray-200 dark:border-gray-700 transition-colors"
                    :class="{
                        'bg-blue-50 dark:bg-blue-900/20': isToday(dayNum),
                        'bg-gray-100 dark:bg-gray-800': !isToday(dayNum) && !isWeekend(dayNum),
                        'bg-gray-200 dark:bg-gray-700': isWeekend(dayNum)
                    }"
                >
                    {{ dayNum }}
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
                            {{ getInitials(employee.name) }}
                        </div>
                        <div class="overflow-hidden">
                            <div class="font-medium">{{ employee.name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ employee.department }}</div>
                        </div>
                    </div>

                    <div
                        v-for="dayNum in daysInMonth"
                        :key="dayNum"
                        class="w-[30px] min-w-[30px] h-[30px] flex items-center justify-center border-l border-gray-200 dark:border-gray-700 transition-colors"
                        :class="{
                            'bg-blue-50 dark:bg-blue-900/20': isToday(dayNum),
                            'bg-gray-200 dark:bg-gray-700': isWeekend(dayNum)
                        }"
                    >
                        <div
                            v-if="getEmployeeStatusForMonthDay(employee, dayNum)"
                            class="w-5 h-5 rounded-full"
                            :style="{ backgroundColor: getEmployeeStatusForMonthDay(employee, dayNum).color }"
                            :title="getEmployeeStatusForMonthDay(employee, dayNum).name"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="mt-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
            <div class="font-bold mb-2">Legende:</div>
            <div class="flex flex-wrap gap-4">
                <div
                    v-for="type in eventTypes"
                    :key="type.value"
                    class="flex items-center gap-2 cursor-pointer px-2 py-1 rounded-md transition-colors hover:bg-gray-100 dark:hover:bg-gray-700"
                    @click="openStatusDialog(type)"
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
            :style="{ width: '80vw', maxWidth: '1000px' }"
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

                <div class="flex flex-wrap gap-4 justify-center">
                    <div
                        v-for="status in departmentStatusSummary"
                        :key="status.type.value"
                        class="flex items-center gap-2 px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-800 shadow-sm"
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
                        <div class="flex items-center gap-4 p-3 bg-gray-100 dark:bg-gray-800">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm" :style="{ backgroundColor: getInitialsColor(employee.name) }">
                                {{ getInitials(employee.name) }}
                            </div>
                            <div class="font-medium flex-1">{{ employee.name }}</div>
                            <div
                                v-if="getEmployeeCurrentStatus(employee)"
                                class="px-2 py-1 rounded text-sm text-white shadow-sm"
                                :style="{ backgroundColor: getEmployeeCurrentStatus(employee).color }"
                            >
                                {{ getEmployeeCurrentStatus(employee).name }}
                            </div>
                        </div>
                        <div class="p-3">
                            <div class="text-sm mb-3">
                                {{ getEmployeeCurrentNotes(employee) || 'Keine Notizen' }}
                            </div>
                            <div v-if="calendarView !== 'day'">
                                <div class="text-sm font-medium mb-2">Tage:</div>
                                <div class="flex flex-wrap gap-1">
                                    <div
                                        v-for="(day, index) in getEmployeePeriodDays(employee)"
                                        :key="index"
                                        class="w-9 h-7 rounded border border-gray-200 dark:border-gray-700 flex items-center justify-center text-xs"
                                        :class="{ 'text-white font-bold': day.status }"
                                        :style="{ backgroundColor: day.status ? day.status.color : 'transparent' }"
                                        :title="day.date.format('DD.MM.YYYY') + (day.status ? ' - ' + day.status.name : ' - Nicht eingetragen')"
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

        <!-- Status Dialog -->
        <Dialog
            v-model:visible="statusDialogVisible"
            :header="`Status: ${selectedStatus ? selectedStatus.name : ''}`"
            :style="{ width: '80vw', maxWidth: '1000px' }"
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

                <div class="flex flex-wrap gap-4 justify-center">
                    <div
                        v-for="dept in statusDepartmentSummary"
                        :key="dept.name"
                        class="px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-800 shadow-sm"
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
                                {{ getInitials(employee.name) }}
                            </div>
                            <div class="font-medium flex-1">{{ employee.name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ employee.department }}</div>
                        </div>
                        <div class="p-3">
                            <div class="text-sm mb-3">
                                {{ getEmployeeCurrentNotes(employee) || 'Keine Notizen' }}
                            </div>
                            <div v-if="calendarView !== 'day'">
                                <div class="text-sm font-medium mb-3">Tage:</div>
                                    <div class="flex flex-wrap gap-1">
                                        <div
                                                v-for="(day, index) in getEmployeePeriodDays(employee)"
                                                :key="index"
                                                class="w-9 h-7 rounded border border-gray-200 dark:border-gray-700 flex items-center justify-center text-xs"
                                                :class="{ 'border-blue-500 dark:border-blue-400 border-2': day.status && day.status.value === selectedStatus.value }"
                                                :style="{ backgroundColor: day.status && day.status.value === selectedStatus.value ? day.status.color : 'transparent' }"
                                                :title="day.date.format('DD.MM.YYYY')"
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
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import weekOfYear from 'dayjs/plugin/weekOfYear';
import isoWeek from 'dayjs/plugin/isoWeek';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Dialog from 'primevue/dialog';
import VacationService from '@/Services/VacationService';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

// Initialize dayjs plugins
dayjs.extend(weekOfYear);
dayjs.extend(isoWeek);
dayjs.extend(isSameOrBefore);
dayjs.extend(isSameOrAfter);
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

const toast = useToast();

// Initialize vacationResponse and vacationError refs
const vacationResponse = ref(null);
const vacationError = ref(null);

// Daten vom Server laden
const fetchCalendarData = async () => {
    try {
        // Reguläre Kalenderdaten laden
        const response = await VacationService.getCompanyCalendarData();

        // Mitarbeiter und Abteilungen aus der regulären Antwort extrahieren
        const employeesData = response.data.employees || [];
        availableDepartments.value = Array.isArray(response.data.departments) ? response.data.departments : [];
        eventTypes.value = Array.isArray(response.data.eventTypes) ? response.data.eventTypes : [];

        // Sicherstellen, dass eventTypes ein Array ist und Urlaub und Geburtstag enthält
        if (!eventTypes.value.some(type => type.value === 'vacation')) {
            eventTypes.value.push({ name: 'Urlaub', value: 'vacation', color: '#9C27B0' });
        }

        if (!eventTypes.value.some(type => type.value === 'birthday')) {
            eventTypes.value.push({ name: 'Geburtstag', value: 'birthday', color: '#FF4500' });
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
                    return {
                        user_id: vacation.user_id,
                        date: vacation.start_date,
                        start_date: vacation.start_date,
                        end_date: vacation.end_date,
                        type: {
                            name: 'Urlaub',
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

    } catch (error) {
        console.error('Fehler beim Laden der Kalenderdaten:', error);
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Die Kalenderdaten konnten nicht geladen werden.',
            life: 3000
        });

        // Keine Fallback-Daten mehr verwenden, stattdessen leere Arrays
        employees.value = [];
        availableDepartments.value = [];
        eventTypes.value = [
            { name: 'Homeoffice', value: 'homeoffice', color: '#4CAF50' },
            { name: 'Büro', value: 'office', color: '#2196F3' },
            { name: 'Außendienst', value: 'field', color: '#FF9800' },
            { name: 'Krank', value: 'sick', color: '#F44336' },
            { name: 'Urlaub', value: 'vacation', color: '#9C27B0' },
            { name: 'Geburtstag', value: 'birthday', color: '#FF4500' },
            { name: 'Sonstiges', value: 'other', color: '#607D8B' }
        ];
    }
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
                border: '2px solid #FF4500',
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

    // Initialisiere alle Status
    if (Array.isArray(eventTypes.value)) {
        eventTypes.value.forEach(type => {
            statuses[type.value] = {
                type: type,
                count: 0,
                employees: []
            };
        });
    }

    // Zähle Mitarbeiter pro Status
    employees.value.forEach(employee => {
        const status = getEmployeeCurrentStatus(employee);
        if (status) {
            statuses[status.value].count++;
            statuses[status.value].employees.push(employee);
        }
    });

    return Object.values(statuses).filter(status => status.count > 0);
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
        const status = getEmployeeCurrentStatus(employee);
        return status && status.value === selectedStatus.value.value;
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

const getEmployeeStatusForDay = (employee, date) => {
    // Prüfen, ob es ein Wochenende ist
    const isWeekend = date.day() === 0 || date.day() === 6; // 0 = Sonntag, 6 = Samstag
    const dateStr = date.format('YYYY-MM-DD');

    // First check for exact date match
    const exactEvent = employee.events.find(e => e.date === dateStr);
    if (exactEvent) {
        // Wenn es ein Geburtstag oder ein sonstiges Ereignis ist, zeige es auch am Wochenende an
        if (exactEvent.type.value === 'birthday' || exactEvent.type.value === 'other' || exactEvent.type.value === 'sonstiges') {
            return exactEvent.type;
        }

        // Für andere Ereignistypen (wie Urlaub) prüfen, ob es ein Wochenende ist
        if (!isWeekend) {
            return exactEvent.type;
        }
    }

    // Then check for events that span multiple days
    for (const event of employee.events || []) {
        // If this is a multi-day event (has start_date and end_date)
        if (event.start_date && event.end_date) {
            const startDate = dayjs(event.start_date);
            const endDate = dayjs(event.end_date);
            if (date.isSameOrAfter(startDate, 'day') && date.isSameOrBefore(endDate, 'day')) {
                // Wenn es ein Geburtstag oder ein sonstiges Ereignis ist, zeige es auch am Wochenende an
                if (event.type.value === 'birthday' || event.type.value === 'other' || event.type.value === 'sonstiges') {
                    return event.type;
                }

                // Für andere Ereignistypen (wie Urlaub) prüfen, ob es ein Wochenende ist
                if (!isWeekend) {
                    return event.type;
                }
            }
        }
    }

    return null;
};

// Füge eine Methode hinzu, um zu prüfen, ob ein Mitarbeiter an einem bestimmten Tag Geburtstag hat
const hasBirthdayOnDay = (employee, date) => {
    if (!employee.birth_date) return false;

    const birthDate = dayjs(employee.birth_date);
    return birthDate.month() === date.month() && birthDate.date() === date.date();
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

// Prüft, ob ein Mitarbeiter im aktuellen Zeitraum (Tag/Woche/Monat) einen Eintrag hat
const hasEmployeeEventInCurrentPeriod = (employee) => {
    if (calendarView.value === 'day') {
        return getEmployeeStatusForDay(employee, currentDate.value) !== null;
    } else if (calendarView.value === 'week') {
        for (let i = 0; i < 7; i++) {
            const date = weekStart.value.add(i, 'day');
            if (getEmployeeStatusForDay(employee, date) !== null) {
                return true;
            }
        }
        return false;
    } else {
        // Monatsansicht
        for (let dayNum of daysInMonth.value) {
            if (getEmployeeStatusForMonthDay(employee, dayNum) !== null) {
                return true;
            }
        }
        return false;
    }
};

// Gibt den aktuellen Status eines Mitarbeiters zurück (abhängig von der Ansicht)
const getEmployeeCurrentStatus = (employee) => {
    if (calendarView.value === 'day') {
        return getEmployeeStatusForDay(employee, currentDate.value);
    } else if (calendarView.value === 'week') {
        // Für die Wochenansicht nehmen wir den ersten verfügbaren Status
        for (let i = 0; i < 7; i++) {
            const date = weekStart.value.add(i, 'day');
            const status = getEmployeeStatusForDay(employee, date);
            if (status) return status;
        }
        return null;
    } else {
        // Für die Monatsansicht nehmen wir den ersten verfügbaren Status
        for (let dayNum of daysInMonth.value) {
            const status = getEmployeeStatusForMonthDay(employee, dayNum);
            if (status) return status;
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

    // Fetch data from the server
    fetchCalendarData();
});
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

