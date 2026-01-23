<template>
    <AppLayout title="HR Urlaubsübersicht">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    HR Urlaubsübersicht
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
                    <div class="flex items-center">
                        <i class="pi pi-info-circle text-blue-500 text-2xl mr-4 mt-1"></i>
                        <div>
                            <h3 class="text-lg font-semibold mb-2">Urlaubsübersicht für die Lohnabrechnung</h3>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">
                                <strong>Aktueller Monat:</strong> {{ getMonthName(currentMonth) }}
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
                            :globalFilterFields="['name', 'department']"
                            stripedRows
                        >
                            <template #header>
                                <div class="flex justify-between items-center w-full">
                                    <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">Urlaubsübersicht</h3>
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
                            <Column field="vacation_days_per_year" header="Jährliches Kontingent" :sortable="true" :filter="false" filterMatchMode="equals">
                                <template #body="{ data }">
                                    <div class="text-center font-semibold">
                                        {{ data.vacation_days_per_year }} Tage
                                    </div>
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

                            <!-- Gesamtresttage -->
                            <Column field="remaining_days_total" header="Resttage (Gesamt)" :sortable="true">
                                <template #body="{ data }">
                                    <div class="text-center font-semibold" :class="{
                                        'text-green-600 dark:text-green-400': data.remaining_days_total > 10,
                                        'text-yellow-600 dark:text-yellow-400': data.remaining_days_total > 0 && data.remaining_days_total <= 10,
                                        'text-red-600 dark:text-red-400': data.remaining_days_total <= 0
                                    }">
                                        {{ data.remaining_days_total }} Tage
                                    </div>
                                </template>
                            </Column>

                            <!-- Monatliche Resttage -->
                            <Column v-for="month in displayMonths" :key="month" :field="`monthly_remaining.month_${month}`" :header="getMonthName(month)" :sortable="true">
                                <template #body="{ data }">
                                    <div class="text-center" :class="{
                                        'text-green-600 dark:text-green-400': data.monthly_remaining[`month_${month}`] > 10,
                                        'text-yellow-600 dark:text-yellow-400': data.monthly_remaining[`month_${month}`] > 0 && data.monthly_remaining[`month_${month}`] <= 10,
                                        'text-red-600 dark:text-red-400': data.monthly_remaining[`month_${month}`] <= 0
                                    }">
                                        {{ data.monthly_remaining[`month_${month}`] }} Tage
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
            :style="{ width: '900px' }"
            :modal="true"
            :closable="true"
            class="modern-dialog"
        >
            <div v-if="selectedEmployee" class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Mitarbeiterinformationen -->
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold mb-3">Mitarbeiterinformationen</h4>
                        <div class="flex items-center gap-3 mb-4">
                            <Avatar :label="selectedEmployee.initials || getInitials(selectedEmployee.name)" shape="circle" size="large" :style="{ backgroundColor: getInitialsColor(selectedEmployee.name) }" />
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ selectedEmployee.name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ selectedEmployee.department }}</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Jährliches Kontingent</div>
                                <div class="font-medium">{{ selectedEmployee.vacation_days_per_year }} Tage</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Resttage (letztes Jahr)</div>
                                <div class="font-medium">{{ selectedEmployee.carry_over_previous_year }} Tage</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Gesamtanspruch</div>
                                <div class="font-medium">{{ selectedEmployee.total_entitlement }} Tage</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Genommene Tage</div>
                                <div class="font-medium">{{ selectedEmployee.used_days_total }} Tage</div>
                            </div>
                        </div>
                    </div>

                    <!-- Monatliche Übersicht -->
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold mb-3">Monatliche Resttage</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white dark:bg-gray-700 rounded-lg">
                                <thead>
                                <tr>
                                    <th class="py-2 px-4 border-b text-left">Monat</th>
                                    <th class="py-2 px-4 border-b text-right">Resttage</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="month in displayMonths" :key="month">
                                    <td class="py-2 px-4 border-b">{{ getMonthName(month) }}</td>
                                    <td class="py-2 px-4 border-b text-right" :class="{
                                            'text-green-600 dark:text-green-400': selectedEmployee.monthly_remaining[`month_${month}`] > 10,
                                            'text-yellow-600 dark:text-yellow-400': selectedEmployee.monthly_remaining[`month_${month}`] > 0 && selectedEmployee.monthly_remaining[`month_${month}`] <= 10,
                                            'text-red-600 dark:text-red-400': selectedEmployee.monthly_remaining[`month_${month}`] <= 0
                                        }">
                                        {{ selectedEmployee.monthly_remaining[`month_${month}`] }} Tage
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Urlaubsanträge des Mitarbeiters -->
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold mb-3">Urlaubsanträge</h4>

                    <div v-if="selectedEmployee.vacation_requests && selectedEmployee.vacation_requests.length > 0">
                        <DataTable
                            :value="selectedEmployee.vacation_requests"
                            :paginator="selectedEmployee.vacation_requests.length > 5"
                            :rows="5"
                            class="p-datatable-sm"
                            stripedRows
                            responsiveLayout="scroll"
                        >
                            <!-- Zeitraum -->
                            <Column header="Zeitraum" style="min-width: 200px">
                                <template #body="{ data }">
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ formatDate(data.start_date) }} - {{ formatDate(data.end_date) }}</span>
                                        <span class="text-sm text-gray-500">{{ data.days }} Tage</span>
                                    </div>
                                </template>
                            </Column>

                            <!-- Status -->
                            <Column header="Status" style="min-width: 120px">
                                <template #body="{ data }">
                                    <Tag :value="data.status_text" :severity="getStatusSeverity(data.status)" />
                                </template>
                            </Column>

                            <!-- Genehmiger -->
                            <Column header="Genehmiger" style="min-width: 150px">
                                <template #body="{ data }">
                                    <div>{{ data.approver || '-' }}</div>
                                </template>
                            </Column>

                            <!-- Datum -->
                            <Column header="Erstellt am" style="min-width: 150px">
                                <template #body="{ data }">
                                    <div>{{ formatDateTime(data.created_at) }}</div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <div v-else class="text-center py-6">
                        <i class="pi pi-calendar-times text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                        <p class="text-gray-500 dark:text-gray-400">Keine Urlaubsanträge für dieses Jahr vorhanden.</p>
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
            displayMonths: 0, // Wird vom Backend gesetzt
            detailsDialogVisible: false,
            selectedEmployee: null,
            departments: [], // Liste der verfügbaren Abteilungen
            filters: {
                global: { value: null, matchMode: 'contains' },
                name: { value: null, matchMode: 'contains' },
                department: { value: null, matchMode: 'equals' },
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

        formatDate(dateString) {
            return dayjs(dateString).format('DD.MM.YYYY');
        },

        formatDateTime(dateTimeString) {
            return dayjs(dateTimeString).format('DD.MM.YYYY HH:mm');
        },

        getStatusSeverity(status) {
            const severityMap = {
                'pending': 'warning',
                'approved': 'success',
                'rejected': 'danger',
                'canceled': 'info'
            };

            return severityMap[status] || 'info';
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
                const response = await axios.get('/api/vacation/hr-overview');
                this.vacationData = response.data.data;
                this.currentMonth = response.data.current_month;
                this.displayMonths = response.data.display_months;

                // Extrahiere die Abteilungen für den Filter
                this.departments = this.extractDepartments();
            } catch (error) {
                console.error('Fehler beim Laden der HR-Urlaubsübersicht:', error);
                this.$toast.add({
                    severity: 'error',
                    summary: 'Fehler',
                    detail: error.response?.data?.message || 'Die Urlaubsübersicht konnte nicht geladen werden.',
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
            const headers = ["Mitarbeiter", "Abteilung", "Jährliches Kontingent", "Resttage (letztes Jahr)", "Resttage (Gesamt)"];

            // Füge monatliche Header hinzu
            for (let month = 1; month <= this.displayMonths; month++) {
                headers.push(`Resttage ${this.getMonthName(month)}`);
            }

            csvContent += headers.join(";") + "\r\n";

            // Daten
            this.vacationData.forEach(employee => {
                const row = [
                    employee.name,
                    employee.department,
                    employee.vacation_days_per_year,
                    employee.carry_over_previous_year,
                    employee.remaining_days_total
                ];

                // Füge monatliche Daten hinzu
                for (let month = 1; month <= this.displayMonths; month++) {
                    row.push(employee.monthly_remaining[`month_${month}`]);
                }

                csvContent += row.join(";") + "\r\n";
            });

            // Erstelle einen Download-Link und klicke darauf
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `urlaubsuebersicht_hr_${dayjs().format('YYYY-MM-DD')}.csv`);
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
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Header */
:deep(.modern-datatable .p-datatable-header) {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: none;
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.dark :deep(.modern-datatable .p-datatable-header) {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-bottom: 1px solid #334155;
}

/* Table Header */
:deep(.modern-datatable .p-datatable-thead > tr > th) {
    background-color: #f8fafc;
    padding: 1rem 1.25rem;
    font-weight: 700;
    color: #64748b;
    border: none;
    border-bottom: 2px solid #e2e8f0;
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.1em;
    transition: background-color 0.2s ease;
}

.dark :deep(.modern-datatable .p-datatable-thead > tr > th) {
    background-color: #1e293b;
    color: #94a3b8;
    border-bottom: 2px solid #334155;
}

:deep(.modern-datatable .p-datatable-thead > tr > th:hover) {
    background-color: #f1f5f9;
}

.dark :deep(.modern-datatable .p-datatable-thead > tr > th:hover) {
    background-color: #334155;
}

/* Table Body */
:deep(.modern-datatable .p-datatable-tbody > tr) {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
}

:deep(.modern-datatable .p-datatable-tbody > tr > td) {
    padding: 1.25rem;
    border: none;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.dark :deep(.modern-datatable .p-datatable-tbody > tr > td) {
    border-bottom: 1px solid #1e293b;
}

:deep(.modern-datatable .p-datatable-tbody > tr:last-child > td) {
    border-bottom: none;
}

/* Row Hover Effect */
:deep(.modern-datatable .p-datatable-tbody > tr:hover) {
    background: linear-gradient(90deg, #eff6ff 0%, #f0f9ff 50%, #ecfeff 100%) !important;
    transform: scale(1.002);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.08);
}

.dark :deep(.modern-datatable .p-datatable-tbody > tr:hover) {
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.1) 0%, rgba(14, 165, 233, 0.08) 100%) !important;
}

/* Striped Rows */
:deep(.modern-datatable .p-datatable-tbody > tr:nth-child(even)) {
    background-color: #fafbfc;
}

.dark :deep(.modern-datatable .p-datatable-tbody > tr:nth-child(even)) {
    background-color: rgba(255, 255, 255, 0.02);
}

/* Sortable Header Icons */
:deep(.modern-datatable .p-sortable-column .p-sortable-column-icon) {
    color: #cbd5e1;
    margin-left: 0.5rem;
    transition: color 0.2s ease;
}

:deep(.modern-datatable .p-sortable-column:hover .p-sortable-column-icon) {
    color: #3b82f6;
}

:deep(.modern-datatable .p-sortable-column.p-highlight .p-sortable-column-icon) {
    color: #3b82f6;
}

/* Paginator */
:deep(.modern-datatable .p-paginator) {
    padding: 1.25rem 1.5rem;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border: none;
    border-top: 1px solid #e2e8f0;
    gap: 0.5rem;
}

.dark :deep(.modern-datatable .p-paginator) {
    background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
    border-top: 1px solid #334155;
}

:deep(.modern-datatable .p-paginator .p-paginator-pages .p-paginator-page) {
    min-width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    margin: 0 0.15rem;
    transition: all 0.2s ease;
    font-weight: 500;
}

:deep(.modern-datatable .p-paginator .p-paginator-pages .p-paginator-page:hover) {
    background-color: #eff6ff;
    color: #3b82f6;
}

:deep(.modern-datatable .p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

/* Filter Input */
:deep(.modern-datatable .p-column-filter) {
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
}

:deep(.modern-datatable .p-column-filter:focus) {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

/* Empty State */
:deep(.modern-datatable .p-datatable-emptymessage td) {
    padding: 4rem 2rem;
    text-align: center;
}

/* Loading Overlay */
:deep(.modern-datatable .p-datatable-loading-overlay) {
    background-color: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(4px);
}

.dark :deep(.modern-datatable .p-datatable-loading-overlay) {
    background-color: rgba(15, 23, 42, 0.8);
}

/* Modern Dialog Styles */
:deep(.modern-dialog) {
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

:deep(.modern-dialog .p-dialog-header) {
    padding: 1.75rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-bottom: 1px solid #e2e8f0;
}

.dark :deep(.modern-dialog .p-dialog-header) {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-bottom: 1px solid #334155;
}

:deep(.modern-dialog .p-dialog-title) {
    font-weight: 700;
    font-size: 1.35rem;
    background: linear-gradient(135deg, #1e293b 0%, #475569 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.dark :deep(.modern-dialog .p-dialog-title) {
    background: linear-gradient(135deg, #f1f5f9 0%, #cbd5e1 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

:deep(.modern-dialog .p-dialog-content) {
    padding: 0;
    background-color: #ffffff;
}

.dark :deep(.modern-dialog .p-dialog-content) {
    background-color: #0f172a;
}

:deep(.modern-dialog .p-dialog-footer) {
    padding: 0 1.75rem 1.75rem 1.75rem;
    background-color: #ffffff;
    border-top: none;
}

.dark :deep(.modern-dialog .p-dialog-footer) {
    background-color: #0f172a;
}

/* Dialog Close Button */
:deep(.modern-dialog .p-dialog-header-icon) {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
}

:deep(.modern-dialog .p-dialog-header-icon:hover) {
    background-color: #fee2e2;
    color: #ef4444;
}

/* Avatar Enhancement */
:deep(.p-avatar) {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    font-weight: 600;
}

/* Tag Enhancement */
:deep(.p-tag) {
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
    font-weight: 600;
    font-size: 0.75rem;
    letter-spacing: 0.025em;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Button Enhancements */
:deep(.p-button-rounded) {
    transition: all 0.2s ease;
}

:deep(.p-button-rounded:hover) {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Input Text Enhancement */
:deep(.p-inputtext) {
    border-radius: 0.5rem;
    transition: all 0.2s ease;
}

:deep(.p-inputtext:focus) {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

/* Search Icon Container */
:deep(.p-input-icon-left > i) {
    color: #94a3b8;
}

/* Column Filter Menu */
:deep(.p-column-filter-menu-button) {
    border-radius: 0.375rem;
    transition: all 0.2s ease;
}

:deep(.p-column-filter-menu-button:hover) {
    background-color: #eff6ff;
    color: #3b82f6;
}

:deep(.p-column-filter-menu-button.p-column-filter-menu-button-active) {
    background-color: #3b82f6;
    color: white;
}
</style>
