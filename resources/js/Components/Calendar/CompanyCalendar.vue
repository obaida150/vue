<template>
    <div class="company-calendar" :class="{ 'dark-mode': isDarkMode }">
        <div class="calendar-header">
            <div class="calendar-controls">
                <Button icon="pi pi pi-chevron-left" @click="previousPeriod" class="p-button-rounded p-button-text" />
                <h2 class="period-title">
                    <span v-if="calendarView === 'day'">{{ formatDate(currentDate) }}</span>
                    <span v-else-if="calendarView === 'week'">KW {{ currentWeekNumber }} ({{ formatDateRange(weekStart, weekEnd) }})</span>
                    <span v-else>{{ currentMonthName }} {{ currentYear }}</span>
                </h2>
                <Button icon="pi pi pi-chevron-right" @click="nextPeriod" class="p-button-rounded p-button-text" />
            </div>
            <div class="view-controls">
                <div class="theme-toggle">
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
                <div class="view-toggle">
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

        <div class="filter-controls">
            <div class="flex justify-between items-center w-full gap-4">
                <div class="search-box flex-1">
          <span class="p-input-icon-left w-full">
            <i class="pi pi-search" />
            <InputText v-model="searchQuery" placeholder="Mitarbeiter suchen..." class="w-full" />
          </span>
                </div>
                <div class="department-filter flex-1">
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

        <!-- Zusammenfassung Karten -->
        <div class="summary-section">
            <div class="department-cards">
                <div
                    v-for="department in departmentSummary"
                    :key="department.name"
                    class="summary-card department-card"
                    @click="openDepartmentDialog(department.name)"
                >
                    <div class="summary-card-title">{{ department.name }}</div>
                    <div class="summary-card-count">{{ department.count }} Mitarbeiter</div>
                </div>
            </div>

            <div class="status-cards">
                <div
                    v-for="status in statusSummary"
                    :key="status.type.value"
                    class="summary-card status-card"
                    :style="{ borderColor: status.type.color }"
                    @click="openStatusDialog(status.type)"
                >
                    <div class="summary-card-icon" :style="{ backgroundColor: status.type.color }"></div>
                    <div class="summary-card-title">{{ status.type.name }}</div>
                    <div class="summary-card-count">{{ status.count }} Mitarbeiter</div>
                </div>
            </div>
        </div>

        <!-- Tagesansicht -->
        <div v-if="calendarView === 'day'" class="day-view">
            <h3 class="date-header">{{ formatDate(currentDate) }}</h3>

            <div class="employee-grid">
                <div class="employee-header">
                    <div class="employee-name-header">Mitarbeiter</div>
                    <div class="employee-status-header">Status</div>
                    <div class="employee-notes-header">Notizen</div>
                </div>

                <div
                    v-for="employee in filteredEmployees"
                    :key="employee.id"
                    class="employee-row"
                >
                    <div class="employee-name">
                        <div class="employee-avatar" :style="{ backgroundColor: getInitialsColor(employee.name) }">
                            {{ getInitials(employee.name) }}
                        </div>
                        <div>
                            <div class="employee-fullname">{{ employee.name }}</div>
                            <div class="employee-department">{{ employee.department }}</div>
                        </div>
                    </div>

                    <div class="employee-status">
                        <div
                            v-if="getEmployeeStatusForDay(employee, currentDate)"
                            class="status-badge"
                            :style="{ backgroundColor: getEmployeeStatusForDay(employee, currentDate).color }"
                        >
                            {{ getEmployeeStatusForDay(employee, currentDate).name }}
                        </div>
                        <div v-else class="status-badge empty">Nicht eingetragen</div>
                    </div>

                    <div class="employee-notes">
                        {{ getEmployeeNotesForDay(employee, currentDate) || '-' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Wochenansicht -->
        <div v-else-if="calendarView === 'week'" class="week-view">
            <div class="week-header">
                <div class="employee-name-header">Mitarbeiter</div>
                <div
                    v-for="(day, index) in weekDays"
                    :key="index"
                    class="day-header"
                    :class="{ 'today': day.isToday, 'weekend': day.isWeekend }"
                >
                    <div class="day-name">{{ day.dayName }}</div>
                    <div class="day-date">{{ formatDayMonth(day.date) }}</div>
                </div>
            </div>

            <div class="week-body">
                <div
                    v-for="employee in filteredEmployees"
                    :key="employee.id"
                    class="employee-week-row"
                >
                    <div class="employee-name">
                        <div class="employee-avatar" :style="{ backgroundColor: getInitialsColor(employee.name) }">
                            {{ getInitials(employee.name) }}
                        </div>
                        <div>
                            <div class="employee-fullname">{{ employee.name }}</div>
                            <div class="employee-department">{{ employee.department }}</div>
                        </div>
                    </div>

                    <div
                        v-for="(day, index) in weekDays"
                        :key="index"
                        class="day-cell"
                        :class="{ 'today': day.isToday, 'weekend': day.isWeekend }"
                    >
                        <div
                            v-if="getEmployeeStatusForDay(employee, day.date)"
                            class="status-badge"
                            :style="{ backgroundColor: getEmployeeStatusForDay(employee, day.date).color }"
                        >
                            {{ getEmployeeStatusForDay(employee, day.date).name }}
                        </div>
                        <div v-else class="status-badge empty">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monatsansicht -->
        <div v-else-if="calendarView === 'month'" class="month-view">
            <div class="month-header">
                <div class="employee-name-header">Mitarbeiter</div>
                <div
                    v-for="dayNum in daysInMonth"
                    :key="dayNum"
                    class="day-number-header"
                    :class="{
            'today': isToday(dayNum),
            'weekend': isWeekend(dayNum)
          }"
                >
                    {{ dayNum }}
                </div>
            </div>

            <div class="month-body">
                <div
                    v-for="employee in filteredEmployees"
                    :key="employee.id"
                    class="employee-month-row"
                >
                    <div class="employee-name">
                        <div class="employee-avatar" :style="{ backgroundColor: getInitialsColor(employee.name) }">
                            {{ getInitials(employee.name) }}
                        </div>
                        <div class="employee-info">
                            <div class="employee-fullname">{{ employee.name }}</div>
                            <div class="employee-department">{{ employee.department }}</div>
                        </div>
                    </div>

                    <div
                        v-for="dayNum in daysInMonth"
                        :key="dayNum"
                        class="month-day-cell"
                        :class="{
              'today': isToday(dayNum),
              'weekend': isWeekend(dayNum)
            }"
                    >
                        <div
                            v-if="getEmployeeStatusForMonthDay(employee, dayNum)"
                            class="status-indicator"
                            :style="{ backgroundColor: getEmployeeStatusForMonthDay(employee, dayNum).color }"
                            :title="getEmployeeStatusForMonthDay(employee, dayNum).name"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legende -->
        <div class="legend">
            <div class="legend-title">Legende:</div>
            <div class="legend-items">
                <div
                    v-for="type in eventTypes"
                    :key="type.value"
                    class="legend-item"
                    @click="openStatusDialog(type)"
                >
                    <div class="legend-color" :style="{ backgroundColor: type.color }"></div>
                    <div class="legend-label">{{ type.name }}</div>
                </div>
            </div>
        </div>

        <!-- Abteilungs-Dialog -->
        <Dialog
            v-model:visible="departmentDialogVisible"
            :header="`Abteilung: ${selectedDepartment}`"
            :style="{ width: '80vw', maxWidth: '1000px' }"
            :modal="true"
            :closable="true"
            :closeOnEscape="true"
        >
            <div class="dialog-content">
                <div class="dialog-period">
                    <span v-if="calendarView === 'day'">{{ formatDate(currentDate) }}</span>
                    <span v-else-if="calendarView === 'week'">KW {{ currentWeekNumber }} ({{ formatDateRange(weekStart, weekEnd) }})</span>
                    <span v-else>{{ currentMonthName }} {{ currentYear }}</span>
                </div>

                <div class="dialog-summary">
                    <div class="dialog-summary-item" v-for="status in departmentStatusSummary" :key="status.type.value">
                        <div class="dialog-summary-icon" :style="{ backgroundColor: status.type.color }"></div>
                        <div class="dialog-summary-label">{{ status.type.name }}: {{ status.count }}</div>
                    </div>
                </div>

                <div class="dialog-employees">
                    <div class="dialog-employee" v-for="employee in departmentEmployees" :key="employee.id">
                        <div class="dialog-employee-header">
                            <div class="employee-avatar" :style="{ backgroundColor: getInitialsColor(employee.name) }">
                                {{ getInitials(employee.name) }}
                            </div>
                            <div class="dialog-employee-name">{{ employee.name }}</div>
                            <div
                                v-if="getEmployeeCurrentStatus(employee)"
                                class="status-badge"
                                :style="{ backgroundColor: getEmployeeCurrentStatus(employee).color }"
                            >
                                {{ getEmployeeCurrentStatus(employee).name }}
                            </div>
                        </div>
                        <div class="dialog-employee-details">
                            <div class="dialog-employee-notes">
                                {{ getEmployeeCurrentNotes(employee) || 'Keine Notizen' }}
                            </div>
                            <div class="dialog-employee-period" v-if="calendarView !== 'day'">
                                <div class="dialog-period-label">Tage:</div>
                                <div class="dialog-employee-days">
                                    <div
                                        v-for="(day, index) in getEmployeePeriodDays(employee)"
                                        :key="index"
                                        class="dialog-day-indicator"
                                        :class="{ 'has-status': day.status }"
                                        :style="{ backgroundColor: day.status ? day.status.color : 'transparent' }"
                                        :title="day.date.format('DD.MM.YYYY') + (day.status ? ' - ' + day.status.name : ' - Nicht eingetragen')"
                                    >
                                        {{ day.date.format('DD') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- Status-Dialog -->
        <Dialog
            v-model:visible="statusDialogVisible"
            :header="`Status: ${selectedStatus ? selectedStatus.name : ''}`"
            :style="{ width: '80vw', maxWidth: '1000px' }"
            :modal="true"
            :closable="true"
            :closeOnEscape="true"
        >
            <div class="dialog-content">
                <div class="dialog-period">
                    <span v-if="calendarView === 'day'">{{ formatDate(currentDate) }}</span>
                    <span v-else-if="calendarView === 'week'">KW {{ currentWeekNumber }} ({{ formatDateRange(weekStart, weekEnd) }})</span>
                    <span v-else>{{ currentMonthName }} {{ currentYear }}</span>
                </div>

                <div class="dialog-summary">
                    <div class="dialog-summary-item" v-for="dept in statusDepartmentSummary" :key="dept.name">
                        <div class="dialog-summary-label">{{ dept.name }}: {{ dept.count }}</div>
                    </div>
                </div>

                <div class="dialog-employees">
                    <div class="dialog-employee" v-for="employee in statusEmployees" :key="employee.id">
                        <div class="dialog-employee-header">
                            <div class="employee-avatar" :style="{ backgroundColor: getInitialsColor(employee.name) }">
                                {{ getInitials(employee.name) }}
                            </div>
                            <div class="dialog-employee-name">{{ employee.name }}</div>
                            <div class="dialog-employee-department">{{ employee.department }}</div>
                        </div>
                        <div class="dialog-employee-details">
                            <div class="dialog-employee-notes">
                                {{ getEmployeeCurrentNotes(employee) || 'Keine Notizen' }}
                            </div>
                            <div class="dialog-employee-period" v-if="calendarView !== 'day'">
                                <div class="dialog-employee-days">
                                    <div
                                        v-for="(day, index) in getEmployeePeriodDays(employee)"
                                        :key="index"
                                        class="dialog-day-indicator"
                                        :class="{ 'active': day.status && day.status.value === selectedStatus.value }"
                                        :style="{ backgroundColor: day.status && day.status.value === selectedStatus.value ? day.status.color : 'transparent' }"
                                        :title="day.date.format('DD.MM.YYYY')"
                                    ></div>
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

// Daten vom Server laden
const fetchCalendarData = async () => {
    try {
        // Reguläre Kalenderdaten laden
        const response = await VacationService.getCompanyCalendarData();

        // Urlaubsanträge laden
        const vacationResponse = await axios.get('/api/vacation/all-requests');

        // Mitarbeiter und Abteilungen aus der regulären Antwort extrahieren
        const employeesData = response.data.employees || [];
        availableDepartments.value = Array.isArray(response.data.departments) ? response.data.departments : [];
        eventTypes.value = Array.isArray(response.data.eventTypes) ? response.data.eventTypes : [];

        // Sicherstellen, dass eventTypes ein Array ist und Urlaub enthält
        if (!eventTypes.value.some(type => type.value === 'vacation')) {
            eventTypes.value.push({ name: 'Urlaub', value: 'vacation', color: '#9C27B0' });
        }

        // Urlaubsanträge in das richtige Format umwandeln
        const vacationEvents = vacationResponse.data
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

        // Aktualisiere die Mitarbeiterliste
        employees.value = employeesData;

    } catch (error) {
        console.error('Fehler beim Laden der Kalenderdaten:', error);
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Die Kalenderdaten konnten nicht geladen werden. Verwende Fallback-Daten.',
            life: 3000
        });

        // Fallback-Daten
        eventTypes.value = [
            { name: 'Homeoffice', value: 'homeoffice', color: '#4CAF50' },
            { name: 'Büro', value: 'office', color: '#2196F3' },
            { name: 'Außendienst', value: 'field', color: '#FF9800' },
            { name: 'Krank', value: 'sick', color: '#F44336' },
            { name: 'Urlaub', value: 'vacation', color: '#9C27B0' },
            { name: 'Sonstiges', value: 'other', color: '#607D8B' }
        ];

        employees.value = [
            {
                id: 1,
                name: 'Max Mustermann',
                department: 'Entwicklung',
                events: [
                    { date: dayjs().format('YYYY-MM-DD'), type: eventTypes.value[0], notes: 'Arbeitet an Projekt A' },
                    { date: dayjs().add(1, 'day').format('YYYY-MM-DD'), type: eventTypes.value[0], notes: 'Arbeitet an Projekt A' }
                ]
            },
            {
                id: 2,
                name: 'Anna Schmidt',
                department: 'Marketing',
                events: [
                    { date: dayjs().format('YYYY-MM-DD'), type: eventTypes.value[1], notes: 'Meeting mit Kunden' },
                    { date: dayjs().add(1, 'day').format('YYYY-MM-DD'), type: eventTypes.value[2], notes: 'Kundenbesuch' }
                ]
            },
            {
                id: 3,
                name: 'Thomas Müller',
                department: 'Vertrieb',
                events: [
                    { date: dayjs().format('YYYY-MM-DD'), type: eventTypes.value[2], notes: 'Kundenakquise' },
                    { date: dayjs().add(2, 'day').format('YYYY-MM-DD'), type: eventTypes.value[1], notes: 'Teammeeting' }
                ]
            }
        ];

        availableDepartments.value = [
            { id: 1, name: 'Entwicklung' },
            { id: 2, name: 'Marketing' },
            { id: 3, name: 'Vertrieb' },
            { id: 4, name: 'Personal' },
            { id: 5, name: 'Finanzen' }
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
        // Wenn es ein Wochenende ist und der Event-Typ Urlaub ist, dann ignorieren
        if (isWeekend && exactEvent.type.value === 'vacation') {
            return null;
        }
        return exactEvent.type;
    }

    // Then check for events that span multiple days
    for (const event of employee.events || []) {
        // Wenn es ein Wochenende ist und der Event-Typ Urlaub ist, dann ignorieren
        if (isWeekend && event.type && event.type.value === 'vacation') {
            continue;
        }

        // If this is a multi-day event (has start_date and end_date)
        if (event.start_date && event.end_date) {
            const startDate = dayjs(event.start_date);
            const endDate = dayjs(event.end_date);
            if (date.isSameOrAfter(startDate, 'day') && date.isSameOrBefore(endDate, 'day')) {
                return event.type;
            }
        }
    }

    return null;
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
.company-calendar {
    font-family: var(--font-family);
    background-color: var(--surface-a);
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    padding: 1.5rem;
    width: 100%;
    overflow-x: auto;
    transition: all 0.3s ease;
}

/* Dark Mode Styles */
.company-calendar.dark-mode {
    background-color: #1e1e1e;
    color: #f0f0f0;
}

.dark-mode .employee-grid,
.dark-mode .week-header,
.dark-mode .week-body,
.dark-mode .month-header,
.dark-mode .month-body,
.dark-mode .summary-card,
.dark-mode .dialog-employee {
    border-color: #444;
}

.dark-mode .employee-header,
.dark-mode .day-header,
.dark-mode .day-number-header {
    background-color: #333;
    color: #f0f0f0;
    border-color: #444;
}

.dark-mode .employee-row,
.dark-mode .employee-week-row,
.dark-mode .employee-month-row {
    border-color: #444;
}

.dark-mode .employee-row:nth-child(even),
.dark-mode .employee-week-row:nth-child(even),
.dark-mode .employee-month-row:nth-child(even) {
    background-color: #2a2a2a;
}

.dark-mode .day-cell,
.dark-mode .month-day-cell {
    border-color: #444;
}

.dark-mode .day-cell.weekend,
.dark-mode .month-day-cell.weekend,
.dark-mode .day-header.weekend,
.dark-mode .day-number-header.weekend {
    background-color: #2a2a2a;
}

.dark-mode .day-cell.today,
.dark-mode .month-day-cell.today,
.dark-mode .day-header.today,
.dark-mode .day-number-header.today {
    background-color: rgba(59, 130, 246, 0.2);
}

.dark-mode .employee-department,
.dark-mode .day-date {
    color: #aaa;
}

.dark-mode .status-badge.empty {
    background-color: #444;
    color: #aaa;
}

.dark-mode .legend,
.dark-mode .summary-card {
    background-color: #333;
    border-color: #444;
}

.dark-mode .dialog-employee-header {
    background-color: #2a2a2a;
}

.dark-mode .dialog-employee:hover {
    background-color: #2a2a2a;
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.calendar-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.period-title {
    margin: 0;
    font-size: 1.5rem;
    text-transform: capitalize;
    font-weight: 600;
}

.view-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.theme-toggle {
    margin-right: 0.5rem;
}

.view-toggle {
    display: flex;
    gap: 0.25rem;
}

/* Summary Section Styles */
.summary-section {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.department-cards, .status-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.summary-card {
    flex: 1;
    min-width: 150px;
    padding: 1rem;
    border-radius: var(--border-radius);
    background-color: var(--surface-b);
    border: 1px solid var(--surface-d);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.department-card {
    border-left: 4px solid var(--primary-color);
}

.status-card {
    border-left-width: 4px;
}

.summary-card-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    margin-bottom: 0.5rem;
}

.summary-card-title {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.summary-card-count {
    font-size: 0.85rem;
    color: var(--text-color-secondary);
}

/* Day View Styles */
.day-view {
    width: 100%;
}

.date-header {
    margin-bottom: 1rem;
    font-size: 1.2rem;
    text-align: center;
    font-weight: 500;
}

.employee-grid {
    display: flex;
    flex-direction: column;
    width: 100%;
    border: 1px solid var(--surface-d);
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.employee-header {
    display: flex;
    background-color: var(--surface-c);
    font-weight: bold;
    padding: 0.75rem;
}

.employee-name-header {
    flex: 2;
    padding: 0 0.5rem;
}

.employee-status-header,
.employee-notes-header {
    flex: 1;
    padding: 0 0.5rem;
}

.employee-row {
    display: flex;
    border-top: 1px solid var(--surface-d);
    padding: 0.75rem;
    transition: background-color 0.2s;
}

.employee-row:hover {
    background-color: var(--surface-hover);
}

.employee-row:nth-child(even) {
    background-color: var(--surface-b);
}

.employee-row:nth-child(even):hover {
    background-color: var(--surface-hover);
}

.employee-name {
    flex: 2;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0 0.5rem;
}

.employee-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.employee-fullname {
    font-weight: 500;
}

.employee-department {
    font-size: 0.85rem;
    color: var(--text-color-secondary);
}

.employee-status,
.employee-notes {
    flex: 1;
    padding: 0 0.5rem;
    display: flex;
    align-items: center;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
    color: white;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.status-badge.empty {
    background-color: var(--surface-d);
    color: var(--text-color-secondary);
}

/* Week View Styles */
.week-view {
    width: 100%;
    overflow-x: auto;
    border-radius: var(--border-radius);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--surface-d);
}

.week-header {
    display: flex;
    min-width: 800px;
}

.week-header .employee-name-header {
    width: 200px;
    min-width: 200px;
    padding: 0.75rem;
    background-color: var(--surface-c);
    font-weight: bold;
}

.day-header {
    flex: 1;
    min-width: 100px;
    padding: 0.75rem;
    text-align: center;
    background-color: var(--surface-c);
    font-weight: bold;
    border-left: 1px solid var(--surface-d);
    transition: background-color 0.2s;
}

.day-header.today {
    background-color: var(--primary-50);
}

.day-header.weekend {
    background-color: var(--surface-d);
}

.day-name {
    font-weight: bold;
}

.day-date {
    font-size: 0.85rem;
    color: var(--text-color-secondary);
}

.week-body {
    display: flex;
    flex-direction: column;
    min-width: 800px;
}

.employee-week-row {
    display: flex;
    border-top: 1px solid var(--surface-d);
    transition: background-color 0.2s;
}

.employee-week-row:hover {
    background-color: var(--surface-hover);
}

.employee-week-row:nth-child(even) {
    background-color: var(--surface-b);
}

.employee-week-row:nth-child(even):hover {
    background-color: var(--surface-hover);
}

.employee-week-row .employee-name {
    width: 200px;
    min-width: 200px;
    padding: 0.75rem;
}

.day-cell {
    flex: 1;
    min-width: 100px;
    padding: 0.75rem;
    text-align: center;
    border-left: 1px solid var(--surface-d);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s;
}

.day-cell:hover {
    background-color: var(--surface-hover);
}

.day-cell.today {
    background-color: var(--primary-50);
}

.day-cell.weekend {
    background-color: var(--surface-d);
}

/* Month View Styles */
.month-view {
    width: 100%;
    overflow-x: auto;
    border-radius: var(--border-radius);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--surface-d);
}

.month-header {
    display: flex;
    min-width: 1200px;
}

.month-header .employee-name-header {
    width: 200px;
    min-width: 200px;
    padding: 0.5rem;
    background-color: var(--surface-c);
    font-weight: bold;
}

.day-number-header {
    width: 30px;
    min-width: 30px;
    padding: 0.5rem 0;
    text-align: center;
    background-color: var(--surface-c);
    font-weight: bold;
    border-left: 1px solid var(--surface-d);
    font-size: 0.85rem;
    transition: background-color 0.2s;
}

.day-number-header.today {
    background-color: var(--primary-50);
}

.day-number-header.weekend {
    background-color: var(--surface-d);
}

.month-body {
    display: flex;
    flex-direction: column;
    min-width: 1200px;
}

.employee-month-row {
    display: flex;
    border-top: 1px solid var(--surface-d);
    transition: background-color 0.2s;
}

.employee-month-row:hover {
    background-color: var(--surface-hover);
}

.employee-month-row:nth-child(even) {
    background-color: var(--surface-b);
}

.employee-month-row:nth-child(even):hover {
    background-color: var(--surface-hover);
}

.employee-month-row .employee-name {
    width: 200px;
    min-width: 200px;
    padding: 0.5rem;
}

.employee-info {
    overflow: hidden;
}

.month-day-cell {
    width: 30px;
    min-width: 30px;
    height: 30px;
    border-left: 1px solid var(--surface-d);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s;
}

.month-day-cell:hover {
    background-color: var(--surface-hover);
}

.month-day-cell.today {
    background-color: var(--primary-50);
}

.month-day-cell.weekend {
    background-color: var(--surface-d);
}

.status-indicator {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Legend Styles */
.legend {
    margin-top: 1.5rem;
    padding: 1rem;
    border: 1px solid var(--surface-d);
    border-radius: var(--border-radius);
    background-color: var(--surface-b);
}

.legend-title {
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.legend-items {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.legend-item:hover {
    background-color: var(--surface-hover);
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 50%;
}

.legend-label {
    font-size: 0.85rem;
}

/* Dialog Styles */
.dialog-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.dialog-period {
    font-size: 1.2rem;
    font-weight: 500;
    text-align: center;
}

.dialog-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
}

.dialog-summary-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    background-color: var(--surface-b);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.dialog-summary-icon {
    width: 16px;
    height: 16px;
    border-radius: 50%;
}

.dialog-summary-label {
    font-size: 0.9rem;
    font-weight: 500;
}

.dialog-employees {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.dialog-employee {
    border: 1px solid var(--surface-d);
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.dialog-employee:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.dialog-employee-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    background-color: var(--surface-c);
}

.dialog-employee-name {
    font-weight: 500;
    flex: 1;
}

.dialog-employee-department {
    font-size: 0.85rem;
    color: var(--text-color-secondary);
    margin-right: 1rem;
}

.dialog-employee-details {
    padding: 0.75rem;
}

.dialog-employee-notes {
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
}

.dialog-employee-period {
    margin-top: 0.5rem;
}

.dialog-period-label {
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.dialog-employee-days {
    display: flex;
    gap: 4px;
}

.dialog-day-indicator {
    width: 28px;
    height: 28px;
    border-radius: 4px;
    border: 1px solid var(--surface-d);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    color: var(--text-color);
}

.dialog-day-indicator.has-status {
    color: white;
    font-weight: bold;
}

.dialog-day-indicator.active {
    border-width: 2px;
    border-color: var(--primary-color);
}

/* Responsive Styles */
@media (max-width: 1024px) {
    .calendar-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .view-controls {
        width: 100%;
        justify-content: space-between;
    }

    .filter-controls {
        flex-direction: column;
    }

    .search-box,
    .department-filter {
        width: 100%;
    }

    .summary-section {
        flex-direction: column;
    }

    .department-cards,
    .status-cards {
        flex-direction: column;
    }

    .summary-card {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .employee-name {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .employee-row {
        flex-direction: column;
        gap: 0.5rem;
    }

    .employee-status,
    .employee-notes {
        padding-left: 3rem;
    }

    .legend-items {
        flex-direction: column;
        gap: 0.5rem;
    }

    .dialog-employee-header {
        flex-wrap: wrap;
    }

    .day-view,
    .week-view,
    .month-view {
        overflow-x: auto;
    }
}

/* Filter Controls Styles */
.filter-controls {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.search-box,
.department-filter {
    flex: 1;
    min-width: 250px;
}

.dark-mode .p-inputtext {
    background-color: #333;
    color: #f0f0f0;
    border-color: #555;
}

.dark-mode .p-inputtext::placeholder {
    color: #aaa;
}

.dark-mode .p-multiselect {
    background-color: #333;
    color: #f0f0f0;
    border-color: #555;
}

.dark-mode .p-multiselect-label {
    color: #f0f0f0;
}

.dark-mode .p-multiselect-panel {
    background-color: #333;
    color: #f0f0f0;
    border-color: #555;
}

.dark-mode .p-multiselect-item {
    color: #f0f0f0;
}

.dark-mode .p-multiselect-item:hover {
    background-color: #444;
}

.dark-mode .p-multiselect-item.p-highlight {
    background-color: var(--primary-color);
    color: var(--primary-color-text);
}

/* Dialog Styles for Dark Mode */
.dark-mode .p-dialog {
    background-color: #1e1e1e;
    color: #f0f0f0;
}

.dark-mode .p-dialog-header {
    background-color: #333;
    color: #f0f0f0;
    border-color: #444;
}

.dark-mode .p-dialog-content {
    background-color: #1e1e1e;
    color: #f0f0f0;
}

.dark-mode .dialog-summary-item {
    background-color: #333;
}

.dark-mode .dialog-day-indicator {
    border-color: #555;
}

/* Verbesserte Responsive Styles */
@media (max-width: 640px) {
    .calendar-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .view-controls {
        margin-top: 1rem;
        width: 100%;
        justify-content: space-between;
    }

    .filter-controls {
        flex-direction: column;
    }

    .search-box,
    .department-filter {
        width: 100%;
        margin-bottom: 0.5rem;
    }

    .employee-row {
        flex-direction: column;
    }

    .employee-status,
    .employee-notes {
        margin-top: 0.5rem;
        padding-left: 0;
    }

    .day-cell,
    .month-day-cell {
        min-width: 40px;
    }

    .employee-name {
        min-width: 150px;
    }

    .legend-items {
        flex-wrap: wrap;
    }
}

/* Hervorhebung des aktuellen Tages */
.day-cell.today,
.month-day-cell.today,
.day-header.today,
.day-number-header.today {
    background-color: rgba(var(--primary-rgb, 59, 130, 246), 0.15);
    font-weight: bold;
    border: 1px solid rgba(var(--primary-rgb, 59, 130, 246), 0.5);
}

.dark-mode .day-cell.today,
.dark-mode .month-day-cell.today,
.dark-mode .day-header.today,
.dark-mode .day-number-header.today {
    background-color: rgba(var(--primary-rgb, 59, 130, 246), 0.25);
    border-color: rgba(var(--primary-rgb, 59, 130, 246), 0.6);
}
</style>

