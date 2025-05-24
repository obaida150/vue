<template>
    <div class="calendar-container">
        <div class="grid grid-cols-7 gap-1 mb-1">
            <div
                v-for="(day, index) in weekdays"
                :key="index"
                class="p-2 text-center font-medium bg-gray-100 dark:bg-gray-800 rounded"
            >
                {{ day }}
            </div>
        </div>

        <div class="grid grid-cols-7 gap-1">
            <div
                v-for="(day, index) in days"
                :key="index"
                :class="[
                    'relative min-h-[100px] p-1 border border-gray-200 dark:border-gray-700 rounded overflow-hidden',
                    {
                        'bg-gray-50 dark:bg-gray-900': day.currentMonth && !isHoliday(dayjs(day.date)) && !hasVacationsForDay(day.date) && !isUserAbsent(day.date),
                        'bg-gray-100 dark:bg-gray-800': !day.currentMonth,
                        'border-blue-300 dark:border-blue-700': day.isToday,
                        'bg-red-50 dark:bg-red-900/20': isHoliday(dayjs(day.date)),
                        'vacation-day': hasVacationsForDay(day.date),
                        'bg-amber-50 dark:bg-amber-900/20': isUserAbsent(day.date) && !isTeamManager,
                        'team-absence-day': isTeamManager && isUserAbsent(day.date) && !hasVacationsForDay(day.date) && !showOnlyOwnEvents,
                        'cursor-pointer': day.currentMonth && !hasVacationsForDay(day.date) && !(isUserAbsent(day.date) && !isTeamManager && !isHrUser),
                        'cursor-not-allowed': hasVacationsForDay(day.date) || (isUserAbsent(day.date) && !isTeamManager && !isHrUser)
                    },
                ]"
                @click="handleDayClick(day.date, day.currentMonth)"
            >
                <div class="flex justify-between items-start mb-1">
                    <span
                        :class="[
                            'inline-flex items-center justify-center w-6 h-6 rounded-full text-sm',
                            {
                                'bg-blue-500 text-white': day.isToday,
                                'text-gray-400 dark:text-gray-500': !day.currentMonth && !day.isToday,
                                'font-bold': day.isWeekend || isHoliday(dayjs(day.date)),
                            },
                        ]"
                    >
                        {{ day.dayNumber }}
                    </span>

                    <button
                        v-if="day.weekNumber && index % 7 === 0"
                        class="text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded px-1"
                        @click.stop="$emit('week-plan', day.weekNumber, getWeekDays(day.date))"
                        title="Wochenplanung"
                    >
                        KW {{ day.weekNumber }}
                    </button>
                </div>

                <div v-if="isHoliday(dayjs(day.date))" class="text-xs text-red-600 dark:text-red-400 mb-1">
                    {{ getHolidayName(dayjs(day.date)) }}
                </div>

                <div v-if="isUserAbsent(day.date) && !isTeamManager" class="text-xs text-amber-600 dark:text-amber-400 mb-1">
                    Als abwesend markiert
                </div>

                <!-- Urlaubsanzeige mit Sperrsymbol -->
                <div v-if="hasVacationsForDay(day.date)" class="vacation-blocked">
                    <div class="text-xs text-purple-600 dark:text-purple-400 mb-1">
                        Urlaub
                    </div>
                    <div class="flex flex-col items-center justify-center mt-2 p-2 bg-purple-50/80 dark:bg-purple-900/30 rounded">
                        <i class="pi pi-ban text-purple-500 dark:text-purple-400 text-lg mb-1"></i>
                        <div class="text-xs text-purple-600 dark:text-purple-400 text-center">
                            Urlaub - Keine Einträge möglich
                        </div>
                    </div>
                </div>

                <!-- Abwesenheitsanzeige für Teammitglieder (nur für Abteilungsleiter in Teamansicht) -->
                <div v-if="isTeamManager && isUserAbsent(day.date) && !showOnlyOwnEvents && !hasVacationsForDay(day.date)" class="team-absence-indicator">
                    <div class="text-xs text-amber-600 dark:text-amber-400 mb-1">
                        Teammitglied abwesend
                    </div>
                    <div class="flex items-center justify-center mt-1 p-1 bg-amber-50/80 dark:bg-amber-900/30 rounded">
                        <i class="pi pi-user-minus text-amber-500 dark:text-amber-400 text-sm mr-1"></i>
                        <div class="text-xs text-amber-600 dark:text-amber-400">
                            Abwesend
                        </div>
                    </div>
                </div>

                <!-- HR-Ansicht oder Abteilungsleiter-Ansicht mit Zusammenfassung für Tage mit vielen Ereignissen -->
                <div v-if="(isHrUser || isTeamManager) && day.currentMonth && !hasVacationsForDay(day.date)">
                    <!-- Zusammenfassung anzeigen, wenn zu viele Ereignisse vorhanden sind -->
                    <div v-if="getEventsForDay(day.date).length > eventDisplayLimit" class="event-summary">
                        <button
                            @click.stop="showAllEventsForDay(day.date)"
                            class="w-full text-left p-1 mb-1 text-xs bg-blue-100 dark:bg-blue-900/30 hover:bg-blue-200 dark:hover:bg-blue-800/50 rounded flex justify-between items-center"
                        >
                            <span>{{ getEventsForDay(day.date).length }} Ereignisse</span>
                            <i class="pi pi-list"></i>
                        </button>

                        <!-- Zeige die ersten paar Ereignisse an -->
                        <template v-for="(event, eventIndex) in getEventsForDay(day.date).slice(0, eventDisplayLimit)" :key="eventIndex">
                            <div
                                class="event-item text-xs p-1 mb-1 rounded truncate cursor-pointer"
                                :style="{ backgroundColor: event.color + '40' }"
                                @click.stop="$emit('event-click', event)"
                                :title="`${event.title} (${event.employee_name || 'Unbekannt'})`"
                            >
                                <div class="flex items-center">
                                    <div class="w-2 h-2 rounded-full mr-1" :style="{ backgroundColor: event.color }"></div>
                                    <span class="font-medium">{{ truncateText(event.employee_name || 'Unbekannt', 12) }}</span>
                                </div>
                                <div class="pl-3 truncate">{{ truncateText(event.title, 15) }}</div>
                            </div>
                        </template>
                    </div>

                    <!-- Normale Anzeige, wenn nicht zu viele Ereignisse -->
                    <template v-else>
                        <div
                            v-for="(event, eventIndex) in getEventsForDay(day.date)"
                            :key="eventIndex"
                            class="event-item text-xs p-1 mb-1 rounded truncate cursor-pointer"
                            :style="{ backgroundColor: event.color + '40' }"
                            @click.stop="$emit('event-click', event)"
                            :title="`${event.title} (${event.employee_name || 'Unbekannt'})`"
                        >
                            <div class="flex items-center">
                                <div class="w-2 h-2 rounded-full mr-1" :style="{ backgroundColor: event.color }"></div>
                                <span class="font-medium">{{ truncateText(event.employee_name || 'Unbekannt', 12) }}</span>
                            </div>
                            <div class="pl-3 truncate">{{ truncateText(event.title, 15) }}</div>
                        </div>
                    </template>
                </div>

                <!-- Standard-Benutzeransicht (nur eigene Ereignisse) -->
                <div v-else-if="day.currentMonth && !hasVacationsForDay(day.date)">
                    <div
                        v-for="(event, eventIndex) in getEventsForDay(day.date)"
                        :key="eventIndex"
                        class="event-item text-xs p-1 mb-1 rounded truncate cursor-pointer"
                        :style="{ backgroundColor: event.color + '40' }"
                        @click.stop="$emit('event-click', event)"
                    >
                        <div class="flex items-center">
                            <div class="w-2 h-2 rounded-full mr-1" :style="{ backgroundColor: event.color }"></div>
                            <span class="font-medium">{{ event.employee_name || 'Unbekannt' }}</span>
                        </div>
                        <div class="pl-3 truncate">{{ event.title }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dialog für alle Ereignisse eines Tages -->
    <Dialog
        v-model:visible="dayEventsDialogVisible"
        :header="selectedDayHeader"
        :style="{ width: '90vw', maxWidth: '500px' }"
        modal
        class="day-events-dialog"
    >
        <div v-if="selectedDayEvents.length > 0" class="flex flex-col gap-2">
            <div class="mb-2">
                <InputText
                    v-model="eventSearchQuery"
                    placeholder="Ereignisse durchsuchen..."
                    class="w-full"
                />
            </div>

            <div class="flex flex-wrap gap-2 mb-3">
                <div
                    v-for="type in availableEventTypes"
                    :key="type.id"
                    @click="toggleEventTypeFilter(type.id)"
                    class="cursor-pointer px-2 py-1 rounded-full text-xs flex items-center gap-1"
                    :class="selectedEventTypes.includes(type.id) ? 'bg-blue-100 dark:bg-blue-900' : 'bg-gray-100 dark:bg-gray-800'"
                >
                    <div class="w-2 h-2 rounded-full" :style="{ backgroundColor: type.color }"></div>
                    <span>{{ type.name }}</span>
                </div>
            </div>

            <div class="max-h-[60vh] overflow-y-auto pr-2">
                <div
                    v-for="event in filteredDayEvents"
                    :key="event.id"
                    class="p-3 mb-2 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700"
                    @click="openEventDetails(event)"
                >
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: event.color }"></div>
                        <span class="font-medium">{{ event.type ? event.type.name : 'Ereignis' }}</span>
                    </div>

                    <div class="text-lg font-medium mb-1">{{ event.title }}</div>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center gap-1">
                            <i class="pi pi-user text-xs"></i>
                            <span>{{ event.employee_name || 'Unbekannt' }}</span>
                        </div>

                        <div v-if="event.description" class="flex items-center gap-1">
                            <i class="pi pi-comment text-xs"></i>
                            <span>{{ truncateText(event.description, 30) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-8">
            <i class="pi pi-calendar text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
            <p class="text-gray-500 dark:text-gray-400">Keine Ereignisse an diesem Tag</p>
        </div>

        <template #footer>
            <Button label="Schließen" icon="pi pi-times" @click="closeDayEventsDialog" class="p-button-text" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import dayjs from 'dayjs';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';

const props = defineProps({
    currentDate: {
        type: Object,
        required: true
    },
    weekdays: {
        type: Array,
        required: true
    },
    holidays: {
        type: Array,
        default: () => []
    },
    events: {
        type: Array,
        default: () => []
    },
    vacations: {
        type: Array,
        default: () => []
    },
    isHoliday: {
        type: Function,
        required: true
    },
    getHolidayName: {
        type: Function,
        required: true
    },
    hasEvents: {
        type: Function,
        required: true
    },
    hasVacations: {
        type: Function,
        required: true
    },
    getEventsForDay: {
        type: Function,
        required: true
    },
    getVacationsForDay: {
        type: Function,
        required: true
    },
    truncateText: {
        type: Function,
        required: true
    },
    isHrUser: {
        type: Boolean,
        default: false
    },
    isTeamManager: {
        type: Boolean,
        default: false
    },
    showOnlyOwnEvents: {
        type: Boolean,
        default: false
    },
    currentUserId: {
        type: Number,
        default: null
    },
    isUserAbsent: {
        type: Function,
        required: true
    }
});

const emit = defineEmits(['day-click', 'event-click', 'vacation-click', 'week-plan']);

// Limit für die Anzahl der angezeigten Ereignisse pro Tag
const eventDisplayLimit = 2;

// Dialog für alle Ereignisse eines Tages
const dayEventsDialogVisible = ref(false);
const selectedDayEvents = ref([]);
const selectedDayDate = ref(null);
const eventSearchQuery = ref('');
const selectedEventTypes = ref([]);

// Lokale Funktion für Urlaubsprüfung
const hasVacationsForDay = (date) => {
    if (!date || !props.currentUserId) {
        return false;
    }

    // Verwende die übergebene hasVacations Funktion
    return props.hasVacations(date);
};

// Berechne den Header für den Dialog
const selectedDayHeader = computed(() => {
    if (!selectedDayDate.value) return 'Ereignisse';
    return `Ereignisse am ${dayjs(selectedDayDate.value).format('DD.MM.YYYY')}`;
});

// Verfügbare Ereignistypen für den Filter
const availableEventTypes = computed(() => {
    if (!selectedDayEvents.value.length) return [];

    const types = new Map();
    selectedDayEvents.value.forEach(event => {
        if (event.type && event.type.id) {
            types.set(event.type.id, {
                id: event.type.id,
                name: event.type.name,
                color: event.color
            });
        }
    });

    return Array.from(types.values());
});

// Gefilterte Ereignisse basierend auf Suchbegriff und ausgewählten Typen
const filteredDayEvents = computed(() => {
    let filtered = [...selectedDayEvents.value];

    // Textsuche
    if (eventSearchQuery.value.trim()) {
        const query = eventSearchQuery.value.toLowerCase();
        filtered = filtered.filter(event =>
            (event.title && event.title.toLowerCase().includes(query)) ||
            (event.description && event.description.toLowerCase().includes(query)) ||
            (event.employee_name && event.employee_name.toLowerCase().includes(query))
        );
    }

    // Typ-Filter
    if (selectedEventTypes.value.length > 0) {
        filtered = filtered.filter(event =>
            event.type && selectedEventTypes.value.includes(event.type.id)
        );
    }

    // Sortieren nach Mitarbeitername
    return filtered.sort((a, b) => {
        const nameA = (a.employee_name || '').toLowerCase();
        const nameB = (b.employee_name || '').toLowerCase();
        return nameA.localeCompare(nameB);
    });
});

// Tage des aktuellen Monats generieren
const days = computed(() => {
    const startOfMonth = props.currentDate.startOf('month');
    const endOfMonth = props.currentDate.endOf('month');

    // Finde den ersten Tag der ersten Woche des Monats (Montag)
    let firstDay = startOfMonth.day();
    firstDay = firstDay === 0 ? 6 : firstDay - 1; // Anpassung für Montag als ersten Tag der Woche

    const daysInMonth = [];

    // Tage aus dem vorherigen Monat hinzufügen
    const prevMonth = startOfMonth.subtract(1, 'day');
    for (let i = firstDay - 1; i >= 0; i--) {
        const date = prevMonth.subtract(i, 'day');
        daysInMonth.push({
            date: date.toDate(),
            dayNumber: date.date(),
            currentMonth: false,
            isToday: date.isSame(dayjs(), 'day'),
            isWeekend: date.day() === 0 || date.day() === 6,
            weekNumber: date.week()
        });
    }

    // Tage des aktuellen Monats hinzufügen
    for (let i = 0; i < endOfMonth.date(); i++) {
        const date = startOfMonth.add(i, 'day');
        daysInMonth.push({
            date: date.toDate(),
            dayNumber: date.date(),
            currentMonth: true,
            isToday: date.isSame(dayjs(), 'day'),
            isWeekend: date.day() === 0 || date.day() === 6,
            weekNumber: date.week()
        });
    }

    // Tage aus dem nächsten Monat hinzufügen, um das Raster zu füllen
    const daysNeeded = 42 - daysInMonth.length; // 6 Wochen * 7 Tage = 42
    const nextMonth = endOfMonth.add(1, 'day');
    for (let i = 0; i < daysNeeded; i++) {
        const date = nextMonth.add(i, 'day');
        daysInMonth.push({
            date: date.toDate(),
            dayNumber: date.date(),
            currentMonth: false,
            isToday: date.isSame(dayjs(), 'day'),
            isWeekend: date.day() === 0 || date.day() === 6,
            weekNumber: date.week()
        });
    }

    return daysInMonth;
});

// Tage einer Woche für die Wochenplanung ermitteln
const getWeekDays = (date) => {
    const dayOfWeek = dayjs(date).day();
    const diff = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
    const monday = dayjs(date).subtract(diff, 'day');

    const weekDays = [];
    for (let i = 0; i < 7; i++) {
        const currentDay = monday.add(i, 'day');
        weekDays.push({
            date: currentDay.toDate(),
            dayNumber: currentDay.date(),
            currentMonth: currentDay.month() === props.currentDate.month(),
            isToday: currentDay.isSame(dayjs(), 'day'),
            isWeekend: currentDay.day() === 0 || currentDay.day() === 6
        });
    }

    return weekDays;
};

// Klick auf einen Tag behandeln
const handleDayClick = (date, isCurrentMonth) => {
    // Wenn der Tag nicht zum aktuellen Monat gehört, ignoriere den Klick
    if (!isCurrentMonth) return;

    // Wenn der Benutzer an diesem Tag Urlaub hat, blockiere den Klick
    if (hasVacationsForDay(date)) {
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
        return;
    }

    // Wenn der Benutzer an diesem Tag als abwesend markiert ist und kein HR-Mitarbeiter oder Abteilungsleiter ist,
    // blockiere die Aktion
    if (props.isUserAbsent(date) && !props.isTeamManager && !props.isHrUser) {
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
        return;
    }

    emit('day-click', date);
};

// Alle Ereignisse eines Tages anzeigen
const showAllEventsForDay = (date) => {
    selectedDayDate.value = date;
    selectedDayEvents.value = props.getEventsForDay(date);
    selectedEventTypes.value = []; // Filter zurücksetzen
    eventSearchQuery.value = ''; // Suche zurücksetzen
    dayEventsDialogVisible.value = true;
};

// Ereignistyp-Filter umschalten
const toggleEventTypeFilter = (typeId) => {
    const index = selectedEventTypes.value.indexOf(typeId);
    if (index === -1) {
        selectedEventTypes.value.push(typeId);
    } else {
        selectedEventTypes.value.splice(index, 1);
    }
};

// Ereignisdetails öffnen
const openEventDetails = (event) => {
    // Prüfen, ob es sich um einen Krankheitseintrag handelt und der Benutzer kein HR-Mitarbeiter ist
    if (event.type &&
        event.type.name === 'Krankheit' &&
        !props.isHrUser) {
        // Trotzdem Details anzeigen, aber eine Warnung in der Konsole ausgeben
        console.warn('Hinweis: Krankheitseinträge können nur von HR-Mitarbeitern bearbeitet oder gelöscht werden.');
    }

    emit('event-click', event);
    closeDayEventsDialog();
};

// Dialog schließen
const closeDayEventsDialog = () => {
    dayEventsDialogVisible.value = false;
};
</script>

<style scoped>
.calendar-container {
    width: 100%;
    overflow-x: auto;
}

.event-item {
    transition: all 0.2s ease;
}

.event-item:hover {
    filter: brightness(0.95);
}

/* Scrollbar-Styling für den Dialog */
.day-events-dialog :deep(.p-dialog-content) {
    padding: 1.5rem;
}

/* Scrollbar-Styling */
.max-h-\[60vh\]::-webkit-scrollbar {
    width: 6px;
}

.max-h-\[60vh\]::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.max-h-\[60vh\]::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.max-h-\[60vh\]::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Styling für Urlaubstage */
.vacation-blocked {
    display: flex;
    flex-direction: column;
    height: 100%;
}

/* Spezifisches Styling für Urlaubstage */
.vacation-day {
    background-color: rgba(156, 39, 176, 0.1) !important;
}

:deep(.dark) .vacation-day {
    background-color: rgba(156, 39, 176, 0.2) !important;
}

/* Styling für Team-Abwesenheitstage */
.team-absence-day {
    background-color: rgba(251, 191, 36, 0.3) !important; /* Amber-Ton */
}

:deep(.dark) .team-absence-day {
    background-color: rgba(251, 191, 36, 0.4) !important; /* Dunklerer Amber-Ton */
}

/* Styling für Teammitglieder-Abwesenheiten */
.team-absence-day {
    background-color: rgba(245, 158, 11, 0.1) !important;
}

:deep(.dark) .team-absence-day {
    background-color: rgba(245, 158, 11, 0.2) !important;
}

.team-absence-indicator {
    display: flex;
    flex-direction: column;
}
</style>
