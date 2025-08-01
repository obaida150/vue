<template>
    <div
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
            @click="$emit('month-click', month - 1)"
        >
            <h3 class="text-center font-medium mb-1 sm:mb-2 text-sm sm:text-base">{{ getMonthName(month - 1) }}</h3>
            <div class="flex w-full border-b border-gray-200 dark:border-gray-700 mb-1">
                <div class="w-[20px] sm:w-[30px] text-[8px] sm:text-[10px] font-bold text-center p-0.5">KW</div>
                <div v-for="day in weekdaysShort" :key="day" class="flex-1 text-center text-[8px] sm:text-[10px] p-0.5">{{ day }}</div>
            </div>

            <div class="flex flex-col w-full">
                <div
                    v-for="(week, weekIndex) in getWeeksInMonthForMini(month - 1, currentDate)"
                    :key="'mini-week-' + weekIndex"
                    class="flex w-full mb-[1px]"
                >
                    <div
                        class="w-[20px] sm:w-[30px] flex items-center justify-center text-[8px] sm:text-[10px] text-gray-500 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700 cursor-pointer transition-colors hover:bg-blue-50 dark:hover:bg-blue-900 hover:text-blue-600 dark:hover:text-blue-400"
                        @click.stop="$emit('week-plan', week.weekNumber, week.days)"
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
                            class="absolute inset-0 opacity-80 z-0 rounded-full bg-purple-600"
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
