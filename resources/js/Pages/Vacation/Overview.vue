<template>
    <AppLayout title="Meine Urlaubsübersicht">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight transition-colors duration-300">
                    Meine Urlaubsübersicht
                </h2>
            </div>
        </template>

        <div class="py-6 transition-colors duration-300 overflow-hidden">
            <div class="max-w-[100rem] mx-auto sm:px-6 lg:px-8 h-full flex flex-col">
                <!-- Urlaubskontingent Karte -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6 border border-gray-200 dark:border-gray-700 transition-all duration-300 flex-shrink-0">
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
                        <div class="vacation-card group hover:shadow-lg transition-all duration-300">
                            <div class="vacation-card-icon bg-blue-100 dark:bg-blue-900/30 mb-3">
                                <i class="pi pi-calendar text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <div class="vacation-card-title">Basis-Urlaubstage</div>
                            <div class="vacation-card-value text-blue-600 dark:text-blue-400">{{ vacationStats.baseEntitlement || 0 }}</div>
                            <div class="vacation-card-subtitle text-gray-500 dark:text-gray-400">
                                Jahresanspruch {{ new Date().getFullYear() }}
                            </div>
                        </div>

                        <div class="vacation-card group hover:shadow-lg transition-all duration-300">
                            <div class="vacation-card-icon bg-green-100 dark:bg-green-900/30 mb-3">
                                <i class="pi pi-arrow-up text-green-600 dark:text-green-400"></i>
                            </div>
                            <div class="vacation-card-title">Übertrag aus Vorjahr</div>
                            <div class="vacation-card-value text-green-600 dark:text-green-400">
                                {{ vacationStats.carryOver || 0 }}
                            </div>
                            <div v-if="vacationStats.carryOverUsed > 0" class="vacation-card-subtitle text-orange-600 dark:text-orange-400">
                                {{ vacationStats.carryOverUsed }} bereits verwendet
                            </div>
                            <div v-else-if="vacationStats.carryOverExpires" class="vacation-card-subtitle text-orange-600 dark:text-orange-400">
                                verfällt am {{ formatDate(vacationStats.carryOverExpires) }}
                            </div>
                            <div v-else class="vacation-card-subtitle text-gray-500 dark:text-gray-400">
                                kein Übertrag
                            </div>
                        </div>

                        <div class="vacation-card group hover:shadow-lg transition-all duration-300">
                            <div class="vacation-card-icon bg-purple-100 dark:bg-purple-900/30 mb-3">
                                <i class="pi pi-chart-pie text-purple-600 dark:text-purple-400"></i>
                            </div>
                            <div class="vacation-card-title">Gesamt verfügbar</div>
                            <div class="vacation-card-value text-purple-600 dark:text-purple-400">
                                {{ vacationStats.totalAvailable || 0 }}
                            </div>
                            <div class="vacation-card-subtitle text-gray-500 dark:text-gray-400">
                                Basis + Übertrag
                            </div>
                        </div>

                        <div class="vacation-card group hover:shadow-lg transition-all duration-300">
                            <div class="vacation-card-icon bg-orange-100 dark:bg-orange-900/30 mb-3">
                                <i class="pi pi-check-circle text-orange-600 dark:text-orange-400"></i>
                            </div>
                            <div class="vacation-card-title">Genommen</div>
                            <div class="vacation-card-value text-gray-900 dark:text-white">{{ vacationStats.used || 0 }}</div>
                            <div v-if="vacationStats.usedCarryOver > 0" class="vacation-card-subtitle">
                                <div class="text-orange-600 dark:text-orange-400 text-sm">{{ vacationStats.usedCarryOver }} aus Übertrag</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm">{{ vacationStats.usedRegular }} regulär</div>
                            </div>
                            <div v-else class="vacation-card-subtitle text-gray-500 dark:text-gray-400">
                                alle aus regulärem Kontingent
                            </div>
                        </div>

                        <div class="vacation-card group hover:shadow-lg transition-all duration-300">
                            <div class="vacation-card-icon bg-indigo-100 dark:bg-indigo-900/30 mb-3">
                                <i class="pi pi-clock text-indigo-600 dark:text-indigo-400"></i>
                            </div>
                            <div class="vacation-card-title">Geplant</div>
                            <div class="vacation-card-value text-indigo-600 dark:text-indigo-400">{{ vacationStats.planned || 0 }}</div>
                            <div class="vacation-card-subtitle text-gray-500 dark:text-gray-400">
                                Zukünftige Anträge
                            </div>
                        </div>

                        <div class="vacation-card group hover:shadow-lg transition-all duration-300">
                            <div class="vacation-card-icon bg-teal-100 dark:bg-teal-900/30 mb-3">
                                <i class="pi pi-users text-teal-600 dark:text-teal-400"></i>
                            </div>
                            <div class="vacation-card-title">Verbleibend</div>
                            <div class="vacation-card-value text-teal-600 dark:text-teal-400">{{ vacationStats.remaining || 0 }}</div>
                            <div v-if="vacationStats.remainingCarryOver > 0" class="vacation-card-subtitle">
                                <div class="text-orange-600 dark:text-orange-400 text-sm">{{ vacationStats.remainingCarryOver }} aus Übertrag</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm">{{ vacationStats.remainingRegular }} regulär</div>
                            </div>
                            <div v-else class="vacation-card-subtitle text-gray-500 dark:text-gray-400">
                                Übertragbar: max. {{ Math.min(Math.max(vacationStats.remaining - vacationStats.planned, 0), 10) }} Tage
                            </div>
                        </div>
                    </div>

                    <!-- ERWEITERTE Carry-Over Warnung mit FIFO-Hinweis -->
                    <div v-if="vacationStats.carryOver > 0 && vacationStats.carryOverExpires"
                         class="mt-6 p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl transition-all duration-300">
                        <div class="flex items-start">
                            <i class="pi pi-exclamation-triangle text-orange-600 dark:text-orange-400 mt-0.5 mr-3"></i>
                            <div>
                                <h3 class="font-semibold text-orange-800 dark:text-orange-200 mb-2">
                                    Achtung: Übertragene Urlaubstage verfallen!
                                </h3>
                                <p class="text-orange-700 dark:text-orange-300 text-sm mb-2">
                                    {{ Math.max(vacationStats.carryOver - (vacationStats.carryOverUsed || 0), 0) }} übertragene Tage
                                    verfallen am {{ formatDate(vacationStats.carryOverExpires) }}.
                                </p>
                                <p class="text-orange-600 dark:text-orange-400 text-xs flex items-center">
                                    <i class="pi pi-info-circle mr-1"></i>
                                    Übertragene Tage werden automatisch zuerst verbraucht.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- NEU: FIFO-Informationsbox -->
                    <div v-if="vacationStats.carryOver > 0"
                         class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl transition-all duration-300">
                        <div class="flex items-start">
                            <i class="pi pi-info-circle text-blue-600 dark:text-blue-400 mt-0.5 mr-3"></i>
                            <div>
                                <h3 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">
                                    Reihenfolge der Urlaubstage-Verwendung
                                </h3>
                                <p class="text-blue-700 dark:text-blue-300 text-sm">
                                    Bei neuen Urlaubsanträgen werden zuerst die übertragenen Tage ({{ vacationStats.remainingCarryOver || 0 }} verbleibend)
                                    und dann die regulären Tage ({{ vacationStats.remainingRegular || 0 }} verbleibend) verwendet.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Verbesserter Progress Bar -->
                    <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl transition-all duration-300">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Urlaubsverbrauch</h3>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ vacationUsagePercentage }}% verbraucht
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-3 mb-2 overflow-hidden">
                            <div
                                class="bg-gradient-to-r from-blue-500 to-purple-600 h-3 rounded-full transition-all duration-1000 ease-out"
                                :style="{ width: `${vacationUsagePercentage}%` }"
                            ></div>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>{{ vacationStats.used || 0 }} von {{ vacationStats.totalAvailable || 0 }} Tagen verwendet</span>
                            <span>{{ vacationStats.remaining || 0 }} Tage verbleibend</span>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <Button
                            label="Neuen Urlaubsantrag stellen"
                            icon="pi pi-plus"
                            @click="showVacationRequestForm = true"
                            class="p-button-primary transition-all duration-200 hover:scale-105"
                        />
                    </div>
                </div>

                <!-- Urlaubsanträge - Flex-grow für volle Höhe -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg border border-gray-200 dark:border-gray-700 transition-all duration-300 flex-1 flex flex-col">
                    <TabView class="vacation-tabs" @tab-change="onTabChange" v-model:activeIndex="activeTabIndex">
                        <TabPanel class="flex-1 flex flex-col">
                            <template #header>
                                <i class="pi pi-calendar mr-2"></i>
                                <span>Meine Urlaubsanträge</span>
                            </template>

                            <div class="p-6 flex-1 flex flex-col">
                                <DataTable
                                    :value="myVacationRequests"
                                    :paginator="myVacationRequests.length > 10"
                                    :rows="10"
                                    :rowsPerPageOptions="[5, 10, 20]"
                                    dataKey="id"
                                    :rowHover="true"
                                    responsiveLayout="scroll"
                                    class="p-datatable-sm vacation-table flex-1"
                                    :loading="loading"
                                    v-model:filters="filters"
                                    filterDisplay="menu"
                                    :globalFilterFields="['startDate', 'endDate', 'status', 'approvedBy', 'rejectedBy']"
                                >
                                    <template #header>
                                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">Meine Urlaubsanträge</h3>
                                            <div class="flex items-center gap-3">
                                                <span class="p-input-icon-left">
                                                    <i class="pi pi-search text-gray-400" />
                                                    <InputText
                                                        v-model="filters['global'].value"
                                                        placeholder="Suchen..."
                                                        class="p-inputtext-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-colors duration-200"
                                                    />
                                                </span>
                                            </div>
                                        </div>
                                    </template>

                                    <Column field="startDate" header="Zeitraum" :sortable="true">
                                        <template #body="{ data }">
                                            <div class="flex flex-col">
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ formatDate(data.startDate) }} - {{ formatDate(data.endDate) }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2 mt-1">
                                                    <Tag
                                                        :value="getDayTypeLabel(data.dayType)"
                                                        :severity="getDayTypeSeverity(data.dayType)"
                                                        class="text-xs"
                                                    />
                                                    <span>{{ getActualDays(data) }} {{ getActualDays(data) === 1 ? 'Tag' : 'Tage' }}</span>
                                                </div>
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="requestDate" header="Beantragt am" :sortable="true">
                                        <template #body="{ data }">
                                            <span class="text-gray-900 dark:text-white">{{ formatDate(data.requestDate) }}</span>
                                        </template>
                                    </Column>

                                    <Column field="substitute.name" header="Vertretung" :sortable="true">
                                        <template #body="{ data }">
                                            <div v-if="data.substitute" class="text-gray-900 dark:text-white">
                                                {{ data.substitute.name }}
                                            </div>
                                            <div v-else class="text-gray-500 dark:text-gray-400">
                                                Keine Vertretung
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="status" header="Status" :sortable="true">
                                        <template #body="{ data }">
                                            <div class="flex flex-col gap-1">
                                                <Tag
                                                    :severity="getStatusSeverity(data.status)"
                                                    :value="getStatusLabel(data.status)"
                                                />
                                                <div v-if="data.status === 'approved'" class="text-xs text-gray-500 dark:text-gray-400">
                                                    Genehmigt von {{ data.approvedBy }}
                                                </div>
                                                <div v-if="data.status === 'rejected'" class="text-xs text-gray-500 dark:text-gray-400">
                                                    Abgelehnt von {{ data.rejectedBy }}
                                                </div>
                                            </div>
                                        </template>
                                    </Column>

                                    <Column field="notes" header="Anmerkungen">
                                        <template #body="{ data }">
                                            <div v-if="data.notes" class="max-w-xs truncate text-gray-900 dark:text-white" :title="data.notes">
                                                {{ data.notes }}
                                            </div>
                                            <div v-else-if="data.rejectionReason" class="max-w-xs truncate text-red-500 dark:text-red-400" :title="data.rejectionReason">
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
                                                    class="p-button-primary p-button-sm p-button-rounded transition-all duration-200 hover:scale-105"
                                                    @click="cancelRequest(data)"
                                                    v-tooltip="'Antrag zurückziehen'"
                                                />
                                                <Button
                                                    icon="pi pi-eye"
                                                    class="p-button-primary p-button-sm p-button-rounded transition-all duration-200 hover:scale-105"
                                                    @click="viewRequestDetails(data)"
                                                    v-tooltip="'Details anzeigen'"
                                                />
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </div>
                        </TabPanel>

                        <TabPanel class="flex-1 flex flex-col">
                            <template #header>
                                <i class="pi pi-calendar-plus mr-2"></i>
                                <span>Urlaubskalender</span>
                            </template>

                            <div class="vacation-calendar p-6 flex-1 flex flex-col">
                                <FullCalendar
                                    ref="calendarRef"
                                    :options="calendarOptions"
                                    class="vacation-calendar-widget flex-1"
                                />
                            </div>
                        </TabPanel>

                        <TabPanel class="flex-1 flex flex-col">
                            <template #header>
                                <i class="pi pi-chart-bar mr-2"></i>
                                <span>Urlaubsstatistik</span>
                            </template>

                            <div class="p-6 flex-1 overflow-auto">
                                <h3 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">Urlaubsstatistik</h3>

                                <div class="mb-6">
                                    <Select
                                        v-model="selectedStatYear"
                                        :options="availableYears"
                                        optionLabel="name"
                                        optionValue="value"
                                        placeholder="Jahr auswählen"
                                        class="w-full sm:w-auto dark:bg-gray-700 dark:border-gray-600"
                                    />
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                                    <!-- Jahresübersicht -->
                                    <div class="bg-white dark:bg-gray-700 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-600 transition-all duration-300">
                                        <h4 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Jahresübersicht {{ selectedStatYear }}</h4>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full">
                                                <thead>
                                                <tr class="border-b border-gray-200 dark:border-gray-600">
                                                    <th class="py-3 px-4 text-left text-gray-900 dark:text-white">Kategorie</th>
                                                    <th class="py-3 px-4 text-right text-gray-900 dark:text-white">Tage</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr class="border-b border-gray-100 dark:border-gray-600">
                                                    <td class="py-3 px-4 text-gray-900 dark:text-white">Grundanspruch</td>
                                                    <td class="py-3 px-4 text-right text-gray-900 dark:text-white">{{ yearlyStats.baseEntitlement }}</td>
                                                </tr>
                                                <tr v-if="yearlyStats.carryOver > 0" class="border-b border-gray-100 dark:border-gray-600">
                                                    <td class="py-3 px-4 text-green-600 dark:text-green-400">Übertrag aus Vorjahr</td>
                                                    <td class="py-3 px-4 text-right text-green-600 dark:text-green-400">+ {{ yearlyStats.carryOver }}</td>
                                                </tr>
                                                <tr class="border-b border-gray-100 dark:border-gray-600">
                                                    <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">Gesamtanspruch</td>
                                                    <td class="py-3 px-4 text-right font-semibold text-gray-900 dark:text-white">{{ yearlyStats.totalEntitlement }}</td>
                                                </tr>
                                                <tr class="border-b border-gray-100 dark:border-gray-600">
                                                    <td class="py-3 px-4 text-gray-900 dark:text-white">Genommen (regulär)</td>
                                                    <td class="py-3 px-4 text-right text-gray-900 dark:text-white">- {{ yearlyStats.used - (yearlyStats.carryOverUsed || 0) }}</td>
                                                </tr>
                                                <tr v-if="yearlyStats.carryOverUsed > 0" class="border-b border-gray-100 dark:border-gray-600">
                                                    <td class="py-3 px-4 text-orange-600 dark:text-orange-400">Genommen (aus Übertrag)</td>
                                                    <td class="py-3 px-4 text-right text-orange-600 dark:text-orange-400">- {{ yearlyStats.carryOverUsed }}</td>
                                                </tr>
                                                <tr class="border-b border-gray-100 dark:border-gray-600">
                                                    <td class="py-3 px-4 text-gray-900 dark:text-white">Geplant</td>
                                                    <td class="py-3 px-4 text-right text-gray-900 dark:text-white">- {{ yearlyStats.planned }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">Verbleibend</td>
                                                    <td class="py-3 px-4 text-right font-semibold text-gray-900 dark:text-white">{{ yearlyStats.remaining }}</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Urlaubsverteilung nach Monaten -->
                                    <div class="bg-white dark:bg-gray-700 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-600 transition-all duration-300">
                                        <h4 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Urlaubsverteilung nach Monaten</h4>
                                        <div class="h-64">
                                            <Chart type="bar" :data="monthlyChartData" :options="chartOptions" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Weitere Statistik-Komponenten hier... -->
                            </div>
                        </TabPanel>
                    </TabView>
                </div>
            </div>
        </div>

        <!-- Dialoge bleiben unverändert, aber mit verbessertem Dark Mode -->
        <Dialog
            v-model:visible="showVacationRequestForm"
            header="Neuen Urlaubsantrag stellen"
            :style="{ width: '800px' }"
            :modal="true"
            :closable="true"
            :closeOnEscape="true"
            class="dark:bg-gray-800"
        >
            <VacationRequest
                @cancel="showVacationRequestForm = false"
                @submitted="handleVacationRequestSubmitted"
                :vacationStats="vacationStats"
                :myVacationRequests="myVacationRequests"
                :holidays="holidays"
            />
        </Dialog>

        <Dialog
            v-model:visible="showRequestDetails"
            :header="'Urlaubsantrag Details'"
            :style="{ width: '600px' }"
            :modal="true"
            class="dark:bg-gray-800"
        >
            <div v-if="selectedRequest" class="request-details">
                <div class="grid">
                    <div class="col-12 md:col-6">
                        <div class="detail-label">Zeitraum:</div>
                        <div class="detail-value">{{ formatDate(selectedRequest.startDate) }} - {{ formatDate(selectedRequest.endDate) }}</div>
                    </div>
                    <div class="col-12 md:col-6">
                        <div class="detail-label">Art:</div>
                        <div class="detail-value">
                            <Tag
                                :value="getDayTypeLabel(selectedRequest.dayType)"
                                :severity="getDayTypeSeverity(selectedRequest.dayType)"
                            />
                        </div>
                    </div>
                    <!-- Weitere Details... -->
                </div>
            </div>
        </Dialog>

        <Toast />
    </AppLayout>
</template>

<script setup>
import {ref, computed, onMounted, onBeforeUnmount, watch, nextTick} from "vue"
import { FilterMatchMode } from "@primevue/core/api"
import AppLayout from "@/Layouts/AppLayout.vue"
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import Button from "primevue/button"
import InputText from "primevue/inputtext"
import Dialog from "primevue/dialog"
import TabView from "primevue/tabview"
import TabPanel from "primevue/tabpanel"
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
import Select from "primevue/select"
import HolidayService from '@/Services/holiday-service';

dayjs.locale("de")

// Dark Mode State
const isDarkMode = ref(false)

// Zustand
const loading = ref(false)
const showVacationRequestForm = ref(false)
const showRequestDetails = ref(false)
const selectedRequest = ref(null)
const activeTabIndex = ref(0)
const calendarRef = ref(null)

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
    carryOverUsed: 0, // Hinzugefügt für Konsistenz
})

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
            data: Array(12).fill(0),
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
                stepSize: 0.5, // Erlaubt halbe Schritte für Halbtage
            },
        },
    },
})

// Aktualisierte vacationStats, um die übertragenen Tage einzubeziehen
const vacationStats = ref({
    baseEntitlement: 0,
    carryOver: 0,
    carryOverUsed: 0,
    carryOverRemaining: 0,
    carryOverExpires: null,
    totalAvailable: 0,
    used: 0,
    usedRegular: 0,
    usedCarryOver: 0,
    planned: 0,
    remaining: 0,
    remainingRegular: 0,
    remainingCarryOver: 0
})

// Berechneter Prozentsatz der verbrauchten Urlaubstage
const vacationUsagePercentage = computed(() => {
    const used = vacationStats.value.used
    const totalAvailable = vacationStats.value.totalAvailable

    // Stellen Sie sicher, dass wir nicht durch Null teilen und begrenzen Sie den Prozentsatz auf maximal 100%
    if (totalAvailable <= 0) return 0
    return Math.min(Math.round((used / totalAvailable) * 100), 100)
})

// Urlaubsanträge
const myVacationRequests = ref([])

// Feiertage für das aktuelle Jahr
const holidays = ref([]);

// NEU: Maps für Status und Tagestypen zur besseren Wartbarkeit
const statusMap = {
    pending: { label: "Ausstehend", severity: "warning" },
    approved: { label: "Genehmigt", severity: "success" },
    rejected: { label: "Abgelehnt", severity: "danger" },
};

const dayTypeMap = {
    morning: { label: 'Vormittag', severity: 'info' },
    afternoon: { label: 'Nachmittag', severity: 'warning' },
    full_day: { label: 'Ganzer Tag', severity: 'primary' },
};

const getDayTypeLabel = (dayType) => dayTypeMap[dayType]?.label || 'Ganzer Tag';
const getDayTypeSeverity = (dayType) => dayTypeMap[dayType]?.severity || 'primary';

const getActualDays = (request) => {
    // Verwende actualDays falls vorhanden, sonst berechne basierend auf dayType
    if (request.actualDays !== undefined) {
        return request.actualDays
    }

    // Fallback-Berechnung für Halbtage, wenn es ein einzelner Tag ist
    const startDate = dayjs(request.startDate)
    const endDate = dayjs(request.endDate)
    if (request.dayType && request.dayType !== 'full_day' && startDate.isSame(endDate, 'day')) {
        return 0.5
    }

    return request.days || 0
}

// Dark Mode Functions
const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value
    localStorage.setItem('darkMode', isDarkMode.value.toString())
    document.documentElement.classList.toggle('dark', isDarkMode.value)
}

// Feiertage laden
const fetchHolidays = async (year) => {
    try {
        holidays.value = await HolidayService.getHolidays(year);
    } catch (error) {
        console.error('Fehler beim Laden der Feiertage:', error);
    }
};

// Kalender-Konfiguration
const calendarOptions = ref({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: "dayGridMonth",
    height: 'auto',
    contentHeight: 600,
    aspectRatio: 1.35,
    headerToolbar: {
        left: "prev next today",
        center: "title",
        right: "dayGridMonth timeGridWeek",
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
    windowResizeDelay: 100,
    dayMaxEvents: true,
    moreLinkClick: 'popover'
})

// Formatierungsfunktionen
const formatDate = (date) => {
    return dayjs(date).format("DD.MM.YYYY")
}

const getStatusLabel = (status) => statusMap[status]?.label || status;
const getStatusSeverity = (status) => statusMap[status]?.severity || "info";

// Aktionen
const viewRequestDetails = (request) => {
    selectedRequest.value = request
    showRequestDetails.value = true
}

const cancelRequest = async (request) => {
    try {
        await VacationService.cancelVacationRequest(request.id);

        const index = myVacationRequests.value.findIndex((req) => req.id === request.id);
        if (index !== -1) {
            myVacationRequests.value.splice(index, 1);
        }

        // Urlaubsstatistik aktualisieren
        if (request.status === "approved") {
            const actualDays = getActualDays(request)
            vacationStats.value.planned -= actualDays;
            vacationStats.value.remaining += actualDays;
        }

        // updateCalendarEvents(); // Wird jetzt durch den Watcher ausgelöst

        toast.add({
            severity: "success",
            summary: "Erfolg",
            detail: "Ihr Urlaubsantrag wurde zurückgezogen.",
            life: 3000,
        });

        fetchVacationData(); // Daten neu laden, um sicherzustellen, dass der Antrag wirklich entfernt wurde
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
    fetchVacationData() // Daten neu laden
}

// Kalender-Funktionen
const updateCalendarEvents = () => {
    const calendarApi = calendarRef.value?.getApi();
    if (!calendarApi) {
        console.warn("FullCalendar API not available yet.");
        return;
    }

    // Explicitly remove all existing events before adding new ones
    calendarApi.removeAllEvents();

    const events = [];

    // Füge Urlaubsanträge hinzu
    myVacationRequests.value.forEach((request) => {
        const statusColorMap = {
            pending: "#f59e0b", // Amber
            approved: "#9C27B0", // Purple (für Urlaub)
            rejected: "#ef4444", // Red
        };
        const color = statusColorMap[request.status] || "#3b82f6"; // Blue default

        let title = getStatusLabel(request.status);
        if (request.dayType && request.dayType !== 'full_day') {
            title += ` (${getDayTypeLabel(request.dayType)})`;
        }

        events.push({
            id: request.id.toString(),
            title: title,
            start: request.startDate,
            end: dayjs(request.endDate).add(1, "day").toDate(), // FullCalendar ist exklusiv für Enddatum
            allDay: true,
            backgroundColor: color,
            borderColor: color,
            extendedProps: {
                status: request.status,
                dayType: request.dayType,
                actualDays: getActualDays(request)
            },
        });
    });

    // Füge Feiertage hinzu
    holidays.value.forEach(holiday => {
        events.push({
            // Verwende eine eindeutige ID für Feiertage, um Konflikte mit Urlaubsanträgen zu vermeiden
            id: `holiday-${dayjs(holiday.date).format('YYYY-MM-DD')}-${holiday.name}`,
            title: holiday.name,
            start: dayjs(holiday.date).format('YYYY-MM-DD'),
            allDay: true,
            backgroundColor: '#FF0000',
            borderColor: '#FF0000',
            className: 'holiday-event',
            display: 'background'
        });
    });

    // Setzt die Events über die FullCalendar API
    calendarApi.setOption('events', events);
};

// Bereinige Ressourcen vor dem Unmount
onBeforeUnmount(() => {
    if (calendarOptions.value && calendarOptions.value.events) {
        calendarOptions.value.events = []
    }
})

// Urlaubshistorie und Statistik
const fetchVacationData = async () => {
    loading.value = true
    try {
        const response = await VacationService.getUserVacationData()

        vacationStats.value = {
            baseEntitlement: response.data.stats.baseEntitlement || 30,
            carryOver: response.data.stats.carryOver || 0,
            carryOverUsed: response.data.stats.carryOverUsed || 0,
            carryOverRemaining: response.data.stats.carryOverRemaining || 0,
            carryOverExpires: response.data.stats.carryOverExpires || null,
            totalAvailable: response.data.stats.totalAvailable || 0,
            used: response.data.stats.used || 0,
            usedRegular: response.data.stats.usedRegular || 0,
            usedCarryOver: response.data.stats.usedCarryOver || 0,
            planned: response.data.stats.planned || 0,
            remaining: response.data.stats.remaining || 0,
            remainingRegular: response.data.stats.remainingRegular || 0,
            remainingCarryOver: 0
        }

        myVacationRequests.value = response.data.requests || []

        if (response.data.history) {
            vacationHistory.value = response.data.history
        }

        if (response.data.yearlyStats) {
            yearlyStats.value = response.data.yearlyStats[selectedStatYear.value] || yearlyStats.value
        }

        if (response.data.monthlyStats) {
            const monthlyData = response.data.monthlyStats[selectedStatYear.value] || Array(12).fill(0)
            monthlyChartData.value.datasets[0].data = monthlyData
        }

        // updateCalendarEvents(); // Wird jetzt durch den Watcher ausgelöst
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

        if (monthlyChartData.value && monthlyChartData.value.datasets && monthlyChartData.value.datasets.length > 0) {
            const monthlyData = Array(12).fill(0)

            yearVacationDetails.value.forEach((detail) => {
                if (detail.status === "approved") {
                    const periodParts = detail.period.split(" - ")
                    if (periodParts.length === 2) {
                        const startDateParts = periodParts[0].split(".")
                        if (startDateParts.length === 3) {
                            const month = parseInt(startDateParts[1]) - 1
                            const daysToAdd = detail.actualDays || detail.days
                            monthlyData[month] += daysToAdd
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

let toast
try {
    toast = useToast()
} catch (error) {
    console.warn("Toast service not available, using fallback")
    toast = {
        add: (message) => console.log("Toast message:", message),
    }
}

const onTabChange = (event) => {
    activeTabIndex.value = event.index

    if (event.index === 1) { // Kalender-Tab
        nextTick(() => {
            setTimeout(() => {
                window.dispatchEvent(new Event('resize')) // FullCalendar resize trigger
            }, 100)
        })
    }
}

onMounted(() => {
    const savedDarkMode = localStorage.getItem('darkMode') === 'true'
    isDarkMode.value = savedDarkMode
    document.documentElement.classList.toggle('dark', savedDarkMode)

    fetchVacationData()
    fetchHolidays(new Date().getFullYear());

    const currentYear = new Date().getFullYear()
    for (let year = currentYear - 5; year <= currentYear; year++) {
        availableYears.value.push({ name: year.toString(), value: year })
    }
    selectedStatYear.value = currentYear
})

// Watch for changes in vacation requests or holidays to update the calendar
watch([myVacationRequests, holidays], () => {
    updateCalendarEvents();
}, { deep: true }); // Deep watch for array content changes

watch(selectedStatYear, (newYear) => {
    updateYearlyStats()
    fetchHolidays(newYear);
})
</script>

<style scoped>
.vacation-card {
    @apply bg-white dark:bg-gray-800 rounded-xl p-6 text-center border border-gray-200 dark:border-gray-700 transition-all duration-300;
}

.vacation-card-icon {
    @apply w-12 h-12 rounded-lg flex items-center justify-center mx-auto transition-all duration-300;
}

.vacation-card-icon i {
    @apply text-xl;
}

.vacation-card-title {
    @apply text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 transition-colors duration-300;
}

.vacation-card-value {
    @apply text-2xl font-bold transition-colors duration-300;
}

.vacation-card-subtitle {
    @apply text-xs mt-2 transition-colors duration-300;
}

.vacation-calendar {
    @apply transition-colors duration-300;
}

.vacation-calendar-widget {
    @apply h-full;
}

.request-details {
    @apply p-4;
}

.detail-label {
    @apply font-semibold mb-1 text-gray-700 dark:text-gray-300 transition-colors duration-300;
}

.detail-value {
    @apply mb-4 text-gray-900 dark:text-white transition-colors duration-300;
}

/* PrimeVue Dark Mode Overrides */
:deep(.p-datatable) {
    @apply bg-white dark:bg-gray-800 transition-colors duration-300;
}

:deep(.p-datatable .p-datatable-header) {
    @apply bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 transition-colors duration-300;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    @apply bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white border-gray-200 dark:border-gray-600 transition-colors duration-300;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
    @apply bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 transition-colors duration-300;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    @apply bg-gray-50 dark:bg-gray-700 transition-colors duration-200;
}

:deep(.p-tabview .p-tabview-nav) {
    @apply border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 transition-colors duration-300;
    display: flex !important;
    flex-direction: row !important;
}

:deep(.p-tabview .p-tabview-nav li) {
    flex: none !important;
    margin-right: 0 !important;
}

:deep(.p-tabview .p-tabview-nav li .p-tabview-nav-link) {
    @apply text-gray-600 dark:text-gray-400 border-transparent transition-all duration-200;
    display: flex !important;
    align-items: center !important;
    padding: 1rem 1.5rem !important;
    border-bottom: 2px solid transparent !important;
    white-space: nowrap !important;
}

:deep(.p-tabview .p-tabview-nav li .p-tabview-nav-link:hover) {
    @apply text-gray-700 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50;
}

:deep(.p-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    @apply text-blue-600 dark:text-blue-400;
    border-bottom-color: #3b82f6 !important;
    background-color: transparent !important;
}

:deep(.p-tabview .p-tabview-panels) {
    @apply bg-white dark:bg-gray-800 transition-colors duration-300;
}

:deep(.p-tabview) {
    @apply h-full flex flex-col;
}

:deep(.p-tabview .p-tabview-panels) {
    @apply flex-1 flex flex-col;
}

:deep(.p-tabview .p-tabview-panel) {
    @apply flex-1 flex flex-col;
}

:deep(.p-inputtext) {
    @apply bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white transition-colors duration-200;
}

:deep(.p-inputtext:focus) {
    @apply border-blue-500 dark:border-blue-400 shadow-blue-500/25 dark:shadow-blue-400/25;
}

:deep(.p-dropdown) {
    @apply bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 transition-colors duration-200;
}

:deep(.p-dropdown .p-dropdown-label) {
    @apply text-gray-900 dark:text-white;
}

:deep(.p-dialog) {
    @apply bg-white dark:bg-gray-800 transition-colors duration-300;
}

:deep(.p-dialog .p-dialog-header) {
    @apply bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 transition-colors duration-300;
}

:deep(.p-dialog .p-dialog-title) {
    @apply text-gray-900 dark:text-white;
}

:deep(.p-dialog .p-dialog-content) {
    @apply bg-white dark:bg-gray-800 text-gray-900 dark:text-white transition-colors duration-300;
}

:deep(.p-button) {
    @apply transition-all duration-200;
}

:deep(.p-button:hover) {
    @apply transform scale-105;
}

:deep(.p-tag) {
    @apply transition-colors duration-200;
}

:deep(.p-chart) {
    @apply transition-colors duration-300;
}

/* FullCalendar Dark Mode */
:deep(.fc) {
    @apply bg-white dark:bg-gray-800 text-gray-900 dark:text-white transition-colors duration-300;
}

:deep(.fc-theme-standard .fc-scrollgrid) {
    @apply border-gray-200 dark:border-gray-600;
}

:deep(.fc-theme-standard td, .fc-theme-standard th) {
    @apply border-gray-200 dark:border-gray-600;
}

:deep(.fc-col-header-cell) {
    @apply bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white;
}

:deep(.fc-daygrid-day) {
    @apply bg-white dark:bg-gray-800;
}

:deep(.fc-daygrid-day:hover) {
    @apply bg-gray-50 dark:bg-gray-700;
}

:deep(.fc-button-primary) {
    @apply bg-[#34d399] dark:bg-[#34d399] border-[#34d399] dark:border-[#34d399];
}

:deep(.fc-button-primary:hover) {
    @apply bg-[#34d399] dark:bg-[#34d399] border-[#34d375] dark:border-[#34d375];
}
/* Smooth transitions for all elements */
* {
    @apply transition-colors duration-300;
}
/* FullCalendar Header Button Colors */
:deep(.fc-prev-button),
:deep(.fc-next-button),
:deep(.fc-today-button) {
    background-color: #34d399 !important;
    border-color: #34d399 !important;
    color: #fff !important;
}
:deep(.fc-prev-button:hover),
:deep(.fc-next-button:hover),
:deep(.fc-today-button:hover) {
    background-color: #34d399 !important;
    border-color: #34d399 !important;
}
:deep(.fc-dayGridMonth-button),
:deep(.fc-timeGridWeek-button) {
    background-color: #34d399 !important;
    border-color: #34d399 !important;
    color: #fff !important;
}
</style>
