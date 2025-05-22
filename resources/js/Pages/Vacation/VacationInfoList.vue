<template>
    <AppLayout title="Urlaub-Info-Liste">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Urlaub-Info-Liste
                </h2>
                <div class="flex items-center gap-2">
                    <Button icon="pi pi-download" label="Exportieren" class="p-button-outlined" @click="exportData" />
                    <Button icon="pi pi-refresh" class="p-button-outlined" @click="refreshData" />
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Informationskarte -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
                    <div class="flex items-start">
                        <i class="pi pi-info-circle text-blue-500 text-2xl mr-4 mt-1"></i>
                        <div>
                            <h3 class="text-lg font-semibold mb-2">Urlaub-Info-Liste für HR</h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                Diese Liste zeigt detaillierte Informationen über den Urlaubsstatus aller Mitarbeiter.
                            </p>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">
                                <strong>Aktueller Monat:</strong> {{ currentMonthName }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Haupttabelle -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-lg">
                    <div class="p-6">
                        <DataTable
                            :value="vacationData"
                            :paginator="vacationData.length > 10"
                            :rows="10"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            dataKey="id"
                            :rowHover="true"
                            responsiveLayout="scroll"
                            class="p-datatable-sm modern-datatable"
                            :loading="loading"
                            v-model:filters="filters"
                            filterDisplay="menu"
                            :globalFilterFields="['name', 'department', 'personnel_number']"
                            stripedRows
                        >
                            <template #header>
                                <div class="flex justify-between items-center w-full">
                                    <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">Urlaub-Info-Liste</h3>
                                    <span class="p-input-icon-left ml-auto">
                                        <i class="pi pi-search" />
                                        <InputText v-model="filters['global'].value" placeholder="Suchen..." class="p-inputtext-sm" />
                                    </span>
                                </div>
                            </template>

                            <template #empty>
                                <div class="text-center p-6">
                                    <i class="pi pi-inbox text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                    <p class="text-gray-500 dark:text-gray-400">Keine Daten verfügbar</p>
                                </div>
                            </template>

                            <!-- Mitarbeiter-Spalte -->
                            <Column field="name" header="Mitarbeiter" :sortable="true" :filter="true" filterMatchMode="contains">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-3">
                                        <Avatar :label="getInitials(data.name)" shape="circle" size="large" :style="{ backgroundColor: getInitialsColor(data.name) }" />
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ data.name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ data.department }}</div>
                                        </div>
                                    </div>
                                </template>
                                <template #filter="{ filterModel, filterCallback }">
                                    <InputText v-model="filterModel.value" @input="filterCallback()" placeholder="Nach Name suchen" class="p-column-filter w-full" />
                                </template>
                            </Column>

                            <!-- Personal NR -->
                            <Column field="personnel_number" header="Personal NR" :sortable="true" :filter="true" filterMatchMode="contains">
                                <template #body="{ data }">
                                    <div class="text-center">
                                        {{ data.personnel_number }}
                                    </div>
                                </template>
                                <template #filter="{ filterModel, filterCallback }">
                                    <InputText v-model="filterModel.value" @input="filterCallback()" placeholder="Nach Personalnummer suchen" class="p-column-filter w-full" />
                                </template>
                            </Column>

                            <!-- Abteilungs-Spalte -->
                            <Column field="department" header="Abteilung" :sortable="true" :filter="true" filterMatchMode="equals">
                                <template #body="{ data }">
                                    <div class="text-center">
                                        <Tag :value="data.department" severity="info" />
                                    </div>
                                </template>
                                <template #filter="{ filterModel, filterCallback }">
                                    <Dropdown
                                        v-model="filterModel.value"
                                        :options="departments"
                                        @change="filterCallback()"
                                        placeholder="Alle Abteilungen"
                                        class="p-column-filter w-full"
                                        showClear
                                    />
                                </template>
                            </Column>

                            <!-- Jährliches Urlaubskontingent -->
                            <Column field="vacation_days_per_year" header="Urlaubstage pro Jahr" :sortable="true" :filter="true" filterMatchMode="equals">
                                <template #body="{ data }">
                                    <div class="text-center font-semibold">
                                        {{ data.vacation_days_per_year }} Tage
                                    </div>
                                </template>
                                <template #filter="{ filterModel, filterCallback }">
                                    <InputNumber v-model="filterModel.value" @input="filterCallback()" placeholder="Tage" class="p-column-filter w-full" />
                                </template>
                            </Column>

                            <!-- Resttage aus dem Vorjahr -->
                            <Column field="carry_over_previous_year" header="Resttage (letztes Jahr)" :sortable="true">
                                <template #body="{ data }">
                                    <div class="text-center" :class="{ 'text-green-600 dark:text-green-400 font-semibold': data.carry_over_previous_year > 0 }">
                                        {{ data.carry_over_previous_year }} Tage
                                    </div>
                                </template>
                            </Column>

                            <!-- Urlaub gesamt -->
                            <Column field="total_entitlement" header="Urlaub gesamt" :sortable="true">
                                <template #body="{ data }">
                                    <div class="text-center font-semibold">
                                        {{ data.total_entitlement }} Tage
                                    </div>
                                </template>
                            </Column>

                            <!-- Genehmigt aktuelles Jahr -->
                            <Column field="approved_days_current_year" header="Genehmigt aktuelles Jahr" :sortable="true">
                                <template #body="{ data }">
                                    <div class="text-center">
                                        {{ data.approved_days_current_year }} Tage
                                    </div>
                                </template>
                            </Column>

                            <!-- Resttage (aktuelles Jahr) -->
                            <Column field="remaining_days_current_year" header="Resttage (aktuelles Jahr)" :sortable="true">
                                <template #body="{ data }">
                                    <div class="text-center font-semibold" :class="{
                                        'text-green-600 dark:text-green-400': data.remaining_days_current_year > 10,
                                        'text-yellow-600 dark:text-yellow-400': data.remaining_days_current_year > 0 && data.remaining_days_current_year <= 10,
                                        'text-red-600 dark:text-red-400': data.remaining_days_current_year <= 0
                                    }">
                                        {{ data.remaining_days_current_year }} Tage
                                    </div>
                                </template>
                            </Column>

                            <!-- Laufender Monat -->
                            <Column field="current_month_days" header="Laufender Monat" :sortable="true">
                                <template #body="{ data }">
                                    <div class="text-center">
                                        {{ data.current_month_days }} Tage
                                    </div>
                                </template>
                            </Column>

                            <!-- Resturlaub Vormonat -->
                            <Column field="remaining_days_previous_month" header="Resturlaub Vormonat" :sortable="true">
                                <template #body="{ data }">
                                    <div class="text-center" :class="{
                                        'text-green-600 dark:text-green-400': data.remaining_days_previous_month > 10,
                                        'text-yellow-600 dark:text-yellow-400': data.remaining_days_previous_month > 0 && data.remaining_days_previous_month <= 10,
                                        'text-red-600 dark:text-red-400': data.remaining_days_previous_month <= 0
                                    }">
                                        {{ data.remaining_days_previous_month }} Tage
                                    </div>
                                </template>
                            </Column>

                            <!-- Aktionen -->
                            <Column header="Aktionen" :exportable="false" style="min-width: 8rem">
                                <template #body="{ data }">
                                    <Button
                                        icon="pi pi-eye"
                                        class="p-button-secondary p-button-rounded"
                                        @click="viewEmployeeDetails(data)"
                                        tooltip="Details anzeigen"
                                        tooltipOptions="{ position: 'top' }"
                                    />
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog für Mitarbeiterdetails -->
        <Dialog
            v-model:visible="detailsDialogVisible"
            :header="`Urlaubsdetails: ${selectedEmployee ? selectedEmployee.name : ''}`"
            :style="{ width: '800px' }"
            :modal="true"
            :closable="true"
            class="modern-dialog"
        >
            <div v-if="selectedEmployee" class="p-4">
                <div class="grid grid-cols-1 gap-6 mb-6">
                    <!-- Mitarbeiterinformationen -->
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold mb-3">Mitarbeiterinformationen</h4>
                        <div class="flex items-center gap-3 mb-4">
                            <Avatar :label="getInitials(selectedEmployee.name)" shape="circle" size="large" :style="{ backgroundColor: getInitialsColor(selectedEmployee.name) }" />
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ selectedEmployee.name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ selectedEmployee.department }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Personal NR: {{ selectedEmployee.personnel_number }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Urlaubsübersicht -->
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold mb-3">Urlaubsübersicht</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Urlaubstage pro Jahr</div>
                                <div class="font-medium">{{ selectedEmployee.vacation_days_per_year }} Tage</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Resttage (letztes Jahr)</div>
                                <div class="font-medium">{{ selectedEmployee.carry_over_previous_year }} Tage</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Urlaub gesamt</div>
                                <div class="font-medium">{{ selectedEmployee.total_entitlement }} Tage</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Genehmigt aktuelles Jahr</div>
                                <div class="font-medium">{{ selectedEmployee.approved_days_current_year }} Tage</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Resttage (aktuelles Jahr)</div>
                                <div class="font-medium" :class="{
                                    'text-green-600 dark:text-green-400': selectedEmployee.remaining_days_current_year > 10,
                                    'text-yellow-600 dark:text-yellow-400': selectedEmployee.remaining_days_current_year > 0 && selectedEmployee.remaining_days_current_year <= 10,
                                    'text-red-600 dark:text-red-400': selectedEmployee.remaining_days_current_year <= 0
                                }">{{ selectedEmployee.remaining_days_current_year }} Tage</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Resturlaub Vormonat</div>
                                <div class="font-medium" :class="{
                                    'text-green-600 dark:text-green-400': selectedEmployee.remaining_days_previous_month > 10,
                                    'text-yellow-600 dark:text-yellow-400': selectedEmployee.remaining_days_previous_month > 0 && selectedEmployee.remaining_days_previous_month <= 10,
                                    'text-red-600 dark:text-red-400': selectedEmployee.remaining_days_previous_month <= 0
                                }">{{ selectedEmployee.remaining_days_previous_month }} Tage</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Urlaubstage im {{ currentMonthName }}</div>
                                <div class="font-medium">{{ selectedEmployee.current_month_days }} Tage</div>
                            </div>
                        </div>
                    </div>

                    <!-- Urlaubsvisualisierung -->
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold mb-3">Urlaubsvisualisierung</h4>
                        <div class="mb-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Urlaubsverbrauch</div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                                <div class="bg-blue-600 h-4 rounded-full" :style="{ width: `${(selectedEmployee.approved_days_current_year / selectedEmployee.total_entitlement) * 100}%` }"></div>
                            </div>
                            <div class="flex justify-between text-xs mt-1">
                                <span>0%</span>
                                <span>{{ Math.round((selectedEmployee.approved_days_current_year / selectedEmployee.total_entitlement) * 100) }}%</span>
                                <span>100%</span>
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Resturlaub</div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                                <div class="bg-green-600 h-4 rounded-full" :style="{ width: `${(selectedEmployee.remaining_days_current_year / selectedEmployee.total_entitlement) * 100}%` }"></div>
                            </div>
                            <div class="flex justify-between text-xs mt-1">
                                <span>0%</span>
                                <span>{{ Math.round((selectedEmployee.remaining_days_current_year / selectedEmployee.total_entitlement) * 100) }}%</span>
                                <span>100%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button
                        label="Schließen"
                        icon="pi pi-times"
                        class="p-button-text"
                        @click="detailsDialogVisible = false"
                    />
                </div>
            </template>
        </Dialog>

        <Toast position="top-right" />
    </AppLayout>
</template>

<script>
import { defineComponent } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dialog from 'primevue/dialog';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import Tag from 'primevue/tag';
import Dropdown from 'primevue/dropdown';
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/de';

dayjs.locale('de');

export default defineComponent({
    components: {
        AppLayout,
        DataTable,
        Column,
        Button,
        InputText,
        InputNumber,
        Dialog,
        Avatar,
        Toast,
        Tag,
        Dropdown
    },

    data() {
        return {
            loading: false,
            vacationData: [],
            currentMonth: new Date().getMonth() + 1, // 1-basiert (1 = Januar, 2 = Februar, ...)
            currentMonthName: '',
            previousMonth: 0,
            previousMonthName: '',
            detailsDialogVisible: false,
            selectedEmployee: null,
            departments: [], // Liste der verfügbaren Abteilungen
            filters: {
                global: { value: null, matchMode: 'contains' },
                name: { value: null, matchMode: 'contains' },
                department: { value: null, matchMode: 'equals' },
                personnel_number: { value: null, matchMode: 'contains' },
                vacation_days_per_year: { value: null, matchMode: 'equals' }
            }
        };
    },

    mounted() {
        this.fetchData();
    },

    methods: {
        getMonthName(month) {
            const date = new Date();
            date.setMonth(month - 1); // 0-basiert für Date-Objekt
            return date.toLocaleString('de-DE', { month: 'long' });
        },

        getInitials(name) {
            return name
                .split(' ')
                .map(part => part.charAt(0))
                .join('')
                .toUpperCase();
        },

        getInitialsColor(name) {
            // Generate a deterministic color based on the name
            let hash = 0;
            for (let i = 0; i < name.length; i++) {
                hash = name.charCodeAt(i) + ((hash << 5) - hash);
            }

            const hue = hash % 360;
            return `hsl(${hue}, 70%, 60%)`;
        },

        // Extrahiere eindeutige Abteilungen aus den Daten
        extractDepartments() {
            if (!this.vacationData || this.vacationData.length === 0) return [];

            const uniqueDepartments = [...new Set(this.vacationData.map(employee => employee.department))];
            return uniqueDepartments.sort();
        },

        async fetchData() {
            this.loading = true;
            try {
                const response = await axios.get('/api/vacation/info-list');
                this.vacationData = response.data.data;
                this.currentMonth = response.data.current_month;
                this.previousMonth = response.data.previous_month;
                this.currentMonthName = this.getMonthName(this.currentMonth);
                this.previousMonthName = this.getMonthName(this.previousMonth);

                // Extrahiere die Abteilungen für den Filter
                this.departments = this.extractDepartments();
            } catch (error) {
                console.error('Fehler beim Laden der Urlaub-Info-Liste:', error);
                this.$toast.add({
                    severity: 'error',
                    summary: 'Fehler',
                    detail: 'Die Urlaub-Info-Liste konnte nicht geladen werden.',
                    life: 3000
                });
            } finally {
                this.loading = false;
            }
        },

        refreshData() {
            this.fetchData();
            this.$toast.add({
                severity: 'info',
                summary: 'Aktualisiert',
                detail: 'Die Daten wurden aktualisiert.',
                life: 3000
            });
        },

        viewEmployeeDetails(employee) {
            this.selectedEmployee = employee;
            this.detailsDialogVisible = true;
        },

        exportData() {
            if (this.vacationData.length === 0) {
                this.$toast.add({
                    severity: 'warn',
                    summary: 'Keine Daten',
                    detail: 'Es sind keine Daten zum Exportieren vorhanden.',
                    life: 3000
                });
                return;
            }

            // Erstelle CSV-Inhalt
            let csvContent = "data:text/csv;charset=utf-8,";

            // Header
            const headers = [
                "Mitarbeiter",
                "Personal NR",
                "Abteilung",
                "Urlaubstage pro Jahr",
                "Resttage (letztes Jahr)",
                "Urlaub gesamt",
                "Genehmigt aktuelles Jahr",
                "Resttage (aktuelles Jahr)",
                "Laufender Monat",
                "Resturlaub Vormonat"
            ];

            csvContent += headers.join(";") + "\r\n";

            // Daten
            this.vacationData.forEach(employee => {
                const row = [
                    employee.name,
                    employee.personnel_number,
                    employee.department,
                    employee.vacation_days_per_year,
                    employee.carry_over_previous_year,
                    employee.total_entitlement,
                    employee.approved_days_current_year,
                    employee.remaining_days_current_year,
                    employee.current_month_days,
                    employee.remaining_days_previous_month
                ];

                csvContent += row.join(";") + "\r\n";
            });

            // Erstelle einen Download-Link und klicke darauf
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `urlaub_info_liste_${dayjs().format('YYYY-MM-DD')}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            this.$toast.add({
                severity: 'success',
                summary: 'Export erfolgreich',
                detail: 'Die Daten wurden erfolgreich exportiert.',
                life: 3000
            });
        }
    }
});
</script>

<style scoped>
.modern-datatable {
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

:deep(.modern-datatable .p-datatable-header) {
    background-color: var(--surface-section);
    border: none;
    padding: 1.25rem;
}

:deep(.modern-datatable .p-datatable-thead > tr > th) {
    background-color: var(--surface-section);
    padding: 1rem;
    font-weight: 600;
    color: var(--text-color-secondary);
    border-color: var(--surface-border);
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
}

:deep(.modern-datatable .p-datatable-tbody > tr) {
    transition: all 0.2s;
    border-color: var(--surface-border);
}

:deep(.modern-datatable .p-datatable-tbody > tr > td) {
    padding: 1rem;
    border-color: var(--surface-border);
}

:deep(.modern-datatable .p-datatable-tbody > tr:hover) {
    background-color: var(--surface-hover);
}

:deep(.modern-datatable .p-paginator) {
    padding: 1rem;
    background-color: var(--surface-section);
    border: none;
}

/* Modern Dialog Styles */
:deep(.modern-dialog) {
    border-radius: 0.75rem;
    overflow: hidden;
}

:deep(.modern-dialog .p-dialog-header) {
    padding: 1.5rem;
    background-color: var(--surface-card);
    border-bottom: 1px solid var(--surface-border);
}

:deep(.modern-dialog .p-dialog-title) {
    font-weight: 600;
    font-size: 1.25rem;
}

:deep(.modern-dialog .p-dialog-content) {
    padding: 0;
    background-color: var(--surface-card);
}

:deep(.modern-dialog .p-dialog-footer) {
    padding: 0 1.5rem 1.5rem 1.5rem;
    background-color: var(--surface-card);
    border-top: none;
}


/* Dark mode adjustments */
:deep(.dark .p-datatable .p-datatable-thead > tr > th) {
    background-color: var(--surface-card);
}

:deep(.dark .p-datatable .p-datatable-tbody > tr:nth-child(even)) {
    background-color: rgba(255, 255, 255, 0.02);
}

:deep(.dark .p-datatable .p-datatable-tbody > tr:hover) {
    background-color: rgba(255, 255, 255, 0.05);
}
</style>
