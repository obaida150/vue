<template>
    <!-- Optimized grid layout for 6x2 with better proportions -->
    <div
        class="w-full px-2 sm:px-4 lg:px-6"
        :class="{
      'grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-6': yearLayout === '6x2',
      'grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4': yearLayout === '4x3'
    }"
    >
        <div
            v-for="month in 12"
            :key="month"
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-3 sm:p-4 cursor-pointer transition-all duration-300 hover:shadow-xl hover:border-blue-400 dark:hover:border-blue-500 hover:scale-105 group"
            @click="$emit('month-click', month - 1)"
        >
            <!-- Month header with improved styling -->
            <h3 class="text-center font-semibold mb-3 text-sm text-slate-900 dark:text-slate-50 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                {{ getMonthName(month - 1) }}
            </h3>

            <!-- Day header row - improved grid alignment with consistent column widths -->
            <div class="grid grid-cols-8 w-full border-b border-slate-200 dark:border-slate-700 mb-2 pb-2 gap-0.5">
                <div class="text-[8px] sm:text-xs font-bold text-center text-slate-600 dark:text-slate-400 col-span-1">KW</div>
                <div v-for="day in weekdaysShort" :key="day" class="text-[8px] sm:text-xs font-semibold text-center text-slate-600 dark:text-slate-400 col-span-1">{{ day }}</div>
            </div>

            <!-- Calendar weeks and days with improved grid layout -->
            <div class="flex flex-col w-full gap-0.5">
                <div
                    v-for="(week, weekIndex) in getWeeksInMonthForMini(month - 1, currentDate)"
                    :key="'mini-week-' + weekIndex"
                    class="grid grid-cols-8 w-full gap-0.5"
                >
                    <!-- Week number - aligned to grid -->
                    <div
                        class="flex items-center justify-center text-[8px] sm:text-xs text-slate-500 dark:text-slate-500 cursor-pointer transition-all duration-150 hover:bg-blue-500 dark:hover:bg-blue-600 hover:text-white font-semibold rounded p-1 col-span-1"
                        @click.stop="$emit('week-plan', week.weekNumber, week.days)"
                    >
                        {{ week.weekNumber }}
                    </div>

                    <!-- Day cells - now using grid for perfect alignment -->
                    <div
                        v-for="(day, dayIndex) in week.days"
                        :key="'mini-day-' + dayIndex"
                        class="flex items-center justify-center h-6 sm:h-7 text-[8px] sm:text-xs text-center relative transition-all duration-150 rounded cursor-pointer hover:scale-105 hover:shadow-sm col-span-1"
                        :class="{
              'text-slate-300 dark:text-slate-600': !day.currentMonth,
              'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800': day.currentMonth && !day.isToday && !isHoliday(dayjs(day.date)) && !hasEvents(day.date) && !hasVacations(day.date),
              'bg-blue-500 text-white font-bold shadow-md': day.isToday,
              'text-slate-500 dark:text-slate-400': day.isWeekend && !day.isToday && !isHoliday(dayjs(day.date)) && !hasEvents(day.date),
              'bg-red-500 text-white font-bold shadow-md': isHoliday(dayjs(day.date)) && !day.isToday
            }"
                    >
                        <!-- Day number with z-index management -->
                        <div class="z-10 relative font-semibold">{{ day.dayNumber }}</div>

                        <!-- Event indicator -->
                        <div
                            v-if="hasEvents(day.date) && !day.isToday && !isHoliday(dayjs(day.date))"
                            class="absolute inset-0 opacity-80 z-0 rounded hover:opacity-100 transition-opacity"
                            :style="{ backgroundColor: getEventColorForDay(day.date) }"
                        ></div>

                        <!-- Vacation indicator -->
                        <div
                            v-if="hasVacations(day.date) && !day.isToday && !isHoliday(dayjs(day.date)) && !hasEvents(day.date)"
                            class="absolute inset-0 opacity-85 z-0 rounded bg-gradient-to-br from-purple-500 to-purple-600 hover:opacity-100 transition-opacity"
                            :title="`Urlaub am ${dayjs(day.date).format('DD.MM.YYYY')}`"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>


<script setup>
// import { defineProps, defineEmits } from 'vue';
import dayjs from 'dayjs';
const weekdaysShort = ['Mo','Di','Mi','Do','Fr','Sa','So'];
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
    hasEvents: {
        type: Function,
        required: true
    },
    hasVacations: {
        type: Function,
        required: true
    },
    getEventColorForDay: {
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

// Farbe eines Ereignisses für einen bestimmten Tag finden (nur eigene Ereignisse)
const getEventColorForDay = (date) => {
    if (!date) return null;
    const dateStr = dayjs(date).format('YYYY-MM-DD');

    const event = props.events.find(event => {
        let eventStartDate, eventEndDate;

        if (event.startDate) {
            eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
            eventEndDate = dayjs(event.endDate || event.startDate).format('YYYY-MM-DD');
        } else if (event.start_date) {
            eventStartDate = dayjs(event.start_date).format('YYYY-MM-DD');
            eventEndDate = dayjs(event.end_date || event.start_date).format('YYYY-MM-DD');
        } else if (event.date) {
            eventStartDate = eventEndDate = dayjs(event.date).format('YYYY-MM-DD');
        } else {
            return false;
        }

        // Nur eigene Ereignisse berücksichtigen
        return dateStr >= eventStartDate && dateStr <= eventEndDate &&
            event.user_id === props.currentUserId;
    });

    return event ? (event.color || event.type?.color) : null;
};

// Prüft, ob ein Tag Ereignisse hat (nur eigene Ereignisse)
const hasEvents = (date) => {
    if (!date) return false;
    const dateStr = dayjs(date).format('YYYY-MM-DD');

    return props.events.some(event => {
        // Prüfe verschiedene Datumsformate
        let eventStartDate, eventEndDate;

        if (event.startDate) {
            eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
            eventEndDate = dayjs(event.endDate || event.startDate).format('YYYY-MM-DD');
        } else if (event.start_date) {
            eventStartDate = dayjs(event.start_date).format('YYYY-MM-DD');
            eventEndDate = dayjs(event.end_date || event.start_date).format('YYYY-MM-DD');
        } else if (event.date) {
            eventStartDate = eventEndDate = dayjs(event.date).format('YYYY-MM-DD');
        } else {
            return false;
        }

        // Nur eigene Ereignisse berücksichtigen
        return dateStr >= eventStartDate && dateStr <= eventEndDate &&
            event.user_id === props.currentUserId;
    });
};

</script>
