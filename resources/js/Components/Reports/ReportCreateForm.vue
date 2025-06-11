<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Bericht erstellen
            </h3>
            <div class="flex gap-2">
                <Button
                    label="Abbrechen"
                    icon="pi pi-times"
                    class="p-button-secondary"
                    @click="$emit('cancelled')"
                />
                <Button
                    label="Speichern"
                    icon="pi pi-check"
                    @click="saveReport"
                    :loading="saving"
                />
            </div>
        </div>

        <form @submit.prevent="saveReport" class="space-y-6">
            <!-- Grunddaten -->
            <Panel header="Grunddaten" :toggleable="true">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="field">
                        <label for="year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Lehrjahr *
                        </label>
                        <Select
                            id="year"
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
                        <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Berichtart *
                        </label>
                        <Select
                            id="type"
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
                        <label for="berichtsnummer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Berichtsnummer *
                        </label>
                        <InputNumber
                            id="berichtsnummer"
                            v-model="form.berichtsnummer"
                            :useGrouping="false"
                            placeholder="z.B. 1"
                            class="w-full"
                            :class="{ 'p-invalid': errors.berichtsnummer }"
                        />
                        <small v-if="errors.berichtsnummer" class="p-error">{{ errors.berichtsnummer }}</small>
                    </div>

                    <div class="field" v-if="isTeam15">
                        <label for="abteilung" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Abteilung *
                        </label>
                        <InputText
                            id="abteilung"
                            v-model="form.abteilung"
                            placeholder="Abteilung eingeben"
                            class="w-full"
                            :class="{ 'p-invalid': errors.abteilung }"
                        />
                        <small v-if="errors.abteilung" class="p-error">{{ errors.abteilung }}</small>
                    </div>

                    <div class="field">
                        <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Von *
                        </label>
                        <DatePicker
                            id="date_from"
                            v-model="form.date_from"
                            dateFormat="dd.mm.yy"
                            placeholder="Startdatum"
                            class="w-full"
                            :class="{ 'p-invalid': errors.date_from }"
                        />
                        <small v-if="errors.date_from" class="p-error">{{ errors.date_from }}</small>
                    </div>

                    <div class="field">
                        <label for="date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Bis *
                        </label>
                        <DatePicker
                            id="date_to"
                            v-model="form.date_to"
                            dateFormat="dd.mm.yy"
                            placeholder="Enddatum"
                            class="w-full"
                            :class="{ 'p-invalid': errors.date_to }"
                        />
                        <small v-if="errors.date_to" class="p-error">{{ errors.date_to }}</small>
                    </div>

                    <div class="field md:col-span-2">
                        <label for="instructor_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Ausbilder *
                        </label>
                        <Select
                            id="instructor_id"
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
                        class="p-button-text"
                        @click="$inertia.visit(route('subjects.index'))"
                    />
                </div>
                <div v-else class="space-y-4">
                    <div v-for="subject in subjects" :key="subject.id" class="field">
                        <label :for="`subject_${subject.id}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ subject.name }}
                        </label>
                        <Textarea
                            :id="`subject_${subject.id}`"
                            v-model="form.subjects_data[subject.id]"
                            rows="4"
                            :placeholder="`Inhalte für ${subject.name} beschreiben...`"
                            class="w-full"
                        />
                    </div>
                </div>
            </Panel>

            <!-- Betrieb Inhalte -->
            <Panel v-if="form.type === 'Betrieb'" header="Betriebliche Inhalte" :toggleable="true">
                <div class="space-y-4">
                    <div class="field">
                        <label for="activities" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Betriebliche Tätigkeiten *
                        </label>
                        <Textarea
                            id="activities"
                            v-model="form.activities"
                            rows="6"
                            placeholder="Beschreiben Sie Ihre Tätigkeiten im Betrieb..."
                            class="w-full"
                            :class="{ 'p-invalid': errors.activities }"
                        />
                        <small v-if="errors.activities" class="p-error">{{ errors.activities }}</small>
                    </div>

                    <div class="field">
                        <label for="unterweisungen" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Unterweisungen, betrieblicher Unterricht, sonstige Schulungen *
                        </label>
                        <Textarea
                            id="unterweisungen"
                            v-model="form.unterweisungen"
                            rows="4"
                            placeholder="Beschreiben Sie Unterweisungen und Schulungen..."
                            class="w-full"
                            :class="{ 'p-invalid': errors.unterweisungen }"
                        />
                        <small v-if="errors.unterweisungen" class="p-error">{{ errors.unterweisungen }}</small>
                    </div>

                    <div v-if="isTeam15" class="field">
                        <label for="unterricht" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Berufsschule (Unterrichtsthemen)
                        </label>
                        <Textarea
                            id="unterricht"
                            v-model="form.unterricht"
                            rows="4"
                            placeholder="Beschreiben Sie die Unterrichtsthemen..."
                            class="w-full"
                        />
                    </div>
                </div>
            </Panel>
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
import axios from 'axios' // Declare axios variable

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

        // Initialize subjects_data
        form.value.subjects_data = {}
        subjects.value.forEach(subject => {
            form.value.subjects_data[subject.id] = ''
        })
    } catch (error) {
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

const saveReport = async () => {
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

        await axios.post('/api/reports', payload)
        emit('saved')
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors
        } else {
            toast.add({
                severity: 'error',
                summary: 'Fehler',
                detail: 'Bericht konnte nicht gespeichert werden',
                life: 3000
            })
        }
    } finally {
        saving.value = false
    }
}

// Lifecycle
onMounted(() => {
    loadInstructors()

    // Set default type for team 15
    if (isTeam15.value) {
        form.value.type = 'Betrieb'
    }
})

// Watchers
watch(() => form.value.year, () => {
    if (form.value.type === 'Berufsschule') {
        loadSubjects()
    }
})
</script>
