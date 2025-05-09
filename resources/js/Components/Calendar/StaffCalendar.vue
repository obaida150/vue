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
                        @click="setCalendarView('month')"
                        class="p-button-sm sm:p-button-md"
                    />
                    <Button
                        :class="{ 'p-button-primary': calendarView === 'year', 'p-button-outlined': calendarView !== 'year' }"
                        label="Jahr"
                        @click="setCalendarView('year')"
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
                                'relative': hasEvents(day.date) || hasVacations(day.date),
                                'bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400': day.isWeekend,
                                'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400': isHoliday(dayjs(day.date))
                            }"
                            @click="!isHoliday(dayjs(day.date)) ? openEventDialog(day.date) : showHolidayInfo(day.date)"
                        >
                            <div class="text-center mb-1 sm:mb-2 text-xs sm:text-base">
                                {{ day.dayNumber }}
                                <span v-if="isHoliday(dayjs(day.date))" class="text-xs text-red-600 dark:text-red-400 block">
                                    {{ getHolidayName(dayjs(day.date)) }}
                                </span>
                            </div>
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
                                <!-- Urlaub-Anzeige -->
                                <div
                                    v-for="vacation in getVacationsForDay(day.date)"
                                    :key="'vacation-' + vacation.id"
                                    class="px-1 py-0.5 rounded text-[10px] sm:text-xs text-white whitespace-nowrap overflow-hidden text-ellipsis flex justify-between items-center bg-purple-600"
                                    :title="'Urlaub: ' + vacation.title"
                                    @click.stop="openVacationDetailsDialog(vacation)"
                                >
                                    <span>{{ truncateText(vacation.title || 'Urlaub', 12) }}</span>
                                    <i class="pi pi-calendar text-[8px] sm:text-[10px] opacity-70 hover:opacity-100"></i>
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
                                'text-gray-400 dark:text-gray-500': day.isWeekend,
                                'bg-red-500 text-white rounded-full font-bold': isHoliday(dayjs(day.date))
                            }"
                        >
                            <div class="z-10">{{ day.dayNumber }}</div>
                            <div
                                v-if="hasEvents(day.date)"
                                class="absolute inset-0 opacity-70 z-0 rounded-full"
                                :style="{ backgroundColor: getEventColorForDay(day.date) }"
                            ></div>
                            <!-- Urlaub-Indikator für Jahresansicht -->
                            <div
                                v-if="hasVacations(day.date)"
                                class="absolute inset-0 opacity-70 z-0 rounded-full bg-purple-600"
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
                            :disabledDates="disabledDates"
                        />
                        <DatePicker
                            v-model="newEvent.endDate"
                            dateFormat="dd.mm.yy"
                            placeholder="Enddatum"
                            class="w-full"
                            :disabledDates="disabledDates"
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

        <!-- Vacation Details Dialog -->
        <Dialog
            v-model:visible="vacationDetailsDialogVisible"
            :style="{ width: '90vw', maxWidth: '500px' }"
            :header="selectedVacation ? (selectedVacation.title || 'Urlaub') : 'Urlaubsdetails'"
            :modal="true"
            class="vacation-details-dialog"
        >
            <div v-if="selectedVacation" class="flex flex-col gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-full bg-purple-600"></div>
                    <span class="font-medium">Urlaub</span>
                    <Badge
                        :value="getStatusLabel(selectedVacation.status)"
                        :severity="getStatusSeverity(selectedVacation.status)"
                        class="ml-auto"
                    />
                </div>

                <div class="border-t border-b border-gray-200 dark:border-gray-700 py-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Startdatum</div>
                            <div>{{ formatDate(selectedVacation.startDate) }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Enddatum</div>
                            <div>{{ formatDate(selectedVacation.endDate) }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="selectedVacation.description">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Beschreibung</div>
                    <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">{{ selectedVacation.description }}</div>
                </div>

                <div class="bg-purple-50 p-3 rounded-lg">
                    <p class="text-sm text-purple-800">
                        Dieser Eintrag stammt aus der Urlaubsverwaltung und kann nur dort bearbeitet werden.
                    </p>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end w-full">
                    <div class="flex gap-2">
                        <Button
                            label="Zur Urlaubsverwaltung"
                            icon="pi pi-external-link"
                            @click="navigateToVacationManagement"
                            class="p-button-sm sm:p-button-md"
                        />
                        <Button
                            label="Schließen"
                            icon="pi pi-times"
                            @click="closeVacationDetailsDialog"
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
            :style="{ width: '60vw' }"
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
                        :class="{ 'bg-red-50 dark:bg-red-900/20': isHoliday(dayjs(day.date)) }"
                    >
                        <div class="bg-gray-100 dark:bg-gray-800 p-2 text-center">
                            <h3 class="text-sm sm:text-base font-medium m-0">{{ weekdays[index] }}</h3>
                            <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">{{ formatDate(day.date) }}</div>
                            <div v-if="isHoliday(dayjs(day.date))" class="text-xs text-red-600 dark:text-red-400 font-bold">
                                {{ getHolidayName(dayjs(day.date)) }}
                            </div>
                            <!-- Urlaub-Indikator in der Wochenplanung -->
                            <div v-if="hasVacations(day.date)" class="text-xs text-purple-600 dark:text-purple-400 font-bold mt-1">
                                Urlaub
                            </div>
                        </div>

                        <div class="p-2">
                            <Select
                                v-model="day.selectedType"
                                :options="eventTypes"
                                optionLabel="name"
                                placeholder="Status wählen"
                                class="w-full mb-2"
                                :disabled="isHoliday(dayjs(day.date)) || hasVacations(day.date)"
                            />

                            <InputText
                                v-model="day.notes"
                                placeholder="Notizen"
                                class="w-full"
                                :disabled="isHoliday(dayjs(day.date)) || hasVacations(day.date)"
                            />

                            <!-- Anzeige des Ereignis-Status, falls vorhanden -->
                            <div v-if="day.eventId" class="mt-2 flex items-center justify-between">
                                <Badge
                                    :value="day.isEdited ? 'Bearbeitet' : 'Vorhanden'"
                                    :severity="day.isEdited ? 'warning' : 'info'"
                                    class="text-xs"
                                />
                                <Button
                                    v-if="day.eventId && !isHoliday(dayjs(day.date)) && !hasVacations(day.date)"
                                    icon="pi pi-trash"
                                    class="p-button-text p-button-danger p-button-sm"
                                    @click="removeWeekDayEvent(index)"
                                    title="Ereignis löschen"
                                />
                            </div>
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

        <!-- Holiday Info Dialog -->
        <Dialog
            v-model:visible="holidayInfoVisible"
            :style="{ width: '450px' }"
            :header="selectedHolidayName"
            :modal="true"
            class="holiday-info-dialog"
        >
            <div class="p-4">
                <p class="text-center mb-4">
                    An diesem Tag können keine Ereignisse eingetragen werden, da es sich um einen Feiertag handelt.
                </p>
                <div class="flex justify-center">
                    <Button label="Schließen" icon="pi pi-times" @click="holidayInfoVisible = false" class="p-button-sm sm:p-button-md" />
                </div>
            </div>
        </Dialog>

        <!-- Legende für Ereignistypen und Urlaub -->
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-medium mb-2">Legende:</h3>
            <div class="flex flex-wrap gap-16">
                <div v-for="type in eventTypes" :key="type.id" class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: type.color }"></div>
                    <span class="text-xs">{{ type.name }}</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-purple-600"></div>
                    <span class="text-xs">Urlaub</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <span class="text-xs">Feiertag</span>
                </div>
            </div>
        </div>
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
import axios from 'axios';
import HolidayService from '@/Services/holiday-service';
import VacationService from '@/Services/VacationService';

// PrimeVue Components
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Badge from 'primevue/badge';

// Use a function that can be provided by the parent component if needed
const navigateTo = (path) => {
    if (typeof window !== 'undefined') {
        window.location.href = path;
    }
};

// Toast functionality - create a simple implementation if useToast is not available
const toast = {
    add: (message) => {
        console.log('Toast message:', message);
    }
};

dayjs.locale('de');
dayjs.extend(weekday);
dayjs.extend(weekOfYear);
dayjs.extend(isBetween);
dayjs.extend(isSameOrBefore);
dayjs.extend(isSameOrAfter);
dayjs.extend(isToday);

const currentDate = ref(dayjs());
const calendarView = ref('month'); // 'month' or 'year'
const yearLayout = ref('6x2'); // '6x2' or '4x3'
const events = ref([]);
const vacations = ref([]); // Neue Ref für Urlaubsdaten
const loading = ref(false);

// Feiertage für das aktuelle Jahr
const holidays = ref([]);
const isLoadingHolidays = ref(false);
const holidayInfoVisible = ref(false);
const selectedHolidayName = ref('');

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

// Vacation Details Dialog
const vacationDetailsDialogVisible = ref(false);
const selectedVacation = ref(null);

// Delete Confirmation Dialog
const deleteConfirmationVisible = ref(false);
const deleting = ref(false);

// Week Plan Dialog
const weekPlanDialogVisible = ref(false);
const selectedWeekNumber = ref(null);
const weekDays = ref([]);
const savingWeekPlan = ref(false);

const weekdays = ref(['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag']);
const weekdaysShort = ref(['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']);
const eventTypes = ref([]);

// Deaktivierte Daten für den DatePicker (Feiertage)
const disabledDates = ref([]);

// Funktion zum Aktualisieren der deaktivierten Daten
const updateDisabledDates = () => {
    // Leere das Array
    disabledDates.value = [];

    // Füge alle Feiertage des aktuellen Jahres hinzu
    holidays.value.forEach(holiday => {
        disabledDates.value.push(holiday.date.toDate());
    });
};

// Feiertage laden
const fetchHolidays = async (year) => {
    isLoadingHolidays.value = true;
    try {
        holidays.value = await HolidayService.getHolidays(year);
        updateDisabledDates(); // Aktualisiere die deaktivierten Daten
    } catch (error) {
        console.error('Fehler beim Laden der Feiertage:', error);
    } finally {
        isLoadingHolidays.value = false;
    }
};

// Prüft, ob ein Datum ein Feiertag ist
const isHoliday = (date) => {
    return HolidayService.isHoliday(date, holidays.value);
};

// Gibt den Namen eines Feiertags zurück
const getHolidayName = (date) => {
    return HolidayService.getHolidayName(date, holidays.value);
};

// Zeigt Informationen zu einem Feiertag an
const showHolidayInfo = (date) => {
    const holidayName = getHolidayName(dayjs(date));
    if (holidayName) {
        selectedHolidayName.value = holidayName;
        holidayInfoVisible.value = true;
    }
};

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
    fetchEvents(); // Dies lädt jetzt auch die Urlaubsdaten
};

const nextPeriod = () => {
    if (calendarView.value === 'month') {
        currentDate.value = currentDate.value.add(1, 'month');
    } else {
        currentDate.value = currentDate.value.add(1, 'year');
    }
    fetchEvents(); // Dies lädt jetzt auch die Urlaubsdaten
};

const setCalendarView = (view) => {
    calendarView.value = view;
    fetchEvents(); // Dies lädt jetzt auch die Urlaubsdaten
};

// Hilfsfunktion, um den ersten Tag einer Woche zu bekommen
const getFirstDayOfWeek = (date) => {
    const day = date.day();
    // Montag ist 1, Sonntag ist 0, daher muss die Berechnung angepasst werden
    const diff = day === 0 ? 6 : day - 1; // Adjust for Monday start (0 = Sunday, so we need to go back 6 days)
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
    let currentWeekStart = dayjs(startOfMonth).day(1); // Montag als erster Tag der Woche
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
    let currentWeekStart = dayjs(startOfMonth).day(1); // Montag als erster Tag der Woche
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
    fetchEvents(); // Dies lädt jetzt auch die Urlaubsdaten
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

// Neue Funktion: Prüft, ob ein Tag Urlaub hat
const hasVacations = (date) => {
    if (!date) return false;
    const dateStr = dayjs(date).format('YYYY-MM-DD');

    // Prüfe, ob das Datum zwischen Start- und Enddatum eines Urlaubseintrags liegt
    return vacations.value.some(vacation => {
        const startDate = dayjs(vacation.startDate).format('YYYY-MM-DD');
        const endDate = dayjs(vacation.endDate).format('YYYY-MM-DD');

        // Debugging-Ausgabe für den 7. Mai
        if (dateStr === '2025-05-07') {
            console.log(`Prüfe Urlaub für 7. Mai: ${startDate} bis ${endDate}`);
        }

        return dayjs(dateStr).isSameOrAfter(startDate) && dayjs(dateStr).isSameOrBefore(endDate);
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

// Neue Funktion: Gibt Urlaubseinträge für einen bestimmten Tag zurück
const getVacationsForDay = (date) => {
    if (!date) return [];
    const dateStr = dayjs(date).format('YYYY-MM-DD');
    const result = vacations.value.filter(vacation => {
        const vacationStartDate = dayjs(vacation.startDate).format('YYYY-MM-DD');
        const vacationEndDate = dayjs(vacation.endDate).format('YYYY-MM-DD');
        return dateStr >= vacationStartDate && dateStr <= vacationEndDate;
    });

    // Debugging-Ausgabe für den 7. Mai
    if (dateStr === '2025-05-07') {
        console.log(`Urlaubseinträge für ${dateStr}:`, result);
    }

    return result;
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
    // Prüfen, ob das Datum ein Feiertag ist
    if (isHoliday(dayjs(date))) {
        showHolidayInfo(date);
        return;
    }

    // Prüfen, ob an diesem Tag bereits Urlaub eingetragen ist
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
        description: ''
    };
    eventDialogVisible.value = true;
};

const closeEventDialog = () => {
    eventDialogVisible.value = false;
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
    let currentDate = startDate.clone();
    while (currentDate.isBefore(endDate) || currentDate.isSame(endDate, 'day')) {
        if (isHoliday(currentDate)) {
            toast.add({
                severity: 'error',
                summary: 'Validierungsfehler',
                detail: `Ereignisse können nicht an Feiertagen eingetragen werden. ${getHolidayName(currentDate)} (${currentDate.format('DD.MM.YYYY')}) liegt im gewählten Zeitraum.`,
                life: 5000
            });
            return;
        }
        currentDate = currentDate.add(1, 'day');
    }

    // Prüfen, ob im gewählten Zeitraum bereits Urlaub eingetragen ist
    currentDate = startDate.clone();
    while (currentDate.isBefore(endDate) || currentDate.isSame(endDate, 'day')) {
        if (hasVacations(currentDate.toDate())) {
            toast.add({
                severity: 'error',
                summary: 'Validierungsfehler',
                detail: `Ereignisse können nicht an Urlaubstagen eingetragen werden. Am ${currentDate.format('DD.MM.YYYY')} ist bereits Urlaub eingetragen.`,
                life: 5000
            });
            return;
        }
        currentDate = currentDate.add(1, 'day');
    }

    saving.value = true;
    try {
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

        // Bereite die Ereignisdaten vor
        const eventData = {
            title: newEvent.value.title,
            event_type_id: newEvent.value.type.id,
            event_type: newEvent.value.type.name,
            start_date: dayjs(newEvent.value.startDate).format('YYYY-MM-DD'),
            end_date: dayjs(newEvent.value.endDate).format('YYYY-MM-DD'),
            is_all_day: newEvent.value.isAllDay,
            description: newEvent.value.description
        };

        if (isEditMode.value && newEvent.value.id) {
            // Prüfen, ob es sich um ein Ereignis aus der Datenbank handelt
            if (selectedEvent.value && selectedEvent.value.source === 'vacation') {
                toast.add({
                    severity: 'info',
                    summary: 'Hinweis',
                    detail: 'Urlaubseinträge können nicht direkt bearbeitet werden. Bitte nutzen Sie die Urlaubsverwaltung.',
                    life: 5000
                });
                return;
            }

            // Update existing event
            try {
                // Versuche zuerst mit POST und _method=PUT (Laravel-Standard für Updates)
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
                console.error('POST mit _method=PUT fehlgeschlagen, versuche direkten PUT-Request:', postError);

                // Fallback: Versuche direkten PUT-Request
                try {
                    const response = await axios.put(`/api/events/${newEvent.value.id}`, eventData, config);

                    if (response.status === 200) {
                        toast.add({
                            severity: 'success',
                            summary: 'Erfolgreich',
                            detail: 'Ereignis wurde aktualisiert.',
                            life: 3000
                        });
                    }
                } catch (putError) {
                    console.error('Auch direkter PUT-Request fehlgeschlagen:', putError);
                    throw putError; // Wirf den Fehler für die äußere catch-Klausel
                }
            }
        } else {
            // Create new event
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

        // Warte kurz, damit der Server Zeit hat, die Änderungen zu verarbeiten
        setTimeout(() => {
            fetchEvents(); // Aktualisiere die Ereignisse nach dem Speichern
        }, 1000);
    } catch (error) {
        console.error('Fehler beim Speichern des Ereignisses:', error);
        console.error('Fehlerdetails:', error.response?.data);

        // Spezifischere Fehlermeldung für 401 Unauthorized
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
        closeEventDetailsDialog(); // Falls wir aus dem Details-Dialog heraus bearbeitet haben
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

// Neue Methoden für Urlaubs-Details-Dialog
const openVacationDetailsDialog = (vacation) => {
    selectedVacation.value = vacation;
    vacationDetailsDialogVisible.value = true;
};

const closeVacationDetailsDialog = () => {
    vacationDetailsDialogVisible.value = false;
    selectedVacation.value = null;
};

// Funktion zum Navigieren zur Urlaubsverwaltung
const navigateToVacationManagement = () => {
    navigateTo('/vacations');
};

const editEvent = () => {
    if (!selectedEvent.value) return;

    // Prüfen, ob es sich um ein Ereignis aus der Datenbank handelt
    if (selectedEvent.value.source === 'vacation') {
        toast.add({
            severity: 'info',
            summary: 'Hinweis',
            detail: 'Urlaubseinträge können nicht direkt bearbeitet werden. Bitte nutzen Sie die Urlaubsverwaltung.',
            life: 5000
        });
        return;
    }
    // Finde den korrekten Ereignistyp aus der Liste der verfügbaren Typen
    let eventType = null;

    if (selectedEvent.value.type) {
        // Versuche zuerst, den Typ anhand der ID zu finden
        if (selectedEvent.value.type.id) {
            eventType = eventTypes.value.find(type => type.id === selectedEvent.value.type.id);
        }

        // Falls keine ID übereinstimmt, versuche es mit dem Namen
        if (!eventType && selectedEvent.value.type.name) {
            // Exakte Übereinstimmung
            eventType = eventTypes.value.find(type => type.name === selectedEvent.value.type.name);

            // Case-insensitive Übereinstimmung
            if (!eventType) {
                eventType = eventTypes.value.find(type =>
                    type.name.toLowerCase() === selectedEvent.value.type.name.toLowerCase()
                );
            }
        }

        // Wenn immer noch kein Typ gefunden wurde, versuche es mit dem Wert
        if (!eventType && selectedEvent.value.type.value) {
            eventType = eventTypes.value.find(type =>
                type.value === selectedEvent.value.type.value ||
                type.value.toLowerCase() === selectedEvent.value.type.value.toLowerCase()
            );
        }

        // Wenn immer noch kein Typ gefunden wurde, versuche es mit dem Titel des Events
        if (!eventType && selectedEvent.value.title) {
            eventType = eventTypes.value.find(type =>
                type.name === selectedEvent.value.title ||
                type.name.toLowerCase() === selectedEvent.value.title.toLowerCase()
            );
        }

        // Fallback auf den ersten Typ, falls nichts gefunden wurde
        if (!eventType && eventTypes.value.length > 0) {
            console.warn('Ereignistyp nicht gefunden, verwende ersten verfügbaren Typ');
            eventType = eventTypes.value[0];
        }
    }

    isEditMode.value = true;
    newEvent.value = {
        id: selectedEvent.value.id,
        title: selectedEvent.value.title,
        type: eventType, // Verwende den gefundenen Ereignistyp
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

        // Versuche zuerst mit direktem DELETE-Request
        try {
            const response = await axios.delete(`/api/events/${selectedEvent.value.id}`, config);

            if (response.status === 200) {
                toast.add({
                    severity: 'success',
                    summary: 'Erfolgreich',
                    detail: 'Ereignis wurde gelöscht.',
                    life: 3000
                });
                fetchEvents(); // Aktualisiere die Ereignisse nach dem Löschen
            }
        } catch (deleteError) {

            // Fallback: Verwende POST mit _method=DELETE
            const response = await axios.post(`/api/events/${selectedEvent.value.id}`,
                { _method: 'DELETE' },
                config
            );

            if (response.status === 200) {
                toast.add({
                    severity: 'success',
                    summary: 'Erfolgreich',
                    detail: 'Ereignis wurde gelöscht.',
                    life: 3000
                });
                fetchEvents(); // Aktualisiere die Ereignisse nach dem Löschen
            }
        }
    } catch (error) {
        console.error('Fehler beim Löschen des Ereignisses:', error);

        // Spezifischere Fehlermeldung für 401 Unauthorized
        if (error.response && error.response.status === 401) {
            toast.add({
                severity: 'error',
                summary: 'Fehler',
                detail: 'Sie sind nicht berechtigt, dieses Ereignis zu löschen. Bitte melden Sie sich erneut an.',
                life: 5000
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Fehler',
                detail: 'Es gab ein Problem beim Löschen des Ereignisses: ' + (error.response?.data?.error || error.message),
                life: 3000
            });
        }
    } finally {
        deleting.value = false;
        deleteConfirmationVisible.value = false;
        closeEventDetailsDialog();
    }
};

// Hilfsfunktion zum Finden eines Ereignistyps anhand des Namens oder der ID
const findEventType = (typeInfo) => {
    if (!typeInfo) return null;

    let eventType = null;

    // Versuche zuerst, den Typ anhand der ID zu finden
    if (typeInfo.id) {
        eventType = eventTypes.value.find(type => type.id === typeInfo.id);
    }

    // Falls keine ID übereinstimmt, versuche es mit dem Namen
    if (!eventType && typeInfo.name) {
        // Exakte Übereinstimmung
        eventType = eventTypes.value.find(type => type.name === typeInfo.name);

        // Case-insensitive Übereinstimmung
        if (!eventType) {
            eventType = eventTypes.value.find(type =>
                type.name.toLowerCase() === typeInfo.name.toLowerCase()
            );
        }
    }

    // Fallback auf den ersten Typ, falls nichts gefunden wurde
    if (!eventType && eventTypes.value.length > 0) {
        eventType = eventTypes.value.find(type => type.name === 'Sonstiges') || eventTypes.value[0];
    }

    return eventType;
};

// Week Plan Dialog Methods
const openWeekPlanDialog = (weekNumber, days) => {
    selectedWeekNumber.value = weekNumber;

    // Initialisiere die Wochentage mit vorhandenen Ereignissen
    weekDays.value = days.map(day => {
        // Prüfen, ob der Tag ein Feiertag ist
        const isHolidayDay = isHoliday(dayjs(day.date));

        // Prüfen, ob der Tag ein Urlaubstag ist
        const isVacationDay = hasVacations(day.date);

        // Suche nach vorhandenen Ereignissen für diesen Tag
        const existingEvents = getEventsForDay(day.date);
        const existingEvent = existingEvents.length > 0 ? existingEvents[0] : null;

        // Wenn ein Ereignis existiert, verwende dessen Werte
        if (existingEvent) {
            return {
                date: day.date,
                selectedType: existingEvent.type,
                notes: existingEvent.description || '',
                eventId: existingEvent.id,
                originalType: existingEvent.type,
                originalNotes: existingEvent.description || '',
                isEdited: false,
                isHoliday: isHolidayDay,
                isVacation: isVacationDay
            };
        }

        // Ansonsten leere Werte
        return {
            date: day.date,
            selectedType: null,
            notes: '',
            eventId: null,
            originalType: null,
            originalNotes: '',
            isEdited: false,
            isHoliday: isHolidayDay,
            isVacation: isVacationDay
        };
    });

    // Überwache Änderungen an den Wochentagen, um den Bearbeitungsstatus zu aktualisieren
    watch(weekDays.value, (newVal, oldVal) => {
        newVal.forEach((day, index) => {
            if (day.eventId) {
                // Prüfe, ob sich der Typ oder die Notizen geändert haben
                const typeChanged = day.selectedType && day.originalType &&
                    (day.selectedType.id !== day.originalType.id);
                const notesChanged = day.notes !== day.originalNotes;

                // Setze den Bearbeitungsstatus
                day.isEdited = typeChanged || notesChanged;
            }
        });
    }, { deep: true });

    weekPlanDialogVisible.value = true;
};

const closeWeekPlanDialog = () => {
    weekPlanDialogVisible.value = false;
};

// Funktion zum Entfernen eines Ereignisses aus der Wochenplanung
const removeWeekDayEvent = (index) => {
    if (weekDays.value[index] && weekDays.value[index].eventId) {
        // Markiere das Ereignis zum Löschen
        weekDays.value[index].toDelete = true;
        weekDays.value[index].selectedType = null;
        weekDays.value[index].notes = '';
        weekDays.value[index].isEdited = true;
    }
};

const saveWeekPlan = async () => {
    savingWeekPlan.value = true;
    try {
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

        // Sammle Ereignisse zum Erstellen, Aktualisieren und Löschen
        const toCreate = [];
        const toUpdate = [];
        const toDelete = [];

        weekDays.value.forEach(day => {
            // Wenn der Tag ein Feiertag oder Urlaubstag ist, überspringe ihn
            if (isHoliday(dayjs(day.date)) || hasVacations(day.date)) {
                return;
            }

            // Wenn das Ereignis gelöscht werden soll
            if (day.eventId && day.toDelete) {
                toDelete.push(day.eventId);
            }
            // Wenn das Ereignis aktualisiert werden soll
            else if (day.eventId && day.isEdited && day.selectedType) {
                toUpdate.push({
                    id: day.eventId,
                    title: day.selectedType.name,
                    description: day.notes || '',
                    start_date: dayjs(day.date).format('YYYY-MM-DD'),
                    end_date: dayjs(day.date).format('YYYY-MM-DD'),
                    event_type_id: day.selectedType.id,
                    is_all_day: true
                });
            }
            // Wenn ein neues Ereignis erstellt werden soll
            else if (!day.eventId && day.selectedType) {
                toCreate.push({
                    title: day.selectedType.name,
                    description: day.notes || '',
                    start_date: dayjs(day.date).format('YYYY-MM-DD'),
                    end_date: dayjs(day.date).format('YYYY-MM-DD'),
                    event_type_id: day.selectedType.id,
                    is_all_day: true
                });
            }
        });

        // Zähler für erfolgreiche Operationen
        let successCount = 0;
        let totalOperations = toCreate.length + toUpdate.length + toDelete.length;

        // 1. Lösche Ereignisse
        for (const eventId of toDelete) {
            try {
                // Versuche zuerst mit direktem DELETE-Request
                try {
                    const response = await axios.delete(`/api/events/${eventId}`, config);
                    if (response.status === 200) {
                        successCount++;
                    }
                } catch (deleteError) {
                    // Fallback: Verwende POST mit _method=DELETE
                    const response = await axios.post(`/api/events/${eventId}`,
                        { _method: 'DELETE' },
                        config
                    );
                    if (response.status === 200) {
                        successCount++;
                    }
                }
            } catch (error) {
                console.error(`Fehler beim Löschen des Ereignisses ${eventId}:`, error);
            }
        }

        // 2. Aktualisiere Ereignisse
        for (const event of toUpdate) {
            try {
                // Versuche zuerst mit POST und _method=PUT (Laravel-Standard für Updates)
                const postData = { ...event, _method: 'PUT' };
                try {
                    const response = await axios.post(`/api/events/${event.id}`, postData, config);
                    if (response.status === 200) {
                        successCount++;
                    }
                } catch (postError) {
                    // Fallback: Versuche direkten PUT-Request
                    const response = await axios.put(`/api/events/${event.id}`, event, config);
                    if (response.status === 200) {
                        successCount++;
                    }
                }
            } catch (error) {
                console.error(`Fehler beim Aktualisieren des Ereignisses ${event.id}:`, error);
            }
        }

        // 3. Erstelle neue Ereignisse
        for (const event of toCreate) {
            try {
                const response = await axios.post('/api/events', event, config);
                if (response.status === 201) {
                    successCount++;
                }
            } catch (error) {
                console.error('Fehler beim Erstellen eines Ereignisses:', error);
            }
        }

        // Zeige Erfolgsmeldung
        if (successCount > 0) {
            toast.add({
                severity: 'success',
                summary: successCount === totalOperations ? 'Erfolg' : 'Teilweise erfolgreich',
                detail: successCount === totalOperations
                    ? 'Wochenplanung wurde gespeichert.'
                    : `${successCount} von ${totalOperations} Änderungen wurden gespeichert.`,
                life: 3000
            });

            // Warte kurz, damit der Server Zeit hat, die Änderungen zu verarbeiten
            setTimeout(() => {
                fetchEvents(); // Aktualisiere die Ereignisse nach dem Speichern
            }, 1000);
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

// Funktion zum Laden der Urlaubsdaten aus dem VacationService
const fetchVacationData = async () => {
    try {
        const response = await VacationService.getUserVacationData();

        if (response && response.data && response.data.requests) {
            // Konvertiere die Urlaubsanträge in das Format, das die Komponente erwartet
            const vacationRequests = response.data.requests.filter(req => req.status === 'approved');

            // Erstelle Urlaubseinträge für jeden genehmigten Urlaubsantrag
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
                source: 'vacation'
            }));

            // Füge die Urlaubseinträge zu den vorhandenen hinzu
            vacations.value = [...vacations.value, ...vacationEntries];

            console.log('Geladene Urlaubseinträge aus VacationService:', vacationEntries);

            // Prüfe speziell für den 7. Mai 2025
            const may7 = '2025-05-07';
            const may7Vacations = vacationEntries.filter(v => {
                const start = dayjs(v.startDate).format('YYYY-MM-DD');
                const end = dayjs(v.endDate).format('YYYY-MM-DD');
                return dayjs(may7).isSameOrAfter(start) && dayjs(may7).isSameOrBefore(end);
            });

            if (may7Vacations.length > 0) {
                console.log(`Gefundene Urlaubseinträge für den 7. Mai 2025:`, may7Vacations);
            } else {
                console.log(`Keine Urlaubseinträge für den 7. Mai 2025 gefunden.`);
            }
        }
    } catch (error) {
        console.error('Fehler beim Laden der Urlaubsdaten aus VacationService:', error);
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

        // Stelle sicher, dass die Ereignistypen geladen sind
        if (eventTypes.value.length === 0) {
            await fetchEventTypes();
        }

        // Fetch regular events
        let allEvents = [];
        try {
            const eventsResponse = await axios.get('/api/events', config);

            // Transform API response for all events
            allEvents = eventsResponse.data.map(event => {
                // Extrahiere den Ereignistyp-Namen aus dem event_type Feld oder dem Titel
                const eventTypeName = event.event_type || event.title;

                // Finde den passenden Ereignistyp
                let eventType = null;

                // 1. Versuche zuerst, den Typ anhand der event_type_id zu finden
                if (event.event_type_id) {
                    eventType = eventTypes.value.find(type => type.id === event.event_type_id);
                }

                // 2. Wenn nicht gefunden, versuche es mit dem event_type String
                if (!eventType && typeof eventTypeName === 'string') {
                    // Exakte Übereinstimmung
                    eventType = eventTypes.value.find(type => type.name === eventTypeName);

                    // Case-insensitive Übereinstimmung
                    if (!eventType) {
                        eventType = eventTypes.value.find(type =>
                            type.name.toLowerCase() === eventTypeName.toLowerCase()
                        );
                    }
                }

                // 3. Fallback auf "Sonstiges"
                if (!eventType) {
                    eventType = eventTypes.value.find(type => type.name === 'Sonstiges') ||
                        { id: 6, name: 'Sonstiges', value: 'other', color: '#607D8B', requires_approval: true };
                }

                // Prüfe, ob es sich um einen Urlaubseintrag handelt
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
                    event_type_id: event.event_type_id
                };
            });
        } catch (error) {
            console.error('Fehler beim Laden der Ereignisse:', error);
        }

        // Teile die Ereignisse in reguläre Ereignisse und Urlaubseinträge auf
        events.value = allEvents.filter(event => event.source !== 'vacation');

        // Füge die Urlaubseinträge aus den regulären Ereignissen zu den Urlaubseinträgen hinzu
        const vacationEvents = allEvents.filter(event => event.source === 'vacation');
        vacations.value = [...vacations.value.filter(v => !vacationEvents.some(ve => ve.id === v.id)), ...vacationEvents];

        // Nach der Aufteilung der Ereignisse
        console.log('Alle Ereignisse:', allEvents);
        console.log('Erkannte Urlaubseinträge:', vacationEvents);
        console.log('Reguläre Ereignisse:', events.value);
        console.log('Alle Urlaubseinträge nach Zusammenführung:', vacations.value);

        // Prüfe speziell für den 7. Mai 2025
        const may7 = '2025-05-07';
        const may7Vacations = vacations.value.filter(v => {
            const start = dayjs(v.startDate).format('YYYY-MM-DD');
            const end = dayjs(v.endDate).format('YYYY-MM-DD');
            return dayjs(may7).isSameOrAfter(start) && dayjs(may7).isSameOrBefore(end);
        });

        if (may7Vacations.length > 0) {
            console.log(`Gefundene Urlaubseinträge für den 7. Mai 2025 nach Zusammenführung:`, may7Vacations);
        } else {
            console.log(`Keine Urlaubseinträge für den 7. Mai 2025 nach Zusammenführung gefunden.`);
        }

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

// Manuelles Hinzufügen eines Urlaubseintrags für den 7. Mai 2025 (falls nötig)
const addMay7Vacation = () => {
    const may7Start = dayjs('2025-05-07').toDate();
    const may7End = dayjs('2025-05-07').toDate();

    // Prüfe, ob bereits ein Eintrag für diesen Tag existiert
    const existingEntry = vacations.value.some(v => {
        const start = dayjs(v.startDate).format('YYYY-MM-DD');
        const end = dayjs(v.endDate).format('YYYY-MM-DD');
        return dayjs('2025-05-07').isSameOrAfter(start) && dayjs('2025-05-07').isSameOrBefore(end);
    });

    if (!existingEntry) {
        vacations.value.push({
            id: `vacation-manual-${Date.now()}`,
            title: 'Urlaub',
            description: 'Manuell hinzugefügter Urlaub',
            startDate: may7Start,
            endDate: may7End,
            isAllDay: true,
            status: 'approved',
            type: {
                name: 'Urlaub',
                value: 'vacation',
                color: '#9C27B0'
            },
            color: '#9C27B0',
            source: 'vacation'
        });

        console.log('Manueller Urlaubseintrag für den 7. Mai 2025 hinzugefügt');
    }
};

onMounted(() => {
    fetchEventTypes();
    fetchEvents(); // Dies lädt jetzt auch die Urlaubsdaten
    fetchVacationData(); // Lade zusätzlich die Urlaubsdaten vom VacationService
    fetchHolidays(new Date().getFullYear());

    // Füge nach einer kurzen Verzögerung manuell einen Urlaubseintrag für den 7. Mai hinzu
    setTimeout(() => {
        addMay7Vacation();
    }, 2000);
});

// Beobachte Änderungen am Jahr und lade die Feiertage neu
watch(
    () => currentDate.value.year(),
    (newYear, oldYear) => {
        if (newYear !== oldYear) {
            fetchHolidays(newYear);
        }
    }
);
</script>

<style scoped>
.event-dialog .p-dialog-header,
.event-details-dialog .p-dialog-header,
.vacation-details-dialog .p-dialog-header,
.delete-confirmation-dialog .p-dialog-header,
.holiday-info-dialog .p-dialog-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--surface-d);
}

.event-dialog .p-dialog-content,
.event-details-dialog .p-dialog-content,
.vacation-details-dialog .p-dialog-content,
.delete-confirmation-dialog .p-dialog-content,
.holiday-info-dialog .p-dialog-content {
    padding: 1.5rem;
}

.event-dialog .p-dialog-footer,
.event-details-dialog .p-dialog-footer,
.vacation-details-dialog .p-dialog-footer,
.delete-confirmation-dialog .p-dialog-footer,
.holiday-info-dialog .p-dialog-footer {
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
