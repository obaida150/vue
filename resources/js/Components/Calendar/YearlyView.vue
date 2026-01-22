<template>
    <div
        class="w-full px-2 sm:px-4 lg:px-6"
        :class="layoutClass"
    >
        <div
            v-for="month in 12"
            :key="month"
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-3 sm:p-4 cursor-pointer transition-all duration-300 hover:shadow-xl hover:border-blue-400 dark:hover:border-blue-500 hover:scale-105 group"
            @click="$emit('month-click', month - 1)"
        >
            <h3 class="text-center font-semibold mb-3 text-sm text-slate-900 dark:text-slate-50 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                {{ getMonthName(month - 1) }}
            </h3>

            <div class="grid grid-cols-8 w-full border-b border-slate-200 dark:border-slate-700 mb-2 pb-2 gap-0.5">
                <div class="text-[8px] sm:text-xs font-bold text-center text-slate-600 dark:text-slate-400 col-span-1">KW</div>
                <div v-for="day in weekdaysShortLocal" :key="day" class="text-[8px] sm:text-xs font-semibold text-center text-slate-600 dark:text-slate-400 col-span-1">{{ day }}</div>
            </div>

            <div class="flex flex-col w-full gap-0.5">
                <div
                    v-for="(week, weekIndex) in monthWeeksCache[month - 1]"
                    :key="'mini-week-' + weekIndex"
                    class="grid grid-cols-8 w-full gap-0.5"
                >
                    <div
                        class="flex items-center justify-center text-[8px] sm:text-xs text-slate-500 dark:text-slate-500 cursor-pointer transition-all duration-150 hover:bg-blue-500 dark:hover:bg-blue-600 hover:text-white font-semibold rounded p-1 col-span-1"
                        @click.stop="$emit('week-plan', week.weekNumber, week.days)"
                    >
                        {{ week.weekNumber }}
                    </div>

                    <div
                        v-for="(day, dayIndex) in week.days"
                        :key="'mini-day-' + dayIndex"
                        class="flex items-center justify-center h-6 sm:h-7 text-[8px] sm:text-xs text-center relative transition-all duration-150 rounded cursor-pointer hover:scale-105 hover:shadow-sm col-span-1"
                        :class="getDayClasses(day)"
                    >
                        <div class="z-10 relative font-semibold">{{ day.dayNumber }}</div>

                        <div
                            v-if="getEventColor(day.dateStr) && !day.isToday && !day.isHolidayCached"
                            class="absolute inset-0 opacity-80 z-0 rounded hover:opacity-100 transition-opacity"
                            :style="{ backgroundColor: getEventColor(day.dateStr) }"
                        ></div>

                        <div
                            v-if="hasVacation(day.dateStr) && !day.isToday && !day.isHolidayCached && !getEventColor(day.dateStr)"
                            class="absolute inset-0 opacity-85 z-0 rounded bg-gradient-to-br from-purple-500 to-purple-600 hover:opacity-100 transition-opacity"
                            :title="`Urlaub am ${day.dateStr}`"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import dayjs from 'dayjs';

const weekdaysShortLocal = ['Mo','Di','Mi','Do','Fr','Sa','So'];

const props = defineProps({
    currentDate: {
        type: Object,
        required: true
    },
    yearLayout: {
        type: String,
        default: '6x2'
    },
    weekdaysShort: {
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
    getMonthName: {
        type: Function,
        required: true
    },
    getWeeksInMonthForMini: {
        type: Function,
        required: true
    },
    currentUserId: {
        type: Number,
        required: true
    }
});

defineEmits(['month-click', 'week-plan']);

const layoutClass = computed(() => ({
    'grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-6': props.yearLayout === '6x2',
    'grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4': props.yearLayout === '4x3'
}));

const monthWeeksCache = computed(() => {
    const cache = {};
    for (let month = 0; month < 12; month++) {
        const weeks = props.getWeeksInMonthForMini(month, props.currentDate);
        cache[month] = weeks.map(week => ({
            ...week,
            days: week.days.map(day => ({
                ...day,
                dateStr: day.date ? dayjs(day.date).format('YYYY-MM-DD') : null,
                isHolidayCached: day.date ? props.isHoliday(dayjs(day.date)) : false
            }))
        }));
    }
    return cache;
});

// Gefilterte Events nur für aktuellen User
const userEvents = computed(() => {
    console.log('Alle Events:', props.events);
    console.log('Current User ID:', props.currentUserId);
    const filtered = props.events.filter(event => {
        const userId = event.user_id ?? event.userId;
        return userId === props.currentUserId;
    });
    console.log('Gefilterte User Events:', filtered);
    return filtered;
});

// Event-Lookup-Map für O(1) Zugriff
const eventsByDate = computed(() => {
    const map = new Map();

    userEvents.value.forEach(event => {
        let startDate, endDate;

        // Verschiedene Datumsformate unterstützen
        const start = event.startDate || event.start_date || event.date;
        const end = event.endDate || event.end_date || event.date;

        if (!start) {
            console.warn('Event ohne Datum:', event);
            return;
        }

        startDate = dayjs(start);
        endDate = dayjs(end || start);

        if (!startDate.isValid() || !endDate.isValid()) {
            console.warn('Ungültiges Datum:', event);
            return;
        }

        let current = startDate;
        while (current.isBefore(endDate) || current.isSame(endDate, 'day')) {
            const dateStr = current.format('YYYY-MM-DD');
            if (!map.has(dateStr)) {
                map.set(dateStr, []);
            }
            map.get(dateStr).push(event);
            current = current.add(1, 'day');
        }
    });

    console.log('Events by Date Map:', Object.fromEntries(map));
    return map;
});

// Vacation-Lookup-Map
// Vacation-Lookup-Map - MIT User-Filterung
const vacationsByDate = computed(() => {
    const map = new Map();

    props.vacations.forEach(vacation => {
        // Prüfe ob Urlaub zum aktuellen User gehört
        const vacationUserId = vacation.user_id ?? vacation.userId;
        if (vacationUserId !== props.currentUserId) return;

        const start = vacation.startDate || vacation.start_date || vacation.date;
        const end = vacation.endDate || vacation.end_date || vacation.date;

        if (!start) return;

        const startDate = dayjs(start);
        const endDate = dayjs(end || start);

        if (!startDate.isValid() || !endDate.isValid()) return;

        let current = startDate;
        while (current.isBefore(endDate) || current.isSame(endDate, 'day')) {
            map.set(current.format('YYYY-MM-DD'), true);
            current = current.add(1, 'day');
        }
    });

    console.log('Vacations by Date Map:', Object.fromEntries(map));
    return map;
});

// Gecachte Event-Farben pro Tag - als Map für korrekten Zugriff
const dayEventColors = computed(() => {
    const colors = new Map();
    eventsByDate.value.forEach((events, dateStr) => {
        if (events.length > 0) {
            const color = events[0].color || events[0].type?.color || '#3b82f6';
            colors.set(dateStr, color);
        }
    });
    return colors;
});

// Gecachte Vacation-Checks
const dayVacations = computed(() => {
    return vacationsByDate.value;
});

// Helper-Funktionen für Template-Zugriff
const getEventColor = (dateStr) => {
    return dateStr ? dayEventColors.value.get(dateStr) : null;
};

const hasVacation = (dateStr) => {
    return dateStr ? dayVacations.value.has(dateStr) : false;
};

// Optimierte Klassen-Berechnung
const getDayClasses = (day) => {
    const eventColor = getEventColor(day.dateStr);
    const hasVac = hasVacation(day.dateStr);
    const isHolidayDay = day.isHolidayCached;

    return {
        'text-slate-300 dark:text-slate-600': !day.currentMonth,
        'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800':
            day.currentMonth && !day.isToday && !isHolidayDay && !eventColor && !hasVac,
        'bg-blue-500 text-white font-bold shadow-md': day.isToday,
        'text-slate-500 dark:text-slate-400':
            day.isWeekend && !day.isToday && !isHolidayDay && !eventColor,
        'bg-red-500 text-white font-bold shadow-md': isHolidayDay && !day.isToday
    };
};
</script>
