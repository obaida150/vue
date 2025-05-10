<template>
    <Dialog
        :visible="visible"
        @update:visible="$emit('update:visible', $event)"
        :style="{ width: '60vw' }"
        :header="`Wochenplanung - KW ${weekNumber}`"
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
                            {{ getHolidayName ? getHolidayName(dayjs(day.date)) : 'Feiertag' }}
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
                                @click="$emit('remove-event', index)"
                                title="Ereignis löschen"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Abbrechen" icon="pi pi-times" @click="$emit('close')" class="p-button-text p-button-sm sm:p-button-md" />
                <Button label="Speichern" icon="pi pi-check" @click="$emit('save')" :loading="saving" autofocus class="p-button-sm sm:p-button-md" />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';
import dayjs from 'dayjs';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Badge from 'primevue/badge';

const props = defineProps({
    visible: {
        type: Boolean,
        required: true
    },
    weekNumber: {
        type: Number,
        default: null
    },
    weekDays: {
        type: Array,
        default: () => []
    },
    weekdays: {
        type: Array,
        required: true
    },
    eventTypes: {
        type: Array,
        required: true
    },
    saving: {
        type: Boolean,
        required: true
    },
    isHoliday: {
        type: Function,
        required: true
    },
    getHolidayName: {
        type: Function,
        default: () => 'Feiertag'
    },
    hasVacations: {
        type: Function,
        required: true
    },
    formatDate: {
        type: Function,
        required: true
    }
});

defineEmits(['update:visible', 'close', 'save', 'remove-event']);
</script>

<style scoped>
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
