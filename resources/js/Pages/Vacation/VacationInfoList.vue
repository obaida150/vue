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

        <div class="py-6">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
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
                                        <i class="pi pi-search p-2" />
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
                                    <div class="flex items-center gap-3 w-60">
                                        <Avatar :label="data.initials || getInitials(data.name)" shape="circle" size="large" :style="{ backgroundColor: getInitialsColor(data.name) }" />
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
                                    <Select
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
                            <Column header="Aktion" :exportable="false" style="min-width: 2rem">
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
                            <Avatar :label=" selectedEmployee.initials ||getInitials(selectedEmployee.name)" shape="circle" size="large" :style="{ backgroundColor: getInitialsColor(selectedEmployee.name) }" />
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
import Select from 'primevue/select';
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
        Select
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
/* Modernisierte Styles für die DataTable / Dialog (nur Styling, keine Funktionalität geändert) */

.modern-datatable {
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
    background-clip: padding-box;
}

/* Header */
:deep(.modern-datatable .p-datatable-header) {
    background: linear-gradient(90deg, #ffffff 0%, #f8fafc 100%);
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e6eef6;
}

/* Table Header */
:deep(.modern-datatable .p-datatable-thead > tr > th) {
    background-color: #f8fafc;
    padding: 0.85rem 1rem;
    font-weight: 700;
    color: #64748b; /* text-secondary */
    border: none;
    border-bottom: 2px solid #e2e8f0; /* surface-border */
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.08em;
    vertical-align: middle;
}

/* Table Body */
:deep(.modern-datatable .p-datatable-tbody > tr) {
    transition: background-color 0.18s ease, transform 0.18s ease;
    background-color: #ffffff;
}

:deep(.modern-datatable .p-datatable-tbody > tr > td) {
    padding: 0.9rem 1rem;
    border-bottom: 1px solid #f1f5f9; /* surface-border light */
    vertical-align: middle;
}

/* Row Hover */
:deep(.modern-datatable .p-datatable-tbody > tr:hover) {
    background: linear-gradient(90deg, #f1f9ff 0%, #eefcff 100%);
    transform: translateZ(0);
    box-shadow: 0 8px 20px rgba(14, 165, 233, 0.06);
}

/* Striped rows */
:deep(.modern-datatable .p-datatable-tbody > tr:nth-child(even)) {
    background-color: #fbfdff;
}

/* Sort icons */
:deep(.modern-datatable .p-sortable-column .p-sortable-column-icon) {
    color: #cbd5e1;
    margin-left: 0.45rem;
    transition: color 0.15s;
}
:deep(.modern-datatable .p-sortable-column:hover .p-sortable-column-icon),
:deep(.modern-datatable .p-sortable-column.p-highlight .p-sortable-column-icon) {
    color: #2563eb;
}

/* Paginator */
:deep(.modern-datatable .p-paginator) {
    padding: 0.9rem 1rem;
    background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
    border-top: 1px solid #e6eef6;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
:deep(.modern-datatable .p-paginator .p-paginator-pages .p-paginator-page) {
    min-width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.5rem;
    margin: 0 0.1rem;
    transition: all 0.15s;
    font-weight: 600;
}
:deep(.modern-datatable .p-paginator .p-paginator-pages .p-paginator-page:hover) {
    background-color: #e6f0ff;
    color: #1e40af;
}
:deep(.modern-datatable .p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
    background: linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);
    color: #fff;
    box-shadow: 0 8px 30px rgba(37, 99, 235, 0.18);
}

/* Filters / Inputs */
:deep(.modern-datatable .p-column-filter),
:deep(.modern-datatable .p-inputtext),
:deep(.modern-datatable .p-inputnumber) {
    border-radius: 0.5rem;
    border: 1px solid #e6eef6;
    padding: 0.45rem 0.6rem;
    transition: box-shadow 0.15s, border-color 0.15s;
}
:deep(.modern-datatable .p-column-filter:focus),
:deep(.modern-datatable .p-inputtext:focus),
:deep(.modern-datatable .p-inputnumber:focus) {
    border-color: #2563eb;
    box-shadow: 0 0 0 6px rgba(37, 99, 235, 0.08);
}

/* Empty state & loading overlay */
:deep(.modern-datatable .p-datatable-emptymessage td) {
    padding: 3.25rem 2rem;
    text-align: center;
    color: #64748b;
}
:deep(.modern-datatable .p-datatable-loading-overlay) {
    background-color: rgba(255,255,255,0.85);
    backdrop-filter: blur(3px);
}

/* Dialog (modern-dialog) */
:deep(.modern-dialog) {
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(2,6,23,0.25);
}
:deep(.modern-dialog .p-dialog-header) {
    padding: 1rem 1.25rem;
    background: linear-gradient(90deg,#ffffff 0%,#f3f7fb 100%);
    border-bottom: 1px solid #e6eef6;
}
:deep(.modern-dialog .p-dialog-title) {
    font-weight: 700;
    font-size: 1.15rem;
    color: #0f172a;
}
:deep(.modern-dialog .p-dialog-content),
:deep(.modern-dialog .p-dialog-footer) {
    background-color: #ffffff;
}

/* Avatar, Tag, Buttons */
:deep(.p-avatar) {
    box-shadow: 0 4px 14px rgba(2,6,23,0.08);
    font-weight: 600;
}
:deep(.p-tag) {
    padding: 0.25rem 0.6rem;
    border-radius: 9999px;
    font-weight: 600;
    font-size: 0.75rem;
    box-shadow: 0 2px 8px rgba(2,6,23,0.04);
}
:deep(.p-button-rounded) {
    transition: transform 0.12s ease, box-shadow 0.12s ease;
}
:deep(.p-button-rounded:hover) {
    transform: scale(1.06);
    box-shadow: 0 8px 24px rgba(2,6,23,0.12);
}

/* Dark mode adjustments (class-based) */
:deep(.dark .p-datatable .p-datatable-header),
:deep(.dark .modern-datatable .p-datatable-header) {
    background: linear-gradient(90deg,#0b1220 0%,#0f172a 100%);
    border-bottom: 1px solid rgba(255,255,255,0.04);
}
:deep(.dark .p-datatable .p-datatable-thead > tr > th),
:deep(.dark .modern-datatable .p-datatable-thead > tr > th) {
    background-color: transparent;
    color: #94a3b8;
    border-bottom: 2px solid rgba(255,255,255,0.03);
}
:deep(.dark .p-datatable .p-datatable-tbody > tr) {
    background-color: transparent;
}
:deep(.dark .p-datatable .p-datatable-tbody > tr > td) {
    border-bottom: 1px solid rgba(255,255,255,0.03);
    color: #e6eef6;
}
:deep(.dark .p-datatable .p-datatable-tbody > tr:hover) {
    background: linear-gradient(90deg, rgba(59,130,246,0.06), rgba(14,165,233,0.04));
}
:deep(.dark .modern-dialog .p-dialog-header),
:deep(.dark .modern-dialog .p-dialog-content),
:deep(.dark .modern-dialog .p-dialog-footer) {
    background-color: #0b1220;
    color: #e6eef6;
    border-color: rgba(255,255,255,0.04);
}
</style>
