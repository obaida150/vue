<template>
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
</template>

<script setup>
import { defineProps } from 'vue';

const props = defineProps({
  eventTypes: {
    type: Array,
    required: true
  }
});
</script>