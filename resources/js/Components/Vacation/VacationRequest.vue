<template>
    <div class="vacation-request-container">
        <div class="p-fluid">
            <!-- Zusammenfassung des Urlaubskontingents -->
            <div class="vacation-summary p-4 mb-6 border-round bg-gray-50 dark:bg-gray-800 shadow-sm">
                <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">Urlaubskontingent</h3>
                <div class="grid">
                    <div class="col-12 md:col-4">
                        <div class="flex flex-column align-items-center">
                            <div class="text-4xl font-bold text-primary mb-2">{{ totalVacationDays }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Gesamtes Kontingent</div>
                        </div>
                    </div>
                    <div class="col-12 md:col-4">
                        <div class="flex flex-column align-items-center">
                            <div class="text-4xl font-bold text-orange-500 mb-2">{{ usedVacationDays }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Bereits genommen</div>
                        </div>
                    </div>
                    <div class="col-12 md:col-4">
                        <div class="flex flex-column align-items-center">
                            <div class="text-4xl font-bold text-green-500 mb-2">{{ remainingVacationDays }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Verbleibend</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <ProgressBar :value="usagePercentage" :showValue="false" class="mb-2" />
                    <div class="flex justify-content-between text-sm text-gray-600 dark:text-gray-400">
                        <span>{{ usagePercentage }}% verbraucht</span>
                        <span>{{ 100 - usagePercentage }}% verfügbar</span>
                    </div>
                </div>
            </div>

            <!-- Formular für Urlaubsantrag -->
            <form @submit.prevent="submitVacationRequest">
                <div class="card p-4 mb-4 shadow-sm border-round bg-white dark:bg-gray-900">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">Urlaubszeiträume</h3>

                    <div v-for="(period, index) in vacationPeriods" :key="index" class="vacation-period p-3 mb-3 border-round bg-gray-50 dark:bg-gray-800">
                        <div class="flex align-items-center justify-content-between mb-2">
                            <h4 class="text-md font-medium m-0">Zeitraum {{ index + 1 }}</h4>
                            <Button
                                v-if="vacationPeriods.length > 1"
                                icon="pi pi-trash"
                                class="p-button-rounded p-button-danger p-button-text p-button-sm"
                                @click="removePeriod(index)"
                                type="button"
                            />
                        </div>

                        <div class="grid">
                            <div class="col-12 md:col-5 field mb-3">
                                <label :for="'startDate-' + index" class="block text-sm font-medium mb-1">Startdatum</label>
                                <Calendar
                                    :id="'startDate-' + index"
                                    v-model="period.startDate"
                                    dateFormat="dd.mm.yy"
                                    :minDate="new Date()"
                                    :disabledDates="disabledDates"
                                    class="w-full"
                                />
                                <small v-if="period.errors.startDate" class="p-error">{{ period.errors.startDate }}</small>
                            </div>

                            <div class="col-12 md:col-5 field mb-3">
                                <label :for="'endDate-' + index" class="block text-sm font-medium mb-1">Enddatum</label>
                                <Calendar
                                    :id="'endDate-' + index"
                                    v-model="period.endDate"
                                    dateFormat="dd.mm.yy"
                                    :minDate="period.startDate || new Date()"
                                    :disabledDates="disabledDates"
                                    class="w-full"
                                />
                                <small v-if="period.errors.endDate" class="p-error">{{ period.errors.endDate }}</small>
                            </div>

                            <div class="col-12 md:col-2 field mb-3">
                                <label :for="'days-' + index" class="block text-sm font-medium mb-1">Tage</label>
                                <div class="p-inputgroup">
                                    <InputNumber
                                        :id="'days-' + index"
                                        v-model="period.days"
                                        :min="1"
                                        :max="remainingVacationDays"
                                        disabled
                                        class="w-full"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-content-center mt-3 mb-3">
                        <Button
                            type="button"
                            label="Weiteren Zeitraum hinzufügen"
                            icon="pi pi-plus"
                            class="p-button-outlined p-button-secondary"
                            @click="addPeriod"
                            :disabled="!canAddMorePeriods"
                        />
                    </div>
                </div>

                <div class="card p-4 mb-4 shadow-sm border-round bg-white dark:bg-gray-900">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">Weitere Informationen</h3>

                    <div class="field mb-4">
                        <label for="substitute" class="block text-sm font-medium mb-1">Vertretung</label>
                        <Dropdown
                            id="substitute"
                            v-model="vacationRequest.substitute"
                            :options="availableSubstitutes"
                            optionLabel="name"
                            placeholder="Vertretung auswählen"
                            class="w-full"
                        />
                        <small class="text-gray-500 dark:text-gray-400 text-xs">Optional: Wenn keine Vertretung ausgewählt wird, geht die Anfrage nur an den Abteilungsleiter.</small>
                    </div>

                    <div class="field mb-4">
                        <label for="notes" class="block text-sm font-medium mb-1">Anmerkungen</label>
                        <Textarea
                            id="notes"
                            v-model="vacationRequest.notes"
                            rows="3"
                            autoResize
                            class="w-full"
                            placeholder="Zusätzliche Informationen zu Ihrem Urlaubsantrag..."
                        />
                    </div>
                </div>

                <div class="card p-4 mb-4 shadow-sm border-round bg-white dark:bg-gray-900">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">Zusammenfassung</h3>

                    <div class="summary-table mb-4">
                        <div class="grid summary-header font-medium text-sm bg-gray-100 dark:bg-gray-700 p-2">
                            <div class="col-6">Zeitraum</div>
                            <div class="col-3">Tage</div>
                            <div class="col-3">Status</div>
                        </div>

                        <div v-for="(period, index) in vacationPeriods" :key="index" class="grid summary-row p-2 border-bottom-1 border-gray-200 dark:border-gray-700">
                            <div class="col-6">
                                {{ formatDate(period.startDate) }} - {{ formatDate(period.endDate) }}
                            </div>
                            <div class="col-3">{{ period.days }}</div>
                            <div class="col-3">
                                <Tag value="Neu" severity="info" />
                            </div>
                        </div>

                        <div class="grid summary-footer font-medium p-2 bg-gray-50 dark:bg-gray-800">
                            <div class="col-6">Gesamt</div>
                            <div class="col-3">{{ totalRequestedDays }}</div>
                            <div class="col-3"></div>
                        </div>
                    </div>

                    <div v-if="totalRequestedDays > remainingVacationDays" class="p-message p-message-error p-3 mb-4">
                        <i class="pi pi-exclamation-triangle mr-2"></i>
                        <span>Sie haben nicht genügend Urlaubstage übrig. Sie benötigen {{ totalRequestedDays }} Tage, aber haben nur {{ remainingVacationDays }} verfügbar.</span>
                    </div>
                </div>

                <div class="flex justify-content-end">
                    <Button type="button" label="Abbrechen" icon="pi pi-times" class="p-button-outlined p-button-secondary mr-2" @click="$emit('cancel')" />
                    <Button type="submit" label="Urlaubsantrag senden" icon="pi pi-send" :loading="loading" :disabled="!isFormValid || loading" />
                </div>
            </form>
        </div>

        <Toast />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';
import Calendar from 'primevue/calendar';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Toast from 'primevue/toast';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import VacationService from '@/Services/VacationService';

dayjs.extend(isSameOrBefore);
dayjs.extend(isSameOrAfter);
dayjs.locale('de');

// Wir verwenden try-catch, um Fehler abzufangen, falls der Toast-Service nicht verfügbar ist
let toast;
try {
    toast = useToast();
} catch (error) {
    console.warn('Toast service not available, using fallback');
    // Fallback für den Toast-Service
    toast = {
        add: (message) => console.log('Toast message:', message)
    };
}

const loading = ref(false);

// Urlaubsantrag Daten
const vacationRequest = ref({
    substitute: null,
    notes: ''
});

// Urlaubszeiträume
const vacationPeriods = ref([
    {
        startDate: null,
        endDate: null,
        days: 0,
        errors: {
            startDate: '',
            endDate: ''
        }
    }
]);

// Urlaubskontingent (wird vom Server geladen)
const totalVacationDays = ref(0);
const usedVacationDays = ref(0);
const remainingVacationDays = computed(() => totalVacationDays.value - usedVacationDays.value);

// Berechnung des Prozentsatzes der verbrauchten Urlaubstage
const usagePercentage = computed(() => {
    return Math.round((usedVacationDays.value / totalVacationDays.value) * 100);
});

// Berechnung der Urlaubstage basierend auf Start- und Enddatum
const calculateVacationDays = (startDate, endDate) => {
    if (!startDate || !endDate) {
        return 0;
    }

    const start = dayjs(startDate);
    const end = dayjs(endDate);
    let days = 0;

    // Zähle nur Werktage (Mo-Fr)
    let current = start;
    while (current.isSameOrBefore(end, 'day')) {
        const dayOfWeek = current.day();
        if (dayOfWeek !== 0 && dayOfWeek !== 6) { // Nicht Sonntag und nicht Samstag
            days++;
        }
        current = current.add(1, 'day');
    }

    return days;
};

// Aktualisiere die Tage für jeden Zeitraum, wenn sich die Daten ändern
const updateVacationDays = () => {
    vacationPeriods.value.forEach(period => {
        period.days = calculateVacationDays(period.startDate, period.endDate);
    });
};

// Gesamtzahl der beantragten Urlaubstage
const totalRequestedDays = computed(() => {
    return vacationPeriods.value.reduce((total, period) => total + period.days, 0);
});

// Kann weitere Zeiträume hinzufügen?
const canAddMorePeriods = computed(() => {
    return vacationPeriods.value.length < 5 && // Maximal 5 Zeiträume
        vacationPeriods.value.every(period => period.startDate && period.endDate); // Alle vorhandenen Zeiträume müssen vollständig sein
});

// Hinzufügen eines neuen Zeitraums
const addPeriod = () => {
    if (!canAddMorePeriods.value) return;

    vacationPeriods.value.push({
        startDate: null,
        endDate: null,
        days: 0,
        errors: {
            startDate: '',
            endDate: ''
        }
    });
};

// Entfernen eines Zeitraums
const removePeriod = (index) => {
    if (vacationPeriods.value.length > 1) {
        vacationPeriods.value.splice(index, 1);
    }
};

// Bereits gebuchte Urlaubstage (würde normalerweise vom Server geladen)
const bookedVacationDates = ref([
    { start: new Date(2025, 2, 10), end: new Date(2025, 2, 15) },
    { start: new Date(2025, 4, 1), end: new Date(2025, 4, 5) }
]);

// Deaktivierte Daten für den Kalender (bereits gebuchte Urlaubstage)
const disabledDates = computed(() => {
    const dates = [];
    bookedVacationDates.value.forEach(vacation => {
        let current = dayjs(vacation.start);
        const end = dayjs(vacation.end);

        while (current.isSameOrBefore(end, 'day')) {
            dates.push(new Date(current.year(), current.month(), current.date()));
            current = current.add(1, 'day');
        }
    });

    return dates;
});

// Verfügbare Vertretungen (würde normalerweise vom Server geladen)
const availableSubstitutes = ref([
    { id: 1, name: 'Anna Schmidt', department: 'Marketing' },
    { id: 2, name: 'Thomas Müller', department: 'Vertrieb' },
    { id: 3, name: 'Julia Weber', department: 'Personal' }
]);

// Formularvalidierung
const isFormValid = computed(() => {
    // Prüfe, ob alle Zeiträume gültig sind
    const allPeriodsValid = vacationPeriods.value.every(period =>
        period.startDate &&
        period.endDate &&
        period.days > 0
    );

    // Prüfe, ob genügend Urlaubstage verfügbar sind
    const enoughDaysAvailable = totalRequestedDays.value <= remainingVacationDays.value;

    return allPeriodsValid && enoughDaysAvailable;
});

// Formatierungsfunktion für Datum
const formatDate = (date) => {
    if (!date) return '-';
    return dayjs(date).format('DD.MM.YYYY');
};

// Daten vom Server laden
const fetchVacationData = async () => {
    try {
        const response = await VacationService.getUserVacationData();
        totalVacationDays.value = response.data.stats.total;
        usedVacationDays.value = response.data.stats.used;
        bookedVacationDates.value = response.data.bookedDates;
        availableSubstitutes.value = response.data.substitutes;
    } catch (error) {
        console.error('Fehler beim Laden der Urlaubsdaten:', error);
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Die Urlaubsdaten konnten nicht geladen werden.',
            life: 3000
        });
    }
};

// Urlaubsantrag absenden
const submitVacationRequest = async () => {
    // Validierung zurücksetzen
    vacationPeriods.value.forEach(period => {
        period.errors.startDate = '';
        period.errors.endDate = '';
    });

    // Validierung durchführen
    let isValid = true;

    vacationPeriods.value.forEach(period => {
        if (!period.startDate) {
            period.errors.startDate = 'Bitte wählen Sie ein Startdatum aus.';
            isValid = false;
        }

        if (!period.endDate) {
            period.errors.endDate = 'Bitte wählen Sie ein Enddatum aus.';
            isValid = false;
        }
    });

    if (!isValid) {
        return;
    }

    if (totalRequestedDays.value > remainingVacationDays.value) {
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Sie haben nicht genügend Urlaubstage übrig.',
            life: 3000
        });
        return;
    }

    loading.value = true;

    try {
        // Daten für den API-Aufruf vorbereiten
        const requestData = {
            periods: vacationPeriods.value.map(period => ({
                startDate: period.startDate,
                endDate: period.endDate,
                days: period.days
            })),
            substitute: vacationRequest.value.substitute ? vacationRequest.value.substitute.id : null,
            notes: vacationRequest.value.notes
        };

        // API-Aufruf
        await VacationService.submitVacationRequest(requestData);

        // Erfolgreiche Antwort
        toast.add({
            severity: 'success',
            summary: 'Erfolg',
            detail: 'Ihr Urlaubsantrag wurde erfolgreich gesendet und wird nun geprüft.',
            life: 3000
        });

        // Formular zurücksetzen
        vacationRequest.value = {
            substitute: null,
            notes: ''
        };

        vacationPeriods.value = [{
            startDate: null,
            endDate: null,
            days: 0,
            errors: {
                startDate: '',
                endDate: ''
            }
        }];

        // Event emittieren, um die übergeordnete Komponente zu informieren
        emit('submitted');
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

// Emits
const emit = defineEmits(['cancel', 'submitted']);

// Beobachte Änderungen an den Zeiträumen
watch(vacationPeriods, () => {
    updateVacationDays();
}, { deep: true });

// Komponente initialisieren
onMounted(() => {
    fetchVacationData();
});
</script>

<style scoped>
.vacation-request-container {
    max-width: 900px;
    margin: 0 auto;
}

.card {
    background-color: var(--surface-card);
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    margin-bottom: 1.5rem;
}

:deep(.p-calendar) {
    width: 100%;
}

:deep(.p-dropdown) {
    width: 100%;
}

:deep(.p-inputnumber) {
    width: 100%;
}

.vacation-period {
    border: 1px solid var(--surface-border);
    border-radius: var(--border-radius);
    padding: 1.25rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    background-color: var(--surface-section);
}

.vacation-period:hover {
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
}

.summary-table {
    border: 1px solid var(--surface-border);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.summary-header {
    font-weight: bold;
    background-color: var(--surface-section);
}

.summary-row {
    transition: background-color 0.2s;
    padding: 0.75rem 0.5rem;
}

.summary-row:hover {
    background-color: var(--surface-hover);
}

.summary-footer {
    font-weight: bold;
    background-color: var(--surface-section);
}

.vacation-summary {
    background-color: var(--surface-section);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.date-range {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

@media (max-width: 768px) {
    .date-range {
        grid-template-columns: 1fr;
    }

    .grid {
        display: flex;
        flex-direction: column;
    }

    .col-12 {
        width: 100%;
        padding: 0.5rem;
    }
}

.dark-mode .vacation-summary {
    background-color: var(--surface-ground);
}
</style>

