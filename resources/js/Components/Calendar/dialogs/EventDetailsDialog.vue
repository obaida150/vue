<template>
    <Dialog
        :visible="visible"
        @update:visible="$emit('update:visible', $event)"
        :style="{ width: '90vw', maxWidth: '500px' }"
        :header="event ? event.title : 'Ereignisdetails'"
        :modal="true"
        class="event-details-dialog"
    >
        <div v-if="event" class="flex flex-col gap-4">
            <div class="flex items-center gap-2">
                <div
                    class="w-4 h-4 rounded-full"
                    :style="{ backgroundColor: event.color }"
                ></div>
                <span class="font-medium">{{ event.type ? event.type.name : 'Ereignis' }}</span>
                <Badge
                    :value="getStatusLabel(event.status)"
                    :severity="getStatusSeverity(event.status)"
                    class="ml-auto"
                />
            </div>

            <div class="border-t border-b border-gray-200 dark:border-gray-700 py-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Startdatum</div>
                        <div>{{ formatDate(event.startDate) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Enddatum</div>
                        <div>{{ formatDate(event.endDate) }}</div>
                    </div>
                </div>
            </div>

            <div v-if="event.description">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Beschreibung</div>
                <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">{{ event.description }}</div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between w-full">
                <Button
                    label="Löschen"
                    icon="pi pi-trash"
                    @click="$emit('delete')"
                    class="p-button-danger p-button-sm sm:p-button-md"
                />
                <div class="flex gap-2">
                    <Button
                        label="Bearbeiten"
                        icon="pi pi-pencil"
                        @click="$emit('edit')"
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
    event: {
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

defineEmits(['update:visible', 'close', 'edit', 'delete']);
</script>

<style scoped>
.event-details-dialog .p-dialog-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--surface-d);
}

.event-details-dialog .p-dialog-content {
    padding: 1.5rem;
}

.event-details-dialog .p-dialog-footer {
    padding: 1.5rem;
    border-top: 1px solid var(--surface-d);
}
</style>
