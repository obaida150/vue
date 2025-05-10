<template>
    <Dialog
        :visible="visible"
        @update:visible="$emit('update:visible', $event)"
        :style="{ width: '90vw', maxWidth: '500px' }"
        :header="vacation ? (vacation.title || 'Urlaub') : 'Urlaubsdetails'"
        :modal="true"
        class="vacation-details-dialog"
    >
        <div v-if="vacation" class="flex flex-col gap-4">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded-full bg-purple-600"></div>
                <span class="font-medium">Urlaub</span>
                <Badge
                    :value="getStatusLabel(vacation.status)"
                    :severity="getStatusSeverity(vacation.status)"
                    class="ml-auto"
                />
            </div>

            <div class="border-t border-b border-gray-200 dark:border-gray-700 py-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Startdatum</div>
                        <div>{{ formatDate(vacation.startDate) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Enddatum</div>
                        <div>{{ formatDate(vacation.endDate) }}</div>
                    </div>
                </div>
            </div>

            <div v-if="vacation.description">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Beschreibung</div>
                <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">{{ vacation.description }}</div>
            </div>

            <div class="bg-purple-50 p-3 rounded-lg">
                <p class="text-sm text-purple-800">
                    Dieser Eintrag stammt aus der Urlaubsverwaltung und kann nur dort bearbeitet werden.
                </p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end w-full">
                <div class="flex gap-2">
                    <Button
                        label="Zur Urlaubsverwaltung"
                        icon="pi pi-external-link"
                        @click="$emit('navigate')"
                        class="p-button-sm sm:p-button-md"
                    />
                    <Button
                        label="Schließen"
                        icon="pi pi-times"
                        @click="$emit('close')"
                        class="p-button-text p-button-sm p-button-md"
                    />
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Badge from 'primevue/badge';

const props = defineProps({
    visible: {
        type: Boolean,
        required: true
    },
    vacation: {
        type: Object,
        default: null
    },
    getStatusLabel: {
        type: Function,
        required: true
    },
    getStatusSeverity: {
        type: Function,
        required: true
    },
    formatDate: {
        type: Function,
        required: true
    }
});

defineEmits(['update:visible', 'close', 'navigate']);
</script>

<style scoped>
.vacation-details-dialog .p-dialog-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--surface-d);
}

.vacation-details-dialog .p-dialog-content {
    padding: 1.5rem;
}

.vacation-details-dialog .p-dialog-footer {
    padding: 1.5rem;
    border-top: 1px solid var(--surface-d);
}
</style>
