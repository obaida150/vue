<template>
    <div class="staff-calendar">
        <div class="calendar-header">
            <div class="calendar-controls">
                <Button icon="pi pi-chevron-left" @click="previousPeriod" />
                <h2 class="month-title">
                    <span v-if="calendarView === 'month'">{{ currentMonthName }} {{ currentYear }}</span>
                    <span v-else>{{ currentYear }}</span>
                </h2>
                <Button icon="pi pi-chevron-right" @click="nextPeriod" />
            </div>
            <div class="view-controls">
                <div class="flex items-center gap-4">
                    <!-- Buttons für die Ansichtsumschaltung -->
                    <div class="view-toggle">
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
                    <div v-if="calendarView === 'year'" class="year-layout-controls">
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
        </div>

        <!-- Monthly Calendar View -->
        <div v-if="calendarView === 'month'" class="month-view">
            <div class="weekdays-row">
                <div class="week-number-header">KW</div>
                <div v-for="day in weekdays" :key="day" class="weekday">{{ day }}</div>
            </div>

            <div class="calendar-body">
                <div v-for="(week, weekIndex) in getWeeksInMonth()" :key="'week-' + weekIndex" class="week-row">
                    <div
                        class="week-number"
                        @click="openWeekPlanDialog(week.weekNumber, week.days)"
                    >
                        {{ week.weekNumber }}
                    </div>

                    <div
                        v-for="(day, dayIndex) in week.days"
                        :key="'day-' + dayIndex"
                        class="day-cell"
                        :class="{
              'other-month': !day.currentMonth,
              'today': day.isToday,
              'has-events': hasEvents(day.date)
            }"
                        @click="openEventDialog(day.date)"
                    >
                        <div class="day-number">{{ day.dayNumber }}</div>
                        <div class="day-events">
                            <div
                                v-for="event in getEventsForDay(day.date)"
                                :key="event.id"
                                class="event-indicator"
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
            class="year-view"
            :class="{
        'grid-6x2': yearLayout === '6x2',
        'grid-4x3': yearLayout === '4x3'
      }"
        >
            <div
                v-for="month in 12"
                :key="month"
                class="mini-month"
                @click="goToMonth(month - 1)"
            >
                <h3>{{ getMonthName(month - 1) }}</h3>
                <div class="mini-weekdays-row">
                    <div class="mini-week-number-header">KW</div>
                    <div v-for="day in weekdaysShort" :key="day" class="mini-weekday">{{ day }}</div>
                </div>

                <div class="mini-calendar-body">
                    <div
                        v-for="(week, weekIndex) in getWeeksInMonthForMini(month - 1)"
                        :key="'mini-week-' + weekIndex"
                        class="mini-week-row"
                    >
                        <div
                            class="mini-week-number"
                            @click.stop="openWeekPlanDialog(week.weekNumber, week.days)"
                        >
                            {{ week.weekNumber }}
                        </div>

                        <div
                            v-for="(day, dayIndex) in week.days"
                            :key="'mini-day-' + dayIndex"
                            class="mini-day"
                            :class="{
                'other-month': !day.currentMonth,
                'today': day.isToday,
                'has-events': hasEvents(day.date)
              }"
                        >
                            {{ day.dayNumber }}
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
            <div class="event-form">
                <div class="field">
                    <label for="event-title">Titel</label>
                    <InputText id="event-title" v-model="newEvent.title" class="w-full" />
                </div>

                <div class="field">
                    <label for="event-type">Ereignistyp</label>
                    <Dropdown
                        id="event-type"
                        v-model="newEvent.type"
                        :options="eventTypes"
                        optionLabel="name"
                        placeholder="Typ auswählen"
                        class="w-full"
                    />
                </div>

                <div class="field">
                    <label>Zeitraum</label>
                    <div class="date-range">
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

                <div class="field">
                    <label for="event-description">Beschreibung</label>
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
                <Button label="Speichern" icon="pi pi-check" @click="saveEvent" autofocus />
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
            <div class="week-plan-form">
                <div class="week-days-grid">
                    <div
                        v-for="(day, index) in weekDays"
                        :key="index"
                        class="week-day-card"
                    >
                        <div class="week-day-header">
                            <h3>{{ weekdays[index] }}</h3>
                            <div class="week-day-date">{{ formatDate(day.date) }}</div>
                        </div>

                        <div class="week-day-content">
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
                <Button label="Speichern" icon="pi pi-check" @click="saveWeekPlan" autofocus />
            </template>
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
import Calendar from 'primevue/calendar';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';

// Initialize dayjs plugins
dayjs.extend(weekOfYear);
dayjs.extend(isoWeek);
dayjs.extend(isSameOrBefore);
dayjs.extend(isSameOrAfter);
dayjs.locale('de');

// Calendar view state
const calendarView = ref('month');
const yearLayout = ref('4x3');

// Current date tracking
const currentDate = ref(dayjs());
const selectedDate = ref(null);

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
const eventTypes = [
    { name: 'Homeoffice', value: 'homeoffice', color: '#4CAF50' },
    { name: 'Büro', value: 'office', color: '#2196F3' },
    { name: 'Außendienst', value: 'field', color: '#FF9800' },
    { name: 'Krank', value: 'sick', color: '#F44336' },
    { name: 'Urlaub', value: 'vacation', color: '#9C27B0' },
    { name: 'Sonstiges', value: 'other', color: '#607D8B' }
];

// Sample events data (would be fetched from API in real implementation)
const events = ref([
    {
        id: 1,
        title: 'Homeoffice',
        description: 'Arbeiten von zu Hause',
        startDate: dayjs().subtract(2, 'day').toDate(),
        endDate: dayjs().add(1, 'day').toDate(),
        type: { name: 'Homeoffice', value: 'homeoffice', color: '#4CAF50' },
        color: '#4CAF50'
    },
    {
        id: 2,
        title: 'Urlaub',
        description: 'Sommerurlaub',
        startDate: dayjs().add(5, 'day').toDate(),
        endDate: dayjs().add(12, 'day').toDate(),
        type: { name: 'Urlaub', value: 'vacation', color: '#9C27B0' },
        color: '#9C27B0'
    }
]);

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
            days.push({
                date: date,
                dayNumber: date.date(),
                currentMonth: date.month() === currentDate.value.month(),
                isToday: date.format('YYYY-MM-DD') === today.format('YYYY-MM-DD')
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
            days.push({
                date: date,
                dayNumber: date.date(),
                currentMonth: date.month() === monthIndex,
                isToday: date.format('YYYY-MM-DD') === today.format('YYYY-MM-DD')
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
};

const nextPeriod = () => {
    if (calendarView.value === 'month') {
        currentDate.value = currentDate.value.add(1, 'month');
    } else {
        currentDate.value = currentDate.value.add(1, 'year');
    }
};

const goToMonth = (monthIndex) => {
    currentDate.value = dayjs().year(currentYear.value).month(monthIndex);
    calendarView.value = 'month';
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

const saveEvent = () => {
    if (!newEvent.value.title || !newEvent.value.type) {
        // Handle validation (would use proper validation in real implementation)
        alert('Bitte füllen Sie alle erforderlichen Felder aus.');
        return;
    }

    const eventToSave = {
        ...newEvent.value,
        id: newEvent.value.id || Date.now(), // Generate ID if new event
        color: newEvent.value.type.color
    };

    if (newEvent.value.id) {
        // Update existing event
        const index = events.value.findIndex(e => e.id === newEvent.value.id);
        if (index !== -1) {
            events.value[index] = eventToSave;
        }
    } else {
        // Add new event
        events.value.push(eventToSave);
    }

    // In a real application, you would send this to your backend API
    // axios.post('/api/events', eventToSave);

    closeEventDialog();
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
            notes: existingEvent ? existingEvent.description : ''
        };
    });

    weekPlanDialogVisible.value = true;
};

const closeWeekPlanDialog = () => {
    weekPlanDialogVisible.value = false;
    selectedWeekNumber.value = null;
    weekDays.value = [];
};

const saveWeekPlan = () => {
    // For each day in the week, create or update an event
    weekDays.value.forEach(day => {
        if (day.selectedType) {
            // Check if there's already an event for this day
            const existingEvents = getEventsForDay(day.date);
            const existingEvent = existingEvents.length > 0 ? existingEvents[0] : null;

            const eventData = {
                title: day.selectedType.name,
                description: day.notes || '',
                startDate: day.date.toDate(),
                endDate: day.date.toDate(),
                type: day.selectedType,
                color: day.selectedType.color
            };

            if (existingEvent) {
                // Update existing event
                const index = events.value.findIndex(e => e.id === existingEvent.id);
                if (index !== -1) {
                    events.value[index] = {
                        ...eventData,
                        id: existingEvent.id
                    };
                }
            } else {
                // Add new event
                events.value.push({
                    ...eventData,
                    id: Date.now() + Math.floor(Math.random() * 1000) // Generate unique ID
                });
            }
        }
    });

    // In a real application, you would send this to your backend API
    // axios.post('/api/week-plan', { weekNumber: selectedWeekNumber.value, days: weekDays.value });

    closeWeekPlanDialog();
};

// Helper methods for events
const hasEvents = (date) => {
    return getEventsForDay(date).length > 0;
};

const getEventsForDay = (date) => {
    return events.value.filter(event => {
        const eventStart = dayjs(event.startDate);
        const eventEnd = dayjs(event.endDate);
        return date.isSameOrAfter(eventStart, 'day') && date.isSameOrBefore(eventEnd, 'day');
    });
};

const truncateText = (text, maxLength) => {
    if (!text) return '';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
};

// Initialize component
onMounted(() => {
    // In a real application, you would fetch events from your API here
    // fetchEvents();
});
</script>

<style scoped>
.staff-calendar {
    font-family: var(--font-family);
    background-color: var(--surface-a);
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    padding: 1.5rem;
    width: 100%;
    overflow-x: auto;
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

.month-title {
    margin: 0;
    font-size: 1.5rem;
    text-transform: capitalize;
}

/* View Controls Styles */
.view-toggle, .year-layout-controls {
    display: flex;
    gap: 0.25rem;
}

/* Month View Styles */
.month-view {
    width: 100%;
    overflow-x: auto;
}

.weekdays-row {
    display: flex;
    width: 100%;
    border-bottom: 1px solid var(--surface-d);
    margin-bottom: 0.5rem;
    min-width: 700px; /* Ensure minimum width for small screens */
}

.week-number-header {
    width: 50px;
    min-width: 50px;
    font-weight: bold;
    text-align: center;
    padding: 0.5rem;
}

.weekday {
    flex: 1;
    text-align: center;
    font-weight: bold;
    padding: 0.5rem;
    min-width: 80px; /* Ensure minimum width for each day */
}

.calendar-body {
    display: flex;
    flex-direction: column;
    width: 100%;
    min-width: 700px; /* Ensure minimum width for small screens */
}

.week-row {
    display: flex;
    width: 100%;
    margin-bottom: 2px;
}

.week-number {
    width: 50px;
    min-width: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: var(--text-color-secondary);
    border-right: 1px solid var(--surface-d);
    cursor: pointer;
    transition: background-color 0.2s;
}

.week-number:hover {
    background-color: var(--primary-50);
    color: var(--primary-color);
}

.day-cell {
    flex: 1;
    min-width: 80px; /* Ensure minimum width for each day */
    border: 1px solid var(--surface-d);
    padding: 0.5rem;
    min-height: 100px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.day-cell:hover {
    background-color: var(--surface-hover);
}

.day-cell.other-month {
    background-color: var(--surface-c);
    color: var(--text-color-secondary);
}

.day-cell.today {
    background-color: var(--primary-50);
    font-weight: bold;
}

.day-cell.has-events {
    position: relative;
}

.day-number {
    font-size: 1rem;
    margin-bottom: 0.5rem;
    text-align: center;
}

.day-events {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.event-indicator {
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 0.75rem;
    color: white;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Year View Styles */
.year-view {
    display: grid;
    gap: 1rem;
    width: 100%;
}

.year-view.grid-6x2 {
    grid-template-columns: repeat(6, 1fr);
    grid-template-rows: repeat(2, auto);
}

.year-view.grid-4x3 {
    grid-template-columns: repeat(4, 1fr);
    grid-template-rows: repeat(3, auto);
}

/* Responsive grid for year view */
@media (max-width: 1200px) {
    .year-view.grid-6x2 {
        grid-template-columns: repeat(3, 1fr);
        grid-template-rows: repeat(4, auto);
    }

    .year-view.grid-4x3 {
        grid-template-columns: repeat(3, 1fr);
        grid-template-rows: repeat(4, auto);
    }
}

@media (max-width: 768px) {
    .year-view.grid-6x2,
    .year-view.grid-4x3 {
        grid-template-columns: repeat(2, 1fr);
        grid-template-rows: repeat(6, auto);
    }
}

@media (max-width: 480px) {
    .year-view.grid-6x2,
    .year-view.grid-4x3 {
        grid-template-columns: 1fr;
        grid-template-rows: repeat(12, auto);
    }

    .calendar-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .view-controls {
        width: 100%;
    }

    .view-controls .flex {
        flex-direction: column;
        gap: 0.5rem;
    }
}

.mini-month {
    border: 1px solid var(--surface-d);
    border-radius: var(--border-radius);
    padding: 0.5rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.mini-month:hover {
    background-color: var(--surface-hover);
}

.mini-month h3 {
    margin: 0 0 0.5rem 0;
    text-align: center;
    font-size: 1rem;
}

.mini-weekdays-row {
    display: flex;
    width: 100%;
    border-bottom: 1px solid var(--surface-d);
    margin-bottom: 0.25rem;
}

.mini-week-number-header {
    width: 30px;
    font-size: 0.6rem;
    font-weight: bold;
    text-align: center;
    padding: 2px;
}

.mini-weekday {
    flex: 1;
    text-align: center;
    font-size: 0.6rem;
    padding: 2px;
}

.mini-calendar-body {
    display: flex;
    flex-direction: column;
    width: 100%;
}

.mini-week-row {
    display: flex;
    width: 100%;
    margin-bottom: 1px;
}

.mini-week-number {
    width: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    color: var(--text-color-secondary);
    border-right: 1px solid var(--surface-d);
    cursor: pointer;
    transition: background-color 0.2s;
}

.mini-week-number:hover {
    background-color: var(--primary-50);
    color: var(--primary-color);
}

.mini-day {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 20px;
    font-size: 0.7rem;
    text-align: center;
}

.mini-day.other-month {
    color: var(--text-color-secondary);
}

.mini-day.today {
    background-color: var(--primary-color);
    color: var(--primary-color-text);
    border-radius: 50%;
}

.mini-day.has-events::after {
    content: '';
    position: absolute;
    bottom: 2px;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background-color: var(--primary-color);
}

/* Week Plan Dialog Styles */
.week-days-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1rem;
}

/* Responsive grid for week plan */
@media (max-width: 1200px) {
    .week-days-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 768px) {
    .week-days-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .week-days-grid {
        grid-template-columns: 1fr;
    }
}

.week-day-card {
    border: 1px solid var(--surface-d);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.week-day-header {
    background-color: var(--surface-c);
    padding: 0.5rem;
    text-align: center;
}

.week-day-header h3 {
    margin: 0;
    font-size: 1rem;
}

.week-day-date {
    font-size: 0.8rem;
    color: var(--text-color-secondary);
}

.week-day-content {
    padding: 0.5rem;
}

/* Event Dialog Styles */
.event-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.field label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.date-range {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}

@media (max-width: 480px) {
    .date-range {
        grid-template-columns: 1fr;
    }
}

/* Utility Classes */
.flex {
    display: flex;
}

.items-center {
    align-items: center;
}

.gap-4 {
    gap: 1rem;
}

.w-full {
    width: 100%;
}

.mb-2 {
    margin-bottom: 0.5rem;
}
</style>

