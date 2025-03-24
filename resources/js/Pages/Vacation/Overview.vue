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
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="vacation-card">
                            <div class="vacation-card-title">Gesamtes Urlaubskontingent</div>
                            <div class="vacation-card-value">{{ vacationStats.total }} Tage</div>
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
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import Tabs from 'primevue/tabs';
import TabPanel from 'primevue/tabpanel';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import VacationRequest from '@/Components/Vacation/VacationRequest.vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import deLocale from '@fullcalendar/core/locales/de';

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

// Zustand
const loading = ref(false);
const showVacationRequestForm = ref(false);
const showRequestDetails = ref(false);
const selectedRequest = ref(null);

// Filter für DataTable
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// Urlaubsstatistik (würde normalerweise vom Server geladen)
const vacationStats = ref({
    total: 30,
    used: 5,
    planned: 10,
    remaining: 15
});

// Berechneter Prozentsatz der verbrauchten Urlaubstage
const vacationUsagePercentage = computed(() => {
    const used = vacationStats.value.used + vacationStats.value.planned;
    const total = vacationStats.value.total;
    return Math.round((used / total) * 100);
});

// Beispieldaten für Urlaubsanträge (würden normalerweise vom Server geladen)
const myVacationRequests = ref([
    {
        id: 1,
        startDate: new Date(2025, 3, 15),
        endDate: new Date(2025, 3, 20),
        days: 5,
        substitute: { id: 2, name: 'Sarah Becker' },
        requestDate: new Date(2025, 3, 1),
        notes: 'Familienurlaub',
        status: 'pending'
    },
    {
        id: 2,
        startDate: new Date(2025, 2, 10),
        endDate: new Date(2025, 2, 14),
        days: 5,
        substitute: { id: 4, name: 'Nina Hoffmann' },
        requestDate: new Date(2025, 1, 25),
        approvedBy: 'Michael Fischer',
        approvedDate: new Date(2025, 1, 27),
        notes: 'Genehmigt',
        status: 'approved'
    },
    {
        id: 3,
        startDate: new Date(2025, 7, 1),
        endDate: new Date(2025, 7, 5),
        days: 5,
        substitute: null,
        requestDate: new Date(2025, 6, 15),
        rejectedBy: 'Anna Schmidt',
        rejectedDate: new Date(2025, 6, 17),
        rejectionReason: 'Wichtige Marketingkampagne in diesem Zeitraum',
        status: 'rejected'
    }
]);

// Kalender-Konfiguration
const calendarOptions = ref({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek'
    },
    locale: deLocale,
    events: [],
    eventClick: info => {
        // Zeige Details zum angeklickten Urlaub
        const request = myVacationRequests.value.find(req => req.id === parseInt(info.event.id));
        if (request) {
            viewRequestDetails(request);
        }
    }
});

// Formatierungsfunktionen
const formatDate = (date) => {
    return dayjs(date).format('DD.MM.YYYY');
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'pending': return 'Ausstehend';
        case 'approved': return 'Genehmigt';
        case 'rejected': return 'Abgelehnt';
        default: return status;
    }
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        default: return 'info';
    }
};

// Aktionen
const viewRequestDetails = (request) => {
    selectedRequest.value = request;
    showRequestDetails.value = true;
};

const cancelRequest = async (request) => {
    try {
        // Hier würde normalerweise der API-Aufruf stattfinden
        await new Promise(resolve => setTimeout(resolve, 1000));

        // Antrag aus der Liste entfernen
        const index = myVacationRequests.value.findIndex(req => req.id === request.id);
        if (index !== -1) {
            myVacationRequests.value.splice(index, 1);
        }

        // Urlaubsstatistik aktualisieren
        vacationStats.value.planned -= request.days;
        vacationStats.value.remaining += request.days;

        // Kalender aktualisieren
        updateCalendarEvents();

        toast.add({
            severity: 'success',
            summary: 'Erfolg',
            detail: 'Ihr Urlaubsantrag wurde zurückgezogen.',
            life: 3000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.',
            life: 3000
        });
    }
};

const handleVacationRequestSubmitted = () => {
    showVacationRequestForm.value = false;

    // Hier würden normalerweise die Daten neu geladen werden
    // Für das Beispiel fügen wir einen neuen Antrag hinzu
    const newRequest = {
        id: myVacationRequests.value.length + 1,
        startDate: new Date(2025, 8, 1),
        endDate: new Date(2025, 8, 5),
        days: 5,
        substitute: { id: 3, name: 'Thomas Müller' },
        requestDate: new Date(),
        notes: '',
        status: 'pending'
    };

    myVacationRequests.value.push(newRequest);

    // Urlaubsstatistik aktualisieren
    vacationStats.value.planned += newRequest.days;
    vacationStats.value.remaining -= newRequest.days;

    // Kalender aktualisieren
    updateCalendarEvents();
};

// Kalender-Funktionen
const updateCalendarEvents = () => {
    const events = [];

    myVacationRequests.value.forEach(request => {
        let color;
        switch (request.status) {
            case 'pending': color = '#f59e0b'; break; // Amber
            case 'approved': color = '#9C27B0'; break; // Purple (für Urlaub)
            case 'rejected': color = '#ef4444'; break; // Red
            default: color = '#3b82f6'; break; // Blue
        }

        events.push({
            id: request.id.toString(),
            title: getStatusLabel(request.status),
            start: request.startDate,
            end: dayjs(request.endDate).add(1, 'day').toDate(), // FullCalendar ist exklusiv für Enddatum
            allDay: true,
            backgroundColor: color,
            borderColor: color,
            extendedProps: {
                status: request.status
            }
        });
    });

    if (calendarOptions.value) {
        calendarOptions.value.events = events;
    }
};

// Bereinige Ressourcen vor dem Unmount
onBeforeUnmount(() => {
    // Entferne alle Event-Listener oder Referenzen, die Probleme verursachen könnten
    if (calendarOptions.value && calendarOptions.value.events) {
        calendarOptions.value.events = [];
    }
});

// Komponente initialisieren
onMounted(() => {
    // Hier könnten Daten vom Server geladen werden
    loading.value = true;

    setTimeout(() => {
        // Simuliere Laden der Daten
        loading.value = false;

        // Kalender-Events initialisieren
        updateCalendarEvents();
    }, 500);
});
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
</style>

