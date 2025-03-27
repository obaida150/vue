<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 w-full overflow-x-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div class="flex items-center gap-4">
                <Button icon="pi pi-chevron-left" @click="previousPeriod" />
                <h2 class="text-xl font-semibold capitalize m-0">
                    <span v-if="calendarView === 'month'">{{ currentMonthName }} {{ currentYear }}</span>
                    <span v-else>{{ currentYear }}</span>
                </h2>
                <Button icon="pi pi-chevron-right" @click="nextPeriod" />
            </div>
            <div class="flex items-center gap-4 flex-wrap">
                <!-- Buttons für die Ansichtsumschaltung -->
                <div class="flex gap-1">
                    <Button
                        :class="{ 'p-button-primary': calendarView === 'month', 'p-button-outlined': calendarView !== 'month' }"
                        label="Monat"
                        @click="calendarView = 'month'"
                    />
                    <Button
                        :class="{ 'p-button-primary': calendarView === 'year', 'p-button-outlined': calendarView !== 'year' }"
                        label="Jahr"
                        @click="calendarView = 'year'"
                    />
                </div>

                <!-- Buttons für das Jahres-Layout -->
                <div v-if="calendarView === 'year'" class="flex gap-1">
                    <Button
                        :class="{ 'p-button-primary': yearLayout === '6x2', 'p-button-outlined': yearLayout !== '6x2' }"
                        label="6×2"
                        @click="yearLayout = '6x2'"
                    />
                    <Button
                        :class="{ 'p-button-primary': yearLayout === '4x3', 'p-button-outlined': yearLayout !== '4x3' }"
                        label="4×3"
                        @click="yearLayout = '4x3'"
                    />
                </div>
            </div>
        </div>

        <!-- Monthly Calendar View -->
        <div v-if="calendarView === 'month'" class="w-full overflow-x-auto">
            <div class="flex w-full border-b border-gray-200 dark:border-gray-700 mb-2 min-w-[700px]">
                <div class="w-[50px] min-w-[50px] font-bold text-center py-2">KW</div>
                <div v-for="day in weekdays" :key="day" class="flex-1 text-center font-bold py-2 min-w-[80px]">{{ day }}</div>
            </div>

            <div class="flex flex-col w-full min-w-[700px]">
                <div v-for="(week, weekIndex) in getWeeksInMonth()" :key="'week-' + weekIndex" class="flex w-full mb-[2px]">
                    <div
                        class="w-[50px] min-w-[50px] flex items-center justify-center font-bold text-gray-500 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700 cursor-pointer transition-colors hover:bg-blue-50 dark:hover:bg-blue-900 hover:text-blue-600 dark:hover:text-blue-400"
                        @click="openWeekPlanDialog(week.weekNumber, week.days)"
                    >
                        {{ week.weekNumber }}
                    </div>

                    <div
                        v-for="(day, dayIndex) in week.days"
                        :key="'day-' + dayIndex"
                        class="flex-1 min-w-[80px] border border-gray-200 dark:border-gray-700 p-2 min-h-[100px] cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
                        :class="{
            'bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500': !day.currentMonth,
            'bg-blue-50 dark:bg-blue-900/20 font-bold border border-blue-300 dark:border-blue-700': day.isToday,
            'relative': hasEvents(day.date),
            'bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400': day.isWeekend
          }"
                        @click="openEventDialog(day.date)"
                    >
                        <div class="text-center mb-2">{{ day.dayNumber }}</div>
                        <div class="flex flex-col gap-1">
                            <div
                                v-for="event in getEventsForDay(day.date)"
                                :key="event.id"
                                class="px-1 py-0.5 rounded text-xs text-white whitespace-nowrap overflow-hidden text-ellipsis"
                                :style="{ backgroundColor: event.color }"
                                :title="event.title"
                            >
                                {{ truncateText(event.title, 12) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yearly Calendar View -->
        <div
            v-else-if="calendarView === 'year'"
            class="grid gap-4 w-full"
            :class="{
      'grid-cols-6 md:grid-cols-6 sm:grid-cols-3 xs:grid-cols-2': yearLayout === '6x2',
      'grid-cols-4 md:grid-cols-4 sm:grid-cols-3 xs:grid-cols-2': yearLayout === '4x3'
    }"
        >
            <div
                v-for="month in 12"
                :key="month"
                class="border border-gray-200 dark:border-gray-700 rounded-lg p-2 cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
                @click="goToMonth(month - 1)"
            >
                <h3 class="text-center font-medium mb-2">{{ getMonthName(month - 1) }}</h3>
                <div class="flex w-full border-b border-gray-200 dark:border-gray-700 mb-1">
                    <div class="w-[30px] text-[10px] font-bold text-center p-0.5">KW</div>
                    <div v-for="day in weekdaysShort" :key="day" class="flex-1 text-center text-[10px] p-0.5">{{ day }}</div>
                </div>

                <div class="flex flex-col w-full">
                    <div
                        v-for="(week, weekIndex) in getWeeksInMonthForMini(month - 1)"
                        :key="'mini-week-' + weekIndex"
                        class="flex w-full mb-[1px]"
                    >
                        <div
                            class="w-[30px] flex items-center justify-center text-[10px] text-gray-500 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700 cursor-pointer transition-colors hover:bg-blue-50 dark:hover:bg-blue-900 hover:text-blue-600 dark:hover:text-blue-400"
                            @click.stop="openWeekPlanDialog(week.weekNumber, week.days)"
                        >
                            {{ week.weekNumber }}
                        </div>

                        <div
                            v-for="(day, dayIndex) in week.days"
                            :key="'mini-day-' + dayIndex"
                            class="flex-1 flex flex-col items-center justify-center h-5 text-[11px] text-center relative"
                            :class="{
              'text-gray-400 dark:text-gray-500': !day.currentMonth,
              'bg-blue-500 text-white rounded-full font-bold': day.isToday,
              'text-gray-400 dark:text-gray-500': day.isWeekend
            }"
                        >
                            <div class="z-10">{{ day.dayNumber }}</div>
                            <div
                                v-if="hasEvents(day.date)"
                                class="absolute inset-0 opacity-70 z-0"
                                :style="{ backgroundColor: getEventColorForDay(day.date) }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Event Dialog -->
        <Dialog
            v-model:visible="eventDialogVisible"
            :style="{ width: '500px' }"
            header="Ereignis hinzufügen"
            :modal="true"
            class="event-dialog"
        >
            <div class="flex flex-col gap-4">
                <div>
                    <label for="event-title" class="block mb-2 font-medium">Titel</label>
                    <InputText id="event-title" v-model="newEvent.title" class="w-full" />
                </div>

                <div>
                    <label for="event-type" class="block mb-2 font-medium">Ereignistyp</label>
                    <Dropdown
                        id="event-type"
                        v-model="newEvent.type"
                        :options="eventTypes"
                        optionLabel="name"
                        placeholder="Typ auswählen"
                        class="w-full"
                    />
                </div>

                <div>
                    <label class="block mb-2 font-medium">Zeitraum</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <Calendar
                            v-model="newEvent.startDate"
                            dateFormat="dd.mm.yy"
                            placeholder="Startdatum"
                            class="w-full"
                        />
                        <Calendar
                            v-model="newEvent.endDate"
                            dateFormat="dd.mm.yy"
                            placeholder="Enddatum"
                            class="w-full"
                        />
                    </div>
                </div>

                <div>
                    <label for="event-description" class="block mb-2 font-medium">Beschreibung</label>
                    <Textarea
                        id="event-description"
                        v-model="newEvent.description"
                        rows="3"
                        class="w-full"
                    />
                </div>
            </div>

            <template #footer>
                <Button label="Abbrechen" icon="pi pi-times" @click="closeEventDialog" class="p-button-text" />
                <Button label="Speichern" icon="pi pi-check" @click="saveEvent" :loading="saving" autofocus />
            </template>
        </Dialog>

        <!-- Week Plan Dialog -->
        <Dialog
            v-model:visible="weekPlanDialogVisible"
            :style="{ width: '800px' }"
            :header="`Wochenplanung - KW ${selectedWeekNumber}`"
            :modal="true"
            class="week-plan-dialog"
        >
            <div>
                <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-7 gap-4">
                    <div
                        v-for="(day, index) in weekDays"
                        :key="index"
                        class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                    >
                        <div class="bg-gray-100 dark:bg-gray-800 p-2 text-center">
                            <h3 class="text-base font-medium m-0">{{ weekdays[index] }}</h3>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(day.date) }}</div>
                        </div>

                        <div class="p-2">
                            <Dropdown
                                v-model="day.selectedType"
                                :options="eventTypes"
                                optionLabel="name"
                                placeholder="Status wählen"
                                class="w-full mb-2"
                            />

                            <InputText
                                v-model="day.notes"
                                placeholder="Notizen"
                                class="w-full"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Abbrechen" icon="pi pi-times" @click="closeWeekPlanDialog" class="p-button-text" />
                <Button label="Speichern" icon="pi pi-check" @click="saveWeekPlan" :loading="savingWeekPlan" autofocus />
            </template>
        </Dialog>

        <Toast />
    </div>
</template>

<script setup>
// Korrigiere die Imports der dayjs-Plugins
import { ref, computed, onMounted, watch } from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import weekOfYear from 'dayjs/plugin/weekOfYear';
import isoWeek from 'dayjs/plugin/isoWeek';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore'; // Korrigiert: Richtiger Import für isSameOrBefore
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';
import axios from 'axios';
import Button from 'primevue/button';
import Calendar from 'primevue/calendar';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { usePage } from '@inertiajs/vue3';

// Initialize dayjs plugins
dayjs.extend(weekOfYear);
dayjs.extend(isoWeek);
dayjs.extend(isSameOrBefore);
dayjs.extend(isSameOrAfter);
dayjs.locale('de');

// Toast service
const toast = useToast();

// Calendar view state
const calendarView = ref('month');
const yearLayout = ref('4x3');

// Current date tracking
const currentDate = ref(dayjs());
const selectedDate = ref(null);

// Loading and saving states
const loading = ref(false);
const saving = ref(false);
const savingWeekPlan = ref(false);

// Event dialog state
const eventDialogVisible = ref(false);
const newEvent = ref({
    id: null,
    title: '',
    description: '',
    startDate: null,
    endDate: null,
    type: null,
    color: ''
});

// Week plan dialog state
const weekPlanDialogVisible = ref(false);
const selectedWeekNumber = ref(null);
const weekDays = ref([]);

// Event types with corresponding colors
const eventTypes = computed(() => {
    const types = [
        { name: 'Homeoffice', value: 'homeoffice', color: '#4CAF50' },
        { name: 'Büro', value: 'office', color: '#2196F3' },
        { name: 'Sonstiges', value: 'other', color: '#607D8B' }
    ];

    // Prüfen, ob der Benutzer im Team Vertrieb ist (ID=3)
    const userTeamId = window.Laravel?.user?.current_team_id || null;
    if (userTeamId === 3) {
        types.push({ name: 'Außendienst', value: 'field', color: '#FF9800' });
    }

    // Prüfen, ob der Benutzer im Personal-Team ist
    const userRole = window.Laravel?.user?.role || null;
    if (userRole === 'HR' || userRole === 'Admin') {
        types.push({ name: 'Krank', value: 'sick', color: '#F44336' });
    }

    return types;
});

// Events data
const events = ref([]);

// Computed properties for calendar display
const currentYear = computed(() => currentDate.value.year());
const currentMonthName = computed(() => currentDate.value.format('MMMM'));
const weekdays = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];
const weekdaysShort = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

// Helper function to get all days in the current month organized by weeks
const getWeeksInMonth = () => {
    const firstDayOfMonth = currentDate.value.startOf('month');
    const lastDayOfMonth = currentDate.value.endOf('month');

    // Get the first day to display (might be from previous month)
    // In Europe, weeks typically start on Monday (1) rather than Sunday (0)
    let startDay = firstDayOfMonth.day();
    startDay = startDay === 0 ? 6 : startDay - 1; // Adjust for Monday start

    const firstCalendarDay = firstDayOfMonth.subtract(startDay, 'day');

    const weeks = [];
    const today = dayjs();

    // Generate 6 weeks
    for (let weekIndex = 0; weekIndex < 6; weekIndex++) {
        const days = [];
        const weekStartDay = firstCalendarDay.add(weekIndex * 7, 'day');
        const weekNumber = weekStartDay.isoWeek();

        // Generate 7 days for each week
        for (let dayIndex = 0; dayIndex < 7; dayIndex++) {
            const date = weekStartDay.add(dayIndex, 'day');
            const isWeekend = date.day() === 0 || date.day() === 6; // 0 = Sonntag, 6 = Samstag

            days.push({
                date: date,
                dayNumber: date.date(),
                currentMonth: date.month() === currentDate.value.month(),
                isToday: date.format('YYYY-MM-DD') === today.format('YYYY-MM-DD'),
                isWeekend: isWeekend
            });
        }

        weeks.push({
            weekNumber: weekNumber,
            days: days
        });
    }

    return weeks;
};

// Helper function to get all days in a specific month organized by weeks (for mini calendar)
const getWeeksInMonthForMini = (monthIndex) => {
    const date = dayjs().year(currentYear.value).month(monthIndex);
    const firstDayOfMonth = date.startOf('month');

    // Get the first day to display (might be from previous month)
    let startDay = firstDayOfMonth.day();
    startDay = startDay === 0 ? 6 : startDay - 1; // Adjust for Monday start

    const firstCalendarDay = firstDayOfMonth.subtract(startDay, 'day');

    const weeks = [];
    const today = dayjs();

    // Generate 6 weeks
    for (let weekIndex = 0; weekIndex < 6; weekIndex++) {
        const days = [];
        const weekStartDay = firstCalendarDay.add(weekIndex * 7, 'day');
        const weekNumber = weekStartDay.isoWeek();

        // Generate 7 days for each week
        for (let dayIndex = 0; dayIndex < 7; dayIndex++) {
            const date = weekStartDay.add(dayIndex, 'day');
            const isWeekend = date.day() === 0 || date.day() === 6; // 0 = Sonntag, 6 = Samstag

            days.push({
                date: date,
                dayNumber: date.date(),
                currentMonth: date.month() === monthIndex,
                isToday: date.format('YYYY-MM-DD') === today.format('YYYY-MM-DD'),
                isWeekend: isWeekend
            });
        }

        weeks.push({
            weekNumber: weekNumber,
            days: days
        });
    }

    return weeks;
};

// Methods for calendar navigation
const previousPeriod = () => {
    if (calendarView.value === 'month') {
        currentDate.value = currentDate.value.subtract(1, 'month');
    } else {
        currentDate.value = currentDate.value.subtract(1, 'year');
    }
    fetchEvents();
};

const nextPeriod = () => {
    if (calendarView.value === 'month') {
        currentDate.value = currentDate.value.add(1, 'month');
    } else {
        currentDate.value = currentDate.value.add(1, 'year');
    }
    fetchEvents();
};

const goToMonth = (monthIndex) => {
    currentDate.value = dayjs().year(currentYear.value).month(monthIndex);
    calendarView.value = 'month';
    fetchEvents();
};

const getMonthName = (monthIndex) => {
    return dayjs().month(monthIndex).format('MMM');
};

// Format date for display
const formatDate = (date) => {
    return date.format('DD.MM.YYYY');
};

// Event handling methods
const openEventDialog = (date) => {
    selectedDate.value = date;
    newEvent.value = {
        id: null,
        title: '',
        description: '',
        startDate: date.toDate(),
        endDate: date.toDate(),
        type: null,
        color: ''
    };
    eventDialogVisible.value = true;
};

const closeEventDialog = () => {
    eventDialogVisible.value = false;
    newEvent.value = {
        id: null,
        title: '',
        description: '',
        startDate: null,
        endDate: null,
        type: null,
        color: ''
    };
};

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

    saving.value = true;

    try {
        const eventData = {
            title: newEvent.value.title,
            description: newEvent.value.description,
            start_date: dayjs(newEvent.value.startDate).format('YYYY-MM-DD'),
            end_date: dayjs(newEvent.value.endDate).format('YYYY-MM-DD'),
            event_type_id: getEventTypeId(newEvent.value.type.value),
            is_all_day: true
        };

        // CSRF-Token aus dem Meta-Tag holen
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Axios-Konfiguration
        const config = {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            withCredentials: true
        };

        let response;

        if (newEvent.value.id) {
            // Update existing event
            response = await axios.put(`/api/events/${newEvent.value.id}`, eventData, config);
            toast.add({
                severity: 'success',
                summary: 'Erfolg',
                detail: 'Ereignis wurde aktualisiert',
                life: 3000
            });
        } else {
            // Create new event
            response = await axios.post('/api/events', eventData, config);
            toast.add({
                severity: 'success',
                summary: 'Erfolg',
                detail: 'Ereignis wurde erstellt',
                life: 3000
            });
        }

        // Refresh events
        fetchEvents();
        closeEventDialog();
    } catch (error) {
        console.error('Fehler beim Speichern des Ereignisses:', error);
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Das Ereignis konnte nicht gespeichert werden.',
            life: 3000
        });
    } finally {
        saving.value = false;
    }
};

// Week plan methods
const openWeekPlanDialog = (weekNumber, days) => {
    selectedWeekNumber.value = weekNumber;

    // Initialize week days with existing events
    weekDays.value = days.map(day => {
        const existingEvents = getEventsForDay(day.date);
        const existingEvent = existingEvents.length > 0 ? existingEvents[0] : null;

        return {
            date: day.date,
            dayName: weekdays[day.date.day() === 0 ? 6 : day.date.day() - 1],
            selectedType: existingEvent ? existingEvent.type : null,
            notes: existingEvent ? existingEvent.description : '',
            eventId: existingEvent ? existingEvent.id : null,
            isWeekend: day.isWeekend
        };
    });

    weekPlanDialogVisible.value = true;
};

const closeWeekPlanDialog = () => {
    weekPlanDialogVisible.value = false;
    selectedWeekNumber.value = null;
    weekDays.value = [];
};

const saveWeekPlan = async () => {
    savingWeekPlan.value = true;

    try {
        // CSRF-Token aus dem Meta-Tag holen
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Axios-Konfiguration für alle Anfragen
        const config = {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            withCredentials: true // Wichtig: Stellt sicher, dass Cookies gesendet werden
        };

        // Create an array of promises for all API calls
        const promises = weekDays.value.map(async (day) => {
            if (day.selectedType && !day.isWeekend) { // Keine Events für Wochenenden speichern
                const eventData = {
                    title: day.selectedType.name,
                    description: day.notes || '',
                    start_date: day.date.format('YYYY-MM-DD'),
                    end_date: day.date.format('YYYY-MM-DD'),
                    event_type_id: getEventTypeId(day.selectedType.value),
                    is_all_day: true
                };

                if (day.eventId) {
                    // Update existing event
                    return axios.put(`/api/events/${day.eventId}`, eventData, config);
                } else {
                    // Create new event
                    return axios.post('/api/events', eventData, config);
                }
            }
            return Promise.resolve(); // No action needed if no type selected or weekend
        });

        // Wait for all API calls to complete
        await Promise.all(promises);

        toast.add({
            severity: 'success',
            summary: 'Erfolg',
            detail: 'Wochenplanung wurde gespeichert',
            life: 3000
        });

        // Refresh events
        fetchEvents();
        closeWeekPlanDialog();
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
    }
};

// Helper methods for events
const hasEvents = (date) => {
    return getEventsForDay(date).length > 0;
};

const getEventsForDay = (date) => {
    // Prüfen, ob es ein Wochenende ist
    const isWeekend = date.day() === 0 || date.day() === 6; // 0 = Sonntag, 6 = Samstag

    return events.value.filter(event => {
        // Wenn es ein Wochenende ist und der Event-Typ Urlaub ist, dann ignorieren
        if (isWeekend && event.type && event.type.value === 'vacation') {
            return false;
        }

        const eventStart = dayjs(event.startDate);
        const eventEnd = dayjs(event.endDate);
        return date.isSameOrAfter(eventStart, 'day') && date.isSameOrBefore(eventEnd, 'day');
    });
};

const getEventColorForDay = (date) => {
    const dayEvents = getEventsForDay(date);
    if (dayEvents.length > 0) {
        return dayEvents[0].color; // Return the color of the first event
    }
    return null;
};

const truncateText = (text, maxLength) => {
    if (!text) return '';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
};

// Get event type ID from value
const getEventTypeId = (value) => {
    // This would typically come from your backend
    // For now, we'll use a simple mapping
    const eventTypeMap = {
        'homeoffice': 1,
        'office': 2,
        'field': 3,
        'sick': 4,
        'vacation': 5,
        'other': 6
    };

    return eventTypeMap[value] || 1;
};

const fetchEvents = async () => {
    loading.value = true;

    try {
        // Calculate date range based on current view
        let startDate, endDate;

        if (calendarView.value === 'month') {
            // For month view, fetch events for the visible month plus padding weeks
            const firstDay = currentDate.value.startOf('month').subtract(7, 'day');
            const lastDay = currentDate.value.endOf('month').add(7, 'day');
            startDate = firstDay.format('YYYY-MM-DD');
            endDate = lastDay.format('YYYY-MM-DD');
        } else {
            // For year view, fetch events for the entire year
            startDate = currentDate.value.startOf('year').format('YYYY-MM-DD');
            endDate = currentDate.value.endOf('year').format('YYYY-MM-DD');
        }

        // CSRF-Token aus dem Meta-Tag holen
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Axios-Konfiguration
        const config = {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            withCredentials: true,
            params: {
                start_date: startDate,
                end_date: endDate
            }
        };

        // Fetch regular events
        const eventsResponse = await axios.get('/api/events', config);

        // Fetch vacation requests
        const vacationResponse = await axios.get('/api/vacation/user-requests', config);

        // Transform API response for regular events
        const regularEvents = eventsResponse.data.map(event => {
            // Find the corresponding event type
            const eventTypeValue = typeof event.event_type === 'object' ?
                event.event_type.name.toLowerCase() :
                (typeof event.event_type === 'string' ? event.event_type.toLowerCase() : 'other');

            const eventType = eventTypes.value.find(type => type.value === eventTypeValue) ||
                { name: 'Sonstiges', value: 'other', color: '#607D8B' }; // Default to 'other'

            return {
                id: event.id,
                title: event.title,
                description: event.description,
                startDate: new Date(event.start_date),
                endDate: new Date(event.end_date),
                type: eventType,
                color: eventType.color,
                source: 'event'
            };
        });

        // Transform vacation requests
        const vacationEvents = vacationResponse.data.map(vacation => {
            // Create a vacation event type
            const vacationType = { name: 'Urlaub', value: 'vacation', color: '#9C27B0' };

            return {
                id: `vacation-${vacation.id}`,
                title: 'Urlaub',
                description: vacation.notes || 'Genehmigter Urlaub',
                startDate: new Date(vacation.start_date),
                endDate: new Date(vacation.end_date),
                type: vacationType,
                color: vacationType.color,
                source: 'vacation',
                status: vacation.status
            };
        }).filter(vacation => vacation.status === 'approved'); // Nur genehmigte Urlaubsanträge anzeigen

        // Combine both types of events
        events.value = [...regularEvents, ...vacationEvents];
    } catch (error) {
        console.error('Fehler beim Laden der Ereignisse:', error);
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Die Ereignisse konnten nicht geladen werden.',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

// Axios konfigurieren
const setupAxios = () => {
    // Globale Axios-Konfiguration
    axios.defaults.withCredentials = true;
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    // CSRF-Token aus dem Meta-Tag holen
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
    }
};

// Watch for changes in calendar view to refresh events
watch(calendarView, () => {
    fetchEvents();
});

// Initialize component
onMounted(() => {
    setupAxios();
    fetchEvents();
});
</script>

