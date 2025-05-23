<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6 w-full overflow-hidden">
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
            :events="processedEvents"
            :vacations="processedVacations"
            :isHoliday="isHoliday"
            :get-holiday-name="getHolidayName"
            :has-events="hasEvents"
            :has-vacations="enhancedHasVacations"
            :get-events-for-day="getEventsForDay"
            :get-vacations-for-day="enhancedGetVacationsForDay"
            :truncate-text="truncateText"
            :is-hr-user="isHrUser"
            :is-team-manager="isTeamManager"
            :show-only-own-events="showOnlyOwnEvents"
            :current-user-id="currentUserId"
            :is-user-absent="isUserAbsent"
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
            :events="processedEvents"
            :vacations="processedVacations"
            :isHoliday="isHoliday"
            :has-events="hasEvents"
            :has-vacations="enhancedHasVacations"
            :get-event-color-for-day="getEventColorForDay"
            :get-month-name="getMonthName"
            :get-weeks-in-month-for-mini="getWeeksInMonthForMini"
            :current-user-id="currentUserId"
            :is-user-absent="isUserAbsent"
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
            :week-number="selectedWeekNumber"
            :week-days="weekDays"
            :weekdays="weekdays"
            :event-types="eventTypes"
            :saving="savingWeekPlan"
            :is-holiday="isHoliday"
            :get-holiday-name="getHolidayName"
            :has-vacations="enhancedHasVacations"
            :format-date="formatDate"
            :is-hr="isHrUser"
            :employees="employees"
            :events="processedEvents"
            :current-user-id="currentUserId"
            @close="closeWeekPlanDialog"
            @save="saveWeekPlan"
            @remove-event="removeWeekDayEvent"
        />

        <HolidayInfoDialog
            :visible="holidayInfoVisible"
            @update:visible="holidayInfoVisible = $event"
            :holiday-name="selectedHolidayName"
            @close="holidayInfoVisible = false"
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
import { usePrimeVue } from 'primevue/config';
import Toast from 'primevue/toast';
import axios from 'axios';

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

// Composables importieren
import { useCalendarState } from '../composables/useCalendarState';
import { useUserRole } from '../composables/useUserRole';
import { useHolidays } from '../composables/useHolidays';
import { useEventTypes } from '../composables/useEventTypes';
import { useEvents } from '../composables/useEvents';
import { useDialogs } from '../composables/useDialogs';
import { useCalendarUtils } from '../composables/useCalendarUtils';
import { useVacations } from '../composables/useVacations';

// Deutsche Lokalisierung für PrimeVue
const de = {
    firstDayOfWeek: 1,
    dayNames: ["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag"],
    dayNamesShort: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
    dayNamesMin: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
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

// dayjs mit Plugins konfigurieren
dayjs.locale('de');
dayjs.extend(weekday);
dayjs.extend(weekOfYear);
dayjs.extend(isBetween);
dayjs.extend(isSameOrBefore);
dayjs.extend(isSameOrAfter);
dayjs.extend(isToday);

// Composables initialisieren
const {
    currentDate,
    calendarView,
    yearLayout,
    events,
    vacations,
    loading,
    showOnlyOwnEvents,
    currentMonthName,
    currentYear,
    previousPeriod,
    nextPeriod,
    setCalendarView,
    toggleEventFilter,
    goToMonth
} = useCalendarState();

const {
    currentUserRoleId,
    currentUserId,
    isTeamManager,
    userTeamId,
    employees,
    isHrUser,
    canEditEvent,
    fetchUserRole,
    fetchEmployees
} = useUserRole();

const {
    holidays,
    isLoadingHolidays,
    disabledDates,
    selectedHolidayName,
    holidayInfoVisible,
    updateDisabledDates,
    fetchHolidays,
    isHoliday,
    getHolidayName,
    showHolidayInfo
} = useHolidays();

const {
    eventTypes,
    filteredEventTypes,
    fetchEventTypes,
    findEventType
} = useEventTypes(currentUserRoleId);

const {
    events: eventsList,
    vacations: vacationsList,
    loading: eventsLoading,
    fetchEvents,
    hasEvents,
    getEventsForDay,
    getEventColorForDay,
    hasVacations: originalHasVacations,
    getVacationsForDay: originalGetVacationsForDay
} = useEvents(
    calendarView,
    currentDate,
    eventTypes,
    currentUserId,
    isHrUser,
    isTeamManager,
    userTeamId,
    showOnlyOwnEvents,
    findEventType
);

// Verwende das neue useVacations Composable
const {
    vacations: vacationsFromService,
    loading: vacationsLoading,
    error: vacationsError,
    currentUserId: vacationsUserId,
    fetchVacations,
    isDateInMyVacation,
    isDateInTeamVacation
} = useVacations();

const {
    newEvent,
    saving,
    isEditMode,
    eventDialogVisible,
    selectedEvent,
    eventDetailsDialogVisible,
    selectedVacation,
    vacationDetailsDialogVisible,
    deleteConfirmationVisible,
    deleting,
    selectedWeekNumber,
    weekDays,
    savingWeekPlan,
    weekPlanDialogVisible,
    weekPlanShowOnlyOwnEvents,
    openEventDialog,
    closeEventDialog,
    saveEvent,
    openEventDetailsDialog,
    closeEventDetailsDialog,
    editEvent: editEventFn,
    openVacationDetailsDialog,
    closeVacationDetailsDialog,
    navigateToVacationManagement,
    confirmDeleteEvent,
    cancelDeleteEvent,
    deleteEvent,
    openWeekPlanDialog: openWeekPlanDialogFn,
    closeWeekPlanDialog,
    toggleWeekPlanFilter: toggleWeekPlanFilterFn,
    removeWeekDayEvent,
    saveWeekPlan,
    truncateText,
    formatDate,
    getStatusLabel,
    getStatusSeverity
} = useDialogs(
    isHrUser,
    isTeamManager,
    currentUserId,
    originalHasVacations,
    isHoliday,
    getHolidayName,
    fetchEvents
);

const {
    weekdays,
    weekdaysShort,
    getFirstDayOfWeek,
    getLastDayOfWeek,
    getWeeksInMonth,
    getWeeksInMonthForMini,
    getMonthName
} = useCalendarUtils();

// Direkt vom Server geladene Urlaubsanträge
const vacationRequests = ref([]);

// Verarbeite Ereignisse, um sicherzustellen, dass Farben korrekt gesetzt sind
const processedEvents = computed(() => {
    return eventsList.value.map(event => {
        // Stelle sicher, dass jedes Ereignis eine Farbe hat
        if (!event.color && event.type && event.type.color) {
            return { ...event, color: event.type.color };
        }
        // Fallback-Farbe, falls keine gefunden wird
        if (!event.color) {
            return { ...event, color: '#10b981' }; // Standard-Grün
        }
        return event;
    });
});

// Kombiniere Urlaubsdaten aus beiden Quellen und verarbeite sie
const processedVacations = computed(() => {
    // Kombiniere Urlaubsdaten aus beiden Quellen
    const allVacations = [
        ...(vacationsList.value || []),
        ...(vacationsFromService.value || []),
        ...processVacationRequests()
    ];

    // Entferne Duplikate basierend auf ID
    const uniqueVacations = allVacations.reduce((acc, vacation) => {
        const id = vacation.id || `${vacation.startDate}-${vacation.endDate}-${vacation.user_id || vacation.userId}`;
        if (!acc.some(v => v.id === id)) {
            acc.push({...vacation, id});
        }
        return acc;
    }, []);

    return uniqueVacations.map(vacation => {
        // Stelle sicher, dass jeder Urlaubseintrag eine Farbe hat
        if (!vacation.color && vacation.type && vacation.type.color) {
            return { ...vacation, color: vacation.type.color };
        }
        // Fallback-Farbe für Urlaub
        if (!vacation.color) {
            return { ...vacation, color: '#9C27B0' }; // Standard-Lila für Urlaub
        }

        // Stelle sicher, dass Start- und Enddatum als Date-Objekte vorliegen
        const startDate = vacation.startDate instanceof Date ?
            vacation.startDate : new Date(vacation.startDate || vacation.start_date || vacation.start);
        const endDate = vacation.endDate instanceof Date ?
            vacation.endDate : new Date(vacation.endDate || vacation.end_date || vacation.end);

        return {
            ...vacation,
            startDate,
            endDate,
            // Stelle sicher, dass der Typ korrekt gesetzt ist
            type: vacation.type || {
                name: "Urlaub",
                value: "vacation",
                color: "#9C27B0"
            },
            // Stelle sicher, dass die Benutzer-ID korrekt gesetzt ist
            user_id: vacation.user_id || vacation.userId
        };
    });
});

// Verarbeite die Urlaubsanträge aus der Datenbank
const processVacationRequests = () => {
    if (!vacationRequests.value || !Array.isArray(vacationRequests.value)) {
        return [];
    }

    return vacationRequests.value
        .filter(request => request.status === 'approved') // Nur genehmigte Urlaubsanträge
        .map(request => {
            return {
                id: `vacation-${request.id}`,
                user_id: request.user_id,
                startDate: new Date(request.start_date),
                endDate: new Date(request.end_date),
                days: request.days,
                type: {
                    name: "Urlaub",
                    value: "vacation",
                    color: "#9C27B0"
                },
                notes: request.notes || 'Genehmigter Urlaub',
                status: request.status
            };
        });
};

// Lade Urlaubsanträge direkt vom Server
const fetchVacationRequests = async () => {
    try {
        const response = await axios.get('/api/vacation/all-requests');
        vacationRequests.value = response.data;
        console.log('Loaded vacation requests:', vacationRequests.value);
    } catch (error) {
        console.error('Error loading vacation requests:', error);
        vacationRequests.value = [];
    }
};

// Verbesserte Funktion zur Erkennung von Urlaubstagen
const enhancedHasVacations = (date) => {
    if (!date || !currentUserId.value) return false;

    // Konvertiere das Datum in ein einheitliches Format
    const dateStr = dayjs(date).format("YYYY-MM-DD");

    // Durchsuche alle Urlaubseinträge
    return processedVacations.value.some(vacation => {
        // Prüfe, ob der Urlaub dem aktuellen Benutzer gehört
        const isCurrentUserVacation =
            vacation.user_id === currentUserId.value ||
            vacation.userId === currentUserId.value;

        if (!isCurrentUserVacation) return false;

        // Prüfe, ob das Datum im Urlaubszeitraum liegt
        const startDate = dayjs(vacation.startDate).format("YYYY-MM-DD");
        const endDate = dayjs(vacation.endDate).format("YYYY-MM-DD");

        return dateStr >= startDate && dateStr <= endDate;
    });
};

// Verbesserte Funktion zum Abrufen von Urlaubseinträgen für einen bestimmten Tag
const enhancedGetVacationsForDay = (date) => {
    if (!date || !currentUserId.value) return [];

    // Konvertiere das Datum in ein einheitliches Format
    const dateStr = dayjs(date).format("YYYY-MM-DD");

    // Filtere alle Urlaubseinträge für diesen Tag
    return processedVacations.value.filter(vacation => {
        // Prüfe, ob der Urlaub dem aktuellen Benutzer gehört
        const isCurrentUserVacation =
            vacation.user_id === currentUserId.value ||
            vacation.userId === currentUserId.value;

        if (!isCurrentUserVacation) return false;

        // Prüfe, ob das Datum im Urlaubszeitraum liegt
        const startDate = dayjs(vacation.startDate).format("YYYY-MM-DD");
        const endDate = dayjs(vacation.endDate).format("YYYY-MM-DD");

        return dateStr >= startDate && dateStr <= endDate;
    });
};

// Ändern Sie die isUserAbsent-Funktion, um auch Abwesenheiten von Teammitgliedern für Abteilungsleiter anzuzeigen

// Ersetzen Sie die bestehende isUserAbsent-Funktion mit dieser verbesserten Version:

const isUserAbsent = (date) => {
    if (!date || !processedEvents.value) return false;

    const dateStr = dayjs(date).format('YYYY-MM-DD');

    // Prüfen, ob der aktuelle Benutzer (Abteilungsleiter) selbst an diesem Tag abwesend ist
    const isCurrentUserAbsent = processedEvents.value.some(event => {
        const isAbsentEvent = event.type &&
            (event.type.name === 'Abwesend' ||
                (event.type.value && event.type.value.toLowerCase() === 'absent'));
        const isCurrentUserEvent = event.user_id === currentUserId.value;
        const eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
        const eventEndDate = dayjs(event.endDate).format('YYYY-MM-DD');
        const isOnDate = dateStr >= eventStartDate && dateStr <= eventEndDate;

        return isAbsentEvent && isCurrentUserEvent && isOnDate;
    });

    // Wenn wir nur eigene Ereignisse anzeigen oder der Benutzer kein Abteilungsleiter ist,
    // geben wir nur zurück, ob der aktuelle Benutzer abwesend ist
    if (showOnlyOwnEvents.value || !isTeamManager.value) {
        return isCurrentUserAbsent;
    }

    // Für Abteilungsleiter in der Teamansicht:
    // Wenn der Abteilungsleiter selbst abwesend ist, zeigen wir das an
    if (isCurrentUserAbsent) {
        return true;
    }

    // Sonst prüfen wir, ob ein Teammitglied abwesend ist
    const isTeamMemberAbsent = processedEvents.value.some(event => {
        const isAbsentEvent = event.type &&
            (event.type.name === 'Abwesend' ||
                (event.type.value && event.type.value.toLowerCase() === 'absent'));
        const isTeamMemberEvent = event.team_id === userTeamId.value && event.user_id !== currentUserId.value;
        const eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
        const eventEndDate = dayjs(event.endDate).format('YYYY-MM-DD');
        const isOnDate = dateStr >= eventStartDate && dateStr <= eventEndDate;

        return isAbsentEvent && isTeamMemberEvent && isOnDate;
    });

    return isTeamMemberAbsent;
};

// Fügen Sie eine neue Funktion hinzu, um zu prüfen, ob ein Teammitglied (nicht der Abteilungsleiter selbst) abwesend ist
const isTeamMemberAbsent = (date) => {
    if (!date || !processedEvents.value || !isTeamManager.value || showOnlyOwnEvents.value) return false;

    const dateStr = dayjs(date).format('YYYY-MM-DD');

    return processedEvents.value.some(event => {
        const isAbsentEvent = event.type &&
            (event.type.name === 'Abwesend' ||
                (event.type.value && event.type.value.toLowerCase() === 'absent'));
        const isTeamMemberEvent = event.team_id === userTeamId.value && event.user_id !== currentUserId.value;
        const eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
        const eventEndDate = dayjs(event.endDate).format('YYYY-MM-DD');
        const isOnDate = dateStr >= eventStartDate && dateStr <= eventEndDate;

        return isAbsentEvent && isTeamMemberEvent && isOnDate;
    });
};

// Wrapper-Funktionen für Composable-Funktionen - WICHTIG: Diese müssen vor ihrer Verwendung definiert werden
const editEvent = () => {
    editEventFn(findEventType);
};

const openWeekPlanDialog = (weekNumber, days) => {
    openWeekPlanDialogFn(weekNumber, days, getEventsForDay);
};

const toggleWeekPlanFilter = () => {
    toggleWeekPlanFilterFn(getEventsForDay);
};

// Ändern Sie die handleDayClick-Funktion, um die Sperrung für Abteilungsleiter zu verhindern
const handleDayClick = (date) => {
    if (isHoliday(dayjs(date))) {
        showHolidayInfo(date);
    } else if (enhancedHasVacations(date)) {
        // Wenn der Benutzer an diesem Tag Urlaub hat, zeige eine Meldung an
        const toast = document.querySelector('.p-toast') ?
            document.querySelector('.p-toast').__vueParentComponent.ctx.add : null;

        if (toast) {
            toast({
                severity: 'info',
                summary: 'Hinweis',
                detail: 'Sie haben an diesem Tag Urlaub. Keine Einträge möglich.',
                life: 3000
            });
        } else {
            alert('Sie haben an diesem Tag Urlaub. Keine Einträge möglich.');
        }
    } else if (isUserAbsent(date) && !isTeamMemberAbsent(date) && !isHrUser.value) {
        // Wenn der Benutzer selbst an diesem Tag als abwesend markiert ist und kein HR-Mitarbeiter ist,
        // zeige eine Meldung an oder blockiere die Aktion
        const toast = document.querySelector('.p-toast') ?
            document.querySelector('.p-toast').__vueParentComponent.ctx.add : null;

        if (toast) {
            toast({
                severity: 'info',
                summary: 'Hinweis',
                detail: 'Sie sind an diesem Tag als abwesend markiert. Keine Einträge möglich.',
                life: 3000
            });
        } else {
            alert('Sie sind an diesem Tag als abwesend markiert. Keine Einträge möglich.');
        }
    } else {
        openEventDialog(date);
    }
};

// Daten neu laden, wenn sich relevante Zustände ändern
watch([calendarView, currentDate, showOnlyOwnEvents], () => {
    fetchEvents();
    fetchVacations();
    fetchVacationRequests();
}, { deep: true });

// Ereignistypen überwachen und Ereignisse neu laden, wenn sie sich ändern
watch(eventTypes, () => {
    if (eventTypes.value && eventTypes.value.length > 0) {
        fetchEvents();
    }
}, { deep: true });

// Debugging-Funktion, um Urlaubsdaten zu überprüfen
const logVacationData = () => {
    console.log('Current User ID:', currentUserId.value);
    console.log('Vacation Requests:', vacationRequests.value);
    console.log('Processed Vacations:', processedVacations.value);

    // Prüfe einen bestimmten Tag (z.B. 22. Mai 2025)
    const testDate = dayjs('2025-05-22').toDate();
    console.log('Has Vacation on 2025-05-22:', enhancedHasVacations(testDate));
    console.log('Vacations for 2025-05-22:', enhancedGetVacationsForDay(testDate));
};

// Komponente initialisieren
onMounted(async () => {
    primevue.config.locale = de;

    // Lade Benutzerrolle und warte auf Abschluss
    await fetchUserRole();

    if (isHrUser.value || isTeamManager.value) {
        await fetchEmployees();
    }

    // Für HR-Benutzer standardmäßig alle Ereignisse anzeigen
    if (isHrUser.value) {
        // Nur die gespeicherte Einstellung laden, wenn sie existiert
        const savedFilter = localStorage.getItem('showOnlyOwnEvents');
        if (savedFilter !== null) {
            showOnlyOwnEvents.value = savedFilter === 'true';
        } else {
            // Für HR-Benutzer standardmäßig alle Ereignisse anzeigen
            showOnlyOwnEvents.value = false;
            localStorage.setItem('showOnlyOwnEvents', 'false');
        }
    } else {
        // Für andere Benutzer die gespeicherte Einstellung laden
        const savedFilter = localStorage.getItem('showOnlyOwnEvents');
        if (savedFilter !== null) {
            showOnlyOwnEvents.value = savedFilter === 'true';
        }
    }

    // Lade Ereignistypen und warte auf Abschluss
    await fetchEventTypes();

    // Lade Ereignisse und Urlaubsdaten parallel
    await Promise.all([
        fetchEvents(),
        fetchVacations(),
        fetchVacationRequests(),
        fetchHolidays(new Date().getFullYear())
    ]);

    // Debugging nach dem Laden aller Daten
    setTimeout(logVacationData, 2000);
});

// Jahr-Änderungen überwachen, um Feiertage neu zu laden
watch(
    () => currentDate.value.year(),
    (newYear, oldYear) => {
        if (newYear !== oldYear) {
            fetchHolidays(newYear);
            // Auch Urlaubsdaten für das neue Jahr laden
            fetchVacations();
            fetchVacationRequests();
        }
    }
);
</script>
