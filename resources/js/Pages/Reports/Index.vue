<template>
    <AppLayout title="Berichtsheft - Übersicht">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Berichtsheft - Übersicht
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <!-- Header mit Aktionen -->
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Meine Berichte
                            </h3>
                            <div class="flex gap-3">
                                <Button
                                    label="Neuer Bericht"
                                    icon="pi pi-plus"
                                    @click="$inertia.visit('/reports/create')"
                                    class="p-button-success"
                                />
                                <Button
                                    label="Fächer verwalten"
                                    icon="pi pi-cog"
                                    @click="$inertia.visit('/subjects')"
                                    class="p-button-secondary"
                                />
                            </div>
                        </div>

                        <!-- Filter -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="field">
                                <label for="yearFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Lehrjahr
                                </label>
                                <Select
                                    id="yearFilter"
                                    v-model="filters.year"
                                    :options="yearOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Alle Lehrjahre"
                                    class="w-full"
                                    @change="loadReports"
                                />
                            </div>
                            <div class="field">
                                <label for="typeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Berichtart
                                </label>
                                <Select
                                    id="typeFilter"
                                    v-model="filters.type"
                                    :options="typeOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Alle Arten"
                                    class="w-full"
                                    @change="loadReports"
                                />
                            </div>
                            <div class="field">
                                <label for="searchFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Suche
                                </label>
                                <InputText
                                    id="searchFilter"
                                    v-model="filters.search"
                                    placeholder="Berichtsnummer oder Ausbilder..."
                                    class="w-full"
                                    @input="debounceSearch"
                                />
                            </div>
                        </div>

                        <!-- Berichte Tabelle -->
                        <DataTable
                            :value="filteredReports"
                            :loading="loading"
                            :paginator="true"
                            :rows="10"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                            currentPageReportTemplate="{first} bis {last} von {totalRecords} Berichten"
                            responsiveLayout="scroll"
                            class="p-datatable-sm"
                        >
                            <template #empty>
                                <div class="text-center py-8">
                                    <i class="pi pi-file text-4xl text-gray-400 mb-4"></i>
                                    <p class="text-gray-500 dark:text-gray-400">Keine Berichte gefunden</p>
                                    <Button
                                        label="Ersten Bericht erstellen"
                                        icon="pi pi-plus"
                                        @click="$inertia.visit('/reports/create')"
                                        class="p-button-text mt-2"
                                    />
                                </div>
                            </template>

                            <Column field="berichtsnummer" header="Nr." sortable class="w-20">
                                <template #body="{ data }">
                                    <Tag :value="data.berichtsnummer" severity="info" />
                                </template>
                            </Column>

                            <Column field="type" header="Art" sortable class="w-32">
                                <template #body="{ data }">
                                    <Tag
                                        :value="data.type"
                                        :severity="data.type === 'Betrieb' ? 'success' : 'warning'"
                                    />
                                </template>
                            </Column>

                            <Column field="year" header="Lehrjahr" sortable class="w-24">
                                <template #body="{ data }">
                                    {{ data.year }}. LJ
                                </template>
                            </Column>

                            <Column header="Zeitraum" sortable class="w-48">
                                <template #body="{ data }">
                                    {{ formatDateRange(data.date_from, data.date_to) }}
                                </template>
                            </Column>

                            <Column field="instructor_name" header="Ausbilder" sortable>
                                <template #body="{ data }">
                                    <div class="flex items-center">
                                        <Avatar
                                            :label="getInitials(data.instructor_name)"
                                            class="mr-2"
                                            size="small"
                                            style="background-color: #2196F3; color: #ffffff"
                                        />
                                        {{ data.instructor_name }}
                                    </div>
                                </template>
                            </Column>

                            <Column field="created_at" header="Erstellt" sortable class="w-32">
                                <template #body="{ data }">
                                    {{ data.created_at }}
                                </template>
                            </Column>

                            <Column header="Aktionen" class="w-40">
                                <template #body="{ data }">
                                    <div class="flex gap-2">
                                        <Button
                                            icon="pi pi-file-pdf"
                                            class="p-button-rounded p-button-text p-button-danger"
                                            v-tooltip.top="'PDF herunterladen'"
                                            @click="downloadPdf(data.id)"
                                            :loading="downloadingPdf === data.id"
                                        />
                                        <Button
                                            icon="pi pi-pencil"
                                            class="p-button-rounded p-button-text p-button-warning"
                                            v-tooltip.top="'Bearbeiten'"
                                            @click="editReport(data.id)"
                                        />
                                        <Button
                                            icon="pi pi-trash"
                                            class="p-button-rounded p-button-text p-button-danger"
                                            v-tooltip.top="'Löschen'"
                                            @click="confirmDelete(data)"
                                        />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Dialog -->
        <Dialog
            v-model:visible="editDialog"
            :header="`Bericht ${editingReport?.berichtsnummer} bearbeiten`"
            :modal="true"
            class="p-fluid"
            :style="{ width: '90vw', maxWidth: '1200px' }"
            :maximizable="true"
        >
            <ReportEditForm
                v-if="editingReport"
                :report="editingReport"
                @saved="onReportSaved"
                @cancelled="editDialog = false"
            />
        </Dialog>

        <!-- Toast für Benachrichtigungen -->
        <Toast />

        <!-- Delete Confirmation -->
        <ConfirmDialog />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import ReportEditForm from '@/Components/Reports/ReportEditForm.vue'
import axios from 'axios'

const confirm = useConfirm()
const toast = useToast()

// Data
const reports = ref([])
const loading = ref(false)
const editDialog = ref(false)
const editingReport = ref(null)
const downloadingPdf = ref(null)

// Filters
const filters = ref({
    year: null,
    type: null,
    search: ''
})

const yearOptions = [
    { label: 'Alle Lehrjahre', value: null },
    { label: '1. Lehrjahr', value: 1 },
    { label: '2. Lehrjahr', value: 2 },
    { label: '3. Lehrjahr', value: 3 }
]

const typeOptions = [
    { label: 'Alle Arten', value: null },
    { label: 'Betrieb', value: 'Betrieb' },
    { label: 'Berufsschule', value: 'Berufsschule' }
]

// Computed
const filteredReports = computed(() => {
    let filtered = reports.value

    if (filters.value.year) {
        filtered = filtered.filter(report => report.year === filters.value.year)
    }

    if (filters.value.type) {
        filtered = filtered.filter(report => report.type === filters.value.type)
    }

    if (filters.value.search) {
        const search = filters.value.search.toLowerCase()
        filtered = filtered.filter(report =>
            report.berichtsnummer.toString().includes(search) ||
            report.instructor_name.toLowerCase().includes(search)
        )
    }

    return filtered
})

// Methods
const loadReports = async () => {
    loading.value = true
    try {
        const response = await axios.get('/api/reports')
        reports.value = response.data
    } catch (error) {
        console.error('Fehler beim Laden der Berichte:', error)
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Berichte konnten nicht geladen werden',
            life: 3000
        })
    } finally {
        loading.value = false
    }
}

const downloadPdf = async (reportId) => {
    downloadingPdf.value = reportId
    try {
        const response = await axios.get(`/api/reports/${reportId}/pdf`, {
            responseType: 'blob'
        })

        // Create download link
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `bericht_${reportId}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)

        toast.add({
            severity: 'success',
            summary: 'Erfolg',
            detail: 'PDF wurde heruntergeladen',
            life: 3000
        })
    } catch (error) {
        console.error('Fehler beim PDF-Download:', error)
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'PDF konnte nicht heruntergeladen werden',
            life: 3000
        })
    } finally {
        downloadingPdf.value = null
    }
}

const editReport = async (reportId) => {
    try {
        // Lade die vollständigen Berichtsdaten
        const response = await axios.get(`/api/reports/${reportId}`)
        editingReport.value = response.data
        editDialog.value = true
    } catch (error) {
        console.error('Fehler beim Laden des Berichts:', error)
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Bericht konnte nicht geladen werden',
            life: 3000
        })
    }
}

const confirmDelete = (report) => {
    confirm.require({
        message: `Möchten Sie den Bericht Nr. ${report.berichtsnummer} wirklich löschen?`,
        header: 'Löschen bestätigen',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Löschen',
        rejectLabel: 'Abbrechen',
        accept: () => deleteReport(report.id)
    })
}

const deleteReport = async (reportId) => {
    try {
        await axios.delete(`/api/reports/${reportId}`)
        reports.value = reports.value.filter(report => report.id !== reportId)
        toast.add({
            severity: 'success',
            summary: 'Erfolg',
            detail: 'Bericht wurde gelöscht',
            life: 3000
        })
    } catch (error) {
        console.error('Fehler beim Löschen:', error)
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Bericht konnte nicht gelöscht werden',
            life: 3000
        })
    }
}

const onReportSaved = () => {
    editDialog.value = false
    loadReports()
    toast.add({
        severity: 'success',
        summary: 'Erfolg',
        detail: 'Bericht wurde aktualisiert',
        life: 3000
    })
}

// Utility functions
const formatDateRange = (from, to) => {
    try {
        if (!from || !to) return 'Kein Datum'

        // Handle different date formats
        let fromDate, toDate

        if (typeof from === 'string') {
            fromDate = new Date(from)
        } else {
            fromDate = from
        }

        if (typeof to === 'string') {
            toDate = new Date(to)
        } else {
            toDate = to
        }

        // Check if dates are valid
        if (isNaN(fromDate.getTime()) || isNaN(toDate.getTime())) {
            return 'Ungültiges Datum'
        }

        const fromFormatted = fromDate.toLocaleDateString('de-DE')
        const toFormatted = toDate.toLocaleDateString('de-DE')
        return `${fromFormatted} - ${toFormatted}`
    } catch (error) {
        console.error('Date formatting error:', error, { from, to })
        return 'Datum-Fehler'
    }
}

const getInitials = (name) => {
    if (!name) return '??'
    return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

let searchTimeout
const debounceSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        // Search is reactive, no need to call anything
    }, 300)
}

// Lifecycle
onMounted(() => {
    loadReports()
})
</script>
