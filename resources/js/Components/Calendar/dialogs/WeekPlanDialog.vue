<template>
    <Dialog
        :visible="localVisible"
        @update:visible="updateVisible"
        :style="{ width: '60vw' }"
        :header="`Wochenplanung - KW ${weekNumber}`"
        :modal="true"
        :closable="true"
        class="week-plan-dialog"
    >
        <div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-2 sm:gap-4">
                <div
                    v-for="(day, index) in weekDays"
                    :key="index"
                    class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                    :class="{
                        'bg-red-50 dark:bg-red-900/20': isHoliday(dayjs(day.date)),
                        'bg-purple-50 dark:bg-purple-900/20': hasVacations(day.date)
                    }"
                >
                    <div class="bg-gray-100 dark:bg-gray-800 p-2 text-center">
                        <h3 class="text-sm sm:text-base font-medium m-0">{{ weekdays[index % 7] }}</h3>
                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">{{ formatDate(day.date) }}</div>
                        <div v-if="isHoliday(dayjs(day.date))" class="text-xs text-red-600 dark:text-red-400 font-bold">
                            {{ getHolidayName(dayjs(day.date)) }}
                        </div>
                        <!-- Urlaub-Indikator in der Wochenplanung -->
                        <div v-if="hasVacations(day.date)" class="text-xs text-purple-600 dark:text-purple-400 font-bold mt-1">
                            Urlaub
                        </div>
                    </div>

                    <div class="p-2">
                        <div v-if="!isHoliday(dayjs(day.date)) && !hasVacations(day.date)">
                            <Select
                                v-model="day.selectedType"
                                :options="eventTypes"
                                optionLabel="name"
                                placeholder="Status wählen"
                                class="w-full mb-2"
                                :disabled="day.toDelete"
                            />

                            <!-- Mitarbeiterauswahl für HR bei Krankheit -->
                            <div v-if="isHr && day.selectedType && day.selectedType.name === 'Krankheit'" class="mb-2">
                                <Select
                                    v-model="day.selectedEmployee"
                                    :options="employees"
                                    optionLabel="name"
                                    placeholder="Mitarbeiter auswählen"
                                    class="w-full"
                                    :disabled="day.toDelete"
                                    @change="updateUserId(index)"
                                />
                            </div>

                            <InputText
                                v-model="day.notes"
                                placeholder="Notizen"
                                class="w-full"
                                :disabled="day.toDelete"
                            />

                            <!-- Anzeige des Ereignis-Status, falls vorhanden -->
                            <div v-if="day.eventId" class="mt-2 flex items-center justify-between">
                                <Badge
                                    :value="day.isEdited ? 'Bearbeitet' : 'Vorhanden'"
                                    :severity="day.isEdited ? 'warning' : 'info'"
                                    class="text-xs"
                                />
                                <Button
                                    v-if="day.eventId && canDeleteEvent(day)"
                                    icon="pi pi-trash"
                                    class="p-button-text p-button-danger p-button-sm"
                                    @click="onRemoveEvent(index)"
                                    title="Ereignis löschen"
                                />
                            </div>
                        </div>
                        <div v-else class="text-center p-4">
                            <i class="pi pi-ban text-gray-400 text-2xl mb-2"></i>
                            <p class="text-gray-500 text-sm">
                                {{ isHoliday(dayjs(day.date)) ? 'Feiertag' : 'Urlaub' }} - Keine Einträge möglich
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Abbrechen" icon="pi pi-times" @click="onClose" class="p-button-text p-button-sm sm:p-button-md" />
                <Button label="Speichern" icon="pi pi-check" @click="onSave" :loading="saving" autofocus class="p-button-sm sm:p-button-md" />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
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
    modelValue: {
        type: Boolean,
        default: false
    },
    weekNumber: {
        type: Number,
        default: 0
    },
    weekDays: {
        type: Array,
        required: true
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
        default: false
    },
    isHoliday: {
        type: Function,
        required: true
    },
    getHolidayName: {
        type: Function,
        required: true
    },
    hasVacations: {
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
    employees: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:visible', 'update:modelValue', 'close', 'save', 'remove-event']);

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

// Prüfen, ob der Benutzer ein Ereignis löschen darf
const canDeleteEvent = (day) => {
    // Wenn es ein Krankheitseintrag ist und der Benutzer kein HR-Mitarbeiter ist
    if (day.selectedType &&
        day.selectedType.name === 'Krankheit' &&
        !props.isHr) {
        return false;
    }

    return true;
};

// Initialisiere selectedEmployee für jeden Tag
onMounted(() => {
    props.weekDays.forEach((day, index) => {
        if (day.user_id) {
            day.selectedEmployee = props.employees.find(emp => emp.id === day.user_id) || null;
        } else {
            day.selectedEmployee = null;
        }
    });
});

// Wenn sich der Typ ändert, prüfe ob es Krankheit ist und setze ggf. den Mitarbeiter zurück
watch(() => props.weekDays, (newDays) => {
    newDays.forEach((day, index) => {
        if (day.selectedType && day.selectedType.name === 'Krankheit' && !day.selectedEmployee && props.isHr) {
            // Hier könnten wir einen Standardmitarbeiter setzen, falls nötig
        }
    });
}, { deep: true });

// Aktualisiere die user_id, wenn sich der ausgewählte Mitarbeiter ändert
const updateUserId = (index) => {
    const day = props.weekDays[index];
    if (day.selectedEmployee) {
        day.user_id = day.selectedEmployee.id;
    } else {
        day.user_id = null;
    }
};

// Dialog schließen
const onClose = () => {
    updateVisible(false);
};

// Wochenplanung speichern
const onSave = () => {
    // Validierung für HR bei Krankheit
    let valid = true;
    props.weekDays.forEach((day, index) => {
        if (props.isHr && day.selectedType && day.selectedType.name === 'Krankheit' && !day.selectedEmployee) {
            alert(`Bitte wählen Sie einen Mitarbeiter für den Krankheitseintrag am ${props.formatDate(day.date)} aus.`);
            valid = false;
        }
    });

    if (!valid) return;

    emit('save');
};

// Ereignis entfernen
const onRemoveEvent = (index) => {
    const day = props.weekDays[index];

    // Prüfen, ob es sich um einen Krankheitseintrag handelt und der Benutzer kein HR-Mitarbeiter ist
    if (day.selectedType &&
        day.selectedType.name === 'Krankheit' &&
        !props.isHr) {
        alert('Krankheitseinträge können nur von HR-Mitarbeitern gelöscht werden.');
        return;
    }

    emit('remove-event', index);
};
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
