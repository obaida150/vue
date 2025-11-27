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
const isAllDayLocal = ref(props.event.is_all_day ?? props.event.isAllDay ?? false);

watch(() => props.visible, (newValue) => {
    localVisible.value = newValue;
    if (newValue) {
        // When dialog opens, initialize local state from prop

        // 1. Basis-Felder setzen (außer syncWithOutlook)
        isAllDayLocal.value = props.event.is_all_day ?? false;

        console.log('[v0] Event beim Laden:', {
            id: props.event.id,
            sync_with_outlook: props.event.sync_with_outlook,
            outlook_event_id: props.event.outlook_event_id,
            is_all_day: props.event.is_all_day,
            start_time: props.event.start_time,
            end_time: props.event.end_time,
        });

        // 2. Zeiten konvertieren (BEVOR syncWithOutlook gesetzt wird!)
        if (props.event.start_time) {
            const timeParts = props.event.start_time.split(':');
            const hours = parseInt(timeParts[0]);
            const minutes = parseInt(timeParts[1]);
            const startTime = new Date();
            startTime.setHours(hours, minutes, 0, 0);
            props.event.startTime = startTime;
            console.log('[v0] Start-Zeit konvertiert:', props.event.start_time, '→', startTime);
        }

        if (props.event.end_time) {
            const timeParts = props.event.end_time.split(':');
            const hours = parseInt(timeParts[0]);
            const minutes = parseInt(timeParts[1]);
            const endTime = new Date();
            endTime.setHours(hours, minutes, 0, 0);
            props.event.endTime = endTime;
            console.log('[v0] End-Zeit konvertiert:', props.event.end_time, '→', endTime);
        }

        // 3. JETZT syncWithOutlook setzen (nachdem die Zeiten bereits konvertiert sind)
        // Der Watch auf syncWithOutlook wird die Zeiten nicht überschreiben, weil sie bereits gesetzt sind
        syncWithOutlook.value = props.event.sync_with_outlook ?? false;

        console.log('[v0] Nach Initialisierung:', {
            isAllDayLocal: isAllDayLocal.value,
            syncWithOutlook: syncWithOutlook.value,
            startTime: props.event.startTime,
            endTime: props.event.endTime,
        });
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
}, { immediate: true, deep: true });

watch(() => props.event.type, (newType) => {
    if (newType && !isEditMode.value) {
        props.event.title = newType.name;
    }
});

// NEW: Watch for changes in isAllDayLocal and update event.isAllDay, clearing times if all-day
watch(isAllDayLocal, (newValue) => {
    props.event.is_all_day = newValue; // Keep props.event.is_all_day in sync
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

watch(syncWithOutlook, (newValue) => {
    if (newValue) {
        // Ganztägig automatisch deaktivieren, damit Zeitfelder erscheinen
        isAllDayLocal.value = false;
        props.event.is_all_day = false;

        if (!props.event.startTime) {
            const defaultStart = new Date();
            defaultStart.setHours(9, 0, 0, 0);
            props.event.startTime = defaultStart;
        }
        if (!props.event.endTime) {
            const defaultEnd = new Date();
            defaultEnd.setHours(17, 0, 0, 0);
            props.event.endTime = defaultEnd;
        }
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

    if (syncWithOutlook.value && !isAllDayLocal.value) {
        if (!props.event.startTime || !props.event.endTime) {
            alert('Bitte geben Sie Start- und Endzeit für das Outlook-Ereignis an.');
            return;
        }
    }

    let startTime = null;
    let endTime = null;

    if (syncWithOutlook.value && !isAllDayLocal.value && props.event.startTime && props.event.endTime) {
        // Zeiten als HH:mm:ss formatieren
        startTime = dayjs(props.event.startTime).format('HH:mm:ss');
        endTime = dayjs(props.event.endTime).format('HH:mm:ss');
    }

    const eventToSave = {
        id: props.event.id,
        title: props.event.title,
        description: props.event.description,
        start_date: props.event.startDate ? dayjs(props.event.startDate).format('YYYY-MM-DD') : null,
        end_date: props.event.endDate ? dayjs(props.event.endDate).format('YYYY-MM-DD') : null,
        start_time: startTime, // Separate Zeit als HH:mm:ss
        end_time: endTime, // Separate Zeit als HH:mm:ss
        is_all_day: isAllDayLocal.value,
        event_type_id: props.event.type ? props.event.type.id : null,
        user_id: props.event.user_id,
        sync_with_outlook: syncWithOutlook.value,
    };

    console.log('[v0] Event to save:', eventToSave);
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
