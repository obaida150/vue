<template>
    <div class="calendar-container">
        <!-- Modernized header with better spacing and styling -->
        <div class="grid grid-cols-7 gap-2 mb-4">
            <div
                v-for="(day, index) in weekdays"
                :key="index"
                class="p-3 text-center font-semibold text-sm bg-gradient-to-b from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-lg text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
            >
                {{ day }}
            </div>
        </div>

        <!-- Enhanced calendar grid with better visual hierarchy -->
        <div class="grid grid-cols-7 gap-2">
            <div
                v-for="(day, index) in days"
                :key="index"
                :class="[
                    'relative min-h-[120px] max-h-[180px] p-3 rounded-lg border transition-all duration-200 flex flex-col',
                    {
                        'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:shadow-md': day.currentMonth && !isHoliday(dayjs(day.date)) && !hasVacationsForDay(day.date) && !isUserAbsent(day.date),
                        'bg-slate-100 dark:bg-slate-900 border-slate-300 dark:border-slate-600': !day.currentMonth,
                        'border-blue-400 dark:border-blue-600 bg-blue-50 dark:bg-blue-900/20 ring-2 ring-blue-200 dark:ring-blue-800': day.isToday,
                        'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800': isHoliday(dayjs(day.date)),
                        'vacation-day': hasVacationsForDay(day.date) && !props.isHrUser && !props.isTeamManager,
                        'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800': isUserAbsent(day.date) && !isTeamManager,
                        'team-absence-day': isTeamManager && isUserAbsent(day.date) && !showOnlyOwnEvents && !hasVacationsForDay(day.date),
                        'cursor-pointer': day.currentMonth && (!hasVacationsForDay(day.date) || props.isHrUser || props.isTeamManager) && !(isUserAbsent(day.date) && !isTeamManager && !isHrUser),
                        'cursor-not-allowed opacity-60': hasVacationsForDay(day.date) && !props.isHrUser && !props.isTeamManager || (isUserAbsent(day.date) && !isTeamManager && !isHrUser)
                    },
                ]"
                @click="handleDayClick(day.date, day.currentMonth)"
            >
                <div class="flex justify-between items-start mb-1">
                    <span
                        :class="[
                            'inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-semibold',
                            {
                                'bg-blue-500 text-white': day.isToday,
                                'text-slate-400 dark:text-slate-500': !day.currentMonth && !day.isToday,
                                'text-slate-900 dark:text-slate-100': day.currentMonth && !day.isToday,
                                'text-slate-600 dark:text-slate-400': day.isWeekend,
                            },
                        ]"
                    >
                        {{ day.dayNumber }}
                    </span>

                    <button
                        v-if="day.weekNumber && index % 7 === 0"
                        class="text-xs font-medium bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 rounded px-1.5 py-0.5 transition-colors text-slate-700 dark:text-slate-300"
                        @click.stop="$emit('week-plan', day.weekNumber, getWeekDays(day.date))"
                        title="Wochenplanung"
                    >
                        KW {{ day.weekNumber }}
                    </button>
                </div>

                <!-- Holiday indicator -->
                <div v-if="isHoliday(dayjs(day.date))" class="text-xs font-medium text-red-600 dark:text-red-400 mb-1 flex items-center gap-1">
                    <i class="pi pi-calendar-times text-sm"></i>
                    <span class="truncate">{{ getHolidayName(dayjs(day.date)) }}</span>
                </div>

                <!-- Updated vacation blocked UI -->
                <div v-if="hasVacationsForDay(day.date) && !props.isHrUser && !props.isTeamManager" class="vacation-blocked">
                    <div class="flex flex-col items-center justify-center p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg border border-purple-200 dark:border-purple-800">
                        <i class="pi pi-ban text-purple-500 dark:text-purple-400 text-lg mb-0.5"></i>
                        <div class="text-xs font-medium text-purple-700 dark:text-purple-300 text-center">
                            Urlaub
                        </div>
                    </div>
                </div>

                <!-- HR vacation indicator -->
                <div v-else-if="hasVacationsForDay(day.date) && (props.isHrUser || props.isTeamManager)" class="vacation-info mb-1">
                    <div class="text-xs font-medium text-purple-600 dark:text-purple-400 flex items-center gap-1">
                        <i class="pi pi-calendar text-sm"></i>
                        Ihr Urlaub
                    </div>
                </div>

                <!-- Team absence indicator -->
                <div v-if="isTeamManager && isUserAbsent(day.date) && !showOnlyOwnEvents && !hasVacationsForDay(day.date)" class="team-absence-indicator mb-1">
                    <div class="text-xs font-medium text-amber-600 dark:text-amber-400 flex items-center gap-1">
                        <i class="pi pi-user-minus text-sm"></i>
                        <span class="truncate">Teammitglied abwesend</span>
                    </div>
                </div>

                <!-- Absence indicator for regular users -->
                <div v-if="isUserAbsent(day.date) && !isTeamManager && !isHrUser" class="text-xs font-medium text-amber-600 dark:text-amber-400 mb-1 flex items-center gap-1">
                    <i class="pi pi-info-circle text-sm"></i>
                    <span class="truncate">Abwesend</span>
                </div>

                <!-- HR absence entries section -->
                <div v-if="isHrUser && getAllAbsenceEntriesForDay && getAllAbsenceEntriesForDay(day.date).length > 0" class="space-y-0.5 mb-1 flex-1 overflow-hidden">
                    <div
                        v-for="(absence, idx) in getAllAbsenceEntriesForDay(day.date).slice(0, 2)"
                        :key="absence.id"
                        class="text-xs p-1.5 bg-amber-100 dark:bg-amber-900/30 rounded-md cursor-pointer hover:bg-amber-200 dark:hover:bg-amber-800/50 transition-colors border border-amber-200 dark:border-amber-700 truncate"
                        @click.stop="$emit('event-click', absence)"
                        :title="`${absence.employee_name || 'Unbekannt'}`"
                    >
                        <div class="flex items-center gap-1 min-w-0">
                            <i class="pi pi-user-minus text-amber-600 dark:text-amber-400 text-xs flex-shrink-0"></i>
                            <span class="font-semibold text-amber-700 dark:text-amber-300 truncate">
                                {{ truncateText(absence.employee_name || 'Unbekannt', 12) }}
                            </span>
                        </div>
                    </div>
                    <!-- Show "and X more" for additional absence entries with click handler -->
                    <div
                        v-if="getAllAbsenceEntriesForDay(day.date).length > 2"
                        class="text-xs text-center p-1.5 bg-amber-50 dark:bg-amber-900/20 rounded-md cursor-pointer hover:bg-amber-100 dark:hover:bg-amber-800/30 transition-colors border border-amber-100 dark:border-amber-800 font-medium text-amber-600 dark:text-amber-400"
                        @click.stop="showAllAbsenceEntriesForDay(day.date)"
                    >
                        +{{ getAllAbsenceEntriesForDay(day.date).length - 2 }}
                    </div>
                </div>

                <!-- Events section - Limit to 2 events with "and X more" functionality -->
                <div v-else-if="day.currentMonth && (!hasVacationsForDay(day.date) || props.isHrUser || props.isTeamManager)" class="space-y-0.5 flex-1 overflow-hidden flex flex-col">
                    <div
                        v-for="(event, eventIndex) in getEventsForDay(day.date).slice(0, 2)"
                        :key="eventIndex"
                        class="text-xs p-1.5 rounded-md cursor-pointer transition-all hover:shadow-sm border-l-3 truncate"
                        :style="{
                            backgroundColor: event.color + '15',
                            borderLeftColor: event.color
                        }"
                        @click.stop="$emit('event-click', event)"
                        :title="`${event.title} - ${event.employee_name || 'Unbekannt'}`"
                    >
                        <div class="font-semibold truncate" :style="{ color: event.color }">
                            {{ event.title }}
                        </div>
                        <div class="text-xs text-slate-600 dark:text-slate-400 truncate">
                            {{ truncateText(event.employee_name || 'Unbekannt', 15) }}
                        </div>
                    </div>

                    <!-- Show "and X more events" button with popup functionality -->
                    <div
                        v-if="getEventsForDay(day.date).length > 2"
                        class="text-xs text-center p-1.5 bg-blue-50 dark:bg-blue-900/20 rounded-md cursor-pointer hover:bg-blue-100 dark:hover:bg-blue-800/30 transition-colors border border-blue-100 dark:border-blue-800 font-medium text-blue-600 dark:text-blue-400"
                        @click.stop="showAllEventsForDay(day.date)"
                    >
                        +{{ getEventsForDay(day.date).length - 2 }} weitere
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced dialog for showing all events for a day -->
    <Dialog
        v-model:visible="dayEventsDialogVisible"
        :header="selectedDayHeader"
        :style="{ width: '90vw', maxWidth: '500px' }"
        modal
        class="day-events-dialog"
    >
        <div v-if="selectedDayEvents.length > 0" class="flex flex-col gap-3">
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
                    class="cursor-pointer px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1 transition-all"
                    :class="selectedEventTypes.includes(type.id) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300'"
                >
                    <div class="w-2 h-2 rounded-full" :style="{ backgroundColor: type.color }"></div>
                    <span>{{ type.name }}</span>
                </div>
            </div>

            <div class="max-h-[60vh] overflow-y-auto pr-2 space-y-2">
                <div
                    v-for="event in filteredDayEvents"
                    :key="event.id"
                    class="p-3 rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-600 transition-all hover:shadow-md"
                    @click="openEventDetails(event)"
                >
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: event.color }"></div>
                        <span class="font-semibold text-sm">{{ event.type ? event.type.name : 'Ereignis' }}</span>
                    </div>

                    <div class="text-base font-bold mb-2 text-slate-900 dark:text-slate-100">{{ event.title }}</div>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
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
            <i class="pi pi-calendar text-5xl text-slate-300 dark:text-slate-600 mb-3"></i>
            <p class="text-slate-500 dark:text-slate-400 font-medium">Keine Ereignisse an diesem Tag</p>
        </div>

        <template #footer>
            <Button label="Schließen" icon="pi pi-times" @click="closeDayEventsDialog" class="p-button-text" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import dayjs from 'dayjs';
import customParseFormat from 'dayjs/plugin/customParseFormat'
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
dayjs.extend(customParseFormat)

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
    },
    hasAbsenceEntriesForDay: {
        type: Function,
        default: () => () => false
    },
    getAllAbsenceEntriesForDay: {
        type: Function,
        default: () => () => []
    },
    absenceEntries: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['day-click', 'event-click', 'vacation-click', 'week-plan', 'absence-details']);

const eventDisplayLimit = 2;

const dayEventsDialogVisible = ref(false);
const selectedDayEvents = ref([]);
const selectedDayDate = ref(null);
const eventSearchQuery = ref('');
const selectedEventTypes = ref([]);

const hasVacationsForDay = (date) => {
    if (!date || !props.currentUserId) {
        return false;
    }

    const dateStr = dayjs(date).format('YYYY-MM-DD');

    const isExplicitVacationInVacationsArray = props.vacations.some(vacation => {
        if (vacation.userId !== props.currentUserId) return false;

        const startDate = dayjs(vacation.start).format('YYYY-MM-DD');
        const endDate = dayjs(vacation.end).format('YYYY-MM-DD');

        return dateStr >= startDate && dateStr <= endDate;
    });

    const isTransferDayVacation = props.hasVacations(date);

    if (dayjs(date).isSame(dayjs(), 'day') && !isExplicitVacationInVacationsArray && isTransferDayVacation) {
        return false;
    }

    return isExplicitVacationInVacationsArray || isTransferDayVacation;
};

const getVacationDetailsForDay = (date) => {
    if (!date || !props.currentUserId) return null;

    const dateStr = dayjs(date).format('YYYY-MM-DD');
    const vacation = props.vacations.find(vacation => {
        if (vacation.userId !== props.currentUserId) return false;

        const startDate = dayjs(vacation.start).format('YYYY-MM-DD');
        const endDate = dayjs(vacation.end).format('YYYY-MM-DD');

        return dateStr >= startDate && dateStr <= endDate;
    });

    return vacation;
};

function formatTime(timeStr) {
    if (!timeStr) return ''
    if (/^\d{2}:\d{2}:\d{2}$/.test(timeStr)) {
        return dayjs(timeStr, 'HH:mm:ss').format('HH:mm')
    }
    if (/^\d{2}:\d{2}$/.test(timeStr)) {
        return timeStr
    }
    const d = dayjs(timeStr)
    return d.isValid() ? d.format('HH:mm') : String(timeStr)
}

const selectedDayHeader = computed(() => {
    if (!selectedDayDate.value) return 'Ereignisse';
    return `Ereignisse am ${dayjs(selectedDayDate.value).format('DD.MM.YYYY')}`;
});

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

const filteredDayEvents = computed(() => {
    let filtered = [...selectedDayEvents.value];

    if (eventSearchQuery.value.trim()) {
        const query = eventSearchQuery.value.toLowerCase();
        filtered = filtered.filter(event =>
            (event.title && event.title.toLowerCase().includes(query)) ||
            (event.description && event.description.toLowerCase().includes(query)) ||
            (event.employee_name && event.employee_name.toLowerCase().includes(query))
        );
    }

    if (selectedEventTypes.value.length > 0) {
        filtered = filtered.filter(event =>
            event.type && selectedEventTypes.value.includes(event.type.id)
        );
    }

    return filtered.sort((a, b) => {
        const nameA = (a.employee_name || '').toLowerCase();
        const nameB = (b.employee_name || '').toLowerCase();
        return nameA.localeCompare(nameB);
    });
});

const days = computed(() => {
    const startOfMonth = props.currentDate.startOf('month');
    const endOfMonth = props.currentDate.endOf('month');

    let firstDay = startOfMonth.day();
    firstDay = firstDay === 0 ? 6 : firstDay - 1;

    const daysInMonth = [];

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

    const daysNeeded = 42 - daysInMonth.length;
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

const handleDayClick = (date, isCurrentMonth) => {
    if (!isCurrentMonth) return;

    if (hasVacationsForDay(date) && !props.isHrUser && !props.isTeamManager) {
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

const showAllEventsForDay = (date) => {
    selectedDayDate.value = date;
    selectedDayEvents.value = props.getEventsForDay(date);
    selectedEventTypes.value = [];
    eventSearchQuery.value = '';
    dayEventsDialogVisible.value = true;
};

const showAllAbsenceEntriesForDay = (date) => {
    selectedDayDate.value = date;
    selectedDayEvents.value = props.getAllAbsenceEntriesForDay ? props.getAllAbsenceEntriesForDay(date) : [];
    selectedEventTypes.value = [];
    eventSearchQuery.value = '';
    dayEventsDialogVisible.value = true;
};

const toggleEventTypeFilter = (typeId) => {
    const index = selectedEventTypes.value.indexOf(typeId);
    if (index === -1) {
        selectedEventTypes.value.push(typeId);
    } else {
        selectedEventTypes.value.splice(index, 1);
    }
};

const openEventDetails = (event) => {
    if (event.type &&
        event.type.name === 'Krank' &&
        !props.isHrUser) {
        console.warn('Hinweis: Krankheitseinträge können nur von HR-Mitarbeitern bearbeitet oder gelöscht werden.');
    }

    emit('event-click', event);
    closeDayEventsDialog();
};

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

.day-events-dialog :deep(.p-dialog-content) {
    padding: 1.5rem;
}

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

.vacation-blocked {
    display: flex;
    flex-direction: column;
    height: 100%;
    justify-content: center;
}

.vacation-day {
    background-color: rgba(156, 39, 176, 0.1) !important;
}

:deep(.dark) .vacation-day {
    background-color: rgba(156, 39, 176, 0.15) !important;
}

.team-absence-day {
    background-color: rgba(251, 191, 36, 0.08) !important;
}

:deep(.dark) .team-absence-day {
    background-color: rgba(251, 191, 36, 0.1) !important;
}

.team-absence-indicator {
    display: flex;
    flex-direction: column;
}

.hr-absence-indicator {
    display: flex;
    flex-direction: column;
}

.hr-absence-indicator .cursor-pointer:hover {
    background-color: rgba(245, 158, 11, 0.1) !important;
}

.vacation-info {
    padding: 0.25rem;
    border-left: 3px solid rgb(156, 39, 176);
    padding-left: 0.5rem;
}

:deep(.dark) .hr-absence-indicator .cursor-pointer:hover {
    background-color: rgba(245, 158, 11, 0.15) !important;
}
</style>
