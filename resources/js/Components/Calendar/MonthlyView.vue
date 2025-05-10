<template>
  <div class="w-full overflow-x-auto">
    <div class="min-w-[700px]">
      <div class="flex w-full border-b border-gray-200 dark:border-gray-700 mb-2">
        <div class="w-[50px] min-w-[50px] font-bold text-center py-2">KW</div>
        <div v-for="day in weekdays" :key="day" class="flex-1 text-center font-bold py-2 min-w-[80px]">{{ day }}</div>
      </div>

      <div class="flex flex-col w-full">
        <div v-for="(week, weekIndex) in weeks" :key="'week-' + weekIndex" class="flex w-full mb-[2px]">
          <div
            class="w-[50px] min-w-[50px] flex items-center justify-center font-bold text-gray-500 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700 cursor-pointer transition-colors hover:bg-blue-50 dark:hover:bg-blue-900 hover:text-blue-600 dark:hover:text-blue-400"
            @click="$emit('week-plan', week.weekNumber, week.days)"
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
            @click="$emit('day-click', day.date)"
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
                @click.stop="$emit('event-click', event)"
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
                @click.stop="$emit('vacation-click', vacation)"
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
</template>

<script setup>
import { defineProps, defineEmits, computed } from 'vue';
import dayjs from 'dayjs';

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
    required: true
  },
  events: {
    type: Array,
    required: true
  },
  vacations: {
    type: Array,
    required: true
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
  }
});

defineEmits(['week-plan', 'day-click', 'event-click', 'vacation-click']);

// Alle Wochen im aktuellen Monat generieren
const weeks = computed(() => {
  const startOfMonth = props.currentDate.startOf('month');
  const endOfMonth = props.currentDate.endOf('month');

  let currentWeekStart = dayjs(startOfMonth).day(1);
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
        currentMonth: date.isSame(props.currentDate, 'month'),
        isToday: date.isToday(),
        isWeekend: date.day() === 0 || date.day() === 6
      });
    }
    weeks.push({ weekNumber, days });
    currentWeekStart = currentWeekStart.add(1, 'week');
  }
  return weeks;
});
</script>