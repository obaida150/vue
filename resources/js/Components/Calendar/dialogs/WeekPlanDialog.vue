<template>
    <Dialog
        :visible="localVisible"
        @update:visible="updateVisible"
        :style="{ width: '90vw', maxWidth: '1500px' }"
        :header="`Wochenplanung - KW ${weekNumber}`"
        :modal="true"
        :closable="true"
        class="week-plan-dialog"
    >
        <div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                <div
                    v-for="(day, index) in weekDays"
                    :key="index"
                    class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm"
                    :class="{
                    'bg-red-50 dark:bg-red-900/20': isHoliday(dayjs(day.date)),
                    'bg-purple-50 dark:bg-purple-900/20': hasVacations(day.date),
                    'bg-amber-50 dark:bg-amber-900/20': isUserAbsent(day.date)
                }"
                >
                    <div class="bg-gray-100 dark:bg-gray-800 p-3 text-center border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm sm:text-base font-medium m-0">{{ weekdays[index % 7] }}</h3>
                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">{{ formatDate(day.date) }}</div>
                        <div v-if="isHoliday(dayjs(day.date))" class="text-xs text-red-600 dark:text-red-400 font-bold mt-1">
                            {{ getHolidayName(dayjs(day.date)) }}
                        </div>
                        <!-- Urlaub-Indikator in der Wochenplanung -->
                        <div v-if="hasVacations(day.date)" class="text-xs text-purple-600 dark:text-purple-400 font-bold mt-1">
                            Urlaub
                        </div>
                        <!-- Abwesenheits-Indikator in der Wochenplanung -->
                        <div v-if="isUserAbsent(day.date)" class="text-xs text-amber-600 dark:text-amber-400 font-bold mt-1">
                            Als abwesend markiert
                        </div>
                    </div>

                    <div class="p-3">
                        <!-- Normale Bearbeitungsansicht für Tage, die nicht blockiert sind -->
                        <div v-if="!isHoliday(dayjs(day.date)) && !hasVacations(day.date) && !isBlockedForUser(day.date)">
                            <Select
                                v-model="day.selectedType"
                                :options="filteredEventTypes"
                                optionLabel="name"
                                placeholder="Status wählen"
                                class="w-full mb-3"
                                :disabled="day.toDelete"
                                @change="markDayAsEdited(index)"
                            >
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center">
                                        <div
                                            class="w-3 h-3 rounded-full mr-2"
                                            :style="{ backgroundColor: slotProps.value.color }"
                                        ></div>
                                        <div>{{ slotProps.value.name }}</div>
                                    </div>
                                    <span v-else>Status wählen</span>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center">
                                        <div
                                            class="w-3 h-3 rounded-full mr-2"
                                            :style="{ backgroundColor: slotProps.option.color }"
                                        ></div>
                                        <div>{{ slotProps.option.name }}</div>
                                    </div>
                                </template>
                            </Select>

                            <!-- Mitarbeiterauswahl für HR bei Krankheit oder Abwesenheit -->
                            <div v-if="isHr && day.selectedType && (day.selectedType.name === 'Krankheit' || day.selectedType.name === 'Abwesend')" class="mb-3">
                                <Select
                                    v-model="day.selectedEmployee"
                                    :options="employees"
                                    optionLabel="name"
                                    placeholder="Mitarbeiter auswählen"
                                    class="w-full"
                                    :disabled="day.toDelete"
                                    @change="updateSelectedUser(index)"
                                />
                            </div>

                            <InputText
                                v-model="day.notes"
                                placeholder="Notizen"
                                class="w-full"
                                :disabled="day.toDelete"
                                @input="markDayAsEdited(index)"
                            />

                            <!-- Anzeige des Ereignis-Status, falls vorhanden -->
                            <div v-if="day.eventId" class="mt-3 flex items-center justify-between">
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
                        <!-- Blockierte Ansicht für Feiertage, Urlaub oder Abwesenheit -->
                        <div v-else class="text-center py-6">
                            <i class="pi pi-ban text-gray-400 text-2xl mb-2"></i>
                            <p class="text-gray-500 text-sm">
                                <span v-if="isHoliday(dayjs(day.date))">Feiertag</span>
                                <span v-else-if="hasVacations(day.date)">Urlaub</span>
                                <span v-else-if="isUserAbsent(day.date)">Abwesend</span>
                                - Keine Einträge möglich
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Abbrechen" icon="pi pi-times" @click="onClose" class="p-button-text" />
                <Button label="Speichern" icon="pi pi-check" @click="onSave" :loading="saving" autofocus />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import dayjs from 'dayjs';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
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
    },
    events: {
        type: Array,
        default: () => []
    },
    currentUserId: {
        type: [Number, String],
        default: null
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

// Filtere die Ereignistypen basierend auf der Benutzerrolle
const filteredEventTypes = computed(() => {
    if (!props.eventTypes) return [];

    return props.eventTypes.filter(type => {
        // Wenn der Event-Typ "Krankheit" oder "Abwesend" ist und der Benutzer nicht die HR-Rolle hat,
        // dann diesen Typ nicht anzeigen
        if ((type.name === 'Krankheit' || type.name === 'Abwesend') && !props.isHr) {
            return false;
        }
        return true;
    });
});

// Prüfen, ob der Benutzer ein Ereignis löschen darf
const canDeleteEvent = (day) => {
    // Wenn es ein Krankheitseintrag oder Abwesenheitseintrag ist und der Benutzer kein HR-Mitarbeiter ist
    if (day.selectedType &&
        (day.selectedType.name === 'Krankheit' || day.selectedType.name === 'Abwesend') &&
        !props.isHr) {
        return false;
    }

    return true;
};

// Prüfen, ob der aktuelle Benutzer an einem bestimmten Tag als abwesend markiert ist
const isUserAbsent = (date) => {
    if (!date || !props.events || !props.currentUserId) return false;

    const dateStr = dayjs(date).format('YYYY-MM-DD');

    return props.events.some(event => {
        // Prüfen, ob es sich um ein Abwesenheitsereignis handelt
        const isAbsentEvent = event.type &&
            (event.type.name === 'Abwesend' ||
                (event.type.value && event.type.value.toLowerCase() === 'absent'));

        // Prüfen, ob das Ereignis dem aktuellen Benutzer gehört
        const isCurrentUserEvent = event.user_id === props.currentUserId;

        // Prüfen, ob das Ereignis am angegebenen Datum stattfindet
        const eventStartDate = dayjs(event.startDate).format('YYYY-MM-DD');
        const eventEndDate = dayjs(event.endDate).format('YYYY-MM-DD');
        const isOnDate = dateStr >= eventStartDate && dateStr <= eventEndDate;

        return isAbsentEvent && isCurrentUserEvent && isOnDate;
    });
};

// Prüfen, ob ein Tag für den aktuellen Benutzer blockiert ist
// HR-Benutzer können immer bearbeiten, normale Benutzer nicht bei Abwesenheit
const isBlockedForUser = (date) => {
    // HR-Benutzer können immer bearbeiten
    if (props.isHr) {
        return false;
    }

    // Für normale Benutzer: blockiert, wenn als abwesend markiert
    return isUserAbsent(date);
};

// Markiere einen Tag als bearbeitet
const markDayAsEdited = (index) => {
    if (index < 0 || index >= props.weekDays.length) return;
    const day = props.weekDays[index];

    // Nur als bearbeitet markieren, wenn es ein bestehendes Ereignis ist
    if (day.eventId) {
        day.isEdited = true;
    }
};

// Wenn sich der ausgewählte Mitarbeiter ändert, aktualisiere die user_id
const updateSelectedUser = (index) => {
    if (index < 0 || index >= props.weekDays.length) return;
    const day = props.weekDays[index];

    if (day.selectedEmployee) {
        day.user_id = day.selectedEmployee.id;
        markDayAsEdited(index);
    } else {
        day.user_id = null;
        markDayAsEdited(index);
    }
};

// Dialog schließen
const onClose = () => {
    updateVisible(false);
};

// Wochenplanung speichern
const onSave = () => {
    // Validierung für HR bei Krankheit oder Abwesenheit
    let valid = true;
    props.weekDays.forEach((day, index) => {
        if (props.isHr && day.selectedType &&
            (day.selectedType.name === 'Krankheit' || day.selectedType.name === 'Abwesend') &&
            !day.selectedEmployee) {
            alert(`Bitte wählen Sie einen Mitarbeiter für den ${day.selectedType.name}-Eintrag am ${props.formatDate(day.date)} aus.`);
            valid = false;
        }
    });

    if (!valid) return;

    emit('save');
};

// Ereignis entfernen
const onRemoveEvent = (index) => {
    const day = props.weekDays[index];

    // Prüfen, ob es sich um einen Krankheitseintrag oder Abwesenheitseintrag handelt
    if (day.selectedType &&
        (day.selectedType.name === 'Krankheit' || day.selectedType.name === 'Abwesend') &&
        !props.isHr) {
        alert('Krankheits- und Abwesenheitseinträge können nur von HR-Mitarbeitern gelöscht werden.');
        return;
    }

    emit('remove-event', index);
};
</script>

<style scoped>
.week-plan-dialog :deep(.p-dialog-header) {
    padding: 1.25rem;
    border-bottom: 1px solid var(--surface-d);
    background-color: #f8f9fa;
}

.week-plan-dialog :deep(.p-dialog-content) {
    padding: 1.5rem;
}

.week-plan-dialog :deep(.p-dialog-footer) {
    padding: 1.25rem;
    border-top: 1px solid var(--surface-d);
    background-color: #f8f9fa;
}

/* Verbesserte Dropdown-Darstellung */
.week-plan-dialog :deep(.p-selectbutton) {
    border-radius: 0.375rem;
}

.week-plan-dialog :deep(.p-inputtext) {
    border-radius: 0.375rem;
}

/* Verbesserte Button-Darstellung */
.week-plan-dialog :deep(.p-button) {
    border-radius: 0.375rem;
}

/* Verbesserte Badge-Darstellung */
.week-plan-dialog :deep(.p-badge) {
    border-radius: 0.25rem;
    padding: 0.25rem 0.5rem;
}
</style>
