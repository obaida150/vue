<template>
    <div class="vacation-request-form">
        <form @submit.prevent="submitRequest" class="space-y-6">
            <!-- Zeitraum Auswahl -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="field">
                    <label for="startDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Startdatum <span class="text-red-500">*</span>
                    </label>
                    <DatePicker
                        id="startDate"
                        v-model="form.startDate"
                        dateFormat="dd.mm.yy"
                        :minDate="minDate"
                        :maxDate="maxDate"
                        :disabledDates="disabledDates"
                        :disabledDays="disabledDays"
                        :locale="de"
                        :showIcon="true"
                        :showButtonBar="true"
                        placeholder="Startdatum wählen"
                        class="w-full"
                        @date-select="onStartDateChange"
                        :class="{ 'p-invalid': errors.startDate }"
                    />
                    <small v-if="errors.startDate" class="p-error">{{ errors.startDate }}</small>
                </div>

                <div class="field">
                    <label for="endDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Enddatum <span class="text-red-500">*</span>
                    </label>
                    <DatePicker
                        id="endDate"
                        v-model="form.endDate"
                        dateFormat="dd.mm.yy"
                        :minDate="form.startDate || minDate"
                        :maxDate="maxDate"
                        :disabledDates="disabledDates"
                        :disabledDays="disabledDays"
                        :locale="de"
                        :showIcon="true"
                        :showButtonBar="true"
                        placeholder="Enddatum wählen"
                        class="w-full"
                        @date-select="onEndDateChange"
                        :class="{ 'p-invalid': errors.endDate }"
                    />
                    <small v-if="errors.endDate" class="p-error">{{ errors.endDate }}</small>
                </div>
            </div>

            <!-- Urlaubstyp -->
            <div class="field">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Urlaubstyp <span class="text-red-500">*</span>
                </label>
                <div class="flex flex-wrap gap-3">
                    <div class="flex align-items-center">
                        <RadioButton
                            id="fullDay"
                            v-model="form.dayType"
                            name="dayType"
                            value="full_day"
                            @change="calculateDays"
                        />
                        <label for="fullDay" class="ml-2">Ganzer Tag</label>
                    </div>
                    <div v-if="isSingleDay" class="flex align-items-center">
                        <RadioButton
                            id="morning"
                            v-model="form.dayType"
                            name="dayType"
                            value="morning"
                            @change="calculateDays"
                        />
                        <label for="morning" class="ml-2">Vormittag</label>
                    </div>
                    <div v-if="isSingleDay" class="flex align-items-center">
                        <RadioButton
                            id="afternoon"
                            v-model="form.dayType"
                            name="dayType"
                            value="afternoon"
                            @change="calculateDays"
                        />
                        <label for="afternoon" class="ml-2">Nachmittag</label>
                    </div>
                </div>
                <small v-if="errors.dayType" class="p-error">{{ errors.dayType }}</small>
            </div>

            <!-- Berechnete Tage Anzeige -->
            <div v-if="calculatedDays > 0" class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300">
                    <i class="pi pi-info-circle"></i>
                    <span class="font-medium">Berechnete Urlaubstage: {{ calculatedDays }}</span>
                </div>
                <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                    {{ form.dayType === 'full_day' ? 'Ganze Tage' : 
                       form.dayType === 'morning' ? 'Vormittag (0,5 Tage)' : 
                       'Nachmittag (0,5 Tage)' }}
                </p>
            </div>

            <!-- Warnung bei bereits gebuchten Tagen -->
            <div v-if="hasConflicts" class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
                    <i class="pi pi-exclamation-triangle"></i>
                    <span class="font-medium">Achtung: Überschneidung mit bestehenden Anträgen!</span>
                </div>
                <p class="text-sm text-red-600 dark:text-red-400 mt-1">
                    Für den gewählten Zeitraum existieren bereits Urlaubsanträge. Bitte wählen Sie andere Daten.
                </p>
            </div>

            <!-- Vertretung -->
            <div class="field">
                <label for="substitute" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Vertretung
                </label>
                <Select
                    id="substitute"
                    v-model="form.substituteId"
                    :options="availableSubstitutes"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Vertretung auswählen (optional)"
                    class="w-full"
                    :showClear="true"
                />
                <small class="text-gray-500 dark:text-gray-400">Optional: Wählen Sie eine Vertretung für Ihre Abwesenheit</small>
            </div>

            <!-- Anmerkungen -->
            <div class="field">
                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Anmerkungen
                </label>
                <Textarea
                    id="notes"
                    v-model="form.notes"
                    rows="3"
                    autoResize
                    class="w-full"
                    placeholder="Zusätzliche Informationen zu Ihrem Urlaubsantrag..."
                    maxlength="1000"
                />
                <small class="text-gray-500 dark:text-gray-400">{{ form.notes?.length || 0 }}/1000 Zeichen</small>
            </div>

            <!-- Verfügbare Urlaubstage Info -->
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Ihr Urlaubskontingent</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500 dark:text-gray-400">Verfügbar</div>
                        <div class="font-medium">{{ availableDays }} Tage</div>
                    </div>
                    <div>
                        <div class="text-gray-500 dark:text-gray-400">Davon Übertrag</div>
                        <div class="font-medium text-green-600 dark:text-green-400">{{ carryOverDays }} Tage</div>
                    </div>
                    <div>
                        <div class="text-gray-500 dark:text-gray-400">Nach Antrag</div>
                        <div class="font-medium" :class="remainingAfterRequest >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                            {{ remainingAfterRequest }} Tage
                        </div>
                    </div>
                    <div v-if="carryOverExpires">
                        <div class="text-gray-500 dark:text-gray-400">Übertrag verfällt</div>
                        <div class="font-medium text-orange-600 dark:text-orange-400">{{ formatDate(carryOverExpires) }}</div>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <Button
                    label="Abbrechen"
                    icon="pi pi-times"
                    class="p-button-text"
                    @click="$emit('cancel')"
                    type="button"
                />
                <Button
                    label="Antrag einreichen"
                    icon="pi pi-check"
                    class="p-button-success"
                    @click="submitRequest"
                    :loading="submitting"
                    :disabled="!isFormValid || hasConflicts"
                    type="submit"
                />
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import DatePicker from 'primevue/datepicker'
import RadioButton from 'primevue/radiobutton'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'
import dayjs from 'dayjs'
import 'dayjs/locale/de'
import VacationService from '@/Services/VacationService'
import HolidayService from '@/Services/holiday-service'

dayjs.locale('de')

// Props
const props = defineProps({
    availableDays: {
        type: Number,
        default: 0
    },
    carryOverDays: {
        type: Number,
        default: 0
    },
    carryOverExpires: {
        type: String,
        default: null
    }
})

// Emits
const emit = defineEmits(['cancel', 'submitted'])

// Composables
const toast = useToast()

// Deutsche Lokalisierung für DatePicker
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
    dateFormat: "dd.mm.yy"
}

// Reactive data
const submitting = ref(false)
const bookedDates = ref([])
const holidays = ref([])
const availableSubstitutes = ref([])

const form = ref({
    startDate: null,
    endDate: null,
    dayType: 'full_day',
    substituteId: null,
    notes: ''
})

const errors = ref({})

// Computed properties
const minDate = computed(() => {
    return new Date() // Heute oder später
})

const maxDate = computed(() => {
    const nextYear = new Date()
    nextYear.setFullYear(nextYear.getFullYear() + 1)
    return nextYear
})

const isSingleDay = computed(() => {
    if (!form.value.startDate || !form.value.endDate) return false
    return dayjs(form.value.startDate).isSame(dayjs(form.value.endDate), 'day')
})

const calculatedDays = computed(() => {
    if (!form.value.startDate || !form.value.endDate) return 0
    
    const start = dayjs(form.value.startDate)
    const end = dayjs(form.value.endDate)
    const daysDiff = end.diff(start, 'day') + 1
    
    // Bei Halbtagen und nur einem Tag
    if (daysDiff === 1 && form.value.dayType !== 'full_day') {
        return 0.5
    }
    
    return daysDiff
})

const remainingAfterRequest = computed(() => {
    return props.availableDays - calculatedDays.value
})

// Deaktivierte Tage (Wochenenden)
const disabledDays = ref([0, 6]) // Sonntag und Samstag

// Deaktivierte Daten (bereits gebucht + Feiertage)
const disabledDates = computed(() => {
    const disabled = []
    
    // Bereits gebuchte Tage hinzufügen
    bookedDates.value.forEach(booking => {
        disabled.push(new Date(booking.date))
    })
    
    // Feiertage hinzufügen
    holidays.value.forEach(holiday => {
        disabled.push(holiday.date.toDate())
    })
    
    return disabled
})

// Prüfe auf Konflikte mit bestehenden Anträgen
const hasConflicts = computed(() => {
    if (!form.value.startDate || !form.value.endDate) return false
    
    const start = dayjs(form.value.startDate)
    const end = dayjs(form.value.endDate)
    
    return bookedDates.value.some(booking => {
        const bookingDate = dayjs(booking.date)
        return bookingDate.isBetween(start, end, 'day', '[]')
    })
})

const isFormValid = computed(() => {
    return form.value.startDate && 
           form.value.endDate && 
           form.value.dayType && 
           calculatedDays.value > 0 &&
           remainingAfterRequest.value >= 0 &&
           !hasConflicts.value
})

// Methods
const formatDate = (date) => {
    return dayjs(date).format('DD.MM.YYYY')
}

const onStartDateChange = () => {
    // Wenn Enddatum vor Startdatum liegt, setze es zurück
    if (form.value.endDate && dayjs(form.value.endDate).isBefore(dayjs(form.value.startDate))) {
        form.value.endDate = null
    }
    calculateDays()
    validateForm()
}

const onEndDateChange = () => {
    calculateDays()
    validateForm()
}

const calculateDays = () => {
    // Wird automatisch durch computed property berechnet
    validateForm()
}

const validateForm = () => {
    errors.value = {}
    
    if (!form.value.startDate) {
        errors.value.startDate = 'Startdatum ist erforderlich'
    }
    
    if (!form.value.endDate) {
        errors.value.endDate = 'Enddatum ist erforderlich'
    }
    
    if (form.value.startDate && form.value.endDate && dayjs(form.value.endDate).isBefore(dayjs(form.value.startDate))) {
        errors.value.endDate = 'Enddatum muss nach dem Startdatum liegen'
    }
    
    if (!form.value.dayType) {
        errors.value.dayType = 'Urlaubstyp ist erforderlich'
    }
    
    if (calculatedDays.value > props.availableDays) {
        errors.value.startDate = `Nicht genügend Urlaubstage verfügbar (${calculatedDays.value} benötigt, ${props.availableDays} verfügbar)`
    }
    
    if (hasConflicts.value) {
        errors.value.startDate = 'Überschneidung mit bestehenden Urlaubsanträgen'
    }
}

const submitRequest = async () => {
    validateForm()
    
    if (!isFormValid.value || Object.keys(errors.value).length > 0) {
        toast.add({
            severity: 'warn',
            summary: 'Formular unvollständig',
            detail: 'Bitte überprüfen Sie Ihre Eingaben.',
            life: 3000
        })
        return
    }
    
    submitting.value = true
    
    try {
        const requestData = {
            start_date: dayjs(form.value.startDate).format('YYYY-MM-DD'),
            end_date: dayjs(form.value.endDate).format('YYYY-MM-DD'),
            day_type: form.value.dayType,
            substitute_id: form.value.substituteId,
            notes: form.value.notes
        }
        
        await VacationService.submitVacationRequest(requestData)
        
        toast.add({
            severity: 'success',
            summary: 'Erfolgreich eingereicht',
            detail: 'Ihr Urlaubsantrag wurde erfolgreich eingereicht.',
            life: 3000
        })
        
        emit('submitted')
        
    } catch (error) {
        console.error('Fehler beim Einreichen des Urlaubsantrags:', error)
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: error.message || 'Fehler beim Einreichen des Urlaubsantrags.',
            life: 5000
        })
    } finally {
        submitting.value = false
    }
}

const loadBookedDates = async () => {
    try {
        const currentYear = new Date().getFullYear()
        bookedDates.value = await VacationService.getBookedDates(currentYear)
    } catch (error) {
        console.error('Fehler beim Laden der gebuchten Tage:', error)
    }
}

const loadHolidays = async () => {
    try {
        const currentYear = new Date().getFullYear()
        holidays.value = await HolidayService.getHolidays(currentYear)
    } catch (error) {
        console.error('Fehler beim Laden der Feiertage:', error)
    }
}

const loadSubstitutes = async () => {
    try {
        // Hier würdest du eine API-Methode aufrufen, um verfügbare Vertretungen zu laden
        // Für jetzt verwenden wir Beispieldaten
        availableSubstitutes.value = [
            { id: 1, name: 'Max Mustermann' },
            { id: 2, name: 'Anna Schmidt' },
            { id: 3, name: 'Peter Weber' }
        ]
    } catch (error) {
        console.error('Fehler beim Laden der Vertretungen:', error)
    }
}

// Watchers
watch(() => form.value.dayType, () => {
    if (!isSingleDay.value && form.value.dayType !== 'full_day') {
        form.value.dayType = 'full_day'
    }
})

// Lifecycle
onMounted(() => {
    loadBookedDates()
    loadHolidays()
    loadSubstitutes()
})
</script>

<style scoped>
.vacation-request-form {
    max-width: 800px;
    margin: 0 auto;
}

.field {
    margin-bottom: 1rem;
}

:deep(.p-datepicker) {
    width: 100%;
}

:deep(.p-calendar) {
    width: 100%;
}

:deep(.p-calendar .p-inputtext) {
    width: 100%;
}

:deep(.p-radiobutton) {
    margin-right: 0.5rem;
}

:deep(.p-invalid) {
    border-color: #e24c4c;
}

.p-error {
    color: #e24c4c;
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: block;
}
</style>
