<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6 w-full overflow-hidden">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-2 sm:gap-4">
            <div class="flex items-center gap-2 sm:gap-4 w-full sm:w-auto">
                <Button icon="pi pi-chevron-left" @click="previousPeriod" class="p-button-sm sm:p-button-md" />
                <h2 class="text-lg sm:text-xl font-semibold capitalize m-0">
                    <span v-if="calendarView === 'month'">{{ currentMonthName }} {{ currentYear }}</span>
                    <span v-else>{{ currentYear }}</span>
                </h2>
                <Button icon="pi pi-chevron-right" @click="nextPeriod" class="p-button-sm sm:p-button-md" />
            </div>
            <div class="flex items-center gap-2 sm:gap-4 flex-wrap w-full sm:w-auto justify-start sm:justify-end mt-2 sm:mt-0">
                <!-- Buttons für die Ansichtsumschaltung -->
                <div class="flex gap-1">
                    <Button
                        :class="{ 'p-button-primary': calendarView === 'month', 'p-button-outlined': calendarView !== 'month' }"
                        label="Monat"
                        @click="calendarView = 'month'"
                        class="p-button-sm sm:p-button-md"
                    />
                    <Button
                        :class="{ 'p-button-primary': calendarView === 'year', 'p-button-outlined': calendarView !== 'year' }"
                        label="Jahr"
                        @click="calendarView = 'year'"
                        class="p-button-sm sm:p-button-md"
                    />
                </div>

                <!-- Buttons für das Jahres-Layout -->
                <div v-if="calendarView === 'year'" class="flex gap-1">
                    <Button
                        :class="{ 'p-button-primary': yearLayout === '6x2', 'p-button-outlined': yearLayout !== '6x2' }"
                        label="6×2"
                        @click="yearLayout = '6x2'"
                        class="p-button-sm sm:p-button-md"
                    />
                    <Button
                        :class="{ 'p-button-primary': yearLayout === '4x3', 'p-button-outlined': yearLayout !== '4x3' }"
                        label="4×3"
                        @click="yearLayout = '4x3'"
                        class="p-button-sm sm:p-button-md"
                    />
                </div>
            </div>
        </div>

        <!-- Monthly Calendar View -->
        <div v-if="calendarView === 'month'" class="w-full overflow-x-auto">
            <div class="min-w-[700px]">
                <div class="flex w-full border-b border-gray-200 dark:border-gray-700 mb-2">
                    <div class="w-[50px] min-w-[50px] font-bold text-center py-2">KW</div>
                    <div v-for="day in weekdays" :key="day" class="flex-1 text-center font-bold py-2 min-w-[80px]">{{ day }}</div>
                </div>

                <div class="flex flex-col w-full">
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
                            class="flex-1 min-w-[80px] border border-gray-200 dark:border-gray-700 p-1 sm:p-2 min-h-[80px] sm:min-h-[100px] cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
                            :class="{
                            'bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500': !day.currentMonth,
                            'bg-blue-50 dark:bg-blue-900/20 font-bold border border-blue-300 dark:border-blue-700': day.isToday,
                            'relative': hasEvents(day.date),
                            'bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400': day.isWeekend
                        }"
                            @click="openEventDialog(day.date)"
                        >
                            <div class="text-center mb-1 sm:mb-2 text-xs sm:text-base">{{ day.dayNumber }}</div>
                            <div class="flex flex-col gap-1">
                                <div
                                    v-for="event in getEventsForDay(day.date)"
                                    :key="event.id"
                                    class="px-1 py-0.5 rounded text-[10px] sm:text-xs text-white whitespace-nowrap overflow-hidden text-ellipsis flex justify-between items-center"
                                    :style="{ backgroundColor: event.color }"
                                    :title="event.title"
                                    @click.stop="openEventDetailsDialog(event)"
                                >
                                    <span>{{ truncateText(event.title, 12) }}</span>
                                    <i class="pi pi-ellipsis-h text-[8px] sm:text-[10px] opacity-70 hover:opacity-100"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yearly Calendar View -->
        <div
            v-else-if="calendarView === 'year'"
            class="grid gap-2 sm:gap-4 w-full"
            :class="{
            'grid-cols-1 xs:grid-cols-2 sm:grid-cols-3 md:grid-cols-6': yearLayout === '6x2',
            'grid-cols-1 xs:grid-cols-2 sm:grid-cols-3 md:grid-cols-4': yearLayout === '4x3'
        }"
        >
            <div
                v-for="month in 12"
                :key="month"
                class="border border-gray-200 dark:border-gray-700 rounded-lg p-1 sm:p-2 cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
                @click="goToMonth(month - 1)"
            >
                <h3 class="text-center font-medium mb-1 sm:mb-2 text-sm sm:text-base">{{ getMonthName(month - 1) }}</h3>
                <div class="flex w-full border-b border-gray-200 dark:border-gray-700 mb-1">
                    <div class="w-[20px] sm:w-[30px] text-[8px] sm:text-[10px] font-bold text-center p-0.5">KW</div>
                    <div v-for="day in weekdaysShort" :key="day" class="flex-1 text-center text-[8px] sm:text-[10px] p-0.5">{{ day }}</div>
                </div>

                <div class="flex flex-col w-full">
                    <div
                        v-for="(week, weekIndex) in getWeeksInMonthForMini(month - 1)"
                        :key="'mini-week-' + weekIndex"
                        class="flex w-full mb-[1px]"
                    >
                        <div
                            class="w-[20px] sm:w-[30px] flex items-center justify-center text-[8px] sm:text-[10px] text-gray-500 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700 cursor-pointer transition-colors hover:bg-blue-50 dark:hover:bg-blue-900 hover:text-blue-600 dark:hover:text-blue-400"
                            @click.stop="openWeekPlanDialog(week.weekNumber, week.days)"
                        >
                            {{ week.weekNumber }}
                        </div>

                        <div
                            v-for="(day, dayIndex) in week.days"
                            :key="'mini-day-' + dayIndex"
                            class="flex-1 flex flex-col items-center justify-center h-4 sm:h-5 text-[9px] sm:text-[11px] text-center relative"
                            :class="{
                            'text-gray-400 dark:text-gray-500': !day.currentMonth,
                            'bg-blue-500 text-white rounded-full font-bold': day.isToday,
                            'text-gray-400 dark:text-gray-500': day.isWeekend
                        }"
                        >
                            <div class="z-10">{{ day.dayNumber }}</div>
                            <div
                                v-if="hasEvents(day.date)"
                                class="absolute inset-0 opacity-70 z-0 rounded-full"
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
            :style="{ width: '90vw', maxWidth: '500px' }"
            header="Ereignis hinzufügen"
            :modal="true"
            class="event-dialog"
        >
            <div class="flex flex-col gap-3 sm:gap-4">
                <div>
                    <label for="event-title" class="block mb-1 sm:mb-2 font-medium">Titel</label>
                    <InputText id="event-title" v-model="newEvent.title" class="w-full" />
                </div>

                <div>
                    <label for="event-type" class="block mb-1 sm:mb-2 font-medium">Ereignistyp</label>
                    <Select
                        id="event-type"
                        v-model="newEvent.type"
                        :options="eventTypes"
                        optionLabel="name"
                        placeholder="Typ auswählen"
                        class="w-full"
                    />
                </div>

                <div>
                    <label class="block mb-1 sm:mb-2 font-medium">Zeitraum</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <DatePicker
                            v-model="newEvent.startDate"
                            dateFormat="dd.mm.yy"
                            placeholder="Startdatum"
                            class="w-full"
                        />
                        <DatePicker
                            v-model="newEvent.endDate"
                            dateFormat="dd.mm.yy"
                            placeholder="Enddatum"
                            class="w-full"
                        />
                    </div>
                </div>

                <div>
                    <div class="flex items-center gap-2 mb-1 sm:mb-2">
                        <Checkbox v-model="newEvent.isAllDay" :binary="true" inputId="is-all-day" />
                        <label for="is-all-day" class="font-medium">Ganztägig</label>
                    </div>
                </div>

                <div>
                    <label for="event-description" class="block mb-1 sm:mb-2 font-medium">Beschreibung</label>
                    <Textarea
                        id="event-description"
                        v-model="newEvent.description"
                        rows="3"
                        class="w-full"
                    />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Abbrechen" icon="pi pi-times" @click="closeEventDialog" class="p-button-text p-button-sm sm:p-button-md" />
                    <Button label="Speichern" icon="pi pi-check" @click="saveEvent" :loading="saving" autofocus class="p-button-sm sm:p-button-md" />
                </div>
            </template>
        </Dialog>

        <!-- Event Details Dialog -->
        <Dialog
            v-model:visible="eventDetailsDialogVisible"
            :style="{ width: '90vw', maxWidth: '500px' }"
            :header="selectedEvent ? selectedEvent.title : 'Ereignisdetails'"
            :modal="true"
            class="event-details-dialog"
        >
            <div v-if="selectedEvent" class="flex flex-col gap-4">
                <div class="flex items-center gap-2">
                    <div
                        class="w-4 h-4 rounded-full"
                        :style="{ backgroundColor: selectedEvent.color }"
                    ></div>
                    <span class="font-medium">{{ selectedEvent.type ? selectedEvent.type.name : 'Ereignis' }}</span>
                    <Badge
                        :value="getStatusLabel(selectedEvent.status)"
                        :severity="getStatusSeverity(selectedEvent.status)"
                        class="ml-auto"
                    />
                </div>

                <div class="border-t border-b border-gray-200 dark:border-gray-700 py-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Startdatum</div>
                            <div>{{ formatDate(selectedEvent.startDate) }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Enddatum</div>
                            <div>{{ formatDate(selectedEvent.endDate) }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="selectedEvent.description">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Beschreibung</div>
                    <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">{{ selectedEvent.description }}</div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between w-full">
                    <Button
                        label="Löschen"
                        icon="pi pi-trash"
                        @click="confirmDeleteEvent"
                        class="p-button-danger p-button-sm sm:p-button-md"
                    />
                    <div class="flex gap-2">
                        <Button
                            label="Bearbeiten"
                            icon="pi pi-pencil"
                            @click="editEvent"
                            class="p-button-sm sm:p-button-md"
                        />
                        <Button
                            label="Schließen"
                            icon="pi pi-times"
                            @click="closeEventDetailsDialog"
                            class="p-button-text p-button-sm p-button-md"
                        />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog
            v-model:visible="deleteConfirmationVisible"
            :style="{ width: '450px' }"
            header="Ereignis löschen"
            :modal="true"
            :closable="false"
            class="delete-confirmation-dialog"
        >
            <div class="flex flex-col gap-4">
                <div class="flex items-start gap-3">
                    <i class="pi pi-exclamation-triangle text-yellow-500 text-2xl mt-1"></i>
                    <div>
                        <h3 class="text-lg font-semibold mb-2">Sind Sie sicher?</h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Möchten Sie dieses Ereignis wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.
                        </p>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button
                        label="Abbrechen"
                        icon="pi pi-times"
                        @click="cancelDeleteEvent"
                        class="p-button-text p-button-sm sm:p-button-md"
                    />
                    <Button
                        label="Löschen"
                        icon="pi pi-trash"
                        @click="deleteEvent"
                        :loading="deleting"
                        class="p-button-danger p-button-sm sm:p-button-md"
                    />
                </div>
            </template>
        </Dialog>

        <!-- Week Plan Dialog -->
        <Dialog
            v-model:visible="weekPlanDialogVisible"
            :style="{ width: '90vw', maxWidth: '800px' }"
            :header="`Wochenplanung - KW ${selectedWeekNumber}`"
            :modal="true"
            class="week-plan-dialog"
        >
            <div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-2 sm:gap-4">
                    <div
                        v-for="(day, index) in weekDays"
                        :key="index"
                        class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                    >
                        <div class="bg-gray-100 dark:bg-gray-800 p-2 text-center">
                            <h3 class="text-sm sm:text-base font-medium m-0">{{ weekdays[index] }}</h3>
                            <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">{{ formatDate(day.date) }}</div>
                        </div>

                        <div class="p-2">
                            <Select
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
                <div class="flex justify-end gap-2">
                    <Button label="Abbrechen" icon="pi pi-times" @click="closeWeekPlanDialog" class="p-button-text p-button-sm sm:p-button-md" />
                    <Button label="Speichern" icon="pi pi-check" @click="saveWeekPlan" :loading="savingWeekPlan" autofocus class="p-button-sm sm:p-button-md" />
                </div>
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import weekday from 'dayjs/plugin/weekday';
import weekOfYear from 'dayjs/plugin/weekOfYear';
import isBetween from 'dayjs/plugin/isBetween';
import isSameOrBefore from 'dayjs/plugin/isSameOrAfter';
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';
import isToday from 'dayjs/plugin/isToday';
import axios from 'axios';

// PrimeVue Components
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Badge from 'primevue/badge';

dayjs.locale('de');
dayjs.extend(weekday);
dayjs.extend(weekOfYear);
dayjs.extend(isBetween);
dayjs.extend(isSameOrBefore);
dayjs.extend(isSameOrAfter);
dayjs.extend(isToday);

const router = useRouter();
const toast = useToast();

const currentDate = ref(dayjs());
const calendarView = ref('month'); // 'month' or 'year'
const yearLayout = ref('6x2'); // '6x2' or '4x3'
const events = ref([]);
const loading = ref(false);

// Event Dialog
const eventDialogVisible = ref(false);
const newEvent = ref({
    title: '',
    type: null,
    startDate: null,
    endDate: null,
    isAllDay: true,
    description: ''
});
const saving = ref(false);
const isEditMode = ref(false);

// Event Details Dialog
const eventDetailsDialogVisible = ref(false);
const selectedEvent = ref(null);

// Delete Confirmation Dialog
const deleteConfirmationVisible = ref(false);
const deleting = ref(false);

// Week Plan Dialog
const weekPlanDialogVisible = ref(false);
const selectedWeekNumber = ref(null);
const weekDays = ref([]);
const savingWeekPlan = ref(false);

const weekdays = ref(['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag']);
const weekdaysShort = ref(['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa']);
const eventTypes = ref([]);

// Lade die Ereignistypen vom Server
const fetchEventTypes = async () => {
    try {
        // CSRF-Token aus dem Meta-Tag holen
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Axios-Konfiguration mit Auth-Headers
        const config = {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            withCredentials: true
        };

        // Korrigierte URL ohne doppeltes /api/
        const response = await axios.get('/api/event-types', config);
        console.log('Ereignistypen erfolgreich geladen:', response.data);

        eventTypes.value = response.data.map(type => ({
            id: type.id,
            name: type.name,
            value: type.name.toLowerCase(),
            color: type.color,
            requires_approval: type.requires_approval
        }));
    } catch (error) {
        console.error('Fehler beim Laden der Ereignistypen:', error);
        console.log('Verwende Fallback-Ereignistypen');

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

const currentMonthName = computed(() => {
    return currentDate.value.format('MMMM');
});

const currentYear = computed(() => {
    return currentDate.value.year();
});

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

// Hilfsfunktion, um den ersten Tag einer Woche zu bekommen
const getFirstDayOfWeek = (date) => {
    const day = date.day();
    const diff = day === 0 ? 6 : day - 1; // Adjust for Monday start
    return date.subtract(diff, 'day');
};

// Hilfsfunktion, um den letzten Tag einer Woche zu bekommen
const getLastDayOfWeek = (date) => {
    return getFirstDayOfWeek(date).add(6, 'day');
};

const getWeeksInMonth = () => {
    const startOfMonth = currentDate.value.startOf('month');
    const endOfMonth = currentDate.value.endOf('month');

    // Finde den ersten Tag der Woche, die den Monatsanfang enthält
    let currentWeekStart = dayjs(startOfMonth).day(0); // Sonntag als erster Tag der Woche
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
                currentMonth: date.isSame(currentDate.value, 'month'),
                isToday: date.isToday(),
                isWeekend: date.day() === 0 || date.day() === 6
            });
        }
        weeks.push({ weekNumber, days });
        currentWeekStart = currentWeekStart.add(1, 'week');
    }
    return weeks;
};

const getWeeksInMonthForMini = (month) => {
    const targetDate = dayjs().month(month).year(currentDate.value.year());
    const startOfMonth = targetDate.startOf('month');
    const endOfMonth = targetDate.endOf('month');

    // Finde den ersten Tag der Woche, die den Monatsanfang enthält
    let currentWeekStart = dayjs(startOfMonth).day(0); // Sonntag als erster Tag der Woche
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

const getMonthName = (month) => {
    return dayjs().month(month).format('MMMM');
};

const goToMonth = (month) => {
    currentDate.value = currentDate.value.month(month);
    calendarView.value = 'month';
    fetchEvents();
};

const hasEvents = (date) => {
    if (!date) return false;
    const dateStr = dayjs(date).format('YYYY-MM-DD');
    return events.value.some(event => {
        const eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
        const eventEndDate = dayjs(event.endDate).format('YYYY-MM-DD');
        return dateStr >= eventStartDate && dateStr <= eventEndDate;
    });
};

const getEventsForDay = (date) => {
    if (!date) return [];
    const dateStr = dayjs(date).format('YYYY-MM-DD');
    return events.value.filter(event => {
        const eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
        const eventEndDate = dayjs(event.endDate).format('YYYY-MM-DD');
        return dateStr >= eventStartDate && dateStr <= eventEndDate;
    });
};

const getEventColorForDay = (date) => {
    if (!date) return '#607D8B';
    const dateStr = dayjs(date).format('YYYY-MM-DD');
    const event = events.value.find(event => {
        const eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
        const eventEndDate = dayjs(event.endDate).format('YYYY-MM-DD');
        return dateStr >= eventStartDate && dateStr <= eventEndDate;
    });
    return event ? event.color : '#607D8B';
};

const truncateText = (text, maxLength) => {
    if (text.length > maxLength) {
        return text.substring(0, maxLength) + '...';
    }
    return text;
};

const formatDate = (date) => {
    return dayjs(date).format('DD.MM.YYYY');
};

// Status-Hilfsfunktionen
const getStatusLabel = (status) => {
    switch (status) {
        case 'pending': return 'Ausstehend';
        case 'approved': return 'Genehmigt';
        case 'rejected': return 'Abgelehnt';
        default: return 'Unbekannt';
    }
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        default: return 'info';
    }
};

// Event Dialog Methods
const openEventDialog = (date) => {
    isEditMode.value = false;
    newEvent.value = {
        title: '',
        type: null,
        startDate: date,
        endDate: date,
        isAllDay: true,
        description: ''
    };
    eventDialogVisible.value = true;
};

const closeEventDialog = () => {
    eventDialogVisible.value = false;
};

// Update the saveEvent method to use a simplified approach without CSRF token

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
    const getEventTypeId = (typeValue) => {
        const type = eventTypes.value.find(t => t.value === typeValue);
        return type ? type.id : null;
    };
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
        await fetchEvents();
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

// Event Details Dialog Methods
const openEventDetailsDialog = (event) => {
    selectedEvent.value = event;
    eventDetailsDialogVisible.value = true;
};

const closeEventDetailsDialog = () => {
    eventDetailsDialogVisible.value = false;
    selectedEvent.value = null;
};

// JavaScript
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

    let currentType = null;
    if (selectedEvent.value.event_type_id) {
        // Suche anhand der event_type_id
        currentType = eventTypes.value.find(
            typeItem => typeItem.id === selectedEvent.value.event_type_id
        );
    }
    // Falls nicht gefunden, nutze das vorhandene Type-Objekt
    if (!currentType && selectedEvent.value.type?.id) {
        currentType = eventTypes.value.find(
            typeItem => typeItem.id === selectedEvent.value.type.id
        );
    }

    newEvent.value = {
        id: selectedEvent.value.id,
        title: selectedEvent.value.title,
        type: currentType,
        startDate: new Date(selectedEvent.value.startDate),
        endDate: new Date(selectedEvent.value.endDate),
        isAllDay: selectedEvent.value.isAllDay,
        description: selectedEvent.value.description
    };

    closeEventDetailsDialog();
    eventDialogVisible.value = true;
};

// Delete Event Methods
const confirmDeleteEvent = () => {
    deleteConfirmationVisible.value = true;
};

const cancelDeleteEvent = () => {
    deleteConfirmationVisible.value = false;
};

const deleteEvent = async () => {
    if (!selectedEvent.value) return;

    deleting.value = true;
    try {
        // Prüfen, ob es sich um ein Ereignis aus der Datenbank handelt
        if (selectedEvent.value.source === 'vacation') {
            toast.add({
                severity: 'info',
                summary: 'Hinweis',
                detail: 'Urlaubseinträge können nicht direkt gelöscht werden. Bitte nutzen Sie die Urlaubsverwaltung.',
                life: 5000
            });
            return;
        }

        // Simplified approach without CSRF token
        const response = await axios({
            method: 'delete',
            url: `/api/events/${selectedEvent.value.id}`,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            withCredentials: true
        });

        if (response.status === 200) {
            toast.add({
                severity: 'success',
                summary: 'Erfolgreich',
                detail: 'Ereignis wurde gelöscht.',
                life: 3000
            });
            fetchEvents(); // Aktualisiere die Ereignisse nach dem Löschen
        }
    } catch (error) {
        console.error('Fehler beim Löschen des Ereignisses:', error);

        // Try alternative approach with query parameter
        try {
            const response = await axios.get(`/api/events/${selectedEvent.value.id}/delete`, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                withCredentials: true
            });

            if (response.status === 200) {
                toast.add({
                    severity: 'success',
                    summary: 'Erfolgreich',
                    detail: 'Ereignis wurde gelöscht.',
                    life: 3000
                });
                fetchEvents(); // Aktualisiere die Ereignisse nach dem Löschen
                deleteConfirmationVisible.value = false;
                closeEventDetailsDialog();
                return;
            }
        } catch (alternativeError) {
            console.error('Alternative Löschmethode fehlgeschlagen:', alternativeError);
        }

        // Show error message
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Es gab ein Problem beim Löschen des Ereignisses: ' + (error.response?.data?.error || error.message),
            life: 3000
        });
    } finally {
        deleting.value = false;
        deleteConfirmationVisible.value = false;
        closeEventDetailsDialog();
    }
};

// Week Plan Dialog Methods
const openWeekPlanDialog = (weekNumber, days) => {
    selectedWeekNumber.value = weekNumber;
    weekDays.value = days.map(day => ({
        date: day.date,
        selectedType: null,
        notes: ''
    }));
    weekPlanDialogVisible.value = true;
};

const closeWeekPlanDialog = () => {
    weekPlanDialogVisible.value = false;
};

const saveWeekPlan = async () => {
    savingWeekPlan.value = true;

    try {
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Prepare request headers
        const headers = {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };

        // Filter days with selected type
        const weekPlanData = weekDays.value
            .filter(day => day.selectedType !== null)
            .map(day => ({
                date: dayjs(day.date).format('YYYY-MM-DD'),
                event_type: day.selectedType.name,
                notes: day.notes || ''
            }));

        // Check if there's anything to save
        if (weekPlanData.length === 0) {
            toast.add({
                severity: 'info',
                summary: 'Hinweis',
                detail: 'Keine Änderungen vorgenommen.',
                life: 3000
            });
            savingWeekPlan.value = false;
            closeWeekPlanDialog();
            return;
        }

        console.log('Sending week plan data:', { events: weekPlanData });

        // Make the API call
        const response = await axios.post('/api/events/week-plan', { events: weekPlanData }, {
            headers,
            withCredentials: true
        });

        console.log('Week plan response:', response.data);

        if (response.status === 200 || response.status === 201) {
            toast.add({
                severity: 'success',
                summary: 'Erfolg',
                detail: 'Wochenplanung wurde gespeichert.',
                life: 3000
            });
            await fetchEvents();
        } else {
            throw new Error('Unexpected status code: ' + response.status);
        }
    } catch (error) {
        console.error('Error saving week plan:', error);
        console.log('Error response:', error.response?.data);

        // Try alternative approach - create events individually
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const headers = {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };

            let successCount = 0;
            const totalEvents = weekDays.value.filter(day => day.selectedType !== null).length;

            // Process each day individually
            for (const day of weekDays.value) {
                if (!day.selectedType) continue;

                try {
                    const eventData = {
                        title: day.selectedType.name,
                        description: day.notes || '',
                        start_date: dayjs(day.date).format('YYYY-MM-DD'),
                        end_date: dayjs(day.date).format('YYYY-MM-DD'),
                        event_type: day.selectedType.name,
                        event_type_id: day.selectedType.id,
                        is_all_day: 1
                    };

                    await axios.post('/api/events', eventData, { headers, withCredentials: true });
                    successCount++;
                } catch (dayError) {
                    console.error('Failed to create event for day:', dayjs(day.date).format('YYYY-MM-DD'), dayError);
                }
            }

            if (successCount > 0) {
                toast.add({
                    severity: 'success',
                    summary: 'Teilweise erfolgreich',
                    detail: `${successCount} von ${totalEvents} Ereignissen wurden gespeichert.`,
                    life: 3000
                });
                await fetchEvents();
            } else {
                throw new Error('Keine Ereignisse konnten gespeichert werden.');
            }
        } catch (altError) {
            console.error('Alternative approach failed:', altError);

            toast.add({
                severity: 'error',
                summary: 'Fehler',
                detail: 'Die Wochenplanung konnte nicht gespeichert werden. Bitte versuchen Sie es später erneut.',
                life: 3000
            });
        }
    } finally {
        savingWeekPlan.value = false;
        closeWeekPlanDialog();
    }
};

// Aktualisiere die fetchEvents-Methode
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
        let regularEvents = [];
        try {
            const eventsResponse = await axios.get('/api/events', config);
            console.log('Geladene Ereignisse:', eventsResponse.data);

            // Transform API response for regular events
            regularEvents = eventsResponse.data.map(event => {
                // Find the corresponding event type
                const eventType = eventTypes.value.find(type =>
                    type.id === (event.event_type?.id || 0)
                ) || { name: 'Sonstiges', value: 'other', color: '#607D8B' }; // Default to 'other'

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
                    source: 'event'
                };
            });
        } catch (error) {
            console.error('Fehler beim Laden der regulären Ereignisse:', error);
            // Wir setzen den Prozess fort, auch wenn die regulären Ereignisse nicht geladen werden konnten
        }

        // Fetch vacation requests
        let vacationEvents = [];
        try {
            const vacationResponse = await axios.get('/api/vacation/user-requests', config);
            console.log('Geladene Urlaubsanträge:', vacationResponse.data);

            // Transform vacation requests - nur für Werktage
            vacationResponse.data.filter(vacation => vacation.status === 'approved').forEach(vacation => {
                // Create a vacation event type
                const vacationType = eventTypes.value.find(type => type.value === 'vacation') ||
                    { name: 'Urlaub', value: 'vacation', color: '#9C27B0' };

                // Für jeden Tag im Urlaubszeitraum ein separates Ereignis erstellen
                const startDate = dayjs(vacation.start_date);
                const endDate = dayjs(vacation.end_date);
                let currentDate = startDate.clone();

                while (currentDate.isSameOrBefore(endDate, 'day')) {
                    // Überprüfe, ob der aktuelle Tag ein Wochenendtag ist (0 = Sonntag, 6 = Samstag)
                    const dayOfWeek = currentDate.day();
                    if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                        vacationEvents.push({
                            id: `vacation-${vacation.id}-${currentDate.format('YYYY-MM-DD')}`,
                            title: 'Urlaub',
                            description: vacation.notes || 'Genehmigter Urlaub',
                            startDate: currentDate.toDate(),
                            endDate: currentDate.toDate(),
                            isAllDay: true,
                            type: vacationType,
                            color: vacationType.color,
                            source: 'vacation',
                            status: vacation.status
                        });
                    }
                    currentDate = currentDate.add(1, 'day');
                }
            });
        } catch (error) {
            console.error('Fehler beim Laden der Urlaubsanträge:', error);
            // Wir setzen den Prozess fort, auch wenn die Urlaubsanträge nicht geladen werden konnten
        }

        // Combine both types of events
        events.value = [...regularEvents, ...vacationEvents];
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

onMounted(() => {
    fetchEventTypes();
    fetchEvents();
});

const updateCalendarEvents = () => {
    // Trigger a re-render of the calendar by updating the events array
    events.value = [...events.value];
};
</script>

<style scoped>
.event-dialog .p-dialog-header,
.event-details-dialog .p-dialog-header,
.delete-confirmation-dialog .p-dialog-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--surface-d);
}

.event-dialog .p-dialog-content,
.event-details-dialog .p-dialog-content,
.delete-confirmation-dialog .p-dialog-content {
    padding: 1.5rem;
}

.event-dialog .p-dialog-footer,
.event-details-dialog .p-dialog-footer,
.delete-confirmation-dialog .p-dialog-footer {
    padding: 1.5rem;
    border-top: 1px solid var(--surface-d);
}

.week-plan-dialog .p-dialog-header {
    padding: 1rem;
    border-bottom: 1px solid var(--surface-d);
}

.week-plan-dialog .p-dialog-content {
    padding: 1rem;
}

.week-plan-dialog .p-dialog-footer {
    padding: 1rem;
    border-top: 1px solid var(--surface-d);
}
</style>

