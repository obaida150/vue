<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p:6 w-full overflow-hidden">
        <Toast />
        <!-- Header mit Navigation -->
        <CalendarHeader
            :calendar-view="calendarView"
            :current-date="currentDate"
            :year-layout="yearLayout"
            :is-team-manager="isTeamManager"
            :show-only-own-events="showOnlyOwnEvents"
            @previous="previousPeriod"
            @next="nextPeriod"
            @set-view="setCalendarView"
            @set-layout="yearLayout = $event"
            @toggle-event-filter="toggleEventFilter"
        />

        <!-- Monatsansicht -->
        <MonthlyView
            v-if="calendarView === 'month'"
            :current-date="currentDate"
            :weekdays="weekdays"
            :holidays="holidays"
            :events="events"
            :vacations="vacations"
            :isHoliday="isHoliday"
            :get-holiday-name="getHolidayName"
            :has-events="hasEvents"
            :has-vacations="hasVacations"
            :get-events-for-day="getEventsForDay"
            :get-vacations-for-day="getVacationsForDay"
            :truncate-text="truncateText"
            :is-hr-user="isHrUser"
            :is-team-manager="isTeamManager"
            :show-only-own-events="showOnlyOwnEvents"
            :current-user-id="currentUserId"
            @week-plan="openWeekPlanDialog"
            @day-click="handleDayClick"
            @event-click="openEventDetailsDialog"
            @vacation-click="openVacationDetailsDialog"
        />

        <!-- Jahresansicht -->
        <YearlyView
            v-else
            :current-date="currentDate"
            :year-layout="yearLayout"
            :weekdays-short="weekdaysShort"
            :holidays="holidays"
            :events="events"
            :vacations="vacations"
            :isHoliday="isHoliday"
            :has-events="hasEvents"
            :has-vacations="hasVacations"
            :get-event-color-for-day="getEventColorForDay"
            :get-month-name="getMonthName"
            :get-weeks-in-month-for-mini="getWeeksInMonthForMini"
            :current-user-id="currentUserId"
            @month-click="goToMonth"
            @week-plan="openWeekPlanDialog"
        />

        <!-- Dialoge -->
        <EventDialog
            :visible="eventDialogVisible"
            @update:visible="eventDialogVisible = $event"
            :event="newEvent"
            :event-types="filteredEventTypes"
            :disabled-dates="disabledDates"
            :locale="de"
            :saving="saving"
            :is-hr="isHrUser"
            :employees="employees"
            @close="closeEventDialog"
            @save="saveEvent"
        />

        <EventDetailsDialog
            :visible="eventDetailsDialogVisible"
            @update:visible="eventDetailsDialogVisible = $event"
            :event="selectedEvent"
            :get-status-label="getStatusLabel"
            :get-status-severity="getStatusSeverity"
            :format-date="formatDate"
            :is-hr="isHrUser"
            :is-team-manager="isTeamManager"
            :current-user-id="currentUserId"
            @close="closeEventDetailsDialog"
            @edit="editEvent"
            @delete="confirmDeleteEvent"
        />

        <VacationDetailsDialog
            :visible="vacationDetailsDialogVisible"
            @update:visible="vacationDetailsDialogVisible = $event"
            :vacation="selectedVacation"
            :get-status-label="getStatusLabel"
            :get-status-severity="getStatusSeverity"
            :format-date="formatDate"
            @close="closeVacationDetailsDialog"
            @navigate="navigateToVacationManagement"
        />

        <DeleteConfirmationDialog
            :visible="deleteConfirmationVisible"
            @update:visible="deleteConfirmationVisible = $event"
            :deleting="deleting"
            @cancel="cancelDeleteEvent"
            @delete="deleteEvent"
        />

        <WeekPlanDialog
            :visible="weekPlanDialogVisible"
            @update:visible="weekPlanDialogVisible = $event"
            :week-number="selectedWeekNumber || 0"
            :week-days="weekDays"
            :weekdays="weekdays"
            :event-types="filteredEventTypes"
            :saving="savingWeekPlan"
            :is-holiday="isHoliday"
            :get-holiday-name="getHolidayName"
            :has-vacations="hasVacations"
            :format-date="formatDate"
            :is-hr="isHrUser"
            :employees="employees"
            @close="closeWeekPlanDialog"
            @save="saveWeekPlan"
            @remove-event="removeWeekDayEvent"
        />

        <HolidayInfoDialog
            :visible="holidayInfoVisible"
            @update:visible="holidayInfoVisible = $event"
            :holiday-name="selectedHolidayName"
        />

        <!-- Legende -->
        <CalendarLegend :event-types="filteredEventTypes" />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import weekday from 'dayjs/plugin/weekday';
import weekOfYear from 'dayjs/plugin/weekOfYear';
import isBetween from 'dayjs/plugin/isBetween';
import isSameOrBefore from 'dayjs/plugin/isSameOrAfter';
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';
import isToday from 'dayjs/plugin/isToday';
import axios from 'axios';
import HolidayService from '@/Services/holiday-service';
import VacationService from '@/Services/VacationService';
import { usePrimeVue } from 'primevue/config';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import ToggleButton from 'primevue/togglebutton';

// Komponenten importieren
import CalendarHeader from './CalendarHeader.vue';
import MonthlyView from './MonthlyView.vue';
import YearlyView from './YearlyView.vue';
import EventDialog from './dialogs/EventDialog.vue';
import EventDetailsDialog from './dialogs/EventDetailsDialog.vue';
import VacationDetailsDialog from './dialogs/VacationDetailsDialog.vue';
import DeleteConfirmationDialog from './dialogs/DeleteConfirmationDialog.vue';
import WeekPlanDialog from './dialogs/WeekPlanDialog.vue';
import HolidayInfoDialog from './dialogs/HolidayInfoDialog.vue';
import CalendarLegend from './CalendarLegend.vue';

// Deutsche Lokalisierung für PrimeVue
const de = {
    firstDayOfWeek: 1,
    dayNames: ["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag"],
    dayNamesShort: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
    dayNamesMin: ["So", "Mo", "Di", "Mi", "Do", "Mi", "Fr", "Sa"],
    monthNames: ["Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember"],
    monthNamesShort: ["Jan", "Feb", "Mär", "Apr", "Mai", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dez"],
    today: "Heute",
    clear: "Löschen",
    weekHeader: "KW",
    dateFormat: "dd.mm.yy",
    firstDay: 1
};

// Globale PrimeVue Instanz für Lokalisierung
const primevue = usePrimeVue();

// Navigation-Hilfe
const navigateTo = (path) => {
    if (typeof window !== 'undefined') {
        window.location.href = path;
    }
};

// Toast-Meldungen
const toast = useToast();

// dayjs mit Plugins konfigurieren
dayjs.locale('de');
dayjs.extend(weekday);
dayjs.extend(weekOfYear);
dayjs.extend(isBetween);
dayjs.extend(isSameOrBefore);
dayjs.extend(isSameOrAfter);
dayjs.extend(isToday);

// Zustands-Variablen
const currentDate = ref(dayjs());
const calendarView = ref('month');
const yearLayout = ref('6x2');
const events = ref([]);
const vacations = ref([]);
const loading = ref(false);
const currentUserRoleId = ref(null); // Variable für die Benutzerrolle
const employees = ref([]); // Neue Variable für die Mitarbeiterliste
const currentUserId = ref(null); // Variable für die aktuelle Benutzer-ID
const isTeamManager = ref(false); // Variable für Abteilungsleiter-Status
const userTeamId = ref(null); // Variable für die Team-ID des Benutzers

// Feiertage
const holidays = ref([]);
const isLoadingHolidays = ref(false);
const selectedHolidayName = ref('');

// Event Dialog
const newEvent = ref({
    title: '',
    type: null,
    startDate: null,
    endDate: null,
    isAllDay: true,
    description: '',
    user_id: null // Neue Eigenschaft für den ausgewählten Mitarbeiter
});
const saving = ref(false);
const isEditMode = ref(false);
const showOnlyOwnEvents = ref(false); // Standardmäßig alle Ereignisse anzeigen

// Event Details
const selectedEvent = ref(null);

// Vacation Details
const selectedVacation = ref(null);

// Delete Confirmation
const deleting = ref(false);

// Week Plan
const selectedWeekNumber = ref(null);
const weekDays = ref([]);
const savingWeekPlan = ref(false);

// Konstanten
const weekdays = ref(['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag']);
const weekdaysShort = ref(['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa']);
const eventTypes = ref([]);
const disabledDates = ref([]);

// Computed Properties
const currentMonthName = computed(() => currentDate.value.format('MMMM'));
const currentYear = computed(() => currentDate.value.year());

const eventDialogVisible = ref(false);
const eventDetailsDialogVisible = ref(false);
const vacationDetailsDialogVisible = ref(false);
const deleteConfirmationVisible = ref(false);
const weekPlanDialogVisible = ref(false);
const holidayInfoVisible = ref(false);

// Prüfen, ob der aktuelle Benutzer HR-Rolle hat
const isHrUser = computed(() => currentUserRoleId.value === 2);

// Prüft, ob der Benutzer ein Ereignis bearbeiten darf
const canEditEvent = computed(() => {
    return (event) => {
        // Debug-Ausgabe
        console.log('canEditEvent Check:', {
            event_id: event.id,
            event_user_id: event.user_id,
            event_team_id: event.team_id,
            current_user_id: currentUserId.value,
            is_hr: isHrUser.value,
            is_team_manager: isTeamManager.value,
            user_team_id: userTeamId.value
        });

        // HR-Benutzer dürfen alle Ereignisse bearbeiten
        if (isHrUser.value) return true;

        // Eigene Ereignisse darf jeder bearbeiten
        if (event.user_id === currentUserId.value) return true;

        // Abteilungsleiter dürfen Ereignisse ihrer Teammitglieder bearbeiten
        if (isTeamManager.value && userTeamId.value) {
            // Prüfen, ob das Ereignis zum Team des Abteilungsleiters gehört
            return event.team_id === userTeamId.value;
        }

        return false;
    };
});

// Gefilterte Event-Typen basierend auf Benutzerrolle
const filteredEventTypes = computed(() => {
    if (!eventTypes.value) return [];

    return eventTypes.value.filter(type => {
        // Wenn der Event-Typ "Krankheit" ist und der Benutzer nicht die HR-Rolle hat (role_id != 2),
        // dann diesen Typ nicht anzeigen
        if (type.name === 'Krankheit' && currentUserRoleId.value !== 2) {
            return false;
        }
        return true;
    });
});

/**
 * Deaktivierte Daten aktualisieren (Feiertage)
 */
const updateDisabledDates = () => {
    disabledDates.value = holidays.value.map(holiday => holiday.date.toDate());
};

/**
 * Feiertage für ein bestimmtes Jahr laden
 */
const fetchHolidays = async (year) => {
    isLoadingHolidays.value = true;
    try {
        holidays.value = await HolidayService.getHolidays(year);
        updateDisabledDates();
    } catch (error) {
        console.error('Fehler beim Laden der Feiertage:', error);
    } finally {
        isLoadingHolidays.value = false;
    }
};

/**
 * Prüft, ob ein Datum ein Feiertag ist
 */
const isHoliday = (date) => {
    return HolidayService.isHoliday(date, holidays.value);
};

/**
 * Gibt den Namen eines Feiertags zurück
 */
const getHolidayName = (date) => {
    return HolidayService.getHolidayName(date, holidays.value);
};

/**
 * Zeigt Informationen zu einem Feiertag an
 */
const showHolidayInfo = (date) => {
    const holidayName = getHolidayName(dayjs(date));
    if (holidayName) {
        selectedHolidayName.value = holidayName;
        holidayInfoVisible.value = true;
    }
};

/**
 * Ereignistypen vom Server laden
 */
const fetchEventTypes = async () => {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const config = {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            withCredentials: true
        };

        const response = await axios.get('/api/event-types', config);
        eventTypes.value = response.data.map(type => ({
            id: type.id,
            name: type.name,
            value: type.name.toLowerCase(),
            color: type.color,
            requires_approval: type.requires_approval
        }));
    } catch (error) {
        console.error('Fehler beim Laden der Ereignistypen:', error);
        // Fallback-Werte
        eventTypes.value = [
            { id: 1, name: 'Homeoffice', value: 'homeoffice', color: '#4CAF50', requires_approval: true },
            { id: 2, name: 'Büro', value: 'office', color: '#2196F3', requires_approval: false },
            { id: 3, name: 'Außendienst', value: 'field', color: '#FF9800', requires_approval: true },
            { id: 4, name: 'Krank', value: 'sick', color: '#F44336', requires_approval: false },
            { id: 5, name: 'Urlaub', value: 'vacation', color: '#9C27B0', requires_approval: true },
            { id: 6, name: 'Sonstiges', value: 'other', color: '#607D8B', requires_approval: true }
        ];
    }
};

/**
 * Benutzerrolle vom Server laden
 */
const fetchUserRole = async () => {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const config = {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            withCredentials: true
        };

        const response = await axios.get('/api/user/role', config);
        currentUserRoleId.value = response.data.role_id;
        currentUserId.value = response.data.user_id;
        isTeamManager.value = response.data.is_team_manager || false;
        userTeamId.value = response.data.team_id || null;

        // Debug-Ausgabe
        console.log('Benutzerrolle geladen:', {
            role_id: currentUserRoleId.value,
            user_id: currentUserId.value,
            is_team_manager: isTeamManager.value,
            team_id: userTeamId.value
        });

        // Wenn der Benutzer ein Abteilungsleiter ist, lade die Teammitglieder
        if (isTeamManager.value && response.data.team_members) {
            employees.value = response.data.team_members;
        }
    } catch (error) {
        console.error('Fehler beim Laden der Benutzerrolle:', error);
        currentUserRoleId.value = null;
        currentUserId.value = null;
        isTeamManager.value = false;
        userTeamId.value = null;
    }
};

/**
 * Mitarbeiterliste vom Server laden
 */
const fetchEmployees = async () => {
    // Wenn bereits Teammitglieder geladen wurden und der Benutzer ein Abteilungsleiter ist, nicht erneut laden
    if (isTeamManager.value && employees.value.length > 0) return;

    // Nur für HR-Benutzer oder Abteilungsleiter laden
    if (!isHrUser.value && !isTeamManager.value) return;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const config = {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            withCredentials: true
        };

        const response = await axios.get('/api/employees', config);
        employees.value = response.data;
    } catch (error) {
        console.error('Fehler beim Laden der Mitarbeiterliste:', error);
        employees.value = [];
    }
};

/**
 * Navigation zum vorherigen Monat/Jahr
 */
const previousPeriod = () => {
    if (calendarView.value === 'month') {
        currentDate.value = currentDate.value.subtract(1, 'month');
    } else {
        currentDate.value = currentDate.value.subtract(1, 'year');
    }
    fetchEvents();
};

/**
 * Navigation zum nächsten Monat/Jahr
 */
const nextPeriod = () => {
    if (calendarView.value === 'month') {
        currentDate.value = currentDate.value.add(1, 'month');
    } else {
        currentDate.value = currentDate.value.add(1, 'year');
    }
    fetchEvents();
};

/**
 * Kalenderansicht ändern (Monat/Jahr)
 */
const setCalendarView = (view) => {
    calendarView.value = view;
    fetchEvents();
};

/**
 * Hilfsfunktion: Ersten Tag einer Woche finden
 */
const getFirstDayOfWeek = (date) => {
    const day = date.day();
    const diff = day === 0 ? 6 : day - 1;
    return date.subtract(diff, 'day');
};

/**
 * Hilfsfunktion: Letzten Tag einer Woche finden
 */
const getLastDayOfWeek = (date) => {
    return getFirstDayOfWeek(date).add(6, 'day');
};

/**
 * Alle Wochen im aktuellen Monat generieren
 */
const getWeeksInMonth = () => {
    const startOfMonth = currentDate.value.startOf('month');
    const endOfMonth = currentDate.value.endOf('month');

    let currentWeekStart = dayjs(startOfMonth).day(1);
    if (currentWeekStart.isAfter(startOfMonth)) {
        currentWeekStart = currentWeekStart.subtract(1, 'week');
    }

    const weeks = [];
    const targetDate = dayjs().year(currentDate.value.year()).month(currentDate.value.month());
    while (currentWeekStart.isBefore(endOfMonth) || currentWeekStart.isSame(endOfMonth, 'day')) {
        const weekNumber = currentWeekStart.week();
        const days = [];
        for (let i = 0; i < 7; i++) {
            const date = currentWeekStart.clone().add(i, 'day');
            days.push({
                date: date.toDate(),
                dayNumber: date.format('D'),
                currentMonth: date.isSame(targetDate, 'month'),
                isToday: date.isToday(),
                isWeekend: date.day() === 0 || date.day() === 6
            });
        }
        weeks.push({ weekNumber, days });
        currentWeekStart = currentWeekStart.add(1, 'week');
    }
    return weeks;
};

/**
 * Wochen für Mini-Kalender in Jahresansicht generieren
 */
const getWeeksInMonthForMini = (month) => {
    const targetDate = dayjs().month(month).year(currentDate.value.year());
    const startOfMonth = targetDate.startOf('month');
    const endOfMonth = targetDate.endOf('month');

    let currentWeekStart = dayjs(startOfMonth).day(1);
    if (currentWeekStart.isAfter(startOfMonth)) {
        currentWeekStart = currentWeekStart.subtract(1, 'week');
    }

    const weeks = [];
    while (currentWeekStart.isBefore(endOfMonth) || currentWeekStart.isSame(endOfMonth, 'day')) {
        const weekNumber = currentWeekStart.week();
        const days = [];
        for (let i = 0; i < 7; i++) {
            const date = currentWeekStart.clone().add(i, 'day');
            days.push({
                date: date.toDate(),
                dayNumber: date.format('D'),
                currentMonth: date.isSame(targetDate, 'month'),
                isToday: date.isToday(),
                isWeekend: date.day() === 0 || date.day() === 6
            });
        }
        weeks.push({ weekNumber, days });
        currentWeekStart = currentWeekStart.add(1, 'week');
    }
    return weeks;
};

/**
 * Monatsname formatieren
 */
const getMonthName = (month) => {
    return dayjs().month(month).format('MMMM');
};

/**
 * Zu einem bestimmten Monat navigieren
 */
const goToMonth = (month) => {
    currentDate.value = currentDate.value.month(month);
    calendarView.value = 'month';
    fetchEvents();
};

/**
 * Umschalten zwischen eigenen und Team-Ereignissen
 */
const toggleEventFilter = () => {
    showOnlyOwnEvents.value = !showOnlyOwnEvents.value;
    // Optional: Speichern der Einstellung im localStorage
    localStorage.setItem('showOnlyOwnEvents', showOnlyOwnEvents.value ? 'true' : 'false');
};

/**
 * Prüft, ob ein Tag Ereignisse hat
 */
const hasEvents = (date) => {
    if (!date) return false;
    const dateStr = dayjs(date).format('YYYY-MM-DD');

    // In der Jahresansicht oder wenn showOnlyOwnEvents aktiviert ist, nur eigene Ereignisse berücksichtigen
    if (calendarView.value === 'year' || showOnlyOwnEvents.value) {
        return events.value.some(event => {
            const eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
            const eventEndDate = dayjs(event.endDate).format('YYYY-MM-DD');
            return dateStr >= eventStartDate && dateStr <= eventEndDate && event.user_id === currentUserId.value;
        });
    }

    // In anderen Ansichten alle Ereignisse berücksichtigen
    return events.value.some(event => {
        const eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
        const eventEndDate = dayjs(event.endDate).format('YYYY-MM-DD');
        return dateStr >= eventStartDate && dateStr <= eventEndDate;
    });
};

/**
 * Ereignisse für einen bestimmten Tag finden
 */
const getEventsForDay = (date) => {
    if (!date) return [];
    const dateStr = dayjs(date).format('YYYY-MM-DD');

    // Wenn showOnlyOwnEvents aktiviert ist, nur eigene Ereignisse zurückgeben
    if (showOnlyOwnEvents.value) {
        return events.value.filter(event => {
            const eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
            const eventEndDate = dayjs(event.endDate).format('YYYY-MM-DD');
            return dateStr >= eventStartDate && dateStr <= eventEndDate && event.user_id === currentUserId.value;
        });
    }

    // Sonst alle Ereignisse zurückgeben
    return events.value.filter(event => {
        const eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
        const eventEndDate = dayjs(event.endDate).format('YYYY-MM-DD');
        return dateStr >= eventStartDate && dateStr <= eventEndDate;
    });
};

/**
 * Farbe eines Ereignisses für einen bestimmten Tag finden
 */
const getEventColorForDay = (date) => {
    const isVacationDay = hasVacations(date); // Move hasVacations call here
    if (!date) return null;
    const dateStr = dayjs(date).format('YYYY-MM-DD');

    // In der Jahresansicht oder wenn showOnlyOwnEvents aktiviert ist, nur eigene Ereignisse berücksichtigen
    if (calendarView.value === 'year' || showOnlyOwnEvents.value) {
        const event = events.value.find(event => {
            const eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
            const eventEndDate = dayjs(event.endDate).format('YYYY-MM-DD');
            return dateStr >= eventStartDate && dateStr <= eventEndDate && event.user_id === currentUserId.value;
        });
        return event ? event.color : null;
    }

    // In anderen Ansichten alle Ereignisse berücksichtigen
    const event = events.value.find(event => {
        const eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
        const eventEndDate = dayjs(event.endDate).format('YYYY-MM-DD');
        return dateStr >= eventStartDate && dateStr <= eventEndDate;
    });
    return event ? event.color : null;
};

/**
 * Text kürzen, falls zu lang
 */
const truncateText = (text, maxLength) => {
    if (!text) return '';
    if (text.length > maxLength) {
        return text.substring(0, maxLength) + '...';
    }
    return text;
};

/**
 * Datum formatieren
 */
const formatDate = (date) => {
    return dayjs(date).format('DD.MM.YYYY');
};

/**
 * Status-Label übersetzen
 */
const getStatusLabel = (status) => {
    switch (status) {
        case 'pending': return 'Ausstehend';
        case 'approved': return 'Genehmigt';
        case 'rejected': return 'Abgelehnt';
        default: return 'Unbekannt';
    }
};

/**
 * Status-Schweregrad (für Badge) zuordnen
 */
const getStatusSeverity = (status) => {
    switch (status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        default: return 'info';
    }
};

/**
 * Klick auf einen Tag behandeln
 */
const handleDayClick = (date) => {
    if (isHoliday(dayjs(date))) {
        showHolidayInfo(date);
    } else {
        openEventDialog(date);
    }
};

/**
 * Event-Dialog öffnen
 */
const openEventDialog = (date) => {
    if (isHoliday(dayjs(date))) {
        showHolidayInfo(date);
        return;
    }

    if (hasVacations(date)) {
        toast.add({
            severity: 'info',
            summary: 'Hinweis',
            detail: 'An diesem Tag ist bereits Urlaub eingetragen. Bitte wählen Sie einen anderen Tag.',
            life: 3000
        });
        return;
    }

    isEditMode.value = false;
    newEvent.value = {
        title: '',
        type: null,
        startDate: date,
        endDate: date,
        isAllDay: true,
        description: '',
        user_id: currentUserId.value // Standardmäßig der aktuelle Benutzer
    };
    eventDialogVisible.value = true;
};

/**
 * Event-Dialog schließen
 */
const closeEventDialog = () => {
    eventDialogVisible.value = false;
};

/**
 * Ereignis speichern
 */
const saveEvent = async () => {
    if (!newEvent.value.title || !newEvent.value.type) {
        toast.add({
            severity: 'error',
            summary: 'Validierungsfehler',
            detail: 'Bitte füllen Sie alle erforderlichen Felder aus.',
            life: 3000
        });
        return;
    }

    // Prüfen, ob ein Benutzer ausgewählt wurde (für HR bei Krankheit)
    if (isHrUser.value && newEvent.value.type.name === 'Krankheit' && !newEvent.value.user_id) {
        toast.add({
            severity: 'error',
            summary: 'Validierungsfehler',
            detail: 'Bitte wählen Sie einen Mitarbeiter aus.',
            life: 3000
        });
        return;
    }

    // Prüfen, ob Start- oder Enddatum ein Feiertag ist
    const startDate = dayjs(newEvent.value.startDate);
    const endDate = dayjs(newEvent.value.endDate);

    if (isHoliday(startDate) || isHoliday(endDate)) {
        toast.add({
            severity: 'error',
            summary: 'Validierungsfehler',
            detail: 'Ereignisse können nicht an Feiertagen eingetragen werden.',
            life: 3000
        });
        return;
    }

    // Prüfen, ob ein Feiertag zwischen Start- und Enddatum liegt
    let date = startDate.clone();
    while (date.isBefore(endDate) || date.isSame(endDate, 'day')) {
        if (isHoliday(date)) {
            toast.add({
                severity: 'error',
                summary: 'Validierungsfehler',
                detail: `Ereignisse können nicht an Feiertagen eingetragen werden. ${getHolidayName(date)} (${date.format('DD.MM.YYYY')}) liegt im gewählten Zeitraum.`,
                life: 5000
            });
            return;
        }
        date = date.add(1, 'day');
    }

    // Prüfen, ob im gewählten Zeitraum bereits Urlaub eingetragen ist
    date = startDate.clone();
    while (date.isBefore(endDate) || date.isSame(endDate, 'day')) {
        if (hasVacations(date.toDate())) {
            toast.add({
                severity: 'error',
                summary: 'Validierungsfehler',
                detail: `Ereignisse können nicht an Urlaubstagen eingetragen werden. Am ${date.format('DD.MM.YYYY')} ist bereits Urlaub eingetragen.`,
                life: 5000
            });
            return;
        }
        date = date.add(1, 'day');
    }

    // Vereinfachte Berechtigungsprüfung für Bearbeitung
    if (isEditMode.value && newEvent.value.id) {
        let canEdit = false;

        // HR-Benutzer dürfen alles bearbeiten
        if (isHrUser.value) {
            canEdit = true;
        }
        // Eigene Ereignisse darf jeder bearbeiten
        else if (newEvent.value.user_id === currentUserId.value) {
            canEdit = true;
        }
        // Abteilungsleiter dürfen Ereignisse ihrer Teammitglieder bearbeiten
        else if (isTeamManager.value) {
            // Für Abteilungsleiter erlauben wir die Bearbeitung, auch wenn team_id nicht übereinstimmt
            canEdit = true;
        }

        if (!canEdit) {
            toast.add({
                severity: 'error',
                summary: 'Keine Berechtigung',
                detail: 'Sie sind nicht berechtigt, dieses Ereignis zu bearbeiten.',
                life: 5000
            });
            saving.value = false;
            return;
        }
    }

    saving.value = true;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const config = {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            withCredentials: true
        };

        const eventData = {
            title: newEvent.value.title,
            event_type_id: newEvent.value.type.id,
            event_type: newEvent.value.type.name,
            start_date: dayjs(newEvent.value.startDate).format('YYYY-MM-DD'),
            end_date: dayjs(newEvent.value.endDate).format('YYYY-MM-DD'),
            is_all_day: newEvent.value.isAllDay,
            description: newEvent.value.description,
            user_id: newEvent.value.user_id // Benutzer-ID hinzufügen
        };

        if (isEditMode.value && newEvent.value.id) {
            if (selectedEvent.value && selectedEvent.value.source === 'vacation') {
                toast.add({
                    severity: 'info',
                    summary: 'Hinweis',
                    detail: 'Urlaubseinträge können nicht direkt bearbeitet werden. Bitte nutzen Sie die Urlaubsverwaltung.',
                    life: 5000
                });
                return;
            }

            try {
                // Laravel-Standard für Updates
                const postData = { ...eventData, _method: 'PUT' };
                const response = await axios.post(`/api/events/${newEvent.value.id}`, postData, config);

                if (response.status === 200) {
                    toast.add({
                        severity: 'success',
                        summary: 'Erfolgreich',
                        detail: 'Ereignis wurde aktualisiert.',
                        life: 3000
                    });
                }
            } catch (postError) {
                // Fallback: direkter PUT-Request
                const response = await axios.put(`/api/events/${newEvent.value.id}`, eventData, config);

                if (response.status === 200) {
                    toast.add({
                        severity: 'success',
                        summary: 'Erfolgreich',
                        detail: 'Ereignis wurde aktualisiert.',
                        life: 3000
                    });
                }
            }
        } else {
            const response = await axios.post('/api/events', eventData, config);

            if (response.status === 201) {
                toast.add({
                    severity: 'success',
                    summary: 'Erfolgreich',
                    detail: 'Ereignis wurde gespeichert.',
                    life: 3000
                });
            }
        }

        setTimeout(() => {
            fetchEvents();
        }, 1000);
    } catch (error) {
        console.error('Fehler beim Speichern des Ereignisses:', error);

        if (error.response && error.response.status === 401) {
            toast.add({
                severity: 'error',
                summary: 'Fehler',
                detail: 'Sie sind nicht berechtigt, dieses Ereignis zu speichern. Bitte melden Sie sich erneut an.',
                life: 5000
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Fehler',
                detail: 'Es gab ein Problem beim Speichern des Ereignisses: ' + (error.response?.data?.error || error.message),
                life: 3000
            });
        }
    } finally {
        saving.value = false;
        closeEventDialog();
        closeEventDetailsDialog();
    }
};

// Ersetze die editEvent-Funktion mit dieser verbesserten Version:

const editEvent = () => {
    if (!selectedEvent.value) return;

    if (selectedEvent.value.source === 'vacation') {
        toast.add({
            severity: 'info',
            summary: 'Hinweis',
            detail: 'Urlaubseinträge können nicht direkt bearbeitet werden. Bitte nutzen Sie die Urlaubsverwaltung.',
            life: 5000
        });
        return;
    }

    // Debug-Ausgabe für Berechtigungsprüfung
    console.log('Bearbeiten-Versuch:', {
        event_id: selectedEvent.value.id,
        event_user_id: selectedEvent.value.user_id,
        event_team_id: selectedEvent.value.team_id,
        current_user_id: currentUserId.value,
        is_hr: isHrUser.value,
        is_team_manager: isTeamManager.value,
        user_team_id: userTeamId.value
    });

    // Vereinfachte Berechtigungsprüfung
    let canEdit = false;

    // HR-Benutzer dürfen alles bearbeiten
    if (isHrUser.value) {
        canEdit = true;
    }
    // Eigene Ereignisse darf jeder bearbeiten
    else if (selectedEvent.value.user_id === currentUserId.value) {
        canEdit = true;
    }
    // Abteilungsleiter dürfen Ereignisse ihrer Teammitglieder bearbeiten
    else if (isTeamManager.value) {
        // Für Abteilungsleiter erlauben wir die Bearbeitung, auch wenn team_id nicht übereinstimmt
        // Dies ist eine Notlösung, falls die team_id nicht korrekt gesetzt ist
        canEdit = true;
    }

    if (!canEdit) {
        toast.add({
            severity: 'error',
            summary: 'Keine Berechtigung',
            detail: 'Sie sind nicht berechtigt, dieses Ereignis zu bearbeiten.',
            life: 5000
        });
        return;
    }

    // Prüfen, ob es sich um einen Krankheitseintrag handelt und der Benutzer kein HR-Mitarbeiter ist
    if (selectedEvent.value.type &&
        selectedEvent.value.type.name === 'Krankheit' &&
        !isHrUser.value) {
        toast.add({
            severity: 'info',
            summary: 'Hinweis',
            detail: 'Krankheitseinträge können nur von HR-Mitarbeitern bearbeitet werden.',
            life: 5000
        });
        return;
    }

    let eventType = findEventType(selectedEvent.value.type);

    isEditMode.value = true;
    newEvent.value = {
        id: selectedEvent.value.id,
        title: selectedEvent.value.title,
        type: eventType,
        startDate: new Date(selectedEvent.value.startDate),
        endDate: new Date(selectedEvent.value.endDate),
        isAllDay: selectedEvent.value.isAllDay,
        description: selectedEvent.value.description,
        user_id: selectedEvent.value.user_id || currentUserId.value,
        team_id: selectedEvent.value.team_id
    };

    closeEventDetailsDialog();
    eventDialogVisible.value = true;
};

// Ersetze auch die fetchEvents-Funktion, um sicherzustellen, dass die team_id korrekt gesetzt wird:

const fetchEvents = async () => {
    loading.value = true;

    try {
        // Datumsbereich basierend auf der aktuellen Ansicht berechnen
        let startDate, endDate;

        if (calendarView.value === 'month') {
            const firstDay = currentDate.value.startOf('month').subtract(7, 'day');
            const lastDay = currentDate.value.endOf('month').add(7, 'day');
            startDate = firstDay.format('YYYY-MM-DD');
            endDate = lastDay.format('YYYY-MM-DD');
        } else {
            startDate = currentDate.value.startOf('year').format('YYYY-MM-DD');
            endDate = currentDate.value.endOf('year').format('YYYY-MM-DD');
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const config = {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            withCredentials: true,
            params: { start_date: startDate, end_date: endDate }
        };

        if (eventTypes.value.length === 0) {
            await fetchEventTypes();
        }

        let allEvents = [];
        try {
            const eventsResponse = await axios.get('/api/events', config);

            allEvents = eventsResponse.data.map(event => {
                const eventTypeName = event.event_type || event.title;
                let eventType = null;

                if (event.event_type_id) {
                    eventType = eventTypes.value.find(type => type.id === event.event_type_id);
                }

                if (!eventType && typeof eventTypeName === 'string') {
                    eventType = eventTypes.value.find(type =>
                        type.name === eventTypeName ||
                        type.name.toLowerCase() === eventTypeName.toLowerCase());
                }

                if (!eventType) {
                    eventType = eventTypes.value.find(type => type.name === 'Sonstiges') ||
                        { id: 6, name: 'Sonstiges', value: 'other', color: '#607D8B', requires_approval: true };
                }

                const isVacation =
                    (eventType && eventType.name.toLowerCase() === 'urlaub') ||
                    (eventTypeName && typeof eventTypeName === 'string' && eventTypeName.toLowerCase().includes('urlaub')) ||
                    (event.title && typeof event.title === 'string' && event.title.toLowerCase().includes('urlaub')) ||
                    (event.event_type && typeof event.event_type === 'string' && event.event_type.toLowerCase().includes('urlaub'));

                return {
                    id: event.id,
                    title: event.title,
                    description: event.description,
                    startDate: new Date(event.start_date),
                    endDate: new Date(event.end_date),
                    isAllDay: event.is_all_day,
                    status: event.status,
                    type: eventType,
                    color: eventType.color,
                    source: isVacation ? 'vacation' : 'event',
                    event_type_id: event.event_type_id,
                    user_id: event.user_id,
                    team_id: event.team_id, // Stelle sicher, dass team_id korrekt gesetzt wird
                    employee_name: event.employee_name || ''
                };
            });

            // Debug-Ausgabe für die geladenen Ereignisse
            console.log('Geladene Ereignisse:', allEvents.map(e => ({
                id: e.id,
                title: e.title,
                user_id: e.user_id,
                team_id: e.team_id
            })));
        } catch (error) {
            console.error('Fehler beim Laden der Ereignisse:', error);
        }

        events.value = allEvents.filter(event => event.source !== 'vacation');

        const vacationEvents = allEvents.filter(event => event.source === 'vacation');
        vacations.value = [
            ...vacations.value.filter(v => !vacationEvents.some(ve => ve.id === v.id)),
            ...vacationEvents
        ];

    } catch (error) {
        console.error('Fehler beim Laden der Ereignisse:', error);
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Die Ereignisse konnten nicht vollständig geladen werden.',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

// Komponente initialisieren
onMounted(() => {
    primevue.config.locale = de;
    fetchUserRole().then(() => {
        if (isHrUser.value || isTeamManager.value) {
            fetchEmployees();
        }
    });
    fetchEventTypes();
    fetchEvents();
    fetchVacationData();
    fetchHolidays(new Date().getFullYear());

    // Laden der gespeicherten Einstellung
    const savedFilter = localStorage.getItem('showOnlyOwnEvents');
    if (savedFilter !== null) {
        showOnlyOwnEvents.value = savedFilter === 'true';
    }
});

// Jahr-Änderungen überwachen, um Feiertage neu zu laden
watch(
    () => currentDate.value.year(),
    (newYear, oldYear) => {
        if (newYear !== oldYear) {
            fetchHolidays(newYear);
        }
    }
);

const hasVacations = (date) => {
    if (!date) return false;
    const dateStr = dayjs(date).format('YYYY-MM-DD');

    return vacations.value.some(vacation => {
        const vacationStartDate = dayjs(vacation.startDate).format('YYYY-MM-DD');
        const vacationEndDate = dayjs(vacation.endDate).format('YYYY-MM-DD');
        return dateStr >= vacationStartDate && dateStr <= vacationEndDate;
    });
};

/**
 * Urlaubseinträge für einen bestimmten Tag finden
 */
const getVacationsForDay = (date) => {
    if (!date) return [];
    const dateStr = dayjs(date).format('YYYY-MM-DD');
    return vacations.value.filter(vacation => {
        const vacationStartDate = dayjs(vacation.startDate).format('YYYY-MM-DD');
        const vacationEndDate = dayjs(vacation.endDate).format('YYYY-MM-DD');
        return dateStr >= vacationStartDate && dateStr <= vacationEndDate;
    });
};

const closeEventDetailsDialog = () => {
    eventDetailsDialogVisible.value = false;
};

const findEventType = (typeInfo) => {
    if (!typeInfo) return null;

    let eventType = null;

    // Nach ID suchen
    if (typeInfo.id) {
        eventType = eventTypes.value.find(type => type.id === typeInfo.id);
    }

    // Nach Name suchen
    if (!eventType && typeInfo.name) {
        eventType = eventTypes.value.find(type => type.name === typeInfo.name ||
            type.name.toLowerCase() === typeInfo.name.toLowerCase());
    }

    // Nach Wert suchen
    if (!eventType && typeInfo.value) {
        eventType = eventTypes.value.find(type => type.value === typeInfo.value ||
            type.value.toLowerCase() === typeInfo.value.toLowerCase());
    }

    // Fallback
    if (!eventType && eventTypes.value.length > 0) {
        eventType = eventTypes.value.find(type => type.name === 'Sonstiges') || eventTypes.value[0];
    }

    return eventType;
};

const fetchVacationData = async () => {
    try {
        // Verwende VacationService statt direktem API-Aufruf
        const response = await VacationService.getUserVacationData();

        if (response && response.data && response.data.requests) {
            const vacationRequests = response.data.requests.filter(req => req.status === 'approved');

            const vacationEntries = vacationRequests.map(req => ({
                id: `vacation-${req.id}`,
                title: 'Urlaub',
                description: req.notes || 'Genehmigter Urlaub',
                startDate: new Date(req.startDate),
                endDate: new Date(req.endDate),
                isAllDay: true,
                status: 'approved',
                type: {
                    name: 'Urlaub',
                    value: 'vacation',
                    color: '#9C27B0'
                },
                color: '#9C27B0',
                source: 'vacation',
                user_id: req.user_id,
                employee_name: req.employee_name || ''
            }));

            vacations.value = [...vacations.value, ...vacationEntries];
        }
    } catch (error) {
        console.error('Fehler beim Laden der Urlaubsdaten aus VacationService:', error);
    }
};

// Füge die fehlenden Funktionen nach der fetchVacationData-Funktion hinzu:

/**
 * Wochenplan-Dialog öffnen
 */
const openWeekPlanDialog = (weekNumber, days) => {
    selectedWeekNumber.value = weekNumber;

    // Tage für die Wochenplanung vorbereiten
    weekDays.value = days.map(day => {
        const existingEvents = getEventsForDay(day.date);
        const existingEvent = existingEvents.find(event =>
            event.user_id === currentUserId.value ||
            (isHrUser.value || (isTeamManager.value && event.team_id === userTeamId.value))
        );

        return {
            date: day.date,
            selectedType: existingEvent ? existingEvent.type : null,
            notes: existingEvent ? existingEvent.description : '',
            eventId: existingEvent ? existingEvent.id : null,
            isEdited: false,
            toDelete: false,
            user_id: existingEvent ? existingEvent.user_id : currentUserId.value,
            selectedEmployee: existingEvent && existingEvent.user_id !== currentUserId.value ?
                employees.value.find(emp => emp.id === existingEvent.user_id) : null
        };
    });

    weekPlanDialogVisible.value = true;
};

/**
 * Wochenplan-Dialog schließen
 */
const closeWeekPlanDialog = () => {
    weekPlanDialogVisible.value = false;
};

/**
 * Ereignis aus der Wochenplanung entfernen
 */
const removeWeekDayEvent = (index) => {
    if (index >= 0 && index < weekDays.value.length) {
        const day = weekDays.value[index];

        if (day.eventId) {
            day.toDelete = true;
            day.selectedType = null;
            day.notes = '';
        } else {
            day.selectedType = null;
            day.notes = '';
        }
    }
};

/**
 * Ereignisdetails-Dialog öffnen
 */
const openEventDetailsDialog = (event) => {
    selectedEvent.value = event;
    eventDetailsDialogVisible.value = true;
};

/**
 * Urlaubsdetails-Dialog öffnen
 */
const openVacationDetailsDialog = (vacation) => {
    selectedVacation.value = vacation;
    vacationDetailsDialogVisible.value = true;
};

/**
 * Urlaubsdetails-Dialog schließen
 */
const closeVacationDetailsDialog = () => {
    vacationDetailsDialogVisible.value = false;
};

/**
 * Zur Urlaubsverwaltung navigieren
 */
const navigateToVacationManagement = () => {
    navigateTo('/vacation/management');
};

/**
 * Löschen eines Ereignisses bestätigen
 */
const confirmDeleteEvent = (event) => {
    if (!event) return;

    // Prüfen, ob es sich um einen Krankheitseintrag handelt und der Benutzer kein HR-Mitarbeiter ist
    if (event.type &&
        event.type.name === 'Krankheit' &&
        !isHrUser.value) {
        toast.add({
            severity: 'info',
            summary: 'Hinweis',
            detail: 'Krankheitseinträge können nur von HR-Mitarbeitern gelöscht werden.',
            life: 5000
        });
        return;
    }

    // Vereinfachte Berechtigungsprüfung
    let canDelete = false;

    // HR-Benutzer dürfen alles löschen
    if (isHrUser.value) {
        canDelete = true;
    }
    // Eigene Ereignisse darf jeder löschen
    else if (event.user_id === currentUserId.value) {
        canDelete = true;
    }
    // Abteilungsleiter dürfen Ereignisse ihrer Teammitglieder löschen
    else if (isTeamManager.value) {
        // Für Abteilungsleiter erlauben wir das Löschen, auch wenn team_id nicht übereinstimmt
        canDelete = true;
    }

    if (!canDelete) {
        toast.add({
            severity: 'error',
            summary: 'Keine Berechtigung',
            detail: 'Sie sind nicht berechtigt, dieses Ereignis zu löschen.',
            life: 5000
        });
        return;
    }

    selectedEvent.value = event;
    deleteConfirmationVisible.value = true;
};

/**
 * Löschen eines Ereignisses abbrechen
 */
const cancelDeleteEvent = () => {
    deleteConfirmationVisible.value = false;
};

/**
 * Ereignis löschen
 */
const deleteEvent = async () => {
    if (!selectedEvent.value) return;

    deleting.value = true;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const config = {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            withCredentials: true
        };

        try {
            // Versuche zuerst mit DELETE-Methode
            const response = await axios.delete(`/api/events/${selectedEvent.value.id}`, config);

            if (response.status === 200) {
                toast.add({
                    severity: 'success',
                    summary: 'Erfolgreich',
                    detail: 'Ereignis wurde gelöscht.',
                    life: 3000
                });
            }
        } catch (deleteError) {
            // Fallback: POST mit _method=DELETE
            const response = await axios.post(`/api/events/${selectedEvent.value.id}`, { _method: 'DELETE' }, config);

            if (response.status === 200) {
                toast.add({
                    severity: 'success',
                    summary: 'Erfolgreich',
                    detail: 'Ereignis wurde gelöscht.',
                    life: 3000
                });
            }
        }

        setTimeout(() => {
            fetchEvents();
        }, 1000);
    } catch (error) {
        console.error('Fehler beim Löschen des Ereignisses:', error);

        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Das Ereignis konnte nicht gelöscht werden: ' + (error.response?.data?.error || error.message),
            life: 3000
        });
    } finally {
        deleting.value = false;
        deleteConfirmationVisible.value = false;
    }
};

/**
 * Wochenplanung speichern
 */
const saveWeekPlan = async () => {
    savingWeekPlan.value = true;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const config = {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            withCredentials: true
        };

        const toCreate = [];
        const toUpdate = [];
        const toDelete = [];

        weekDays.value.forEach(day => {
            if (isHoliday(dayjs(day.date)) || hasVacations(day.date)) {
                return;
            }

            // Prüfen, ob für Krankheit ein Benutzer ausgewählt wurde
            if (day.selectedType && day.selectedType.name === 'Krankheit' && !day.user_id && isHrUser.value) {
                toast.add({
                    severity: 'error',
                    summary: 'Validierungsfehler',
                    detail: `Bitte wählen Sie einen Mitarbeiter für den Krankheitseintrag am ${dayjs(day.date).format('DD.MM.YYYY')} aus.`,
                    life: 5000
                });
                return;
            }

            if (day.eventId && day.toDelete) {
                toDelete.push(day.eventId);
            }
            else if (day.eventId && day.isEdited && day.selectedType) {
                toUpdate.push({
                    id: day.eventId,
                    title: day.selectedType.name,
                    description: day.notes || '',
                    start_date: dayjs(day.date).format('YYYY-MM-DD'),
                    end_date: dayjs(day.date).format('YYYY-MM-DD'),
                    event_type_id: day.selectedType.id,
                    is_all_day: true,
                    user_id: day.user_id
                });
            }
            else if (!day.eventId && day.selectedType) {
                toCreate.push({
                    title: day.selectedType.name,
                    description: day.notes || '',
                    start_date: dayjs(day.date).format('YYYY-MM-DD'),
                    end_date: dayjs(day.date).format('YYYY-MM-DD'),
                    event_type_id: day.selectedType.id,
                    is_all_day: true,
                    user_id: day.user_id
                });
            }
        });

        let successCount = 0;
        const totalOperations = toCreate.length + toUpdate.length + toDelete.length;

        // Löschoperationen
        for (const eventId of toDelete) {
            try {
                try {
                    const response = await axios.delete(`/api/events/${eventId}`, config);
                    if (response.status === 200) successCount++;
                } catch (deleteError) {
                    const response = await axios.post(`/api/events/${eventId}`, { _method: 'DELETE' }, config);
                    if (response.status === 200) successCount++;
                }
            } catch (error) {
                console.error(`Fehler beim Löschen des Ereignisses ${eventId}:`, error);
            }
        }

        // Aktualisierungsoperationen
        for (const event of toUpdate) {
            try {
                try {
                    const postData = { ...event, _method: 'PUT' };
                    const response = await axios.post(`/api/events/${event.id}`, postData, config);
                    if (response.status === 200) successCount++;
                } catch (postError) {
                    const response = await axios.put(`/api/events/${event.id}`, event, config);
                    if (response.status === 200) successCount++;
                }
            } catch (error) {
                console.error(`Fehler beim Aktualisieren des Ereignisses ${event.id}:`, error);
            }
        }

        // Erstellungsoperationen
        for (const event of toCreate) {
            try {
                const response = await axios.post('/api/events', event, config);
                if (response.status === 201) successCount++;
            } catch (error) {
                console.error('Fehler beim Erstellen eines Ereignisses:', error);
            }
        }

        if (successCount > 0) {
            toast.add({
                severity: 'success',
                summary: successCount === totalOperations ? 'Erfolg' : 'Teilweise erfolgreich',
                detail: successCount === totalOperations
                    ? 'Wochenplanung wurde gespeichert.'
                    : `${successCount} von ${totalOperations} Änderungen wurden gespeichert.`,
                life: 3000
            });
            setTimeout(() => fetchEvents(), 1000);
        } else if (totalOperations === 0) {
            toast.add({
                severity: 'info',
                summary: 'Hinweis',
                detail: 'Keine Änderungen vorgenommen.',
                life: 3000
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Fehler',
                detail: 'Die Wochenplanung konnte nicht gespeichert werden.',
                life: 3000
            });
        }
    } catch (error) {
        console.error('Fehler beim Speichern der Wochenplanung:', error);
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Die Wochenplanung konnte nicht gespeichert werden.',
            life: 3000
        });
    } finally {
        savingWeekPlan.value = false;
        closeWeekPlanDialog();
    }
};
</script>
