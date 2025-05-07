<template>
    <AppLayout title="Meine Urlaubsübersicht">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Meine Urlaubsübersicht
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Urlaubskontingent Karte -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                        <div class="vacation-card">
                            <div class="vacation-card-title">Gesamtes Urlaubskontingent</div>
                            <div class="vacation-card-value">{{ vacationStats.total }} Tage</div>
                            <div v-if="vacationStats.carryOver > 0" class="vacation-card-subtitle text-green-600 dark:text-green-400">
                                inkl. {{ vacationStats.carryOver }} Resttage aus {{ previousYear }}
                            </div>
                        </div>

                        <div class="vacation-card">
                            <div class="vacation-card-title">Genommen</div>
                            <div class="vacation-card-value">{{ vacationStats.used }} Tage</div>
                        </div>

                        <div class="vacation-card">
                            <div class="vacation-card-title">Geplant</div>
                            <div class="vacation-card-value">{{ vacationStats.planned }} Tage</div>
                        </div>

                        <div class="vacation-card">
                            <div class="vacation-card-title">Verbleibend</div>
                            <div class="vacation-card-value">{{ vacationStats.remaining }} Tage</div>
                        </div>

                        <div class="vacation-card">
                            <div class="vacation-card-title">Übertragbar ins nächste Jahr</div>
                            <div class="vacation-card-value">{{ Math.min(vacationStats.remaining - vacationStats.planned, 10) }} Tage</div>
                            <div class="vacation-card-subtitle text-gray-500 dark:text-gray-400">max. 10 Tage</div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <ProgressBar :value="vacationUsagePercentage" :showValue="false" />
                        <div class="flex justify-content-between mt-2 text-sm">
                            <span>{{ vacationUsagePercentage }}% verbraucht</span>
                            <span>{{ 100 - vacationUsagePercentage }}% verfügbar</span>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-content-end">
                        <Button label="Neuen Urlaubsantrag stellen" icon="pi pi-plus" @click="showVacationRequestForm = true" />
                    </div>
                </div>

                <!-- Urlaubsanträge -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <Tabs>
                        <TabPanel header="Meine Urlaubsanträge">
                            <DataTable
                                :value="myVacationRequests"
                                :paginator="myVacationRequests.length > 10"
                                :rows="10"
                                :rowsPerPageOptions="[5, 10, 20]"
                                dataKey="id"
                                :rowHover="true"
                                responsiveLayout="scroll"
                                class="p-datatable-sm"
                                :loading="loading"
                                v-model:filters="filters"
                                filterDisplay="menu"
                                :globalFilterFields="['startDate', 'endDate', 'status', 'approvedBy', 'rejectedBy']"
                            >
                                <template #header>
                                    <div class="flex justify-content-between items-center">
                                        <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">Meine Urlaubsanträge</h3>
                                        <span class="p-input-icon-left">
                    <i class="pi pi-search" />
                    <InputText v-model="filters['global'].value" placeholder="Suchen..." class="p-inputtext-sm" />
                  </span>
                                    </div>
                                </template>

                                <Column field="startDate" header="Zeitraum" :sortable="true">
                                    <template #body="{ data }">
                                        {{ formatDate(data.startDate) }} - {{ formatDate(data.endDate) }}
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ data.days }} Tage</div>
                                    </template>
                                </Column>

                                <Column field="requestDate" header="Beantragt am" :sortable="true">
                                    <template #body="{ data }">
                                        {{ formatDate(data.requestDate) }}
                                    </template>
                                </Column>

                                <Column field="substitute.name" header="Vertretung" :sortable="true">
                                    <template #body="{ data }">
                                        <div v-if="data.substitute">
                                            {{ data.substitute.name }}
                                        </div>
                                        <div v-else class="text-gray-500 dark:text-gray-400">
                                            Keine Vertretung
                                        </div>
                                    </template>
                                </Column>

                                <Column field="status" header="Status" :sortable="true">
                                    <template #body="{ data }">
                                        <Tag
                                            :severity="getStatusSeverity(data.status)"
                                            :value="getStatusLabel(data.status)"
                                        />
                                        <div v-if="data.status === 'approved'" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            Genehmigt von {{ data.approvedBy }} am {{ formatDate(data.approvedDate) }}
                                        </div>
                                        <div v-if="data.status === 'rejected'" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            Abgelehnt von {{ data.rejectedBy }} am {{ formatDate(data.rejectedDate) }}
                                        </div>
                                    </template>
                                </Column>

                                <Column field="notes" header="Anmerkungen">
                                    <template #body="{ data }">
                                        <div v-if="data.notes" class="max-w-xs truncate" :title="data.notes">
                                            {{ data.notes }}
                                        </div>
                                        <div v-else-if="data.rejectionReason" class="max-w-xs truncate text-red-500" :title="data.rejectionReason">
                                            {{ data.rejectionReason }}
                                        </div>
                                        <div v-else class="text-gray-500 dark:text-gray-400">
                                            -
                                        </div>
                                    </template>
                                </Column>

                                <Column header="Aktionen" :exportable="false" style="min-width: 8rem">
                                    <template #body="{ data }">
                                        <div class="flex gap-2">
                                            <Button
                                                v-if="data.status === 'pending'"
                                                icon="pi pi-times"
                                                class="p-button-danger p-button-sm"
                                                @click="cancelRequest(data)"
                                                tooltip="Antrag zurückziehen"
                                            />
                                            <Button
                                                icon="pi pi-eye"
                                                class="p-button-secondary p-button-sm"
                                                @click="viewRequestDetails(data)"
                                                tooltip="Details anzeigen"
                                            />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </TabPanel>

                        <TabPanel header="Urlaubskalender">
                            <div class="vacation-calendar">
                                <FullCalendar
                                    :options="calendarOptions"
                                    class="vacation-calendar-widget"
                                />
                            </div>
                        </TabPanel>

                        <TabPanel header="Urlaubsstatistik">
                            <div class="p-4">
                                <h3 class="text-xl font-bold mb-4">Urlaubsstatistik</h3>

                                <div class="mb-6">
                                    <Dropdown v-model="selectedStatYear" :options="availableYears" optionLabel="name" optionValue="value" placeholder="Jahr auswählen" class="w-full sm:w-auto" @change="updateYearlyStats" />
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                                    <!-- Jahresübersicht -->
                                    <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow">
                                        <h4 class="text-lg font-semibold mb-3">Jahresübersicht {{ selectedStatYear }}</h4>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full bg-white dark:bg-gray-700 rounded-lg">
                                                <thead>
                                                <tr>
                                                    <th class="py-2 px-4 border-b text-left">Kategorie</th>
                                                    <th class="py-2 px-4 border-b text-right">Tage</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td class="py-2 px-4 border-b">Grundanspruch</td>
                                                    <td class="py-2 px-4 border-b text-right">{{ yearlyStats.baseEntitlement }}</td>
                                                </tr>
                                                <tr v-if="yearlyStats.carryOver > 0">
                                                    <td class="py-2 px-4 border-b">Übertrag aus Vorjahr</td>
                                                    <td class="py-2 px-4 border-b text-right">+ {{ yearlyStats.carryOver }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="py-2 px-4 border-b font-semibold">Gesamtanspruch</td>
                                                    <td class="py-2 px-4 border-b text-right font-semibold">{{ yearlyStats.totalEntitlement }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="py-2 px-4 border-b">Genommen</td>
                                                    <td class="py-2 px-4 border-b text-right">- {{ yearlyStats.used }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="py-2 px-4 border-b">Geplant</td>
                                                    <td class="py-2 px-4 border-b text-right">- {{ yearlyStats.planned }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="py-2 px-4 font-semibold">Verbleibend</td>
                                                    <td class="py-2 px-4 text-right font-semibold">{{ yearlyStats.remaining }}</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Urlaubsverteilung nach Monaten -->
                                    <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow">
                                        <h4 class="text-lg font-semibold mb-3">Urlaubsverteilung nach Monaten</h4>
                                        <div class="h-64">
                                            <Chart type="bar" :data="monthlyChartData" :options="chartOptions" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Urlaubshistorie -->
                                <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow mb-6">
                                    <h4 class="text-lg font-semibold mb-3">Urlaubshistorie</h4>
                                    <div class="overflow-x-auto">
                                        <DataTable :value="vacationHistory" stripedRows class="p-datatable-sm" responsiveLayout="scroll">
                                            <Column field="year" header="Jahr" :sortable="true"></Column>
                                            <Column field="totalEntitlement" header="Gesamtanspruch" :sortable="true">
                                                <template #body="slotProps">
                                                    {{ slotProps.data.totalEntitlement }} Tage
                                                    <span v-if="slotProps.data.carryOver > 0" class="text-sm text-green-600 dark:text-green-400">
                          (inkl. {{ slotProps.data.carryOver }} aus Vorjahr)
                        </span>
                                                </template>
                                            </Column>
                                            <Column field="used" header="Genommen" :sortable="true">
                                                <template #body="slotProps">
                                                    {{ slotProps.data.used }} Tage
                                                </template>
                                            </Column>
                                            <Column field="remaining" header="Übrig" :sortable="true">
                                                <template #body="slotProps">
                                                    {{ slotProps.data.remaining }} Tage
                                                </template>
                                            </Column>
                                            <Column field="carryOverToNextYear" header="Übertrag ins Folgejahr" :sortable="true">
                                                <template #body="slotProps">
                                                    {{ slotProps.data.carryOverToNextYear }} Tage
                                                </template>
                                            </Column>
                                        </DataTable>
                                    </div>
                                </div>

                                <!-- Urlaubsdetails -->
                                <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow">
                                    <h4 class="text-lg font-semibold mb-3">Urlaubsdetails {{ selectedStatYear }}</h4>
                                    <DataTable :value="yearVacationDetails" stripedRows class="p-datatable-sm" responsiveLayout="scroll"
                                               :paginator="yearVacationDetails.length > 10" :rows="10">
                                        <Column field="period" header="Zeitraum" :sortable="true"></Column>
                                        <Column field="days" header="Tage" :sortable="true"></Column>
                                        <Column field="status" header="Status" :sortable="true">
                                            <template #body="slotProps">
                                                <Tag :value="getStatusLabel(slotProps.data.status)" :severity="getStatusSeverity(slotProps.data.status)" />
                                            </template>
                                        </Column>
                                        <Column field="requestDate" header="Beantragt am" :sortable="true"></Column>
                                        <Column field="notes" header="Anmerkungen"></Column>
                                    </DataTable>
                                </div>
                            </div>
                        </TabPanel>
                    </Tabs>
                </div>
            </div>
        </div>

        <!-- Dialog für Urlaubsantrag -->
        <Dialog
            v-model:visible="showVacationRequestForm"
            header="Neuen Urlaubsantrag stellen"
            :style="{ width: '800px' }"
            :modal="true"
            :closable="true"
            :closeOnEscape="true"
        >
            <VacationRequest
                @cancel="showVacationRequestForm = false"
                @submitted="handleVacationRequestSubmitted"
            />
        </Dialog>

        <!-- Dialog für Antragsdetails -->
        <Dialog
            v-model:visible="showRequestDetails"
            :header="'Urlaubsantrag Details'"
            :style="{ width: '500px' }"
            :modal="true"
        >
            <div v-if="selectedRequest" class="request-details">
                <div class="grid">
                    <div class="col-12 md:col-6">
                        <div class="detail-label">Zeitraum:</div>
                        <div class="detail-value">{{ formatDate(selectedRequest.startDate) }} - {{ formatDate(selectedRequest.endDate) }}</div>
                    </div>
                    <div class="col-12 md:col-6">
                        <div class="detail-label">Anzahl Tage:</div>
                        <div class="detail-value">{{ selectedRequest.days }} Tage</div>
                    </div>
                    <div class="col-12 md:col-6">
                        <div class="detail-label">Beantragt am:</div>
                        <div class="detail-value">{{ formatDate(selectedRequest.requestDate) }}</div>
                    </div>
                    <div class="col-12 md:col-6">
                        <div class="detail-label">Status:</div>
                        <div class="detail-value">
                            <Tag
                                :severity="getStatusSeverity(selectedRequest.status)"
                                :value="getStatusLabel(selectedRequest.status)"
                            />
                        </div>
                    </div>
                    <div class="col-12 md:col-6" v-if="selectedRequest.substitute">
                        <div class="detail-label">Vertretung:</div>
                        <div class="detail-value">{{ selectedRequest.substitute.name }}</div>
                    </div>
                    <div class="col-12" v-if="selectedRequest.status === 'approved'">
                        <div class="detail-label">Genehmigt von:</div>
                        <div class="detail-value">{{ selectedRequest.approvedBy }} am {{ formatDate(selectedRequest.approvedDate) }}</div>
                    </div>
                    <div class="col-12" v-if="selectedRequest.status === 'rejected'">
                        <div class="detail-label">Abgelehnt von:</div>
                        <div class="detail-value">{{ selectedRequest.rejectedBy }} am {{ formatDate(selectedRequest.rejectedDate) }}</div>
                    </div>
                    <div class="col-12" v-if="selectedRequest.notes">
                        <div class="detail-label">Anmerkungen:</div>
                        <div class="detail-value">{{ selectedRequest.notes }}</div>
                    </div>
                    <div class="col-12" v-if="selectedRequest.rejectionReason">
                        <div class="detail-label">Ablehnungsgrund:</div>
                        <div class="detail-value text-red-500">{{ selectedRequest.rejectionReason }}</div>
                    </div>
                </div>
            </div>
        </Dialog>

        <Toast />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from "vue"
import { FilterMatchMode, FilterOperator } from "@primevue/core/api"
import AppLayout from "@/Layouts/AppLayout.vue"
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import Button from "primevue/button"
import InputText from "primevue/inputtext"
import Dialog from "primevue/dialog"
import Tabs from "primevue/tabs"
import TabPanel from "primevue/tabpanel"
import ProgressBar from "primevue/progressbar"
import Tag from "primevue/tag"
import Toast from "primevue/toast"
import { useToast } from "primevue/usetoast"
import dayjs from "dayjs"
import "dayjs/locale/de"
import VacationRequest from "@/Components/Vacation/VacationRequest.vue"
import FullCalendar from "@fullcalendar/vue3"
import dayGridPlugin from "@fullcalendar/daygrid"
import timeGridPlugin from "@fullcalendar/timegrid"
import interactionPlugin from "@fullcalendar/interaction"
import deLocale from "@fullcalendar/core/locales/de"
import VacationService from "@/Services/VacationService"
import Chart from "primevue/chart"
import Dropdown from "primevue/dropdown"
import HolidayService from '@/Services/holiday-service';

dayjs.locale("de")

// Zustand
const loading = ref(false)
const showVacationRequestForm = ref(false)
const showRequestDetails = ref(false)
const selectedRequest = ref(null)

// Filter für DataTable
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
})

// Für die Urlaubsstatistik
const selectedStatYear = ref(new Date().getFullYear())
const availableYears = ref([])
const yearlyStats = ref({
    baseEntitlement: 0,
    carryOver: 0,
    totalEntitlement: 0,
    used: 0,
    planned: 0,
    remaining: 0,
})
const previousYear = ref(new Date().getFullYear() - 1)

// Urlaubshistorie
const vacationHistory = ref([])

// Urlaubsdetails für das ausgewählte Jahr
const yearVacationDetails = ref([])

// Daten für das Balkendiagramm
const monthlyChartData = ref({
    labels: ["Jan", "Feb", "Mär", "Apr", "Mai", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dez"],
    datasets: [
        {
            label: "Urlaubstage",
            backgroundColor: "#42A5F5",
            data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        },
    ],
})

// Optionen für das Balkendiagramm
const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1,
            },
        },
    },
})

// Aktualisieren Sie die vacationStats, um die übertragenen Tage einzubeziehen
const vacationStats = ref({
    total: 0,
    used: 0,
    planned: 0,
    remaining: 0,
    carryOver: 0,
})

// Berechneter Prozentsatz der verbrauchten Urlaubstage
const vacationUsagePercentage = computed(() => {
    const used = vacationStats.value.used
    const total = vacationStats.value.total

    // Stellen Sie sicher, dass wir nicht durch Null teilen und begrenzen Sie den Prozentsatz auf maximal 100%
    if (total <= 0) return 0
    return Math.min(Math.round((used / total) * 100), 100)
})
// Urlaubsanträge
const myVacationRequests = ref([])

// Feiertage für das aktuelle Jahr
const holidays = ref([]);

// Feiertage laden
const fetchHolidays = async (year) => {
    try {
        holidays.value = await HolidayService.getHolidays(year);
    } catch (error) {
        console.error('Fehler beim Laden der Feiertage:', error);
    }
};

// Prüft, ob ein Datum ein Feiertag ist
const isHoliday = (date) => {
    return HolidayService.isHoliday(dayjs(date), holidays.value);
};

// Gibt den Namen eines Feiertags zurück
const getHolidayName = (date) => {
    return HolidayService.getHolidayName(dayjs(date), holidays.value);
};

// Füge diese Funktion hinzu, um Feiertage als Ereignisse zu formatieren
const getHolidayEvents = () => {
    return holidays.value.map(holiday => ({
        title: holiday.name,
        start: holiday.date.format('YYYY-MM-DD'),
        allDay: true,
        backgroundColor: '#FF0000',
        borderColor: '#FF0000',
        className: 'holiday-event',
        display: 'background'
    }));
};

// Kalender-Konfiguration
const calendarOptions = ref({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: "dayGridMonth",
    headerToolbar: {
        left: "prev,next today",
        center: "title",
        right: "dayGridMonth,timeGridWeek",
    },
    locale: deLocale,
    events: [],
    eventClick: (info) => {
        // Zeige Details zum angeklickten Urlaub
        const request = myVacationRequests.value.find((req) => req.id === parseInt(info.event.id))
        if (request) {
            viewRequestDetails(request)
        }
    },
})

// Formatierungsfunktionen
const formatDate = (date) => {
    return dayjs(date).format("DD.MM.YYYY")
}

const getStatusLabel = (status) => {
    switch (status) {
        case "pending":
            return "Ausstehend"
        case "approved":
            return "Genehmigt"
        case "rejected":
            return "Abgelehnt"
        default:
            return status
    }
}

const getStatusSeverity = (status) => {
    switch (status) {
        case "pending":
            return "warning"
        case "approved":
            return "success"
        case "rejected":
            return "danger"
        default:
            return "info"
    }
}

// Aktionen
const viewRequestDetails = (request) => {
    selectedRequest.value = request
    showRequestDetails.value = true
}

// Stelle sicher, dass die cancelRequest-Funktion korrekt implementiert ist
const cancelRequest = async (request) => {
    try {
        // Echten API-Aufruf durchführen
        await VacationService.cancelVacationRequest(request.id);

        // Antrag aus der Liste entfernen
        const index = myVacationRequests.value.findIndex((req) => req.id === request.id);
        if (index !== -1) {
            myVacationRequests.value.splice(index, 1);
        }

        // Urlaubsstatistik aktualisieren
        if (request.status === "approved") {
            vacationStats.value.planned -= request.days;
            vacationStats.value.remaining += request.days;
        }

        // Kalender aktualisieren
        updateCalendarEvents();

        toast.add({
            severity: "success",
            summary: "Erfolg",
            detail: "Ihr Urlaubsantrag wurde zurückgezogen.",
            life: 3000,
        });

        // Daten neu laden, um sicherzustellen, dass der Antrag wirklich entfernt wurde
        fetchVacationData();
    } catch (error) {
        console.error("Fehler beim Zurückziehen des Urlaubsantrags:", error);
        toast.add({
            severity: "error",
            summary: "Fehler",
            detail: "Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.",
            life: 3000,
        });
    }
};

const handleVacationRequestSubmitted = () => {
    showVacationRequestForm.value = false
    // Daten neu laden
    fetchVacationData()
}

// Kalender-Funktionen
const updateCalendarEvents = () => {
    const events = [];

    // Füge Urlaubsanträge hinzu
    myVacationRequests.value.forEach((request) => {
        let color;
        switch (request.status) {
            case "pending":
                color = "#f59e0b";
                break // Amber
            case "approved":
                color = "#9C27B0";
                break // Purple (für Urlaub)
            case "rejected":
                color = "#ef4444";
                break // Red
            default:
                color = "#3b82f6";
                break // Blue
        }

        events.push({
            id: request.id.toString(),
            title: getStatusLabel(request.status),
            start: request.startDate,
            end: dayjs(request.endDate).add(1, "day").toDate(), // FullCalendar ist exklusiv für Enddatum
            allDay: true,
            backgroundColor: color,
            borderColor: color,
            extendedProps: {
                status: request.status,
            },
        });
    });

    // Füge Feiertage hinzu
    events.push(...getHolidayEvents());

    if (calendarOptions.value) {
        calendarOptions.value.events = events;
    }
};

// Bereinige Ressourcen vor dem Unmount
onBeforeUnmount(() => {
    // Entferne alle Event-Listener oder Referenzen, die Probleme verursachen könnten
    if (calendarOptions.value && calendarOptions.value.events) {
        calendarOptions.value.events = []
    }
})

// Urlaubshistorie und Statistik
const fetchVacationData = async () => {
    loading.value = true
    try {
        const response = await VacationService.getUserVacationData()

        // Aktualisiere die Urlaubsstatistik mit den übertragenen Tagen
        vacationStats.value = {
            total: response.data.stats.total,
            used: response.data.stats.used,
            planned: response.data.stats.planned,
            remaining: response.data.stats.remaining,
            carryOver: response.data.stats.carryOver || 0, // Übertragene Tage aus dem Vorjahr
        }

        myVacationRequests.value = response.data.requests

        // Urlaubshistorie laden
        if (response.data.history) {
            vacationHistory.value = response.data.history
        }

        // Aktualisiere die Daten für das aktuelle Jahr in der Statistik
        if (response.data.yearlyStats) {
            yearlyStats.value = response.data.yearlyStats[selectedStatYear.value] || yearlyStats.value
        }

        // Aktualisiere die Urlaubsdetails für das ausgewählte Jahr
        if (response.data.yearVacationDetails) {
            yearVacationDetails.value = response.data.yearVacationDetails[selectedStatYear.value] || yearVacationDetails.value
        }

        // Aktualisiere die Daten für das Balkendiagramm
        if (response.data.monthlyStats) {
            const monthlyData = response.data.monthlyStats[selectedStatYear.value] || Array(12).fill(0)
            monthlyChartData.value.datasets[0].data = monthlyData
        }

        updateCalendarEvents()
    } catch (error) {
        console.error("Fehler beim Laden der Urlaubsdaten:", error)
        toast.add({
            severity: "error",
            summary: "Fehler",
            detail: "Die Urlaubsdaten konnten nicht geladen werden.",
            life: 3000,
        })
    } finally {
        loading.value = false
    }
}

const updateYearlyStats = async () => {
    if (!selectedStatYear.value) return

    try {
        const response = await VacationService.getYearlyVacationData(selectedStatYear.value)
        yearVacationDetails.value = response.data.details
        yearlyStats.value = response.data.stats

        // Aktualisiere die Daten für das Balkendiagramm
        // Hier müssten wir eigentlich die Daten vom Server laden, aber wir verwenden die vorhandenen Daten
        if (monthlyChartData.value && monthlyChartData.value.datasets && monthlyChartData.value.datasets.length > 0) {
            // Berechne die monatlichen Urlaubstage aus den Urlaubsdetails
            const monthlyData = Array(12).fill(0)

            yearVacationDetails.value.forEach((detail) => {
                if (detail.status === "approved") {
                    // Extrahiere den Monat aus dem Zeitraum (Format: "01.03.2024 - 05.03.2024")
                    const periodParts = detail.period.split(" - ")
                    if (periodParts.length === 2) {
                        const startDateParts = periodParts[0].split(".")
                        if (startDateParts.length === 3) {
                            const month = parseInt(startDateParts[1]) - 1 // 0-basierter Index
                            monthlyData[month] += detail.days
                        }
                    }
                }
            })

            monthlyChartData.value.datasets[0].data = monthlyData
        }
    } catch (error) {
        console.error("Fehler beim Laden der Jahresstatistik:", error)
        toast.add({
            severity: "error",
            summary: "Fehler",
            detail: "Die Jahresstatistik konnte nicht geladen werden.",
            life: 3000,
        })
    }
}

// Stelle sicher, dass die Zahlen in der Jahresübersicht korrekt formatiert werden
const formatNumberValue = (value) => {
    // Wenn der Wert negativ ist (wie -10 oder -20), mache ihn positiv
    if (typeof value === 'number') {
        return Math.abs(value);
    }
    return value;
};

// Wir verwenden try-catch, um Fehler abzufangen, falls der Toast-Service nicht verfügbar ist
let toast
try {
    toast = useToast()
} catch (error) {
    console.warn("Toast service not available, using fallback")
    // Fallback für den Toast-Service
    toast = {
        add: (message) => console.log("Toast message:", message),
    }
}

onMounted(() => {
    fetchVacationData()
    fetchHolidays(new Date().getFullYear());

    // Verfügbare Jahre für die Statistik laden
    const currentYear = new Date().getFullYear()
    for (let year = currentYear - 5; year <= currentYear; year++) {
        availableYears.value.push({ name: year.toString(), value: year })
    }
    selectedStatYear.value = currentYear
})

// Beobachte Änderungen am ausgewählten Jahr und aktualisiere die Daten entsprechend
watch(selectedStatYear, (newYear) => {
    updateYearlyStats()
})


</script>

<style scoped>
.vacation-card {
    background-color: var(--surface-card);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--card-shadow);
    text-align: center;
}

.vacation-card-title {
    font-size: 1rem;
    color: var(--text-color-secondary);
    margin-bottom: 0.5rem;
}

.vacation-card-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-color);
}

.vacation-card-subtitle {
    font-size: 1rem;
    margin-top: 0.25rem;
}

.vacation-calendar {
    height: 600px;
    margin-top: 1rem;
}

.vacation-calendar-widget {
    height: 100%;
}

.request-details {
    padding: 1rem;
}

.detail-label {
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: var(--text-color-secondary);
}

.detail-value {
    margin-bottom: 1rem;
}

:deep(.p-datatable .p-datatable-header) {
    background-color: var(--surface-section);
    border: none;
    padding: 1rem;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    background-color: var(--surface-section);
}

:deep(.p-tabview .p-tabview-nav) {
    border-bottom: 1px solid var(--surface-border);
}

:deep(.p-tabview .p-tabview-nav li .p-tabview-nav-link) {
    border: none;
    color: var(--text-color-secondary);
    transition: all 0.2s;
}

:deep(.p-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: var(--primary-color);
    border-bottom: 2px solid var(--primary-color);
}

:deep(.p-chart) {
    width: 100%;
    height: 100%;
}
</style>
