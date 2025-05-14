<template>
    <Dialog
        :visible="localVisible"
        @update:visible="updateVisible"
        :style="{ width: '90vw', maxWidth: '500px' }"
        :header="event ? (event.title || 'Ereignisdetails') : 'Ereignisdetails'"
        :modal="true"
        :closable="true"
        class="event-details-dialog"
    >
        <div v-if="event" class="flex flex-col gap-4">
            <div class="flex items-center gap-2">
                <div
                    class="w-4 h-4 rounded-full"
                    :style="{ backgroundColor: event.color || (event.type ? event.type.color : '#607D8B') }"
                ></div>
                <span class="font-medium">{{ event.type ? event.type.name : '' }}</span>
                <Badge
                    v-if="event.status"
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

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Ganztägig</div>
                    <div>{{ event.isAllDay ? 'Ja' : 'Nein' }}</div>
                </div>
                <div v-if="event.employee_name">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Mitarbeiter</div>
                    <div>{{ event.employee_name }}</div>
                </div>
            </div>

            <div v-if="event.description">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Beschreibung</div>
                <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">{{ event.description }}</div>
            </div>

            <div v-if="event.source === 'vacation'" class="bg-purple-50 p-3 rounded-lg">
                <p class="text-sm text-purple-800">
                    Dieser Eintrag stammt aus der Urlaubsverwaltung und kann nur dort bearbeitet werden.
                </p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end w-full">
                <div class="flex gap-2">
                    <Button
                        v-if="event && event.source !== 'vacation' && canDeleteEvent"
                        label="Löschen"
                        icon="pi pi-trash"
                        class="p-button-danger p-button-sm sm:p-button-md"
                        @click="onDelete"
                    />
                    <Button
                        v-if="event && event.source !== 'vacation' && (isHrUser || !isKrankheitEvent)"
                        label="Bearbeiten"
                        icon="pi pi-pencil"
                        class="p-button-primary p-button-sm sm:p-button-md"
                        @click="onEdit"
                    />
                    <Button
                        label="Schließen"
                        icon="pi pi-times"
                        class="p-button-text p-button-sm sm:p-button-md"
                        @click="onClose"
                    />
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Badge from 'primevue/badge';

const props = defineProps({
    visible: {
        type: Boolean,
        required: true
    },
    modelValue: {
        type: Boolean,
        default: false
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
    },
    isHr: {
        type: Boolean,
        default: false
    },
    isHrUser: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:visible', 'update:modelValue', 'close', 'edit', 'delete']);

// Lokale Variable für die Sichtbarkeit
const localVisible = ref(props.visible);

// Aktualisiere die lokale Variable, wenn sich die Prop ändert
watch(() => props.visible, (newValue) => {
    localVisible.value = newValue;
});

// Aktualisiere die Prop, wenn sich die lokale Variable ändert
const updateVisible = (value) => {
    localVisible.value = value;
    emit('update:visible', value);
    if (!value) {
        emit('close');
    }
};

const canDeleteEvent = computed(() => {
    if (!props.event) return false;

    // Wenn es ein Krankheitseintrag ist und der Benutzer kein HR-Mitarbeiter ist
    if (props.event.type &&
        props.event.type.name === 'Krankheit' &&
        !props.isHr) {
        return false;
    }

    return true;
});

// Computed property, um zu prüfen, ob es sich um einen Krankheitseintrag handelt
const isKrankheitEvent = computed(() => {
    if (!props.event || !props.event.type) return false;
    return props.event.type.name === 'Krankheit';
});

const onClose = () => {
    updateVisible(false);
};

const onEdit = () => {
    emit('edit');
};

const onDelete = () => {
    emit('delete');
};
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
