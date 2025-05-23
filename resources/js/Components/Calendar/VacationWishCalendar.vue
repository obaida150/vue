<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6 w-full overflow-hidden">
        <Toast />

        <!-- Header mit Navigation -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
            <div class="flex items-center">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                    Urlaubswunsch-Kalender
                </h2>
            </div>

            <!-- Team-Monats-Navigation -->
            <div class="flex items-center gap-4">
                <Button
                    icon="pi pi-chevron-left"
                    class="p-button-rounded p-button-outlined"
                    @click="previousTeamMonth"
                />
                <h3 class="text-lg font-semibold min-w-[200px] text-center">
                    {{ getMonthName(currentTeamMonth) }} {{ currentYear }}
                </h3>
                <Button
                    icon="pi pi-chevron-right"
                    class="p-button-rounded p-button-outlined"
                    @click="nextTeamMonth"
                />
            </div>

            <!-- Info-Panel -->
            <div class="flex items-center gap-4 text-sm">
                <div class="text-center">
                    <div class="font-semibold text-blue-600">{{ availableVacationDays }}</div>
                    <div class="text-gray-500">Verfügbar</div>
                </div>
                <div class="text-center">
                    <div class="font-semibold text-purple-600">{{ plannedWishDays }}</div>
                    <div class="text-gray-500">Geplant</div>
                </div>
            </div>
        </div>

        <!-- Team-Kalender -->
        <div class="calendar-container">
            <!-- Wochentage Header -->
            <div class="grid grid-cols-7 gap-2 mb-2">
                <div v-for="(day, index) in weekdaysShort" :key="index"
                     class="text-center text-sm font-semibold text-gray-600 dark:text-gray-400 py-2">
                    {{ day }}
                </div>
            </div>

            <!-- Kalendertage -->
            <div class="grid grid-cols-7 gap-2">
                <div
                    v-for="(day, index) in getTeamMonthDays()"
                    :key="index"
                    :class="[
                        'relative min-h-[120px] p-2 border rounded-lg transition-all duration-200',
                        {
                            'border-gray-200 dark:border-gray-600': day.currentMonth,
                            'border-gray-100 dark:border-gray-700 opacity-50': !day.currentMonth,
                            'bg-blue-50 border-blue-300': isToday(day.date),
                            'bg-red-100 dark:bg-red-900/20 border-red-300': isHoliday(day.date) && day.currentMonth,
                            'bg-gray-50': isWeekend(day.date) && day.currentMonth && !isHoliday(day.date),
                            'hover:bg-gray-100 dark:hover:bg-gray-700': day.currentMonth
                        }
                    ]"
                    @click="handleDayClick(day)"
                >
                    <!-- Tagesnummer -->
                    <div class="flex justify-between items-start mb-2">
                        <span :class="[
                            'text-sm font-medium',
                            {
                                'text-blue-600 font-bold': isToday(day.date),
                                'text-gray-400': !day.currentMonth,
                                'text-red-600 font-bold': isHoliday(day.date),
                                'text-gray-600': day.currentMonth && !isToday(day.date) && !isHoliday(day.date)
                            }
                        ]">
                            {{ day.dayNumber }}
                        </span>

                        <!-- Anzahl Team-Wünsche Badge -->
                        <span v-if="getTeamWishCount(day.date) > 0"
                              class="inline-flex items-center justify-center w-5 h-5 text-xs bg-amber-500 text-white rounded-full">
                            {{ getTeamWishCount(day.date) }}
                        </span>
                    </div>

                    <!-- Feiertagsname anzeigen -->
                    <div v-if="isHoliday(day.date) && day.currentMonth" class="text-xs text-red-600 font-medium mb-1 truncate">
                        {{ getHolidayName(day.date) }}
                    </div>

                    <!-- Alle Urlaubswünsche (eigene und Team) -->
                    <div v-for="(wish, idx) in getAllSortedWishesForDay(day.date)"
                         :key="'wish-' + wish.id"
                         :class="[
                            'mb-1 p-1 text-white text-xs rounded truncate flex items-center justify-between',
                            {
                                'bg-purple-500': wish.isOwn,
                                'bg-green-500': !wish.isOwn && idx === 0,
                                'bg-yellow-500': !wish.isOwn && idx === 1,
                                'bg-orange-500': !wish.isOwn && idx >= 2
                            }
                        ]">
                        <span>{{ wish.isOwn ? 'Ich' : getInitials(wish.employeeName) }}</span>
                        <span class="text-[10px]">{{ idx + 1 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog: Meine Urlaubswünsche -->
        <Dialog
            v-model:visible="showMyWishesDialog"
            header="Meine Urlaubswünsche"
            :style="{ width: '90vw', maxWidth: '600px' }"
            modal
        >
            <div v-if="myWishes.length > 0">
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-lg font-medium">Geplante Wunschtage: {{ plannedWishDays }}</h3>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Verfügbar: {{ availableVacationDays }}
                        </span>
                    </div>
                    <ProgressBar :value="(plannedWishDays / availableVacationDays) * 100" />
                </div>

                <DataTable :value="myWishes" responsiveLayout="scroll" class="p-datatable-sm">
                    <Column field="startDate" header="Von" :sortable="true">
                        <template #body="slotProps">
                            {{ formatDate(slotProps.data.startDate) }}
                        </template>
                    </Column>
                    <Column field="endDate" header="Bis" :sortable="true">
                        <template #body="slotProps">
                            {{ formatDate(slotProps.data.endDate) }}
                        </template>
                    </Column>
                    <Column field="days" header="Tage" :sortable="true" />
                    <Column field="notes" header="Notizen" />
                    <Column header="Aktionen">
                        <template #body="slotProps">
                            <Button
                                icon="pi pi-trash"
                                class="p-button-rounded p-button-danger p-button-text"
                                @click="confirmDeleteWish(slotProps.data)"
                                aria-label="Löschen"
                            />
                        </template>
                    </Column>
                </DataTable>
            </div>
            <div v-else class="text-center py-8">
                <i class="pi pi-calendar-times text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                <p class="text-gray-500 dark:text-gray-400">Sie haben noch keine Urlaubswünsche eingetragen</p>
            </div>

            <template #footer>
                <Button label="Schließen" icon="pi pi-times" @click="showMyWishesDialog = false" class="p-button-text" />
            </template>
        </Dialog>

        <!-- Dialog: Tagesdetails -->
        <Dialog
            v-model:visible="showDayDetailsDialog"
            :header="dayDetailsTitle"
            :style="{ width: '90vw', maxWidth: '700px' }"
            modal
        >
            <div v-if="selectedDay">
                <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <h3 class="text-lg font-semibold mb-2">{{ formatDate(selectedDay.date) }}</h3>
                    <p v-if="isHoliday(selectedDay.date)" class="text-red-500 font-medium">
                        <i class="pi pi-calendar-times mr-2"></i>Feiertag: {{ getHolidayName(selectedDay.date) }}
                    </p>
                </div>

                <!-- Alle Urlaubswünsche für diesen Tag -->
                <div v-if="getAllSortedWishesForDay(selectedDay.date).length > 0" class="mb-6">
                    <h4 class="text-md font-medium mb-3 flex items-center">
                        <span class="inline-block w-3 h-3 bg-amber-500 rounded-full mr-2"></span>
                        Urlaubswünsche
                    </h4>
                    <div class="bg-amber-50 dark:bg-amber-900/20 p-3 rounded-lg">
                        <div v-for="(wish, index) in getAllSortedWishesForDay(selectedDay.date)" :key="wish.id"
                             class="mb-3 last:mb-0 p-2 rounded-lg"
                             :class="[
                                wish.isOwn ? 'bg-purple-100 dark:bg-purple-900/30' :
                                index === 0 ? 'bg-green-100 dark:bg-green-900/30' :
                                index === 1 ? 'bg-yellow-100 dark:bg-yellow-900/30' :
                                index === 2 ? 'bg-orange-100 dark:bg-orange-900/30' :
                                'bg-gray-100 dark:bg-gray-800/50'
                            ]"
                        >
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <span class="font-medium">{{ wish.isOwn ? 'Ich' : wish.employeeName }}</span>
                                        <span class="ml-2 text-xs px-2 py-1 rounded-full"
                                              :class="[
                                                index === 0 ? 'bg-green-200 text-green-800' :
                                                index === 1 ? 'bg-yellow-200 text-yellow-800' :
                                                index === 2 ? 'bg-orange-200 text-orange-800' :
                                                'bg-gray-200 text-gray-800'
                                            ]"
                                        >
                                            {{ index === 0 ? 'Erste Priorität' :
                                            index === 1 ? 'Zweite Priorität' :
                                                index === 2 ? 'Dritte Priorität' :
                                                    `Priorität ${index + 1}` }}
                                        </span>
                                    </div>
                                    <p class="text-sm mt-1">{{ formatDate(wish.startDate) }} - {{ formatDate(wish.endDate) }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ wish.days }} Tage</p>
                                    <p v-if="wish.notes" class="text-sm mt-1 italic">{{ wish.notes }}</p>
                                    <p class="text-xs text-gray-500 mt-1">Eingetragen am: {{ formatDateTime(wish.created_at) }}</p>
                                </div>
                                <Button
                                    v-if="wish.isOwn"
                                    icon="pi pi-trash"
                                    class="p-button-rounded p-button-danger p-button-text"
                                    @click="confirmDeleteWish(wish)"
                                    aria-label="Löschen"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Neuen Urlaubswunsch hinzufügen -->
                <div v-if="!isHoliday(selectedDay.date) && !isWeekend(selectedDay.date) && !hasWish(selectedDay.date)" class="mt-4">
                    <Button
                        label="Urlaubswunsch hinzufügen"
                        icon="pi pi-plus"
                        class="p-button-success w-full"
                        @click="openAddWishDialog(selectedDay)"
                    />
                </div>
            </div>

            <template #footer>
                <Button label="Schließen" icon="pi pi-times" @click="showDayDetailsDialog = false" class="p-button-text" />
            </template>
        </Dialog>

        <!-- Dialog: Urlaubswunsch hinzufügen -->
        <Dialog
            v-model:visible="showAddWishDialog"
            :header="addWishDialogTitle"
            :style="{ width: '90vw', maxWidth: '500px' }"
            modal
        >
            <div class="p-fluid">
                <div class="field mb-4">
                    <label for="startDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Von</label>
                    <Calendar
                        v-model="newWish.startDate"
                        id="startDate"
                        :showIcon="true"
                        :locale="de"
                        dateFormat="dd.mm.yy"
                        :disabled="saving"
                        :disabledDates="disabledDates"
                        @date-select="updateEndDateMin"
                    />
                </div>

                <div class="field mb-4">
                    <label for="endDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bis</label>
                    <Calendar
                        v-model="newWish.endDate"
                        id="endDate"
                        :showIcon="true"
                        :locale="de"
                        dateFormat="dd.mm.yy"
                        :disabled="saving"
                        :disabledDates="disabledDates"
                        :minDate="endDateMin"
                        @date-select="calculateDays"
                    />
                </div>

                <div class="field mb-4">
                    <label for="days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Urlaubstage</label>
                    <InputNumber
                        v-model="newWish.days"
                        id="days"
                        :disabled="true"
                        class="w-full"
                    />
                </div>

                <div class="field mb-4">
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notizen</label>
                    <Textarea
                        v-model="newWish.notes"
                        id="notes"
                        rows="3"
                        :disabled="saving"
                        class="w-full"
                    />
                </div>

                <div v-if="exceedsVacationDays" class="p-message p-message-warning mb-4">
                    <i class="pi pi-exclamation-triangle p-message-icon"></i>
                    <span class="p-message-text">
                        Achtung: Die gewählten Tage überschreiten Ihr verfügbares Urlaubskontingent.
                    </span>
                </div>

                <div v-if="conflictingTeamWishes.length > 0" class="p-message p-message-info mb-4">
                    <i class="pi pi-info-circle p-message-icon"></i>
                    <span class="p-message-text">
                        Hinweis: {{ conflictingTeamWishes.length }} Teammitglied(er) haben bereits Urlaubswünsche für diesen Zeitraum eingetragen.
                        Sie können trotzdem Ihren Wunsch speichern.
                    </span>
                    <div class="mt-2 pl-5">
                        <div v-for="(wish, index) in conflictingTeamWishes" :key="wish.id" class="mb-2">
                            <div class="flex items-center">
                                <span class="inline-block w-2 h-2 rounded-full mr-2"
                                      :class="[
                                        index === 0 ? 'bg-green-500' :
                                        index === 1 ? 'bg-yellow-500' :
                                        index === 2 ? 'bg-orange-500' :
                                        'bg-gray-500'
                                    ]"
                                ></span>
                                <span>
                                    {{ wish.employeeName }} ({{ formatDate(wish.startDate) }} - {{ formatDate(wish.endDate) }})
                                    <span class="text-xs text-gray-500 ml-1">
                                        (Eingetragen: {{ formatDateTime(wish.created_at) }})
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button
                    label="Abbrechen"
                    icon="pi pi-times"
                    @click="showAddWishDialog = false"
                    class="p-button-text"
                    :disabled="saving"
                />
                <Button
                    label="Speichern"
                    icon="pi pi-check"
                    @click="saveWish"
                    :loading="saving"
                    :disabled="!canSaveWish"
                />
            </template>
        </Dialog>

        <!-- Dialog: Urlaubswunsch löschen -->
        <Dialog
            v-model:visible="showDeleteWishDialog"
            header="Urlaubswunsch löschen"
            :style="{ width: '450px' }"
            modal
        >
            <div class="confirmation-content">
                <i class="pi pi-exclamation-triangle mr-3 text-yellow-500" style="font-size: 2rem"></i>
                <span>Sind Sie sicher, dass Sie diesen Urlaubswunsch löschen möchten?</span>
            </div>

            <template #footer>
                <Button
                    label="Nein"
                    icon="pi pi-times"
                    @click="showDeleteWishDialog = false"
                    class="p-button-text"
                />
                <Button
                    label="Ja"
                    icon="pi pi-check"
                    @click="deleteWish"
                    class="p-button-danger"
                    :loading="deleting"
                />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, reactive } from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import axios from 'axios';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

// PrimeVue Komponenten
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Calendar from 'primevue/calendar';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import ProgressBar from 'primevue/progressbar';

// dayjs Plugins
import weekday from 'dayjs/plugin/weekday';
import weekOfYear from 'dayjs/plugin/weekOfYear';
import isBetween from 'dayjs/plugin/isBetween';
import isSameOrBefore from 'dayjs/plugin/isSameOrAfter';
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';

// Importiere den HolidayService
import HolidayService from '@/Services/holiday-service';

dayjs.extend(weekday);
dayjs.extend(weekOfYear);
dayjs.extend(isBetween);
dayjs.extend(isSameOrBefore);
dayjs.extend(isSameOrAfter);

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

// Wochentage
const weekdaysShort = ["Mo", "Di", "Mi", "Do", "Fr", "Sa", "So"];

// Zustand
const currentYear = ref(new Date().getFullYear());
const availableVacationDays = ref(0);
const myWishes = ref([]);
const teamWishes = ref([]);
const holidays = ref([]);
const loading = ref(false);
const currentUserId = ref(null);
const userTeamId = ref(null);

// Dialog-Zustände
const showMyWishesDialog = ref(false);
const showAddWishDialog = ref(false);
const showDeleteWishDialog = ref(false);
const showDayDetailsDialog = ref(false);
const addWishDialogTitle = ref('Urlaubswunsch hinzufügen');
const dayDetailsTitle = ref('Tagesdetails');
const saving = ref(false);
const deleting = ref(false);
const selectedDay = ref(null);

// Neuer Urlaubswunsch
const newWish = reactive({
    startDate: null,
    endDate: null,
    days: 0,
    notes: ''
});

// Hilfsvariablen
const endDateMin = ref(null);
const selectedWishToDelete = ref(null);
const disabledDates = ref([]);

// Berechnete Eigenschaften
const plannedWishDays = computed(() => {
    return myWishes.value.reduce((total, wish) => total + wish.days, 0);
});

const exceedsVacationDays = computed(() => {
    const currentPlannedDays = myWishes.value.reduce((total, wish) => {
        // Wenn der aktuelle Wunsch bearbeitet wird, nicht mitzählen
        if (selectedWishToDelete.value && selectedWishToDelete.value.id === wish.id) {
            return total;
        }
        return total + wish.days;
    }, 0);

    return currentPlannedDays + newWish.days > availableVacationDays.value;
});

const conflictingTeamWishes = computed(() => {
    if (!newWish.startDate || !newWish.endDate) {
        return [];
    }

    const start = dayjs(newWish.startDate);
    const end = dayjs(newWish.endDate);

    return teamWishes.value.filter(wish => {
        const wishStart = dayjs(wish.startDate);
        const wishEnd = dayjs(wish.endDate);

        // Prüfen, ob sich die Zeiträume überschneiden
        return (
            (start.isSame(wishStart) || start.isBefore(wishEnd)) &&
            (end.isSame(wishEnd) || end.isAfter(wishStart))
        );
    });
});

const canSaveWish = computed(() => {
    return newWish.startDate &&
        newWish.endDate &&
        newWish.days > 0 &&
        !saving.value;
});

// Methoden
const getMonthName = (monthIndex) => {
    return de.monthNames[monthIndex];
};

const getDaysInMonth = (monthIndex) => {
    const year = currentYear.value;
    const firstDayOfMonth = dayjs(new Date(year, monthIndex, 1));
    const daysInMonth = firstDayOfMonth.daysInMonth();

    // Bestimme den Wochentag des ersten Tags im Monat (0 = Sonntag, 1 = Montag, ...)
    let firstWeekday = firstDayOfMonth.day();
    // Anpassen für Montag als ersten Tag der Woche
    firstWeekday = firstWeekday === 0 ? 6 : firstWeekday - 1;

    const days = [];

    // Tage aus dem vorherigen Monat
    const prevMonth = monthIndex === 0 ? 11 : monthIndex - 1;
    const prevYear = monthIndex === 0 ? year - 1 : year;
    const daysInPrevMonth = dayjs(new Date(prevYear, prevMonth, 1)).daysInMonth();

    for (let i = 0; i < firstWeekday; i++) {
        const day = daysInPrevMonth - firstWeekday + i + 1;
        days.push({
            date: new Date(prevYear, prevMonth, day),
            dayNumber: day,
            currentMonth: false
        });
    }

    // Tage des aktuellen Monats
    for (let i = 1; i <= daysInMonth; i++) {
        days.push({
            date: new Date(year, monthIndex, i),
            dayNumber: i,
            currentMonth: true
        });
    }

    // Tage aus dem nächsten Monat
    const nextMonth = monthIndex === 11 ? 0 : monthIndex + 1;
    const nextYear = monthIndex === 11 ? year + 1 : year;
    const remainingDays = 42 - days.length; // 6 Wochen * 7 Tage = 42

    for (let i = 1; i <= remainingDays; i++) {
        days.push({
            date: new Date(nextYear, nextMonth, i),
            dayNumber: i,
            currentMonth: false
        });
    }

    return days;
};

const isToday = (date) => {
    return dayjs(date).isSame(new Date(), 'day');
};

const isHoliday = (date) => {
    if (!date || !holidays.value || !Array.isArray(holidays.value)) {
        return false;
    }

    const checkDate = dayjs(date);
    const holiday = holidays.value.find(holiday => {
        // Der HolidayService gibt dayjs-Objekte zurück
        const holidayDate = holiday.date ? holiday.date : dayjs(holiday);
        return holidayDate.format('YYYY-MM-DD') === checkDate.format('YYYY-MM-DD');
    });

    return holiday || false;
};

const getHolidayName = (date) => {
    const holiday = isHoliday(date);
    if (holiday && holiday.name) {
        return holiday.name;
    }
    return '';
};

const isWeekend = (date) => {
    const day = dayjs(date).day();
    return day === 0 || day === 6; // Sonntag oder Samstag
};

const hasWish = (date) => {
    if (!date || !myWishes.value || !Array.isArray(myWishes.value)) {
        return false;
    }

    // Wenn es ein Wochenende oder Feiertag ist, keine Wünsche anzeigen
    if (isWeekend(date) || isHoliday(date)) {
        return [];
    }

    const dateStr = dayjs(date).format('YYYY-MM-DD');

    return myWishes.value.some(wish => {
        if (!wish.startDate || !wish.endDate) return false;

        const startDate = dayjs(wish.startDate).format('YYYY-MM-DD');
        const endDate = dayjs(wish.endDate).format('YYYY-MM-DD');

        return dateStr >= startDate && dateStr <= endDate;
    });
};

const getTeamWishCount = (date) => {
    if (!date || !teamWishes.value || !Array.isArray(teamWishes.value)) {
        return 0;
    }

    // Wenn es ein Wochenende oder Feiertag ist, keine Wünsche zählen
    if (isWeekend(date) || isHoliday(date)) {
        return 0;
    }

    const dateStr = dayjs(date).format('YYYY-MM-DD');

    return teamWishes.value.filter(wish => {
        if (!wish.startDate || !wish.endDate) return false;

        const startDate = dayjs(wish.startDate).format('YYYY-MM-DD');
        const endDate = dayjs(wish.endDate).format('YYYY-MM-DD');

        return dateStr >= startDate && dateStr <= endDate;
    }).length;
};

const getMyWishesForDay = (date) => {
    if (!date || !myWishes.value || !Array.isArray(myWishes.value)) {
        return [];
    }

    // Wenn es ein Wochenende oder Feiertag ist, keine Wünsche anzeigen
    if (isWeekend(date) || isHoliday(date)) {
        return [];
    }

    const dateStr = dayjs(date).format('YYYY-MM-DD');

    return myWishes.value.filter(wish => {
        if (!wish.startDate || !wish.endDate) return false;

        const startDate = dayjs(wish.startDate).format('YYYY-MM-DD');
        const endDate = dayjs(wish.endDate).format('YYYY-MM-DD');

        return dateStr >= startDate && dateStr <= endDate;
    });
};

const getTeamWishesForDay = (date) => {
    if (!date || !teamWishes.value || !Array.isArray(teamWishes.value)) {
        return [];
    }

    // Wenn es ein Wochenende oder Feiertag ist, keine Wünsche anzeigen
    if (isWeekend(date) || isHoliday(date)) {
        return [];
    }

    const dateStr = dayjs(date).format('YYYY-MM-DD');

    return teamWishes.value.filter(wish => {
        if (!wish.startDate || !wish.endDate) return false;

        const startDate = dayjs(wish.startDate).format('YYYY-MM-DD');
        const endDate = dayjs(wish.endDate).format('YYYY-MM-DD');

        return dateStr >= startDate && dateStr <= endDate;
    });
};

const getSortedTeamWishesForDay = (date) => {
    const wishes = getTeamWishesForDay(date);
    // Sortieren nach Erstellungsdatum (älteste zuerst)
    return [...wishes].sort((a, b) => {
        return new Date(a.created_at) - new Date(b.created_at);
    });
};

const getSortedConflictingWishes = () => {
    // Sortieren nach Erstellungsdatum (älteste zuerst)
    return [...conflictingTeamWishes.value].sort((a, b) => {
        return new Date(a.created_at) - new Date(b.created_at);
    });
};

const handleDayClick = (day) => {
    // Nur Tage des aktuellen Monats
    if (!day.currentMonth) {
        return;
    }

    // Tag auswählen und Dialog öffnen
    selectedDay.value = day;
    dayDetailsTitle.value = `Details für ${formatDate(day.date)}`;
    showDayDetailsDialog.value = true;
};

const openAddWishDialog = (day) => {
    // Neuen Wunsch initialisieren
    newWish.startDate = day.date;
    newWish.endDate = day.date;
    calculateDays();
    newWish.notes = '';

    // Dialog-Titel setzen
    addWishDialogTitle.value = `Urlaubswunsch für ${formatDate(day.date)}`;

    // Dialog öffnen
    showDayDetailsDialog.value = false;
    showAddWishDialog.value = true;
};

const updateEndDateMin = () => {
    if (newWish.startDate) {
        endDateMin.value = newWish.startDate;

        // Wenn das Enddatum vor dem Startdatum liegt, setze es auf das Startdatum
        if (newWish.endDate && dayjs(newWish.endDate).isBefore(dayjs(newWish.startDate))) {
            newWish.endDate = newWish.startDate;
        }

        calculateDays();
    }
};

const calculateDays = () => {
    try {
        if (newWish.startDate && newWish.endDate) {
            const start = dayjs(newWish.startDate);
            const end = dayjs(newWish.endDate);

            // Validierung der Daten
            if (!start.isValid() || !end.isValid()) {
                newWish.days = 0;
                return;
            }

            // Stelle sicher, dass das Enddatum nicht vor dem Startdatum liegt
            if (end.isBefore(start)) {
                newWish.days = 0;
                return;
            }

            // Berechne die Anzahl der Werktage (Mo-Fr) zwischen Start und Ende
            let days = 0;
            let current = start.clone(); // Clone verwenden für Sicherheit

            // Sicherheitscheck: Maximal 365 Tage iterieren
            let iterations = 0;
            const maxIterations = 365;

            while (!current.isAfter(end, 'day') && iterations < maxIterations) {
                // Prüfe, ob der aktuelle Tag ein Werktag ist (1-5 = Mo-Fr, 0,6 = Sa,So)
                const dayOfWeek = current.day();
                if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                    // Prüfe, ob der Tag ein Feiertag ist
                    if (!isHoliday(current.toDate())) {
                        days++;
                    }
                }

                current = current.add(1, 'day');
                iterations++;
            }

            newWish.days = days;
        } else {
            newWish.days = 0;
        }
    } catch (error) {
        newWish.days = 0;

        // Toast-Nachricht für den Benutzer
        toast.add({
            severity: 'warn',
            summary: 'Warnung',
            detail: 'Fehler bei der Berechnung der Urlaubstage. Bitte prüfen Sie Ihre Eingaben.',
            life: 3000
        });
    }
};

const saveWish = async () => {
    if (!canSaveWish.value) {
        return;
    }

    saving.value = true;

    try {
        const requestData = {
            startDate: dayjs(newWish.startDate).format('YYYY-MM-DD'),
            endDate: dayjs(newWish.endDate).format('YYYY-MM-DD'),
            days: newWish.days,
            notes: newWish.notes
        };

        await axios.post('/api/vacation-wishes', requestData);

        // Erfolgsmeldung anzeigen
        toast.add({
            severity: 'success',
            summary: 'Erfolg',
            detail: 'Urlaubswunsch wurde gespeichert',
            life: 3000
        });

        // Dialog schließen und Daten neu laden
        showAddWishDialog.value = false;
        showDayDetailsDialog.value = false; // Schließe auch den Tagesdetails-Dialog, falls geöffnet
        fetchData();
    } catch (error) {
        // Detaillierte Fehlermeldung anzeigen
        let errorMessage = 'Der Urlaubswunsch konnte nicht gespeichert werden';

        if (error.response) {
            // Spezifische Fehlermeldung aus der API-Antwort extrahieren
            if (error.response.data.message) {
                errorMessage = error.response.data.message;
            }

            // Validierungsfehler anzeigen, falls vorhanden
            if (error.response.data.errors) {
                const validationErrors = Object.values(error.response.data.errors).flat();
                if (validationErrors.length > 0) {
                    errorMessage = validationErrors.join(', ');
                }
            }
        }

        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: errorMessage,
            life: 5000
        });
    } finally {
        saving.value = false;
    }
};

const confirmDeleteWish = (wish) => {
    selectedWishToDelete.value = wish;
    showDeleteWishDialog.value = true;
};

const deleteWish = async () => {
    if (!selectedWishToDelete.value) {
        return;
    }

    deleting.value = true;

    try {
        await axios.delete(`/api/vacation-wishes/${selectedWishToDelete.value.id}`);

        // Erfolgsmeldung anzeigen
        toast.add({
            severity: 'success',
            summary: 'Erfolg',
            detail: 'Urlaubswunsch wurde gelöscht',
            life: 3000
        });

        // Dialog schließen und Daten neu laden
        showDeleteWishDialog.value = false;
        showDayDetailsDialog.value = false;
        fetchData();
    } catch (error) {
        // Fehlermeldung anzeigen
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: error.response?.data?.message || 'Der Urlaubswunsch konnte nicht gelöscht werden',
            life: 3000
        });
    } finally {
        deleting.value = false;
        selectedWishToDelete.value = null;
    }
};

const formatDate = (date) => {
    if (!date) return '';
    return dayjs(date).format('DD.MM.YYYY');
};

const formatDateTime = (date) => {
    if (!date) return '';
    return dayjs(date).format('DD.MM.YYYY HH:mm');
};

// Daten laden
const fetchData = async () => {
    loading.value = true;

    try {
        // Parallel Anfragen für bessere Performance mit Promise.allSettled
        const responses = await Promise.allSettled([
            axios.get(`/api/vacation/balance/${currentYear.value}`),
            axios.get(`/api/vacation-wishes/my/${currentYear.value}`),
            axios.get(`/api/vacation-wishes/team/${currentYear.value}`),
            HolidayService.getHolidays(currentYear.value), // Verwende HolidayService
            axios.get('/api/user/info')
        ]);

        // Daten setzen mit Fehlerbehandlung
        if (responses[0].status === 'fulfilled') {
            availableVacationDays.value = responses[0].value.data.available_days || 0;
        } else {
            availableVacationDays.value = 30; // Fallback-Wert
        }

        if (responses[1].status === 'fulfilled') {
            myWishes.value = responses[1].value.data.map(wish => ({
                ...wish,
                startDate: new Date(wish.start_date),
                endDate: new Date(wish.end_date)
            }));
        } else {
            myWishes.value = [];
        }

        if (responses[2].status === 'fulfilled') {
            const teamWishesData = responses[2].value.data;

            if (Array.isArray(teamWishesData) && teamWishesData.length > 0) {
                teamWishes.value = teamWishesData.map(wish => ({
                    ...wish,
                    startDate: new Date(wish.start_date),
                    endDate: new Date(wish.end_date),
                    employeeName: wish.employee_name || 'Unbekannt',
                    created_at: wish.created_at || new Date().toISOString()
                }));
            } else {
                teamWishes.value = [];
            }
        } else {
            teamWishes.value = [];
        }

        if (responses[3].status === 'fulfilled') {
            holidays.value = responses[3].value; // HolidayService gibt direkt das Array zurück
        } else {
            holidays.value = [];
        }

        if (responses[4].status === 'fulfilled') {
            currentUserId.value = responses[4].value.data.id;
            userTeamId.value = responses[4].value.data.team_id;
        }

        // Deaktivierte Daten für den Kalender aktualisieren
        updateDisabledDates();
    } catch (error) {
        // Fehlermeldung anzeigen
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Die Daten konnten nicht vollständig geladen werden. Einige Funktionen sind möglicherweise eingeschränkt.',
            life: 5000
        });
    } finally {
        loading.value = false;
    }
};

// Deaktivierte Daten für den Kalender aktualisieren
const updateDisabledDates = () => {
    // Nur Feiertage deaktivieren, nicht mehr Wochenenden
    const disabledDatesArray = [];

    // Feiertage hinzufügen
    if (holidays.value && Array.isArray(holidays.value)) {
        holidays.value.forEach(holiday => {
            if (holiday.date) {
                // HolidayService gibt dayjs-Objekte zurück
                disabledDatesArray.push(holiday.date.toDate());
            }
        });
    }

    disabledDates.value = disabledDatesArray;
};

// Komponente initialisieren
onMounted(() => {
    fetchData();
});

// Jahr-Änderungen überwachen
watch(currentYear, () => {
    fetchData();
});

const toast = useToast();

// Team-Monats-Navigation
const currentTeamMonth = ref(new Date().getMonth());

// Methode zum Navigieren zum vorherigen Monat in der Team-Ansicht
const previousTeamMonth = () => {
    if (currentTeamMonth.value === 0) {
        currentTeamMonth.value = 11;
        currentYear.value--;
    } else {
        currentTeamMonth.value--;
    }
    fetchData();
};

// Methode zum Navigieren zum nächsten Monat in der Team-Ansicht
const nextTeamMonth = () => {
    if (currentTeamMonth.value === 11) {
        currentTeamMonth.value = 0;
        currentYear.value++;
    } else {
        currentTeamMonth.value++;
    }
    fetchData();
};

// Methode zum Abrufen der Tage für den Team-Monat
const getTeamMonthDays = () => {
    return getDaysInMonth(currentTeamMonth.value);
};

// Methode zum Extrahieren der Initialen aus einem Namen
const getInitials = (name) => {
    if (!name) return '??';

    const parts = name.split(' ');
    if (parts.length === 1) {
        return parts[0].substring(0, 2).toUpperCase();
    }

    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};

const getAllSortedWishesForDay = (date) => {
    if (!date) return [];

    // Wenn es ein Wochenende oder Feiertag ist, keine Wünsche anzeigen
    if (isWeekend(date) || isHoliday(date)) {
        return [];
    }

    const dateStr = dayjs(date).format('YYYY-MM-DD');

    // Eigene Wünsche mit isOwn-Flag versehen
    const myWishesForDay = myWishes.value
        .filter(wish => {
            if (!wish.startDate || !wish.endDate) return false;
            const startDate = dayjs(wish.startDate).format('YYYY-MM-DD');
            const endDate = dayjs(wish.endDate).format('YYYY-MM-DD');
            return dateStr >= startDate && dateStr <= endDate;
        })
        .map(wish => ({
            ...wish,
            isOwn: true,
            employeeName: 'Ich',
            created_at: wish.created_at || new Date().toISOString()
        }));

    // Team-Wünsche
    const teamWishesForDay = teamWishes.value
        .filter(wish => {
            if (!wish.startDate || !wish.endDate) return false;
            const startDate = dayjs(wish.startDate).format('YYYY-MM-DD');
            const endDate = dayjs(wish.endDate).format('YYYY-MM-DD');
            return dateStr >= startDate && dateStr <= endDate;
        })
        .map(wish => ({
            ...wish,
            isOwn: false
        }));

    // Alle Wünsche zusammenführen und nach Erstellungsdatum sortieren
    const allWishes = [...myWishesForDay, ...teamWishesForDay];
    return allWishes.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
};
</script>

<style scoped>
/* Zusätzliche Stile für den Urlaubswunsch-Kalender */
.vacation-day {
    position: relative;
}

.vacation-day::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background-color: #9C27B0;
}

/* Anpassungen für die Dialoge */
:deep(.p-dialog-content) {
    padding: 1.5rem;
}

/* Anpassungen für die DataTable */
:deep(.p-datatable .p-datatable-thead > tr > th) {
    background-color: #f8f9fa;
    color: #495057;
    font-weight: 600;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
    background-color: #ffffff;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background-color: #f8f9fa;
}

/* Anpassungen für die Bestätigungsdialoge */
.confirmation-content {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem 0;
}

.calendar-container {
    padding: 1rem;
    border-radius: 0.5rem;
}
</style>
