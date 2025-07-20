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

            <div v-if="syncWithOutlook && !isAllDayLocal">
                <label class="block mb-1 sm:mb-2 font-medium">Uhrzeit</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <DatePicker
                        v-model="event.startTime"
                        dateFormat="HH:mm"
                        placeholder="Startzeit"
                        class="w-full"
                        timeOnly
                        hourFormat="24"
                        :required="syncWithOutlook && !isAllDayLocal"
                    />
                    <DatePicker
                        v-model="event.endTime"
                        dateFormat="HH:mm"
                        placeholder="Endzeit"
                        class="w-full"
                        timeOnly
                        hourFormat="24"
                        :required="syncWithOutlook && !isAllDayLocal"
                    />
                </div>
            </div>

            <div>
                <div class="flex items-center gap-2 mb-1 sm:mb-2">
                    <Checkbox v-model="isAllDayLocal" :binary="true" inputId="is-all-day" />
                    <label for="is-all-day" class="font-medium">Ganztägig</label>
                </div>
            </div>

            <!-- NEU: Outlook Synchronisierungs-Checkbox -->
            <div class="mb-3 sm:mb-4">
                <div class="flex items-center gap-2">
                    <Checkbox v-model="syncWithOutlook" :binary="true" inputId="sync-outlook" />
                    <label for="sync-outlook" class="font-medium">Mit Outlook synchronisieren</label>
                </div>
                <small class="text-gray-500 dark:text-gray-400">
                    Ereignisse werden mit Ihrem persönlichen Outlook-Kalender synchronisiert.
                </small>
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
import { route } from 'ziggy-js';
import dayjs from 'dayjs';

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
    },
    isTeamManager: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:visible', 'update:modelValue', 'close', 'save']);

const localVisible = ref(props.visible);
const syncWithOutlook = ref(false);
const selectedEmployee = ref(null);

// NEW: Local ref for isAllDay to ensure robust reactivity
const isAllDayLocal = ref(props.event.isAllDay);

watch(() => props.visible, (newValue) => {
    localVisible.value = newValue;
    if (newValue) {
        // When dialog opens, initialize local state from prop
        isAllDayLocal.value = props.event.isAllDay;
        syncWithOutlook.value = props.event.sync_with_outlook ?? false; // Ensure syncWithOutlook is initialized
    }
});

const updateVisible = (value) => {
    localVisible.value = value;
    emit('update:visible', value);
    if (!value) {
        emit('close');
    }
};

const isEditMode = computed(() => {
    return props.event && props.event.id;
});

watch(selectedEmployee, (newValue) => {
    if (newValue) {
        props.event.user_id = newValue.id;
        console.log('Mitarbeiter ausgewählt:', newValue.name, 'ID:', newValue.id);
    } else {
        props.event.user_id = null;
    }
}, { immediate: true });

watch(() => props.event, (newEvent) => {
    if (newEvent.user_id) {
        selectedEmployee.value = props.employees.find(emp => emp.id === newEvent.user_id) || null;
    } else {
        selectedEmployee.value = null;
    }

    // Initialisiere startTime und endTime, wenn sie nicht vorhanden sind
    if (!newEvent.startTime) newEvent.startTime = null;
    if (!newEvent.endTime) newEvent.endTime = null;

    // Wenn ein bestehendes Event geladen wird, das Zeiten hat, diese in startTime/endTime setzen
    if (newEvent.start_date && !newEvent.isAllDay && !newEvent.startTime) {
        props.event.startTime = dayjs(newEvent.start_date).toDate();
    }
    if (newEvent.end_date && !newEvent.isAllDay && !newEvent.endTime) {
        props.event.endTime = dayjs(newEvent.end_date).toDate();
    }

}, { immediate: true, deep: true });

watch(() => props.event.type, (newType) => {
    if (newType && !isEditMode.value) {
        props.event.title = newType.name;
    }
});

// NEW: Watch for changes in isAllDayLocal and update event.isAllDay, clearing times if all-day
watch(isAllDayLocal, (newValue) => {
    props.event.isAllDay = newValue; // Keep props.event.isAllDay in sync
    if (newValue) {
        props.event.startTime = null;
        props.event.endTime = null;
    }
});

// NEW: Watch for changes in startTime/endTime and update isAllDayLocal
watch(() => [props.event.startTime, props.event.endTime], ([newStartTime, newEndTime]) => {
    if ((newStartTime !== null || newEndTime !== null) && isAllDayLocal.value) {
        isAllDayLocal.value = false;
    }
});

const onClose = () => {
    updateVisible(false);
};

const onSave = () => {
    if (props.isHr && props.event.type && props.event.type.name === 'Krankheit' && !selectedEmployee.value) {
        alert('Bitte wählen Sie einen Mitarbeiter aus.');
        return;
    }

    if (syncWithOutlook.value && !isAllDayLocal.value) { // Use isAllDayLocal here
        if (!props.event.startTime || !props.event.endTime) {
            alert('Bitte geben Sie Start- und Endzeit für das Outlook-Ereignis an.');
            return;
        }
    }

    let finalStartDate = props.event.startDate;
    let finalEndDate = props.event.endDate;

    // Wenn mit Outlook synchronisiert wird und es KEIN ganztägiges Ereignis ist,
    // kombiniere Datum und Zeit aus den separaten Feldern.
    // Für ganztägige Outlook-Events werden die Zeiten im Backend gesetzt.
    if (syncWithOutlook.value && !isAllDayLocal.value) { // Use isAllDayLocal here
        if (props.event.startTime) {
            const time = dayjs(props.event.startTime);
            finalStartDate = dayjs(props.event.startDate)
                .set('hour', time.hour())
                .set('minute', time.minute())
                .set('second', time.second())
                .toDate();
        }
        if (props.event.endTime) {
            const time = dayjs(props.event.endTime);
            finalEndDate = dayjs(props.event.endDate)
                .set('hour', time.hour())
                .set('minute', time.minute())
                .set('second', time.second())
                .toDate();
        }
    }

    const eventToSave = {
        id: props.event.id, // Wichtig für Updates
        title: props.event.title,
        description: props.event.description,
        start_date: finalStartDate ? dayjs(finalStartDate).format('YYYY-MM-DD HH:mm:ss') : null,
        end_date: finalEndDate ? dayjs(finalEndDate).format('YYYY-MM-DD HH:mm:ss') : null,
        is_all_day: isAllDayLocal.value, // Use isAllDayLocal here
        event_type_id: props.event.type ? props.event.type.id : null,
        user_id: props.event.user_id,
        sync_with_outlook: syncWithOutlook.value,
    };
    emit('save', eventToSave);
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
