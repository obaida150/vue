<template>
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-2 sm:gap-4">
    <div class="flex items-center gap-2 sm:gap-4 w-full sm:w-auto">
      <Button icon="pi pi-chevron-left" @click="$emit('previous')" class="p-button-sm sm:p-button-md" />
      <h2 class="text-lg sm:text-xl font-semibold capitalize m-0">
        <span v-if="calendarView === 'month'">{{ currentDate.format('MMMM YYYY') }}</span>
        <span v-else>{{ currentDate.year() }}</span>
      </h2>
      <Button icon="pi pi-chevron-right" @click="$emit('next')" class="p-button-sm sm:p-button-md" />
    </div>
    <div class="flex items-center gap-2 sm:gap-4 flex-wrap w-full sm:w-auto justify-start sm:justify-end mt-2 sm:mt-0">
      <!-- Buttons für die Ansichtsumschaltung -->
      <div class="flex gap-1">
        <Button
          :class="{ 'p-button-primary': calendarView === 'month', 'p-button-outlined': calendarView !== 'month' }"
          label="Monat"
          @click="$emit('set-view', 'month')"
          class="p-button-sm sm:p-button-md"
        />
        <Button
          :class="{ 'p-button-primary': calendarView === 'year', 'p-button-outlined': calendarView !== 'year' }"
          label="Jahr"
          @click="$emit('set-view', 'year')"
          class="p-button-sm sm:p-button-md"
        />
      </div>

      <!-- Buttons für das Jahres-Layout -->
      <div v-if="calendarView === 'year'" class="flex gap-1">
        <Button
          :class="{ 'p-button-primary': yearLayout === '6x2', 'p-button-outlined': yearLayout !== '6x2' }"
          label="6×2"
          @click="$emit('set-layout', '6x2')"
          class="p-button-sm sm:p-button-md"
        />
        <Button
          :class="{ 'p-button-primary': yearLayout === '4x3', 'p-button-outlined': yearLayout !== '4x3' }"
          label="4×3"
          @click="$emit('set-layout', '4x3')"
          class="p-button-sm sm:p-button-md"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';
import Button from 'primevue/button';

const props = defineProps({
  calendarView: {
    type: String,
    required: true
  },
  currentDate: {
    type: Object,
    required: true
  },
  yearLayout: {
    type: String,
    required: true
  }
});

defineEmits(['previous', 'next', 'set-view', 'set-layout']);
</script>