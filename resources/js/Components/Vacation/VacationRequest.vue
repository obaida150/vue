<template>
    <div class="vacation-request-container">
        <div class="p-fluid">
            <!-- Zusammenfassung des Urlaubskontingents -->
            <div class="vacation-summary p-4 mb-6 border-round bg-gray-50 dark:bg-gray-800 shadow-sm">
                <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">Urlaubskontingent</h3>
                <div class="grid">
                    <div class="col-12 md:col-4">
                        <div class="flex flex-column align-items-center">
                            <div class="text-4xl font-bold text-primary mb-2">{{ displayTotalVacationDays }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Gesamtes Kontingent</div>
                        </div>
                    </div>
                    <div class="col-12 md:col-4">
                        <div class="flex flex-column align-items-center">
                            <div class="text-4xl font-bold text-orange-500 mb-2">{{ displayUsedVacationDays }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Bereits genommen</div>
                        </div>
                    </div>
                    <div class="col-12 md:col-4">
                        <div class="flex flex-column align-items-center">
                            <div class="text-4xl font-bold text-green-500 mb-2">{{ displayRemainingVacationDays }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Verbleibend</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <ProgressBar :value="displayUsagePercentage" :showValue="false" class="mb-2" />
                    <div class="flex justify-content-between text-sm text-gray-600 dark:text-gray-400">
                        <span>{{ displayUsagePercentage }}% verbraucht</span>
                        <span>{{ 100 - displayUsagePercentage }}% verfügbar</span>
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
                            <div class="col-12 md:col-4 field mb-3">
                                <label :for="'startDate-' + index" class="block text-sm font-medium mb-1">Startdatum</label>
                                <DatePicker
                                    :id="'startDate-' + index"
                                    v-model="period.startDate"
                                    dateFormat="dd.mm.yy"
                                    :minDate="new Date()"
                                    :disabledDates="disabledDates"
                                    class="w-full"
                                    @date-select="updatePeriodDays(index)"
                                    :locale="de"
                                />
                                <small v-if="period.errors.startDate" class="p-error">{{ period.errors.startDate }}</small>
                            </div>

                            <div class="col-12 md:col-4 field mb-3">
                                <label :for="'endDate-' + index" class="block text-sm font-medium mb-1">Enddatum</label>
                                <DatePicker
                                    :id="'endDate-' + index"
                                    v-model="period.endDate"
                                    dateFormat="dd.mm.yy"
                                    :minDate="period.startDate || new Date()"
                                    :disabledDates="disabledDates"
                                    class="w-full"
                                    @date-select="updatePeriodDays(index)"
                                    :locale="de"
                                />
                                <small v-if="period.errors.endDate" class="p-error">{{ period.errors.endDate }}</small>
                            </div>

                            <div class="col-12 md:col-2 field mb-3">
                                <label :for="'days-' + index" class="block text-sm font-medium mb-1">Tage</label>
                                <div class="p-inputgroup">
                                    <InputNumber
                                        :id="'days-' + index"
                                        v-model="period.days"
                                        :min="0.5"
                                        :max="displayRemainingVacationDays"
                                        :minFractionDigits="1"
                                        :maxFractionDigits="1"
                                        disabled
                                        class="w-full"
                                    />
                                </div>
                            </div>

                            <div class="col-12 md:col-2 field mb-3">
                                <label :for="'dayType-' + index" class="block text-sm font-medium mb-1">Art</label>
                                <Select
                                    :id="'dayType-' + index"
                                    v-model="period.dayType"
                                    :options="dayTypeOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Wählen..."
                                    class="w-full"
                                    @change="updatePeriodDays(index)"
                                    :disabled="!isSingleDay(period)"
                                />
                            </div>
                        </div>

                        <!-- Hinweis für Halbtage -->
                        <div v-if="isSingleDay(period) && period.dayType !== 'full_day'" class="p-message p-message-info p-2 mt-2">
                            <i class="pi pi-info-circle mr-2"></i>
                            <span>
                                {{ period.dayType === 'morning' ? 'Vormittag (bis 12:00 Uhr)' : 'Nachmittag (ab 12:00 Uhr)' }} -
                                Wird als 0,5 Urlaubstage berechnet
                            </span>
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
                        <Select
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
                            <div class="col-5">Zeitraum</div>
                            <div class="col-2">Art</div>
                            <div class="col-2">Tage</div>
                            <div class="col-3">Status</div>
                        </div>

                        <div v-for="(period, index) in vacationPeriods" :key="index" class="grid summary-row p-2 border-bottom-1 border-gray-200 dark:border-gray-700">
                            <div class="col-5">
                                {{ formatDate(period.startDate) }} - {{ formatDate(period.endDate) }}
                            </div>
                            <div class="col-2">
                                <Tag
                                    :value="getDayTypeLabel(period.dayType)"
                                    :severity="period.dayType === 'full_day' ? 'primary' : 'info'"
                                />
                            </div>
                            <div class="col-2">{{ period.days }}</div>
                            <div class="col-3">
                                <Tag value="Neu" severity="info" />
                            </div>
                        </div>

                        <div class="grid summary-footer font-medium p-2 bg-gray-50 dark:bg-gray-800">
                            <div class="col-5">Gesamt</div>
                            <div class="col-2"></div>
                            <div class="col-2">{{ totalRequestedDays }}</div>
                            <div class="col-3"></div>
                        </div>
                    </div>

                    <div v-if="holidaysInPeriods.length > 0" class="p-message p-message-info p-3 mb-4">
                        <i class="pi pi-info-circle mr-2"></i>
                        <span>
                            Folgende Feiertage liegen in Ihrem Urlaubszeitraum und werden nicht als Urlaubstage gezählt:
                            <ul class="mt-2 pl-4">
                                <li v-for="(holiday, index) in holidaysInPeriods" :key="index">
                                    {{ holiday.name }} ({{ formatDate(holiday.date) }})
                                </li>
                            </ul>
                        </span>
                    </div>

                    <div v-if="totalRequestedDays > displayRemainingVacationDays" class="p-message p-message-error p-3 mb-4">
                        <i class="pi pi-exclamation-triangle mr-2"></i>
                        <span>Sie haben nicht genügend Urlaubstage übrig. Sie benötigen {{ totalRequestedDays }} Tage, aber haben nur {{ displayRemainingVacationDays }} verfügbar.</span>
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
import { ref, computed, onMounted, watch } from "vue"
import dayjs from "dayjs"
import "dayjs/locale/de"
import axios from "axios"
import isSameOrBefore from "dayjs/plugin/isSameOrBefore"
import isSameOrAfter from "dayjs/plugin/isSameOrAfter"
import DatePicker from "primevue/datepicker"
import InputNumber from "primevue/inputnumber"
import Select from "primevue/select"
import Textarea from "primevue/textarea"
import Button from "primevue/button"
import Toast from "primevue/toast"
import ProgressBar from "primevue/progressbar"
import Tag from "primevue/tag"
import { useToast } from "primevue/usetoast"
import { usePrimeVue } from "primevue/config"
import VacationService from "@/Services/VacationService"
import HolidayService from "@/Services/holiday-service"

dayjs.extend(isSameOrBefore)
dayjs.extend(isSameOrAfter)
dayjs.locale("de")

// Deutsche Lokalisierung für PrimeVue
const de = {
    firstDayOfWeek: 1,
    dayNames: ["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag"],
    dayNamesShort: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
    dayNamesMin: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
    monthNames: ["Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember"],
    monthNamesShort: ["Jan", "Feb", "Mär", "Apr", "Mai", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dez"],
    today: "Heute",
    clear: "Löschen",
    weekHeader: "KW",
    dateFormat: "dd.mm.yy",
    firstDay: 1
};

// PrimeVue global konfigurieren
const primevue = usePrimeVue();

const loading = ref(false)

// Props von der übergeordneten Komponente
const props = defineProps({
    vacationStats: Object,
    myVacationRequests: Array,
    holidays: Array,
});

// Optionen für Urlaubsart
const dayTypeOptions = ref([
    { label: 'Ganzer Tag', value: 'full_day' },
    { label: 'Vormittag', value: 'morning' },
    { label: 'Nachmittag', value: 'afternoon' }
])

// Urlaubsantrag Daten
const vacationRequest = ref({
    substitute: null,
    notes: "",
})

// Urlaubszeiträume
const vacationPeriods = ref([
    {
        startDate: null,
        endDate: null,
        days: 0, // Initial auf 0 setzen
        dayType: 'full_day',
        errors: {
            startDate: "",
            endDate: "",
        },
    },
])

// Dynamische Anzeige des Urlaubskontingents basierend auf Props und aktuellem Antrag
const displayTotalVacationDays = computed(() => props.vacationStats?.totalAvailable || 0);
const displayUsedVacationDays = computed(() => {
    // Bereits genommene Tage aus den Props + die Tage des aktuellen Antrags
    return (props.vacationStats?.used || 0) + totalRequestedDays.value;
});
const displayRemainingVacationDays = computed(() => {
    // Verbleibende Tage aus den Props - die Tage des aktuellen Antrags
    return (props.vacationStats?.remaining || 0) - totalRequestedDays.value;
});

// Berechnung des Prozentsatzes der verbrauchten Urlaubstage für die Anzeige im Dialog
const displayUsagePercentage = computed(() => {
    const used = displayUsedVacationDays.value;
    const total = displayTotalVacationDays.value;
    if (total <= 0) return 0;
    return Math.min(Math.round((used / total) * 100), 100);
});

// Prüft, ob ein Zeitraum ein einzelner Tag ist
const isSingleDay = (period) => {
    if (!period.startDate || !period.endDate) return false
    return dayjs(period.startDate).isSame(dayjs(period.endDate), 'day')
}

// Gibt das Label für den Urlaubstyp zurück
const getDayTypeLabel = (dayType) => {
    const option = dayTypeOptions.value.find(opt => opt.value === dayType)
    return option ? option.label : 'Ganzer Tag'
}

// Prüft, ob ein Datum ein Feiertag ist
const isHoliday = (date) => {
    if (!date) return false;
    const djsDate = dayjs(date);
    if (!djsDate.isValid()) return false;
    // Sicherstellen, dass props.holidays immer ein Array ist
    const safeHolidays = props.holidays || [];
    return HolidayService.isHoliday(djsDate, safeHolidays.map(h => ({ date: h.date, name: h.name })));
}

// Gibt den Namen eines Feiertags zurück
const getHolidayName = (date) => {
    if (!date) return null;
    const djsDate = dayjs(date);
    if (!djsDate.isValid()) return null;
    // Sicherstellen, dass props.holidays immer ein Array ist
    const safeHolidays = props.holidays || [];
    return HolidayService.getHolidayName(djsDate, safeHolidays.map(h => ({ date: h.date, name: h.name })));
}

/**
 * Berechnet die Anzahl der einzigartigen Arbeitstage in einem Zeitraum,
 * unter Berücksichtigung von Wochenenden, Feiertagen und bereits gebuchten/ausstehenden Urlaubstagen.
 *
 * @param {Date} startDate - Das Startdatum des Zeitraums.
 * @param {Date} endDate - Das Enddatum des Zeitraums.
 * @param {Array} currentFormPeriods - Alle Zeiträume, die aktuell im Formular ausgewählt sind (um interne Überschneidungen zu vermeiden).
 * @param {Array} existingRequests - Alle bereits genehmigten oder ausstehenden Urlaubsanträge des Benutzers.
 * @param {Array} holidays - Eine Liste von Feiertagen.
 * @param {string} dayType - Der Typ des Tages ('full_day', 'morning', 'afternoon').
 * @returns {number} Die Anzahl der einzigartigen Arbeitstage.
 */
const getUniqueWorkDays = (startDate, endDate, currentFormPeriods, existingRequests, holidays, dayType = 'full_day') => {
    if (!startDate || !endDate) {
        return 0;
    }

    const start = dayjs(startDate);
    const end = dayjs(endDate);

    if (!start.isValid() || !end.isValid()) {
        return 0;
    }

    // Sicherstellen, dass existingRequests und holidays immer Arrays sind
    const safeExistingRequests = existingRequests || [];
    const safeHolidays = holidays || [];

    // Wenn es ein einzelner Tag ist und Halbtag gewählt wurde
    if (start.isSame(end, 'day') && dayType !== 'full_day') {
        const dayOfWeek = start.day();
        if (dayOfWeek !== 0 && dayOfWeek !== 6 && !isHoliday(start)) {
            const dateString = start.format('YYYY-MM-DD');
            const isCoveredByExisting = safeExistingRequests.some(req => {
                const reqStart = dayjs(req.startDate);
                const reqEnd = dayjs(req.endDate);
                return (
                    (req.status === 'approved' || req.status === 'pending') &&
                    reqStart.isValid() && reqEnd.isValid() &&
                    start.isSameOrAfter(reqStart, 'day') &&
                    start.isSameOrBefore(reqEnd, 'day') &&
                    (req.dayType === 'full_day' || req.dayType === dayType)
                );
            });
            if (!isCoveredByExisting) {
                return 0.5;
            }
        }
        return 0;
    }

    const uniqueDaysSet = new Set();

    // Sammle alle Tage aus den bestehenden genehmigten und ausstehenden Anträgen
    safeExistingRequests.forEach(req => {
        if (req.status === 'approved' || req.status === 'pending') {
            let d = dayjs(req.startDate);
            const reqEnd = dayjs(req.endDate);
            if (!d.isValid() || !reqEnd.isValid()) return;
            while (d.isSameOrBefore(reqEnd, 'day')) {
                uniqueDaysSet.add(d.format('YYYY-MM-DD'));
                d = d.add(1, 'day');
            }
        }
    });

    // Sammle alle Tage aus den ANDEREN Zeiträumen im aktuellen Formular
    currentFormPeriods.forEach(period => {
        if (period.startDate && period.endDate) {
            let d = dayjs(period.startDate);
            const pEnd = dayjs(period.endDate);
            if (!d.isValid() || !pEnd.isValid()) return;
            while (d.isSameOrBefore(pEnd, 'day')) {
                uniqueDaysSet.add(d.format('YYYY-MM-DD'));
                d = d.add(1, 'day');
            }
        }
    });

    let currentPeriodWorkDays = 0;
    let current = start;
    while (current.isSameOrBefore(end, 'day')) {
        const dayOfWeek = current.day();
        const dateString = current.format('YYYY-MM-DD');

        if (dayOfWeek !== 0 && dayOfWeek !== 6 && !isHoliday(current)) {
            if (!uniqueDaysSet.has(dateString)) {
                currentPeriodWorkDays += 1;
            }
        }
        current = current.add(1, 'day');
    }

    return currentPeriodWorkDays;
};


// Aktualisiere die Tage für einen bestimmten Zeitraum
const updatePeriodDays = (index) => {
    const period = vacationPeriods.value[index];

    // Wenn es kein einzelner Tag ist, setze dayType auf full_day
    if (!isSingleDay(period)) {
        period.dayType = 'full_day';
    }

    // Berechne die Tage für den aktuellen Zeitraum unter Berücksichtigung aller anderen Faktoren
    period.days = getUniqueWorkDays(
        period.startDate,
        period.endDate,
        vacationPeriods.value.filter((_, i) => i !== index), // Alle anderen Perioden im Formular
        props.myVacationRequests, // Wird als existingRequests übergeben
        props.holidays, // Wird als holidays übergeben
        period.dayType
    );
};

// Gesamtzahl der beantragten Urlaubstage (für den aktuellen Antrag)
const totalRequestedDays = computed(() => {
    // Summiere die berechneten Tage jeder Periode
    let sumOfPeriodDays = vacationPeriods.value.reduce((total, period) => total + period.days, 0);
    return sumOfPeriodDays;
});


// Feiertage in den ausgewählten Zeiträumen
const holidaysInPeriods = computed(() => {
    const holidaysList = []

    vacationPeriods.value.forEach(period => {
        if (period.startDate && period.endDate) {
            let current = dayjs(period.startDate)
            const end = dayjs(period.endDate)

            if (!current.isValid() || !end.isValid()) return;

            while (current.isSameOrBefore(end, "day")) {
                if (isHoliday(current)) {
                    const holidayName = getHolidayName(current)
                    if (holidayName) {
                        // Prüfe, ob der Feiertag bereits in der Liste ist
                        const exists = holidaysList.some(h =>
                            h.name === holidayName &&
                            dayjs(h.date).isSame(current, "day")
                        )

                        if (!exists) {
                            holidaysList.push({
                                name: holidayName,
                                date: current.toDate()
                            })
                        }
                    }
                }
                current = current.add(1, "day")
            }
        }
    })

    return holidaysList
})

// Kann weitere Zeiträume hinzufügen?
const canAddMorePeriods = computed(() => {
    return (
        vacationPeriods.value.length < 5 && // Maximal 5 Zeiträume
        vacationPeriods.value.every((period) => period.startDate && period.endDate && period.days >= 0) // Alle vorhandenen Zeiträume müssen vollständig sein
    )
})

// Hinzufügen eines neuen Zeitraums
const addPeriod = () => {
    if (!canAddMorePeriods.value) return

    vacationPeriods.value.push({
        startDate: null,
        endDate: null,
        days: 0,
        dayType: 'full_day',
        errors: {
            startDate: "",
            endDate: "",
        },
    })
}

// Entfernen eines Zeitraums
const removePeriod = (index) => {
    if (vacationPeriods.value.length > 1) {
        vacationPeriods.value.splice(index, 1)
        // Nach dem Entfernen eines Zeitraums müssen alle Tage neu berechnet werden,
        // da sich die Überschneidungen ändern könnten.
        vacationPeriods.value.forEach((_, i) => updatePeriodDays(i));
    }
}

// Deaktivierte Daten für den Kalender (eigene Urlaubstage und Feiertage)
const disabledDates = computed(() => {
    const dates = new Set();

    // Füge Feiertage hinzu
    // Sicherstellen, dass props.holidays immer ein Array ist
    const safeHolidays = props.holidays || [];
    safeHolidays.forEach(holiday => {
        const djsHolidayDate = dayjs(holiday.date);
        if (djsHolidayDate.isValid()) {
            dates.add(djsHolidayDate.format('YYYY-MM-DD'));
        }
    });

    // Füge bereits genehmigte oder ausstehende Urlaubstage hinzu
    // Sicherstellen, dass props.myVacationRequests immer ein Array ist
    const safeMyVacationRequests = props.myVacationRequests || [];
    safeMyVacationRequests.forEach(request => {
        if (request.status === 'approved' || request.status === 'pending') {
            let current = dayjs(request.startDate);
            const end = dayjs(request.endDate);
            if (!current.isValid() || !end.isValid()) return;

            while (current.isSameOrBefore(end, 'day')) {
                dates.add(current.format('YYYY-MM-DD'));
                current = current.add(1, 'day');
            }
        }
    });

    // Konvertiere das Set von Strings in ein Array von Date-Objekten
    return Array.from(dates).map(dateString => dayjs(dateString).toDate());
});


// Verfügbare Vertretungen (wird vom Server geladen)
const availableSubstitutes = ref([])

// Formularvalidierung
const isFormValid = computed(() => {
    // Prüfe, ob alle Zeiträume gültig sind (Start- und Enddatum gesetzt, Tage > 0)
    const allPeriodsValid = vacationPeriods.value.every((period) =>
        period.startDate && period.endDate && period.days >= 0
    );

    // Prüfe, ob genügend Urlaubstage verfügbar sind
    const enoughDaysAvailable = totalRequestedDays.value <= displayRemainingVacationDays.value;

    // Prüfe, ob mindestens ein Tag beantragt wurde
    const atLeastOneDayRequested = totalRequestedDays.value > 0;

    return allPeriodsValid && enoughDaysAvailable && atLeastOneDayRequested;
})

// Formatierungsfunktion für Datum
const formatDate = (date) => {
    if (!date) return "-"
    const djsDate = dayjs(date);
    if (!djsDate.isValid()) return "-";
    return djsDate.format("DD.MM.YYYY")
}

const toast = useToast()

// Daten vom Server laden (nur noch Vertretungen)
const fetchSubstitutes = async () => {
    try {
        const response = await VacationService.getUserVacationData() // Diese API liefert auch Vertretungen
        availableSubstitutes.value = response.data.substitutes
    } catch (error) {
        console.error("Fehler beim Laden der Vertretungen:", error)
        toast.add({
            severity: "error",
            summary: "Fehler",
            detail: "Die Vertretungsdaten konnten nicht geladen werden.",
            life: 3000,
        })
    }
}

// Urlaubsantrag absenden
const submitVacationRequest = async () => {
    // Validierung zurücksetzen
    vacationPeriods.value.forEach((period) => {
        period.errors.startDate = ""
        period.errors.endDate = ""
    })

    // Validierung durchführen
    let isValid = true

    vacationPeriods.value.forEach((period) => {
        if (!period.startDate) {
            period.errors.startDate = "Bitte wählen Sie ein Startdatum aus."
            isValid = false
        }

        if (!period.endDate) {
            period.errors.endDate = "Bitte wählen Sie ein Enddatum aus."
            isValid = false
        }
    })

    if (!isValid) {
        toast.add({
            severity: "error",
            summary: "Fehler",
            detail: "Bitte füllen Sie alle erforderlichen Felder aus.",
            life: 3000,
        });
        return
    }

    if (totalRequestedDays.value <= 0) {
        toast.add({
            severity: "error",
            summary: "Fehler",
            detail: "Bitte wählen Sie mindestens einen Urlaubstag aus.",
            life: 3000,
        });
        return;
    }

    if (totalRequestedDays.value > displayRemainingVacationDays.value) {
        toast.add({
            severity: "error",
            summary: "Fehler",
            detail: "Sie haben nicht genügend Urlaubstage übrig. Sie benötigen " + totalRequestedDays.value + " Tage, aber haben nur " + displayRemainingVacationDays.value + " verfügbar.",
            life: 5000,
        })
        return
    }

    loading.value = true

    try {
        // Daten für den API-Aufruf vorbereiten
        const requestData = {
            periods: vacationPeriods.value.map((period) => ({
                // Format dates to ensure they're interpreted correctly by the server
                startDate: period.startDate ? dayjs(period.startDate).format("YYYY-MM-DD") : null,
                endDate: period.endDate ? dayjs(period.endDate).format("YYYY-MM-DD") : null,
                days: period.days,
                dayType: period.dayType,
            })),
            substitute: vacationRequest.value.substitute ? vacationRequest.value.substitute.id : null,
            notes: vacationRequest.value.notes,
        }

        // API-Aufruf
        await VacationService.submitVacationRequest(requestData)

        // Erfolgreiche Antwort
        toast.add({
            severity: "success",
            summary: "Erfolg",
            detail: "Ihr Urlaubsantrag wurde erfolgreich gesendet und wird nun geprüft.",
            life: 3000,
        })

        // Formular zurücksetzen
        vacationRequest.value = {
            substitute: null,
            notes: "",
        }

        vacationPeriods.value = [
            {
                startDate: null,
                endDate: null,
                days: 0,
                dayType: 'full_day',
                errors: {
                    startDate: "",
                    endDate: "",
                },
            },
        ]

        // Event emittieren, um die übergeordnete Komponente zu informieren
        emit("submitted")
    } catch (error) {
        console.error("Fehler beim Senden des Urlaubsantrags:", error);
        let errorMessage = "Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.";
        if (error.response && error.response.data && error.response.data.message) {
            errorMessage = error.response.data.message;
        }
        toast.add({
            severity: "error",
            summary: "Fehler",
            detail: errorMessage,
            life: 5000,
        })
    } finally {
        loading.value = false
    }
}

// Emits
const emit = defineEmits(["cancel", "submitted"])

// Beobachte Änderungen an den Zeiträumen, um die Tage neu zu berechnen
watch(
    vacationPeriods,
    () => {
        // Trigger update for all periods when any period changes
        vacationPeriods.value.forEach((_, i) => updatePeriodDays(i));
    },
    { deep: true },
)

// Komponente initialisieren
onMounted(() => {
    // Setze die globale PrimeVue-Lokalisierung
    primevue.config.locale = de;

    fetchSubstitutes(); // Nur noch Vertretungen laden

    // Hinzugefügt für Debugging: Überprüfen Sie die empfangenen Props
    console.log("VacationRequest received vacationStats:", props.vacationStats);
    console.log("VacationRequest received myVacationRequests:", props.myVacationRequests);
    console.log("VacationRequest received holidays:", props.holidays);
})

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

:deep(.p-datepicker) {
    width: 100%;
}

:deep(.p-select) {
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

/* Hervorhebung für Feiertage im Kalender */
:deep(.p-datepicker .p-datepicker-calendar td.p-disabled) {
    opacity: 0.65;
}

:deep(.p-datepicker .p-datepicker-calendar td.p-disabled[title]) {
    position: relative;
}

:deep(.p-datepicker .p-datepicker-calendar td.p-disabled[title]:hover::after) {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    white-space: nowrap;
    z-index: 1000;
}
</style>
