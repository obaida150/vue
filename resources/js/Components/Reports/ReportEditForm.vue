<template>
    <div class="space-y-6">
        <form @submit.prevent="updateReport" class="space-y-6">
            <!-- Grunddaten -->
            <Panel header="Grunddaten" :toggleable="true">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="field">
                        <label for="edit_year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Lehrjahr *
                        </label>
                        <Select
                            id="edit_year"
                            v-model="form.year"
                            :options="yearOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Lehrjahr wählen"
                            class="w-full"
                            :class="{ 'p-invalid': errors.year }"
                            @change="onYearChange"
                        />
                        <small v-if="errors.year" class="p-error">{{ errors.year }}</small>
                    </div>

                    <div class="field" v-if="!isTeam15">
                        <label for="edit_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Berichtart *
                        </label>
                        <Select
                            id="edit_type"
                            v-model="form.type"
                            :options="typeOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Berichtart wählen"
                            class="w-full"
                            :class="{ 'p-invalid': errors.type }"
                            @change="onTypeChange"
                        />
                        <small v-if="errors.type" class="p-error">{{ errors.type }}</small>
                    </div>

                    <div class="field">
                        <label for="edit_berichtsnummer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Berichtsnummer *
                        </label>
                        <InputNumber
                            id="edit_berichtsnummer"
                            v-model="form.berichtsnummer"
                            :useGrouping="false"
                            placeholder="z.B. 1"
                            class="w-full"
                            :class="{ 'p-invalid': errors.berichtsnummer }"
                        />
                        <small v-if="errors.berichtsnummer" class="p-error">{{ errors.berichtsnummer }}</small>
                    </div>

                    <div class="field" v-if="isTeam15">
                        <label for="edit_abteilung" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Abteilung *
                        </label>
                        <InputText
                            id="edit_abteilung"
                            v-model="form.abteilung"
                            placeholder="Abteilung eingeben"
                            class="w-full"
                            :class="{ 'p-invalid': errors.abteilung }"
                        />
                        <small v-if="errors.abteilung" class="p-error">{{ errors.abteilung }}</small>
                    </div>

                    <div class="field">
                        <label for="edit_date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Von *
                        </label>
                        <DatePicker
                            id="edit_date_from"
                            v-model="form.date_from"
                            dateFormat="dd.mm.yy"
                            placeholder="Startdatum"
                            class="w-full"
                            :class="{ 'p-invalid': errors.date_from }"
                        />
                        <small v-if="errors.date_from" class="p-error">{{ errors.date_from }}</small>
                    </div>

                    <div class="field">
                        <label for="edit_date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Bis *
                        </label>
                        <DatePicker
                            id="edit_date_to"
                            v-model="form.date_to"
                            dateFormat="dd.mm.yy"
                            placeholder="Enddatum"
                            class="w-full"
                            :class="{ 'p-invalid': errors.date_to }"
                        />
                        <small v-if="errors.date_to" class="p-error">{{ errors.date_to }}</small>
                    </div>

                    <div class="field md:col-span-2">
                        <label for="edit_instructor_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Ausbilder *
                        </label>
                        <Select
                            id="edit_instructor_id"
                            v-model="form.instructor_id"
                            :options="instructors"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Ausbilder wählen"
                            class="w-full"
                            :class="{ 'p-invalid': errors.instructor_id }"
                            filter
                        />
                        <small v-if="errors.instructor_id" class="p-error">{{ errors.instructor_id }}</small>
                    </div>
                </div>
            </Panel>

            <!-- Berufsschule Fächer -->
            <Panel v-if="form.type === 'Berufsschule'" header="Berufsschulfächer" :toggleable="true">
                <div v-if="subjects.length === 0" class="text-center py-8">
                    <i class="pi pi-info-circle text-4xl text-blue-400 mb-4"></i>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        Keine Fächer für das {{ form.year }}. Lehrjahr gefunden
                    </p>
                    <Button
                        label="Fächer verwalten"
                        icon="pi pi-cog"
                        @click="$inertia.visit('/subjects')"
                        class="p-button-secondary"
                    />
                </div>
                <div v-else class="space-y-4">
                    <div v-for="subject in subjects" :key="subject.id" class="field">
                        <label :for="`edit_subject_${subject.id}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ subject.name }} ({{ subject.hours }} Stunden)
                        </label>
                        <Textarea
                            :id="`edit_subject_${subject.id}`"
                            v-model="form.subjects_data[subject.id]"
                            rows="4"
                            :placeholder="`Inhalte für ${subject.name} beschreiben...`"
                            class="w-full"
                        />
                        <small class="text-gray-500">{{ subject.description }}</small>
                    </div>
                </div>
            </Panel>

            <!-- Betrieb Inhalte -->
            <Panel v-if="form.type === 'Betrieb'" header="Betriebliche Inhalte" :toggleable="true">
                <div class="space-y-4">
                    <div class="field">
                        <label for="edit_activities" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Betriebliche Tätigkeiten *
                        </label>
                        <Textarea
                            id="edit_activities"
                            v-model="form.activities"
                            rows="6"
                            placeholder="Beschreiben Sie Ihre Tätigkeiten im Betrieb..."
                            class="w-full"
                            :class="{ 'p-invalid': errors.activities }"
                        />
                        <small v-if="errors.activities" class="p-error">{{ errors.activities }}</small>
                    </div>

                    <div class="field">
                        <label for="edit_unterweisungen" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Unterweisungen, betrieblicher Unterricht, sonstige Schulungen *
                        </label>
                        <Textarea
                            id="edit_unterweisungen"
                            v-model="form.unterweisungen"
                            rows="4"
                            placeholder="Beschreiben Sie Unterweisungen und Schulungen..."
                            class="w-full"
                            :class="{ 'p-invalid': errors.unterweisungen }"
                        />
                        <small v-if="errors.unterweisungen" class="p-error">{{ errors.unterweisungen }}</small>
                    </div>

                    <div v-if="isTeam15" class="field">
                        <label for="edit_unterricht" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Berufsschule (Unterrichtsthemen)
                        </label>
                        <Textarea
                            id="edit_unterricht"
                            v-model="form.unterricht"
                            rows="4"
                            placeholder="Beschreiben Sie die Unterrichtsthemen..."
                            class="w-full"
                        />
                    </div>
                </div>
            </Panel>

            <!-- Actions -->
            <div class="flex justify-end gap-2">
                <Button
                    label="Abbrechen"
                    icon="pi pi-times"
                    class="p-button-secondary"
                    @click="$emit('cancelled')"
                />
                <Button
                    label="Aktualisieren"
                    icon="pi pi-check"
                    type="submit"
                    :loading="saving"
                />
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Button from 'primevue/button'
import Panel from 'primevue/panel'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import DatePicker from 'primevue/datepicker'
import Textarea from 'primevue/textarea'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

const props = defineProps({
    report: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['saved', 'cancelled'])
const toast = useToast()
const page = usePage()

// Data
const saving = ref(false)
const instructors = ref([])
const subjects = ref([])
const errors = ref({})

const form = ref({
    type: '',
    year: null,
    berichtsnummer: null,
    date_from: null,
    date_to: null,
    instructor_id: null,
    subjects_data: {},
    activities: '',
    unterweisungen: '',
    unterricht: '',
    abteilung: ''
})

// Computed
const isTeam15 = computed(() => {
    return page.props.auth?.user?.current_team_id === 15
})

const yearOptions = [
    { label: '1. Lehrjahr', value: 1 },
    { label: '2. Lehrjahr', value: 2 },
    { label: '3. Lehrjahr', value: 3 }
]

const typeOptions = [
    { label: 'Betrieb', value: 'Betrieb' },
    { label: 'Berufsschule', value: 'Berufsschule' }
]

// Methods
const loadInstructors = async () => {
    try {
        const response = await axios.get('/api/instructors')
        instructors.value = response.data
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Ausbilder konnten nicht geladen werden',
            life: 3000
        })
    }
}

const loadSubjects = async () => {
    if (!form.value.year) return

    try {
        const response = await axios.get(`/api/subjects/year/${form.value.year}`)
        subjects.value = response.data

        // Initialisiere subjects_data für alle Fächer, falls noch nicht vorhanden
        subjects.value.forEach(subject => {
            if (!(subject.id in form.value.subjects_data)) {
                form.value.subjects_data[subject.id] = ''
            }
        })
    } catch (error) {
        console.error('Error loading subjects:', error)
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Fächer konnten nicht geladen werden',
            life: 3000
        })
    }
}

const onYearChange = () => {
    if (form.value.type === 'Berufsschule') {
        loadSubjects()
    }
}

const onTypeChange = () => {
    if (form.value.type === 'Berufsschule' && form.value.year) {
        loadSubjects()
    }
}

const updateReport = async () => {
    saving.value = true
    errors.value = {}

    try {
        const payload = { ...form.value }

        // Convert dates to proper format
        if (payload.date_from) {
            payload.date_from = new Date(payload.date_from).toISOString().split('T')[0]
        }
        if (payload.date_to) {
            payload.date_to = new Date(payload.date_to).toISOString().split('T')[0]
        }

        await axios.put(`/api/reports/${props.report.id}`, payload)
        emit('saved')
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors
        } else {
            toast.add({
                severity: 'error',
                summary: 'Fehler',
                detail: 'Bericht konnte nicht aktualisiert werden',
                life: 3000
            })
        }
    } finally {
        saving.value = false
    }
}

const initializeForm = async () => {

    form.value = {
        type: props.report.type || '',
        year: props.report.year || null,
        berichtsnummer: props.report.berichtsnummer || null,
        date_from: props.report.date_from ? new Date(props.report.date_from) : null,
        date_to: props.report.date_to ? new Date(props.report.date_to) : null,
        instructor_id: props.report.instructor_id || null,
        subjects_data: {},
        activities: props.report.activities || '',
        unterweisungen: props.report.unterweisungen || '',
        unterricht: props.report.unterricht || '',
        abteilung: props.report.abteilung || ''
    }

    // Initialisiere subjects_data aus dem Report
    if (props.report.subjects_data && typeof props.report.subjects_data === 'object') {
        // Wenn subjects_data ein Array von Objekten ist (aus der Datenbank)
        if (Array.isArray(props.report.subjects_data)) {
            props.report.subjects_data.forEach(subjectData => {
                if (subjectData.id && subjectData.content) {
                    form.value.subjects_data[subjectData.id] = subjectData.content
                }
            })
        } else {
            // Wenn subjects_data ein Objekt ist (direkte Zuordnung)
            form.value.subjects_data = { ...props.report.subjects_data }
        }
    }

    if (form.value.type === 'Berufsschule' && form.value.year) {
        await loadSubjects()
    }
}

// Lifecycle
onMounted(() => {
    loadInstructors()
    initializeForm()
})

// Watch for prop changes
watch(() => props.report, () => {
    initializeForm()
}, { deep: true })
</script>
