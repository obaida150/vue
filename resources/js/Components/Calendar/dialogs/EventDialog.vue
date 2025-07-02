<template>
    <Dialog
        :visible="localVisible"
        @update:visible="updateVisible"
        :style="{ width: '90vw', maxWidth: '500px' }"
        :header="isEditMode ? 'Ereignis bearbeiten' : 'Ereignis hinzufügen'"
        :modal="true"
        :closable="true"
        class="event-dialog"
    >
        <div class="flex flex-col gap-3 sm:gap-4">
            <div>
                <label for="event-type" class="block mb-1 sm:mb-2 font-medium">Ereignistyp</label>
                <Select
                    id="event-type"
                    v-model="event.type"
                    :options="eventTypes"
                    optionLabel="name"
                    placeholder="Typ auswählen"
                    class="w-full"
                    required
                />
            </div>

            <div>
                <label for="event-title" class="block mb-1 sm:mb-2 font-medium">Titel</label>
                <InputText id="event-title" v-model="event.title" class="w-full" required autofocus />
            </div>


            <!-- Mitarbeiterauswahl für HR bei Krankheit -->
            <div v-if="isHr && event.type && event.type.name === 'Krankheit'" class="mb-3 sm:mb-4">
                <label for="employee" class="block mb-1 sm:mb-2 font-medium">Mitarbeiter</label>
                <Select
                    id="employee"
                    v-model="selectedEmployee"
                    :options="employees"
                    optionLabel="name"
                    placeholder="Mitarbeiter auswählen"
                    class="w-full"
                    required
                />
            </div>

            <div>
                <label class="block mb-1 sm:mb-2 font-medium">Zeitraum</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <DatePicker
                        v-model="event.startDate"
                        dateFormat="dd.mm.yy"
                        placeholder="Startdatum"
                        class="w-full"
                        :disabledDates="disabledDates"
                        :locale="locale"
                        required
                    />
                    <DatePicker
                        v-model="event.endDate"
                        dateFormat="dd.mm.yy"
                        placeholder="Enddatum"
                        class="w-full"
                        :disabledDates="disabledDates"
                        :locale="locale"
                        required
                    />
                </div>
            </div>

            <div>
                <div class="flex items-center gap-2 mb-1 sm:mb-2">
                    <Checkbox v-model="event.isAllDay" :binary="true" inputId="is-all-day" />
                    <label for="is-all-day" class="font-medium">Ganztägig</label>
                </div>
            </div>

            <div>
                <label for="event-description" class="block mb-1 sm:mb-2 font-medium">Beschreibung</label>
                <Textarea
                    id="event-description"
                    v-model="event.description"
                    rows="3"
                    class="w-full"
                    autoResize
                />
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button
                    label="Abbrechen"
                    icon="pi pi-times"
                    @click="onClose"
                    class="p-button-text p-button-sm sm:p-button-md"
                />
                <Button
                    label="Speichern"
                    icon="pi pi-check"
                    @click="onSave"
                    :loading="saving"
                    autofocus
                    class="p-button-sm sm:p-button-md"
                />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import DatePicker from 'primevue/datepicker';

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
        required: true
    },
    eventTypes: {
        type: Array,
        required: true
    },
    disabledDates: {
        type: Array,
        default: () => []
    },
    locale: {
        type: Object,
        required: true
    },
    saving: {
        type: Boolean,
        default: false
    },
    isHr: {
        type: Boolean,
        default: false
    },
    employees: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:visible', 'update:modelValue', 'close', 'save']);

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

// Ausgewählter Mitarbeiter
const selectedEmployee = ref(null);

// Prüfen, ob wir im Bearbeitungsmodus sind
const isEditMode = computed(() => {
    return props.event && props.event.id;
});

// Wenn sich der ausgewählte Mitarbeiter ändert, aktualisiere die user_id im Event
watch(selectedEmployee, (newValue) => {
    if (newValue) {
        props.event.user_id = newValue.id;
        console.log('Mitarbeiter ausgewählt:', newValue.name, 'ID:', newValue.id);
    } else {
        props.event.user_id = null;
    }
}, { immediate: true });

// Wenn sich das Event ändert, aktualisiere den ausgewählten Mitarbeiter
watch(() => props.event, (newEvent) => {
    if (newEvent.user_id) {
        selectedEmployee.value = props.employees.find(emp => emp.id === newEvent.user_id) || null;
    } else {
        selectedEmployee.value = null;
    }
}, { immediate: true, deep: true });

// NEU: Titel automatisch setzen, wenn Ereignistyp ausgewählt wird und es ein neues Ereignis ist
watch(() => props.event.type, (newType) => {
    if (newType && !isEditMode.value) {
        props.event.title = newType.name;
    }
});

// Dialog schließen
const onClose = () => {
    updateVisible(false);
};

// Event speichern
const onSave = () => {
    // Validierung für HR bei Krankheit
    if (props.isHr && props.event.type && props.event.type.name === 'Krankheit' && !selectedEmployee.value) {
        alert('Bitte wählen Sie einen Mitarbeiter aus.');
        return;
    }

    emit('save');
};
</script>

<style scoped>
.event-dialog .p-dialog-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--surface-d);
}

.event-dialog .p-dialog-content {
    padding: 1.5rem;
}

.event-dialog .p-dialog-footer {
    padding: 1.5rem;
    border-top: 1px solid var(--surface-d);
}
</style>
