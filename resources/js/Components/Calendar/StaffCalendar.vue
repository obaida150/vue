<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6 w-full overflow-hidden">
        <Toast />
        <!-- Header mit Navigation -->
        <CalendarHeader
            :calendar-view="calendarView"
            :current-date="currentDate"
            :year-layout="yearLayout"
            :is-team-manager="isTeamManager"
            :is-hr-user="isHrUser"
            :show-only-own-events="showOnlyOwnEvents"
            :current-user-role-id="currentUserRoleId"
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
            :has-absence-entries-for-day="hasAbsenceEntriesForDay"
            :get-all-absence-entries-for-day="getAllAbsenceEntriesForDay"
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
            :is-team-manager="isTeamManager"
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
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';
import isToday from 'dayjs/plugin/isToday';
import { usePrimeVue } from 'primevue/config';
import { useToast } from 'primevue/usetoast';
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
    findEventType,
    currentUserRoleId // Added currentUserRoleId parameter
);

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
    saveEvent: saveEventFn, // Umbenannt, um Konflikt zu vermeiden
    openEventDetailsDialog,
    closeEventDetailsDialog,
    editEvent: editEventFn,
    openVacationDetailsDialog,
    closeVacationDetailsDialog,
    navigateToVacationManagement,
    confirmDeleteEvent,
    cancelDeleteEvent,
    deleteEvent: deleteEventFn, // Umbenannt, um Konflikt zu vermeiden
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

// Ändern Sie die Deklaration der toast Variable von `const toast = ref(null);` zu:
const toast = useToast();

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
    const processed = vacationRequests.value
        .filter(request => {
            return request.status === 'approved'; // Nur genehmigte Urlaubsanträge
        })
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

    return processed;
};

// Lade Urlaubsanträge direkt vom Server
const fetchVacationRequests = async () => {
    try {
        const response = await axios.get('/api/vacation/all-requests');
        vacationRequests.value = response.data;
    } catch (error) {
        console.error('Error loading vacation requests:', error);
        vacationRequests.value = [];
    }
};

// Verbesserte Funktion zur Erkennung von Urlaubstagen
const enhancedHasVacations = (date) => {
    if (!date || !currentUserId.value) {
        return false;
    }

    // Konvertiere das Datum in ein einheitliches Format (nur Datum, keine Zeit)
    const checkDate = dayjs(date).startOf('day');

    // Durchsuche alle Urlaubseinträge
    const matchingVacations = processedVacations.value.filter(vacation => {
        // Prüfe, ob der Urlaub dem aktuellen Benutzer gehört
        const isCurrentUserVacation =
            vacation.user_id === currentUserId.value ||
            vacation.userId === currentUserId.value;

        if (!isCurrentUserVacation) return false;

        // Konvertiere Start- und Enddatum ebenfalls zu einheitlichen Formaten
        const startDate = dayjs(vacation.startDate).startOf('day');
        const endDate = dayjs(vacation.endDate).startOf('day');

        // Prüfe, ob das Datum im Urlaubszeitraum liegt (inklusive Start- und Enddatum)
        const isInRange = checkDate.isSameOrAfter(startDate, 'day') && checkDate.isSameOrBefore(endDate, 'day');

        return isInRange;
    });

    return matchingVacations.length > 0;
};

// Verbesserte Funktion zum Abrufen von Urlaubseinträgen für einen bestimmten Tag
const enhancedGetVacationsForDay = (date) => {
    if (!date || !currentUserId.value) return [];

    // Konvertiere das Datum in ein einheitliches Format (nur Datum, keine Zeit)
    const checkDate = dayjs(date).startOf('day');

    // Filtere alle Urlaubseinträge für diesen Tag
    return processedVacations.value.filter(vacation => {
        // Prüfe, ob der Urlaub dem aktuellen Benutzer gehört
        const isCurrentUserVacation =
            vacation.user_id === currentUserId.value ||
            vacation.userId === currentUserId.value;

        if (!isCurrentUserVacation) return false;

        // Konvertiere Start- und Enddatum ebenfalls zu einheitlichen Formaten
        const startDate = dayjs(vacation.startDate).startOf('day');
        const endDate = dayjs(vacation.endDate).startOf('day');

        // Prüfe, ob das Datum im Urlaubszeitraum liegt (inklusive Start- und Enddatum)
        const isInRange = checkDate.isSameOrAfter(startDate, 'day') && checkDate.isSameOrBefore(endDate, 'day');

        return isInRange;
    });
};

const isUserAbsent = (date) => {
    if (!date || !processedEvents.value) return false;

    const dateStr = dayjs(date).format('YYYY-MM-DD');

    // Prüfen, ob der aktuelle Benutzer selbst an diesem Tag abwesend ist
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

    // Wenn wir nur eigene Ereignisse anzeigen, nur eigene Abwesenheit zurückgeben
    if (showOnlyOwnEvents.value) {
        return isCurrentUserAbsent;
    }

    // Wenn der aktuelle Benutzer selbst abwesend ist, zeigen wir das an
    if (isCurrentUserAbsent) {
        return true;
    }

    // Für HR-Benutzer in der "Alle Benutzer" Ansicht: alle Abwesenheiten prüfen
    if (isHrUser.value && !showOnlyOwnEvents.value) {
        const isAnyUserAbsent = processedEvents.value.some(event => {
            const isAbsentEvent = event.type &&
                (event.type.name === 'Abwesend' ||
                    (event.type.value && event.type.value.toLowerCase() === 'absent'));
            const eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
            const eventEndDate = dayjs(event.endDate).format('YYYY-MM-DD');
            const isOnDate = dateStr >= eventStartDate && dateStr <= eventEndDate;

            return isAbsentEvent && isOnDate;
        });
        return isAnyUserAbsent;
    }

    // Für Abteilungsleiter in der Teamansicht: Team-Abwesenheiten prüfen
    if (isTeamManager.value && !showOnlyOwnEvents.value) {
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
    }

    return false;
};

// Fügen Sie eine neue Funktion hinzu, um zu prüfen, ob ein Teammitglied (nicht der Abteilungsleiter selbst) abwesend ist
const checkTeamMemberAbsence = (date) => {
    if (!date || !processedEvents.value || (!isTeamManager.value && !isHrUser.value) || showOnlyOwnEvents.value) return false;

    const dateStr = dayjs(date).format('YYYY-MM-DD');

    if (isHrUser.value) {
        // HR sieht alle Abwesenheiten außer der eigenen
        return processedEvents.value.some(event => {
            const isAbsentEvent = event.type &&
                (event.type.name === 'Abwesend' ||
                    (event.type.value && event.type.value.toLowerCase() === 'absent'));
            const isOtherUserEvent = event.user_id !== currentUserId.value;
            const eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
            const eventEndDate = dayjs(event.endDate).format('YYYY-MM-DD');
            const isOnDate = dateStr >= eventStartDate && dateStr <= eventEndDate;

            return isAbsentEvent && isOtherUserEvent && isOnDate;
        });
    }

    // Für Abteilungsleiter: Nur Team-Mitglieder
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

// Neue Funktion für HR-Benutzer, um alle Abwesenheitseinträge zu sehen
const getAllAbsenceEntriesForDay = (date) => {
    if (!date || !processedEvents.value) return [];

    const dateStr = dayjs(date).format('YYYY-MM-DD');

    // Für HR-Benutzer: Zeige Abwesenheitseinträge basierend auf Filter
    if (isHrUser.value) {
        const absenceEvents = processedEvents.value.filter(event => {
            const isAbsentEvent = event.type && (
                event.type.name === 'Abwesend' ||
                event.type.name === 'Absent' ||
                event.type.value === 'absent' ||
                event.type.value === 'abwesend' ||
                (event.type.value && event.type.value.toLowerCase() === 'absent') ||
                (event.type.value && event.type.value.toLowerCase() === 'abwesend') ||
                event.event_type_id === 4
            );

            const eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
            const eventEndDate = dayjs(event.endDate).format('YYYY-MM-DD');
            const isOnDate = dateStr >= eventStartDate && dateStr <= eventEndDate;

            // Wenn "Nur eigene Events" aktiviert ist, nur eigene Abwesenheiten zeigen
            if (showOnlyOwnEvents.value) {
                return isAbsentEvent && isOnDate && event.user_id === currentUserId.value;
            }

            // Sonst alle Abwesenheitseinträge zeigen
            return isAbsentEvent && isOnDate;
        });

        return absenceEvents;
    }

    // Für andere Benutzer: bestehende Logik beibehalten
    return processedEvents.value.filter(event => {
        const isAbsentEvent = event.type && (
            event.type.name === 'Abwesend' ||
            event.type.name === 'Absent' ||
            event.type.value === 'absent' ||
            event.type.value === 'abwesend' ||
            (event.type.value && event.type.value.toLowerCase() === 'absent') ||
            (event.type.value && event.type.value.toLowerCase() === 'abwesend') ||
            event.event_type_id === 4
        );

        const eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
        const eventEndDate = dayjs(event.endDate).format('YYYY-MM-DD');
        const isOnDate = dateStr >= eventStartDate && dateStr <= eventEndDate;

        const isOwnOrTeamEvent = event.user_id === currentUserId.value ||
            (isTeamManager.value && event.team_id === userTeamId.value);

        return isAbsentEvent && isOnDate && isOwnOrTeamEvent;
    });
};

// Funktion um zu prüfen, ob es Abwesenheitseinträge für einen Tag gibt (für HR)
const hasAbsenceEntriesForDay = (date) => {
    if (!isHrUser.value) return false;
    return getAllAbsenceEntriesForDay(date).length > 0;
};

// Wrapper-Funktionen für Composable-Funktionen
const editEvent = () => {
    editEventFn(findEventType);
};

// NEU: saveEvent Funktion, die das Event vom Dialog empfängt
const saveEvent = async (eventData) => {
    saving.value = true;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
        const config = {
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            withCredentials: true,
        };

        if (eventData.id) {
            // Update existing event
            await axios.put(`/api/events/${eventData.id}`, eventData, config);
            // Wenn das Event erfolgreich aktualisiert wurde, aktualisieren Sie auch das selectedEvent
            selectedEvent.value = { ...selectedEvent.value, ...eventData };
            toast.add({ severity: 'success', summary: 'Erfolgreich', detail: 'Ereignis aktualisiert', life: 3000 });
        } else {
            // Create new event
            await axios.post('/api/events', eventData, config);
            toast.add({ severity: 'success', summary: 'Erfolgreich', detail: 'Ereignis erstellt', life: 3000 });
        }
        closeEventDialog();
        await fetchEvents();
    } catch (error) {
        console.error('Error saving event:', error);
        console.error('Error details:', error.response?.data);
        console.error('Error status:', error.response?.status);
        toast.add({ severity: 'error', summary: 'Fehler', detail: 'Fehler beim Speichern des Ereignisses', life: 3000 });
    } finally {
        saving.value = false;
    }
};

// Neue deleteEvent Funktion die nach dem Löschen beide Dialoge schließt
const deleteEvent = async () => {
    try {
        // Führe die ursprüngliche deleteEvent Funktion aus
        await deleteEventFn();

        // Schließe den Event-Details-Dialog nach erfolgreichem Löschen
        eventDetailsDialogVisible.value = false;

    } catch (error) {
        console.error('Fehler beim Löschen des Events:', error);
    }
};

const handleDayClick = (date) => {
    console.log('StaffCalendar handleDayClick - date:', date);
    console.log('StaffCalendar handleDayClick - isHrUser:', isHrUser.value);
    console.log('StaffCalendar handleDayClick - isTeamManager:', isTeamManager.value);
    console.log('StaffCalendar handleDayClick - enhancedHasVacations:', enhancedHasVacations(date));
    console.log('StaffCalendar handleDayClick - isHoliday:', isHoliday(dayjs(date)));
    console.log('StaffCalendar handleDayClick - isUserAbsent:', isUserAbsent(date));

    if (isHoliday(dayjs(date))) {
        console.log('StaffCalendar handleDayClick - Showing holiday info');
        showHolidayInfo(date);
    } else if (enhancedHasVacations(date) && !isHrUser.value && !isTeamManager.value) {
        // Nur normale Benutzer werden blockiert, HR und TeamManager können weiterhin Einträge erstellen
        console.log('StaffCalendar handleDayClick - Blocked by vacation (normal user)');
        const toastInstance = toast;
        if (toastInstance) {
            toastInstance.add({
                severity: 'info',
                summary: 'Hinweis',
                detail: 'Sie haben an diesem Tag Urlaub. Keine Einträge möglich.',
                life: 3000
            });
        } else {
            alert('Sie haben an diesem Tag Urlaub. Keine Einträge möglich.');
        }
    } else if (isUserAbsent(date) && !checkTeamMemberAbsence(date) && !isHrUser.value) {
        // Wenn der Benutzer selbst an diesem Tag als abwesend markiert ist und kein HR-Mitarbeiter ist,
        // zeige eine Meldung an oder blockiere die Aktion
        console.log('StaffCalendar handleDayClick - Blocked by absence (normal user)');
        const toastInstance = toast;
        if (toastInstance) {
            toastInstance.add({
                severity: 'info',
                summary: 'Hinweis',
                detail: 'Sie sind an diesem Tag als abwesend markiert. Keine Einträge möglich.',
                life: 3000
            });
        } else {
            alert('Sie sind an diesem Tag als abwesend markiert. Keine Einträge möglich.');
        }
    } else {
        console.log('StaffCalendar handleDayClick - Opening event dialog');
        openEventDialog(date);
    }
};

const openWeekPlanDialog = (weekNumber, days) => {
    openWeekPlanDialogFn(weekNumber, days, getEventsForDay);
};

const toggleWeekPlanFilter = () => {
    toggleWeekPlanFilterFn(getEventsForDay);
};

// Daten neu laden, wenn sich relevante Zustände ändern
watch([calendarView, currentDate, showOnlyOwnEvents], () => {
    fetchEvents();
    fetchVacationRequests();
}, { deep: true });

// Ereignistypen überwachen und Ereignisse neu laden, wenn sie sich ändern
watch(eventTypes, () => {
    if (eventTypes.value && eventTypes.value.length > 0) {
        fetchEvents();
    }
}, { deep: true });

// Komponente initialisieren
onMounted(async () => {
    primevue.config.locale = de;

    // console.log('   StaffCalendar mounted - Before fetchUserRole');
    // console.log('isHrUser before fetch:', isHrUser.value);
    // console.log('currentUserId before fetch:', currentUserId.value);

    // Lade Benutzerrolle und warte auf Abschluss
    await fetchUserRole();

    // console.log('StaffCalendar - After fetchUserRole');
    // console.log('User Role geladen. isHrUser:', isHrUser.value, 'isTeamManager:', isTeamManager.value);
    // console.log('currentUserRoleId:', useUserRole().currentUserRoleId.value);

    if (isHrUser.value || isTeamManager.value) {
        // console.log('Lade Employees...');
        await fetchEmployees();
        // console.log('Employees geladen:', employees.value);
        // console.log('Anzahl Employees:', employees.value?.length);
        // console.log('Employees Details:', JSON.stringify(employees.value, null, 2));
    }

    // Für HR-Benutzer und Abteilungsleiter: Intelligente Standardeinstellung
    if (isHrUser.value || isTeamManager.value) {
        const savedFilter = localStorage.getItem('showOnlyOwnEvents');
        if (savedFilter !== null) {
            showOnlyOwnEvents.value = savedFilter === 'true';
        } else {
            // Standardmäßig alle Events anzeigen für HR und Abteilungsleiter
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
        fetchVacationRequests(),
        fetchHolidays(new Date().getFullYear())
    ]);
});

// Jahr-Änderungen überwachen, um Feiertage neu zu laden
watch(
    () => currentDate.value.year(),
    (newYear, oldYear) => {
        if (newYear !== oldYear) {
            fetchHolidays(newYear);
            // Auch Urlaubsdaten für das neue Jahr laden
            fetchVacationRequests();
        }
    }
);
</script>
