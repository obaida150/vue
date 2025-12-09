<template>
    <div class="hr-vacation-entry-container">
        <div class="p-fluid">
            <!-- Header -->
            <div class="mb-4">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2">
                    Urlaub für Mitarbeiter eintragen
                </h2>
                <p class="text-gray-600 dark:text-gray-400">
                    Als HR können Sie hier nachträglich Urlaub für Mitarbeiter eintragen (z.B. bei spontaner Verlängerung während des Urlaubs).
                </p>
            </div>

            <!-- Formular -->
            <form @submit.prevent="submitHrVacationEntry">
                <!-- Mitarbeiterauswahl -->
                <div class="card mb-4 p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                    <h3 class="text-lg font-semibold mb-3 text-gray-800 dark:text-gray-200">Mitarbeiter auswählen</h3>

                    <div class="grid grid-cols-1 gap-4">
                        <!-- Entferne loadingEmployees, da Daten als Props kommen -->
                        <div>
                            <label for="employee-select" class="block mb-2 font-medium text-gray-700 dark:text-gray-300">
                                Mitarbeiter
                            </label>
                            <Select
                                id="employee-select"
                                v-model="selectedEmployee"
                                :options="employees"
                                optionLabel="name"
                                placeholder="Mitarbeiter auswählen..."
                                filter
                                :filterFields="['name', 'employee_number', 'department']"
                                class="w-full"
                                :disabled="employees.length === 0"
                            >
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center gap-2">
                                        <Avatar
                                            :label="slotProps.value.initials"
                                            class="bg-blue-500 text-white"
                                            shape="circle"
                                            size="normal"
                                        />
                                        <span>{{ slotProps.value.name }}</span>
                                    </div>
                                    <span v-else>{{ slotProps.placeholder }}</span>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center gap-2">
                                        <Avatar
                                            :label="slotProps.option.initials"
                                            class="bg-blue-500 text-white"
                                            shape="circle"
                                            size="normal"
                                        />
                                        <div>
                                            <div class="font-medium">{{ slotProps.option.name }}</div>
                                            <div class="text-sm text-gray-500">
                                                {{ slotProps.option.employee_number }} | {{ slotProps.option.department }}
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </Select>
                        </div>

                        <!-- Urlaubskontingent anzeigen -->
                        <div v-if="selectedEmployee" class="bg-blue-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h4 class="font-semibold mb-2 text-gray-800 dark:text-gray-200">Urlaubskontingent</h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <div class="text-gray-600 dark:text-gray-400">Jahresurlaub</div>
                                    <div class="font-semibold text-lg text-gray-800 dark:text-gray-200">
                                        {{ selectedEmployee.vacation_days_per_year }} Tage
                                    </div>
                                </div>
                                <div>
                                    <div class="text-gray-600 dark:text-gray-400">Verfügbar</div>
                                    <div class="font-semibold text-lg text-gray-800 dark:text-gray-200">
                                        {{ selectedEmployee.total_available }} Tage
                                    </div>
                                </div>
                                <div>
                                    <div class="text-gray-600 dark:text-gray-400">Verbraucht</div>
                                    <div class="font-semibold text-lg text-red-600 dark:text-red-400">
                                        {{ selectedEmployee.used_days }} Tage
                                    </div>
                                </div>
                                <div>
                                    <div class="text-gray-600 dark:text-gray-400">Restliche Tage</div>
                                    <div class="font-semibold text-lg text-green-600 dark:text-green-400">
                                        {{ selectedEmployee.remaining_days }} Tage
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mitarbeiter-Info (wenn ausgewählt) -->
                <div v-if="selectedEmployee" class="p-3 bg-blue-50 dark:bg-blue-900 border-round mb-4">
                    <div class="grid">
                        <div class="col-12 md:col-3">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Gesamtkontingent</div>
                            <div class="text-lg font-bold text-gray-800 dark:text-gray-200">
                                {{ selectedEmployee.total_available }} Tage
                            </div>
                        </div>
                        <div class="col-12 md:col-3">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Bereits genommen</div>
                            <div class="text-lg font-bold text-orange-600">
                                {{ selectedEmployee.used_days }} Tage
                            </div>
                        </div>
                        <div class="col-12 md:col-3">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Verbleibend</div>
                            <div class="text-lg font-bold text-green-600">
                                {{ selectedEmployee.remaining_days }} Tage
                            </div>
                        </div>
                        <div class="col-12 md:col-3">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Nach Eintrag</div>
                            <div class="text-lg font-bold" :class="remainingAfterEntry >= 0 ? 'text-green-600' : 'text-red-600'">
                                {{ remainingAfterEntry }} Tage
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Urlaubszeiträume -->
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
                                <label :for="'startDate-' + index" class="block text-sm font-medium mb-1">Startdatum *</label>
                                <DatePicker
                                    :id="'startDate-' + index"
                                    v-model="period.startDate"
                                    dateFormat="dd.mm.yy"
                                    :disabledDates="selectedEmployee ? getDisabledDates(selectedEmployee.id) : []"
                                    class="w-full"
                                    @date-select="updatePeriodDays(index)"
                                    :locale="de"
                                    showButtonBar
                                />
                                <small v-if="period.errors.startDate" class="p-error">{{ period.errors.startDate }}</small>
                            </div>

                            <div class="col-12 md:col-4 field mb-3">
                                <label :for="'endDate-' + index" class="block text-sm font-medium mb-1">Enddatum *</label>
                                <DatePicker
                                    :id="'endDate-' + index"
                                    v-model="period.endDate"
                                    dateFormat="dd.mm.yy"
                                    :minDate="period.startDate"
                                    :disabledDates="selectedEmployee ? getDisabledDates(selectedEmployee.id) : []"
                                    class="w-full"
                                    @date-select="updatePeriodDays(index)"
                                    :locale="de"
                                    showButtonBar
                                />
                                <small v-if="period.errors.endDate" class="p-error">{{ period.errors.endDate }}</small>
                            </div>

                            <div class="col-12 md:col-2 field mb-3">
                                <label :for="'days-' + index" class="block text-sm font-medium mb-1">Tage</label>
                                <InputNumber
                                    :id="'days-' + index"
                                    v-model="period.days"
                                    :min="0.5"
                                    :minFractionDigits="1"
                                    :maxFractionDigits="1"
                                    disabled
                                    class="w-full"
                                />
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
                        <div v-if="isSingleDay(period) && period.dayType !== 'full_day'" class="flex align-items-center p-3 bg-blue-50 dark:bg-blue-900 border-round">
                            <i class="pi pi-info-circle mr-2 text-blue-600"></i>
                            <span class="text-sm">
                                {{ period.dayType === 'morning' ? 'Vormittag (bis 12:00 Uhr)' : 'Nachmittag (ab 12:00 Uhr)' }} -
                                Wird als 0,5 Urlaubstage berechnet
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-content-center mt-3">
                        <Button
                            type="button"
                            label="Weiterer Zeitraum hinzufügen"
                            icon="pi pi-plus"
                            class="p-button-outlined"
                            @click="addPeriod"
                            :disabled="!canAddMorePeriods || !selectedEmployee"
                        />
                    </div>
                </div>

                <!-- Grund und Anmerkungen -->
                <div class="card p-4 mb-4 shadow-sm border-round bg-white dark:bg-gray-900">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">Grund und Anmerkungen</h3>
                    <div class="field mb-4">
                        <label for="reason" class="block text-sm font-medium mb-1">Grund für den nachträglichen Eintrag *</label>
                        <Textarea
                            id="reason"
                            v-model="hrEntry.reason"
                            rows="3"
                            autoResize
                            class="w-full"
                            placeholder="Z.B.: Mitarbeiter hat Urlaub spontan verlängert und konnte den Antrag nicht selbst stellen..."
                        />
                        <small v-if="errors.reason" class="p-error">{{ errors.reason }}</small>
                        <small class="text-gray-500 dark:text-gray-400 text-xs block mt-1">
                            Dieser Grund wird dem Mitarbeiter in der Benachrichtigung angezeigt.
                        </small>
                    </div>

                    <div class="field mb-4">
                        <label for="notes" class="block text-sm font-medium mb-1">Zusätzliche Anmerkungen</label>
                        <Textarea
                            id="notes"
                            v-model="hrEntry.notes"
                            rows="2"
                            autoResize
                            class="w-full"
                            placeholder="Optionale zusätzliche Informationen..."
                        />
                    </div>
                </div>

                <!-- Zusammenfassung -->
                <div class="card p-4 mb-4 shadow-sm border-round bg-white dark:bg-gray-900">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">Zusammenfassung</h3>

                    <div class="summary-table mb-4">
                        <div class="grid font-medium text-sm bg-gray-100 dark:bg-gray-700 p-2 border-round-top">
                            <div class="col-5">Zeitraum</div>
                            <div class="col-2">Art</div>
                            <div class="col-2">Tage</div>
                            <div class="col-3">Status</div>
                        </div>

                        <div v-for="(period, index) in vacationPeriods" :key="index" class="grid p-2 border-bottom-1 border-gray-200 dark:border-gray-700">
                            <div class="col-5">
                                {{ formatDate(period.startDate) }} - {{ formatDate(period.endDate) }}
                            </div>
                            <div class="col-2">
                                <Tag
                                    :value="getDayTypeLabel(period.dayType)"
                                    :severity="period.dayType === 'full_day' ? 'success' : 'info'"
                                />
                            </div>
                            <div class="col-2">{{ period.days }}</div>
                            <div class="col-3">
                                <Tag value="Wird genehmigt" severity="success" />
                            </div>
                        </div>

                        <div class="grid font-medium p-2 bg-gray-50 dark:bg-gray-800 border-round-bottom">
                            <div class="col-5">Gesamt</div>
                            <div class="col-2"></div>
                            <div class="col-2">{{ totalRequestedDays }}</div>
                            <div class="col-3"></div>
                        </div>
                    </div>

                    <!-- Warnungen -->
                    <div v-if="!selectedEmployee" class="flex align-items-center p-3 bg-yellow-50 dark:bg-yellow-900 border-round mb-3">
                        <i class="pi pi-exclamation-triangle mr-2 text-yellow-600"></i>
                        <span>Bitte wählen Sie zuerst einen Mitarbeiter aus.</span>
                    </div>

                    <div v-if="selectedEmployee && totalRequestedDays > selectedEmployee.remaining_days" class="flex align-items-center p-3 bg-red-50 dark:bg-red-900 border-round mb-3">
                        <i class="pi pi-times-circle mr-2 text-red-600"></i>
                        <span>
                            <strong>Nicht genügend Urlaubstage!</strong><br>
                            Benötigt: {{ totalRequestedDays }} Tage | Verfügbar: {{ selectedEmployee.remaining_days }} Tage
                        </span>
                    </div>

                    <div v-if="holidaysInPeriods.length > 0" class="flex align-items-start p-3 bg-blue-50 dark:bg-blue-900 border-round">
                        <i class="pi pi-info-circle mr-2 text-blue-600 mt-1"></i>
                        <div>
                            <span>Folgende Feiertage liegen im Urlaubszeitraum und werden nicht als Urlaubstage gezählt:</span>
                            <ul class="mt-2 pl-4 mb-0">
                                <li v-for="(holiday, index) in holidaysInPeriods" :key="index">
                                    {{ holiday.name }} ({{ formatDate(holiday.date) }})
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Aktionen -->
                <div class="flex justify-content-end gap-2">
                    <Button
                        type="button"
                        label="Abbrechen"
                        icon="pi pi-times"
                        severity="secondary"
                        outlined
                        @click="$emit('cancel')"
                    />
                    <Button
                        type="submit"
                        label="Urlaub eintragen"
                        icon="pi pi-check"
                        :loading="loading"
                        :disabled="!isFormValid || loading"
                    />
                </div>
            </form>
        </div>

        <Toast />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue"
import { useToast } from "primevue/usetoast"
import Toast from "primevue/toast"
import Card from "primevue/card"
import Select from "primevue/select"
import DatePicker from "primevue/datepicker"
import isSameOrBefore from "dayjs/plugin/isSameOrBefore";
import isSameOrAfter from "dayjs/plugin/isSameOrAfter";
import Button from "primevue/button"
import InputText from "primevue/inputtext"
import InputNumber from "primevue/inputnumber"
import Textarea from "primevue/textarea"
import Checkbox from "primevue/checkbox"
import Avatar from "primevue/avatar"
import RadioButton from "primevue/radiobutton"
import Message from "primevue/message"
import Tag from "primevue/tag"
import dayjs from "dayjs"
import isoWeek from "dayjs/plugin/isoWeek"
import customParseFormat from "dayjs/plugin/customParseFormat"
import { router } from '@inertiajs/vue3'

dayjs.extend(isoWeek)
dayjs.extend(customParseFormat)
dayjs.extend(isSameOrBefore);
dayjs.extend(isSameOrAfter);

const toast = useToast()

const props = defineProps({
    employees: {
        type: Array,
        required: true
    },
    holidays: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['cancel', 'submitted', 'vacation-created'])

const loading = ref(false)

const selectedEmployee = ref(null)

// Urlaubszeiträume
const vacationPeriods = ref([
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
])

const hrEntry = ref({
    reason: "",
    notes: "",
})

const errors = ref({
    employee: "",
    reason: "",
})

// Feiertage für Disabled Dates
const disabledDates = computed(() => {
    if (!props.holidays || props.holidays.length === 0) return []
    return props.holidays.map((holiday) => {
        const date = new Date(holiday.date)
        return date
    })
})

// Optionen für Urlaubsart
const dayTypeOptions = ref([
    { label: 'Ganzer Tag', value: 'full_day' },
    { label: 'Vormittag', value: 'morning' },
    { label: 'Nachmittag', value: 'afternoon' }
])

const de = ref({
    firstDayOfWeek: 1,
    dayNames: ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'],
    dayNamesShort: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
    dayNamesMin: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
    monthNames: ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
    monthNamesShort: ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
    today: 'Heute',
    clear: 'Löschen'
})

// Deaktivierte Daten für den Kalender (Wochenenden und Feiertage)
const getDisabledDates = (employeeId) => {
    const dates = new Set()

    // Füge Feiertage hinzu
    const safeHolidays = props.holidays || []
    safeHolidays.forEach(holiday => {
        const djsHolidayDate = dayjs(holiday.date)
        if (djsHolidayDate.isValid()) {
            dates.add(djsHolidayDate.format('YYYY-MM-DD'))
        }
    })

    return Array.from(dates).map(dateString => dayjs(dateString).toDate())
}

// Prüft, ob ein Datum ein Feiertag ist
const isHoliday = (date) => {
    if (!date) return false
    const djsDate = dayjs(date)
    if (!djsDate.isValid()) return false
    const safeHolidays = props.holidays || []
    return safeHolidays.some(h => dayjs(h.date).isSame(djsDate, 'day'))
}

// Gibt den Namen eines Feiertags zurück
const getHolidayName = (date) => {
    if (!date) return null
    const djsDate = dayjs(date)
    if (!djsDate.isValid()) return null
    const safeHolidays = props.holidays || []
    const holiday = safeHolidays.find(h => dayjs(h.date).isSame(djsDate, 'day'))
    return holiday ? holiday.name : null
}

// Berechnet Arbeitstage zwischen zwei Daten
const getWorkDays = (startDate, endDate, dayType = 'full_day') => {
    if (!startDate || !endDate) return 0

    const start = dayjs(startDate)
    const end = dayjs(endDate)

    if (!start.isValid() || !end.isValid()) return 0

    // Wenn es ein einzelner Tag ist und Halbtag gewählt wurde
    if (start.isSame(end, 'day') && dayType !== 'full_day') {
        const dayOfWeek = start.day()
        if (dayOfWeek !== 0 && dayOfWeek !== 6 && !isHoliday(start)) {
            return 0.5
        }
        return 0
    }

    let workDays = 0
    let current = start

    while (current.isSameOrBefore(end, 'day')) {
        const dayOfWeek = current.day()
        if (dayOfWeek !== 0 && dayOfWeek !== 6 && !isHoliday(current)) {
            workDays += 1
        }
        current = current.add(1, 'day')
    }

    return workDays
}

// Aktualisiere die Tage für einen bestimmten Zeitraum
const updatePeriodDays = (index) => {
    const period = vacationPeriods.value[index]

    // Wenn es kein einzelner Tag ist, setze dayType auf full_day
    if (!isSingleDay(period)) {
        period.dayType = 'full_day'
    }

    period.days = getWorkDays(period.startDate, period.endDate, period.dayType)
}

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

// Gesamtzahl der beantragten Urlaubstage
const totalRequestedDays = computed(() => {
    return vacationPeriods.value.reduce((total, period) => total + period.days, 0)
})

// Verbleibende Tage nach dem Eintrag
const remainingAfterEntry = computed(() => {
    if (!selectedEmployee.value) return 0
    return selectedEmployee.value.remaining_days - totalRequestedDays.value
})

// Feiertage in den ausgewählten Zeiträumen
const holidaysInPeriods = computed(() => {
    const holidaysList = []

    vacationPeriods.value.forEach(period => {
        if (period.startDate && period.endDate) {
            let current = dayjs(period.startDate)
            const end = dayjs(period.endDate)

            if (!current.isValid() || !end.isValid()) return

            while (current.isSameOrBefore(end, "day")) {
                if (isHoliday(current)) {
                    const holidayName = getHolidayName(current)
                    if (holidayName) {
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
        vacationPeriods.value.length < 5 &&
        vacationPeriods.value.every((period) => period.startDate && period.endDate && period.days >= 0)
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
    }
}

// Formatierungsfunktion für Datum
const formatDate = (date) => {
    if (!date) return "-"
    const djsDate = dayjs(date)
    if (!djsDate.isValid()) return "-"
    return djsDate.format("DD.MM.YYYY")
}

// Formularvalidierung
const isFormValid = computed(() => {
    // Prüfe, ob ein Mitarbeiter ausgewählt wurde
    if (!selectedEmployee.value) return false

    // Prüfe, ob alle Zeiträume gültig sind
    const allPeriodsValid = vacationPeriods.value.every((period) =>
        period.startDate && period.endDate && period.days > 0
    )

    // Prüfe, ob ein Grund angegeben wurde
    const hasReason = hrEntry.value.reason.trim().length > 0

    // Prüfe, ob genügend Urlaubstage verfügbar sind
    const enoughDaysAvailable = totalRequestedDays.value <= selectedEmployee.value.remaining_days

    // Prüfe, ob mindestens ein Tag beantragt wurde
    const atLeastOneDayRequested = totalRequestedDays.value > 0

    return allPeriodsValid && hasReason && enoughDaysAvailable && atLeastOneDayRequested
})

// Urlaubsantrag für Mitarbeiter absenden
const submitHrVacationEntry = async () => {
    console.log('  submitHrVacationEntry aufgerufen')

    // Validierung zurücksetzen
    errors.value = {
        employee: "",
        reason: "",
    }

    vacationPeriods.value.forEach((period) => {
        period.errors.startDate = ""
        period.errors.endDate = ""
    })

    // Validierung durchführen
    let isValid = true

    if (!selectedEmployee.value) {
        errors.value.employee = "Bitte wählen Sie einen Mitarbeiter aus."
        isValid = false
    }

    if (!hrEntry.value.reason.trim()) {
        errors.value.reason = "Bitte geben Sie einen Grund für den nachträglichen Eintrag an."
        isValid = false
    }

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
        console.log('  Validierung fehlgeschlagen')
        toast.add({
            severity: "error",
            summary: "Fehler",
            detail: "Bitte füllen Sie alle erforderlichen Felder aus.",
            life: 3000,
        })
        return
    }

    if (totalRequestedDays.value <= 0) {
        console.log('  Keine Urlaubstage ausgewählt')
        toast.add({
            severity: "error",
            summary: "Fehler",
            detail: "Bitte wählen Sie mindestens einen Urlaubstag aus.",
            life: 3000,
        })
        return
    }

    if (totalRequestedDays.value > selectedEmployee.value.remaining_days) {
        console.log('  Nicht genügend Urlaubstage verfügbar', {
            requested: totalRequestedDays.value,
            available: selectedEmployee.value.remaining_days
        })
        toast.add({
            severity: "error",
            summary: "Fehler",
            detail: `Der Mitarbeiter hat nicht genügend Urlaubstage. Benötigt: ${totalRequestedDays.value} Tage, Verfügbar: ${selectedEmployee.value.remaining_days} Tage.`,
            life: 5000,
        })
        return
    }

    loading.value = true

    try {
        const requestData = {
            employee_id: selectedEmployee.value.id,
            periods: vacationPeriods.value.map((period) => ({
                startDate: period.startDate ? dayjs(period.startDate).format("YYYY-MM-DD") : null,
                endDate: period.endDate ? dayjs(period.endDate).format("YYYY-MM-DD") : null,
                days: period.days,
                dayType: period.dayType,
            })),
            reason: hrEntry.value.reason,
            notes: hrEntry.value.notes,
        }

        console.log('  Sende Request an Server:', requestData)

        router.post('/vacation/hr/store-for-employee', requestData, {
            preserveScroll: true,
            onSuccess: (page) => {
                console.log('  Urlaub erfolgreich eingetragen', page)
                toast.add({
                    severity: "success",
                    summary: "Erfolg",
                    detail: `Urlaub wurde erfolgreich für ${selectedEmployee.value.name} eingetragen.`,
                    life: 3000,
                })

                // Formular zurücksetzen
                selectedEmployee.value = null
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
                hrEntry.value = {
                    reason: "",
                    notes: "",
                }

                emit("submitted")
                emit("vacation-created")
            },
            onError: (errors) => {
                console.error("  Fehler vom Server:", errors)
                let errorMessage = "Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut."
                if (errors && errors.error) {
                    errorMessage = errors.error
                } else if (errors && typeof errors === 'object') {
                    // Zeige alle Validierungsfehler an
                    errorMessage = Object.values(errors).flat().join(', ')
                }
                toast.add({
                    severity: "error",
                    summary: "Fehler",
                    detail: errorMessage,
                    life: 5000,
                })
            },
            onFinish: () => {
                console.log('  Request abgeschlossen')
                loading.value = false
            }
        })
    } catch (error) {
        console.error("  Exception beim Eintragen des Urlaubs:", error)
        toast.add({
            severity: "error",
            summary: "Fehler",
            detail: "Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.",
            life: 5000,
        })
        loading.value = false
    }
}

// Beim Mounten Mitarbeiter laden
onMounted(() => {
    // Hier könnte Code stehen, um Mitarbeiter zu laden, falls erforderlich
})
</script>

<style scoped>
.hr-vacation-entry-container {
    max-width: 1200px;
    margin: 0 auto;
}

.vacation-period {
    transition: all 0.3s ease;
}

.vacation-period:hover {
    background-color: rgba(59, 130, 246, 0.05);
}

.summary-table {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
}

@media (max-width: 768px) {
    .summary-table .grid > div {
        font-size: 0.875rem;
    }
}
</style>
