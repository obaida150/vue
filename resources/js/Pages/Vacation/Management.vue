<template>
    <AppLayout title="Urlaubsverwaltung">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Urlaubsverwaltung
                </h2>
                <div class="flex items-center gap-2">
                    <Button icon="pi pi-calendar" label="Kalenderansicht" class="p-button-outlined" @click="showCalendarView" />
                    <Button icon="pi pi-download" label="Exportieren" class="p-button-outlined" />
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Statistik-Karten -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Offene Anträge</h3>
                                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ pendingRequests.length }}</p>
                            </div>
                            <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full">
                                <i class="pi pi-clock text-blue-600 dark:text-blue-400 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-green-500">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Genehmigte Anträge</h3>
                                <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ approvedRequests.length }}</p>
                            </div>
                            <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full">
                                <i class="pi pi-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-red-500">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Abgelehnte Anträge</h3>
                                <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ rejectedRequests.length }}</p>
                            </div>
                            <div class="bg-red-100 dark:bg-red-900 p-3 rounded-full">
                                <i class="pi pi-times-circle text-red-600 dark:text-red-400 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-lg">
                    <div class="p-6">
                        <Tabs>
                            <TabPanel header="Offene Anträge">
                                <div class="card">
                                    <DataTable
                                        :value="pendingRequests"
                                        :paginator="pendingRequests.length > 10"
                                        :rows="10"
                                        :rowsPerPageOptions="[5, 10, 20, 50]"
                                        dataKey="id"
                                        :rowHover="true"
                                        responsiveLayout="scroll"
                                        class="p-datatable-sm modern-datatable"
                                        :loading="loading"
                                        v-model:filters="filters"
                                        filterDisplay="menu"
                                        :globalFilterFields="['employee.name', 'department', 'startDate', 'endDate', 'status']"
                                        stripedRows
                                    >
                                        <template #header>
                                            <div class="flex justify-between items-center w-full">
                                                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">Offene Urlaubsanträge</h3>
                                                <span class="p-input-icon-left ml-auto">
                        <i class="pi pi-search" />
                        <InputText v-model="filters['global'].value" placeholder="Suchen..." class="p-inputtext-sm" />
                      </span>
                                            </div>
                                        </template>

                                        <template #empty>
                                            <div class="text-center p-6">
                                                <i class="pi pi-inbox text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                                <p class="text-gray-500 dark:text-gray-400">Keine offenen Urlaubsanträge vorhanden</p>
                                            </div>
                                        </template>

                                        <Column field="employee.name" header="Mitarbeiter" :sortable="true">
                                            <template #body="{ data }">
                                                <div class="flex items-center gap-3">
                                                    <Avatar :label="getInitials(data.employee.name)" shape="circle" size="large" :style="{ backgroundColor: getInitialsColor(data.employee.name) }" />
                                                    <div>
                                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ data.employee.name }}</div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ data.department }}</div>
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="startDate" header="Zeitraum" :sortable="true">
                                            <template #body="{ data }">
                                                <div class="flex flex-col">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                                        {{ formatDate(data.startDate) }} - {{ formatDate(data.endDate) }}
                                                    </div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-1">
                                                        <i class="pi pi-calendar text-xs"></i>
                                                        <span>{{ data.days }} Tage</span>
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="substitute.name" header="Vertretung" :sortable="true">
                                            <template #body="{ data }">
                                                <div v-if="data.substitute" class="flex items-center gap-2">
                                                    <Avatar :label="getInitials(data.substitute.name)" shape="circle" size="small" :style="{ backgroundColor: getInitialsColor(data.substitute.name) }" />
                                                    <span>{{ data.substitute.name }}</span>
                                                </div>
                                                <div v-else class="text-gray-500 dark:text-gray-400 italic">
                                                    Keine Vertretung
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="requestDate" header="Beantragt am" :sortable="true">
                                            <template #body="{ data }">
                                                <div class="flex items-center gap-2">
                                                    <i class="pi pi-clock text-gray-500 dark:text-gray-400"></i>
                                                    <span>{{ formatDateTime(data.requestDate) }}</span>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="notes" header="Anmerkungen">
                                            <template #body="{ data }">
                                                <div v-if="data.notes" class="max-w-xs truncate" :title="data.notes">
                                                    <i class="pi pi-comment text-gray-500 dark:text-gray-400 mr-2"></i>
                                                    {{ data.notes }}
                                                </div>
                                                <div v-else class="text-gray-400 dark:text-gray-500 italic">
                                                    Keine Anmerkungen
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="Aktionen" :exportable="false" style="min-width: 10rem">
                                            <template #body="{ data }">
                                                <div class="flex gap-2">
                                                    <Button
                                                        icon="pi pi-check"
                                                        class="p-button-success p-button-rounded"
                                                        @click="approveRequest(data)"
                                                        tooltip="Genehmigen"
                                                        tooltipOptions="{ position: 'top' }"
                                                    />
                                                    <Button
                                                        icon="pi pi-times"
                                                        class="p-button-danger p-button-rounded"
                                                        @click="rejectRequest(data)"
                                                        tooltip="Ablehnen"
                                                        tooltipOptions="{ position: 'top' }"
                                                    />
                                                    <Button
                                                        icon="pi pi-eye"
                                                        class="p-button-secondary p-button-rounded"
                                                        @click="viewRequestDetails(data)"
                                                        tooltip="Details anzeigen"
                                                        tooltipOptions="{ position: 'top' }"
                                                    />
                                                </div>
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                            </TabPanel>

                            <TabPanel header="Genehmigte Anträge">
                                <div class="card">
                                    <DataTable
                                        :value="approvedRequests"
                                        :paginator="approvedRequests.length > 10"
                                        :rows="10"
                                        :rowsPerPageOptions="[5, 10, 20, 50]"
                                        dataKey="id"
                                        :rowHover="true"
                                        responsiveLayout="scroll"
                                        class="p-datatable-sm modern-datatable"
                                        :loading="loading"
                                        v-model:filters="approvedFilters"
                                        filterDisplay="menu"
                                        :globalFilterFields="['employee.name', 'department', 'startDate', 'endDate', 'approvedBy']"
                                        stripedRows
                                    >
                                        <template #header>
                                            <div class="flex justify-between items-center w-full">
                                                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">Genehmigte Urlaubsanträge</h3>
                                                <span class="p-input-icon-left ml-auto">
                        <i class="pi pi-search" />
                        <InputText v-model="approvedFilters['global'].value" placeholder="Suchen..." class="p-inputtext-sm" />
                      </span>
                                            </div>
                                        </template>

                                        <template #empty>
                                            <div class="text-center p-6">
                                                <i class="pi pi-inbox text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                                <p class="text-gray-500 dark:text-gray-400">Keine genehmigten Urlaubsanträge vorhanden</p>
                                            </div>
                                        </template>

                                        <Column field="employee.name" header="Mitarbeiter" :sortable="true">
                                            <template #body="{ data }">
                                                <div class="flex items-center gap-3">
                                                    <Avatar :label="getInitials(data.employee.name)" shape="circle" size="large" :style="{ backgroundColor: getInitialsColor(data.employee.name) }" />
                                                    <div>
                                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ data.employee.name }}</div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ data.department }}</div>
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="startDate" header="Zeitraum" :sortable="true">
                                            <template #body="{ data }">
                                                <div class="flex flex-col">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                                        {{ formatDate(data.startDate) }} - {{ formatDate(data.endDate) }}
                                                    </div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-1">
                                                        <i class="pi pi-calendar text-xs"></i>
                                                        <span>{{ data.days }} Tage</span>
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="approvedBy" header="Genehmigt von" :sortable="true">
                                            <template #body="{ data }">
                                                <div class="flex flex-col">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ data.approvedBy }}</div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-1">
                                                        <i class="pi pi-calendar text-xs"></i>
                                                        <span>{{ formatDate(data.approvedDate) }}</span>
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="notes" header="Anmerkungen">
                                            <template #body="{ data }">
                                                <div v-if="data.notes" class="max-w-xs truncate" :title="data.notes">
                                                    <i class="pi pi-comment text-gray-500 dark:text-gray-400 mr-2"></i>
                                                    {{ data.notes }}
                                                </div>
                                                <div v-else class="text-gray-400 dark:text-gray-500 italic">
                                                    Keine Anmerkungen
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="Aktionen" :exportable="false" style="min-width: 8rem">
                                            <template #body="{ data }">
                                                <Button
                                                    icon="pi pi-eye"
                                                    class="p-button-secondary p-button-rounded"
                                                    @click="viewRequestDetails(data)"
                                                    tooltip="Details anzeigen"
                                                    tooltipOptions="{ position: 'top' }"
                                                />
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                            </TabPanel>

                            <TabPanel header="Abgelehnte Anträge">
                                <div class="card">
                                    <DataTable
                                        :value="rejectedRequests"
                                        :paginator="rejectedRequests.length > 10"
                                        :rows="10"
                                        :rowsPerPageOptions="[5, 10, 20, 50]"
                                        dataKey="id"
                                        :rowHover="true"
                                        responsiveLayout="scroll"
                                        class="p-datatable-sm modern-datatable"
                                        :loading="loading"
                                        v-model:filters="rejectedFilters"
                                        filterDisplay="menu"
                                        :globalFilterFields="['employee.name', 'department', 'startDate', 'endDate', 'rejectedBy']"
                                        stripedRows
                                    >
                                        <template #header>
                                            <div class="flex justify-between items-center w-full">
                                                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">Abgelehnte Urlaubsanträge</h3>
                                                <span class="p-input-icon-left ml-auto">
                        <i class="pi pi-search" />
                        <InputText v-model="rejectedFilters['global'].value" placeholder="Suchen..." class="p-inputtext-sm" />
                      </span>
                                            </div>
                                        </template>

                                        <template #empty>
                                            <div class="text-center p-6">
                                                <i class="pi pi-inbox text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                                <p class="text-gray-500 dark:text-gray-400">Keine abgelehnten Urlaubsanträge vorhanden</p>
                                            </div>
                                        </template>

                                        <Column field="employee.name" header="Mitarbeiter" :sortable="true">
                                            <template #body="{ data }">
                                                <div class="flex items-center gap-3">
                                                    <Avatar :label="getInitials(data.employee.name)" shape="circle" size="large" :style="{ backgroundColor: getInitialsColor(data.employee.name) }" />
                                                    <div>
                                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ data.employee.name }}</div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ data.department }}</div>
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="startDate" header="Zeitraum" :sortable="true">
                                            <template #body="{ data }">
                                                <div class="flex flex-col">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                                        {{ formatDate(data.startDate) }} - {{ formatDate(data.endDate) }}
                                                    </div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-1">
                                                        <i class="pi pi-calendar text-xs"></i>
                                                        <span>{{ data.days }} Tage</span>
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="rejectedBy" header="Abgelehnt von" :sortable="true">
                                            <template #body="{ data }">
                                                <div class="flex flex-col">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ data.rejectedBy }}</div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-1">
                                                        <i class="pi pi-calendar text-xs"></i>
                                                        <span>{{ formatDate(data.rejectedDate) }}</span>
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="rejectionReason" header="Ablehnungsgrund">
                                            <template #body="{ data }">
                                                <div v-if="data.rejectionReason" class="max-w-xs truncate text-red-600 dark:text-red-400" :title="data.rejectionReason">
                                                    <i class="pi pi-exclamation-circle mr-2"></i>
                                                    {{ data.rejectionReason }}
                                                </div>
                                                <div v-else class="text-gray-400 dark:text-gray-500 italic">
                                                    Kein Grund angegeben
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="Aktionen" :exportable="false" style="min-width: 8rem">
                                            <template #body="{ data }">
                                                <Button
                                                    icon="pi pi-eye"
                                                    class="p-button-secondary p-button-rounded"
                                                    @click="viewRequestDetails(data)"
                                                    tooltip="Details anzeigen"
                                                    tooltipOptions="{ position: 'top' }"
                                                />
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                            </TabPanel>
                        </Tabs>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog für Genehmigung -->
        <Dialog
            v-model:visible="approveDialogVisible"
            header="Urlaubsantrag genehmigen"
            :style="{ width: '500px' }"
            :modal="true"
            :closable="true"
            class="modern-dialog"
        >
            <div class="confirmation-content p-4">
                <div class="bg-green-50 dark:bg-green-900/30 p-4 rounded-lg mb-6 flex items-start">
                    <i class="pi pi-check-circle text-green-500 dark:text-green-400 text-2xl mr-4 mt-1"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-green-800 dark:text-green-300 mb-2">Urlaubsantrag genehmigen</h3>
                        <p class="text-green-700 dark:text-green-400">
                            Sie sind dabei, den Urlaubsantrag von <strong>{{ selectedRequest?.employee?.name }}</strong> zu genehmigen.
                        </p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg mb-6">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-3">Antragsdetails</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Zeitraum</div>
                            <div class="font-medium">
                                {{ selectedRequest ? formatDate(selectedRequest.startDate) : '' }} - {{ selectedRequest ? formatDate(selectedRequest.endDate) : '' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Anzahl Tage</div>
                            <div class="font-medium">{{ selectedRequest?.days }} Tage</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Abteilung</div>
                            <div class="font-medium">{{ selectedRequest?.department }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Beantragt am</div>
                            <div class="font-medium">{{ selectedRequest ? formatDate(selectedRequest.requestDate) : '' }}</div>
                        </div>
                    </div>
                </div>

                <div class="field mb-4">
                    <label for="approvalNotes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Anmerkungen (optional)</label>
                    <Textarea
                        id="approvalNotes"
                        v-model="approvalNotes"
                        rows="3"
                        autoResize
                        class="w-full p-inputtext-sm"
                        placeholder="Fügen Sie hier Ihre Anmerkungen zur Genehmigung hinzu..."
                    />
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button
                        label="Abbrechen"
                        icon="pi pi-times"
                        class="p-button-text"
                        @click="closeApproveDialog"
                    />
                    <Button
                        label="Genehmigen"
                        icon="pi pi-check"
                        class="p-button-success"
                        @click="confirmApprove"
                        :loading="processingRequest"
                    />
                </div>
            </template>
        </Dialog>

        <!-- Dialog für Ablehnung -->
        <Dialog
            v-model:visible="rejectDialogVisible"
            header="Urlaubsantrag ablehnen"
            :style="{ width: '500px' }"
            :modal="true"
            :closable="true"
            class="modern-dialog"
        >
            <div class="confirmation-content p-4">
                <div class="bg-red-50 dark:bg-red-900/30 p-4 rounded-lg mb-6 flex items-start">
                    <i class="pi pi-exclamation-triangle text-red-500 dark:text-red-400 text-2xl mr-4 mt-1"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-red-800 dark:text-red-300 mb-2">Urlaubsantrag ablehnen</h3>
                        <p class="text-red-700 dark:text-red-400">
                            Sie sind dabei, den Urlaubsantrag von <strong>{{ selectedRequest?.employee?.name }}</strong> abzulehnen.
                        </p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg mb-6">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-3">Antragsdetails</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Zeitraum</div>
                            <div class="font-medium">
                                {{ selectedRequest ? formatDate(selectedRequest.startDate) : '' }} - {{ selectedRequest ? formatDate(selectedRequest.endDate) : '' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Anzahl Tage</div>
                            <div class="font-medium">{{ selectedRequest?.days }} Tage</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Abteilung</div>
                            <div class="font-medium">{{ selectedRequest?.department }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Beantragt am</div>
                            <div class="font-medium">{{ selectedRequest ? formatDate(selectedRequest.requestDate) : '' }}</div>
                        </div>
                    </div>
                </div>

                <div class="field mb-4">
                    <label for="rejectionReason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ablehnungsgrund <span class="text-red-500">*</span></label>
                    <Textarea
                        id="rejectionReason"
                        v-model="rejectionReason"
                        rows="3"
                        autoResize
                        class="w-full p-inputtext-sm"
                        placeholder="Bitte geben Sie einen Grund für die Ablehnung an..."
                    />
                    <small class="text-gray-500 dark:text-gray-400 mt-1 block">Diese Information wird dem Mitarbeiter angezeigt.</small>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button
                        label="Abbrechen"
                        icon="pi pi-times"
                        class="p-button-text"
                        @click="closeRejectDialog"
                    />
                    <Button
                        label="Ablehnen"
                        icon="pi pi-times"
                        class="p-button-danger"
                        @click="confirmReject"
                        :loading="processingRequest"
                        :disabled="!rejectionReason"
                    />
                </div>
            </template>
        </Dialog>

        <!-- Dialog für Antragsdetails -->
        <Dialog
            v-model:visible="detailsDialogVisible"
            :header="`Urlaubsantrag Details: ${selectedRequest?.employee?.name || ''}`"
            :style="{ width: '500px' }"
            :modal="true"
            :closable="true"
            class="modern-dialog"
        >
            <div class="p-4" v-if="selectedRequest">
                <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg mb-6 flex items-start">
                    <i class="pi pi-info-circle text-blue-500 dark:text-blue-400 text-2xl mr-4 mt-1"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-300 mb-2">Urlaubsantrag Details</h3>
                        <p class="text-blue-700 dark:text-blue-400">
                            Details zum Urlaubsantrag von <strong>{{ selectedRequest.employee.name }}</strong>.
                        </p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg mb-6">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-3">Antragsdetails</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Zeitraum</div>
                            <div class="font-medium">
                                {{ formatDate(selectedRequest.startDate) }} - {{ formatDate(selectedRequest.endDate) }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Anzahl Tage</div>
                            <div class="font-medium">{{ selectedRequest.days }} Tage</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Abteilung</div>
                            <div class="font-medium">{{ selectedRequest.department }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Beantragt am</div>
                            <div class="font-medium">{{ formatDate(selectedRequest.requestDate) }}</div>
                        </div>
                        <div v-if="selectedRequest.substitute">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Vertretung</div>
                            <div class="font-medium">{{ selectedRequest.substitute.name }}</div>
                        </div>
                        <div v-if="selectedRequest.status === 'approved'">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Genehmigt von</div>
                            <div class="font-medium">{{ selectedRequest.approvedBy }} am {{ formatDate(selectedRequest.approvedDate) }}</div>
                        </div>
                        <div v-if="selectedRequest.status === 'rejected'">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Abgelehnt von</div>
                            <div class="font-medium">{{ selectedRequest.rejectedBy }} am {{ formatDate(selectedRequest.rejectedDate) }}</div>
                        </div>
                    </div>

                    <div class="mt-4" v-if="selectedRequest.notes">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Anmerkungen</div>
                        <div class="font-medium">{{ selectedRequest.notes }}</div>
                    </div>

                    <div class="mt-4" v-if="selectedRequest.rejectionReason">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Ablehnungsgrund</div>
                        <div class="font-medium text-red-600 dark:text-red-400">{{ selectedRequest.rejectionReason }}</div>
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

<script setup>
import { ref, onMounted, computed } from 'vue';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Tabs from 'primevue/tabs';
import TabPanel from 'primevue/tabpanel';
import Avatar from 'primevue/avatar';
import Calendar from 'primevue/calendar';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import dayjs from 'dayjs';
import 'dayjs/locale/de';

dayjs.locale('de');

// Änderung im Script-Teil, um den Toast richtig zu initialisieren
const toast = useToast();

// Zustand
const loading = ref(false);
const processingRequest = ref(false);
const approveDialogVisible = ref(false);
const rejectDialogVisible = ref(false);
const detailsDialogVisible = ref(false);
const selectedRequest = ref(null);
const approvalNotes = ref('');
const rejectionReason = ref('');

// Filter für DataTable
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const approvedFilters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const rejectedFilters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// Beispieldaten (würden normalerweise vom Server geladen)
const pendingRequests = ref([
    {
        id: 1,
        employee: { id: 1, name: 'Max Mustermann' },
        department: 'Entwicklung',
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
        employee: { id: 3, name: 'Anna Schmidt' },
        department: 'Marketing',
        startDate: new Date(2025, 4, 5),
        endDate: new Date(2025, 4, 10),
        days: 4,
        substitute: null,
        requestDate: new Date(2025, 3, 20),
        notes: '',
        status: 'pending'
    },
    {
        id: 3,
        employee: { id: 5, name: 'Thomas Müller' },
        department: 'Vertrieb',
        startDate: new Date(2025, 5, 1),
        endDate: new Date(2025, 5, 14),
        days: 10,
        substitute: { id: 9, name: 'Felix Wagner' },
        requestDate: new Date(2025, 4, 15),
        notes: 'Sommerurlaub',
        status: 'pending'
    }
]);

const approvedRequests = ref([
    {
        id: 4,
        employee: { id: 2, name: 'Julia Weber' },
        department: 'Personal',
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
        id: 5,
        employee: { id: 7, name: 'Markus Klein' },
        department: 'Entwicklung',
        startDate: new Date(2025, 6, 20),
        endDate: new Date(2025, 7, 3),
        days: 10,
        substitute: { id: 6, name: 'Sarah Becker' },
        requestDate: new Date(2025, 5, 10),
        approvedBy: 'Max Mustermann',
        approvedDate: new Date(2025, 5, 12),
        notes: '',
        status: 'approved'
    }
]);

const rejectedRequests = ref([
    {
        id: 6,
        employee: { id: 8, name: 'Laura Schulz' },
        department: 'Marketing',
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

// Formatierungsfunktionen
const formatDate = (date) => {
    return dayjs(date).format('DD.MM.YYYY');
};

const formatDateTime = (date) => {
    return dayjs(date).format('DD.MM.YYYY HH:mm');
};

const getInitials = (name) => {
    return name
        .split(' ')
        .map(part => part.charAt(0))
        .join('')
        .toUpperCase();
};

const getInitialsColor = (name) => {
    // Generate a deterministic color based on the name
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }

    const hue = hash % 360;
    return `hsl(${hue}, 70%, 60%)`;
};

const viewRequestDetails = (request) => {
    selectedRequest.value = request;
    detailsDialogVisible.value = true;
};

// Dialog-Funktionen
const approveRequest = (request) => {
    selectedRequest.value = request;
    approvalNotes.value = '';
    approveDialogVisible.value = true;
};

const rejectRequest = (request) => {
    selectedRequest.value = request;
    rejectionReason.value = '';
    rejectDialogVisible.value = true;
};

const closeApproveDialog = () => {
    approveDialogVisible.value = false;
    selectedRequest.value = null;
    approvalNotes.value = '';
};

const closeRejectDialog = () => {
    rejectDialogVisible.value = false;
    selectedRequest.value = null;
    rejectionReason.value = '';
};

const confirmApprove = async () => {
    if (!selectedRequest.value) return;

    processingRequest.value = true;

    try {
        // Hier würde normalerweise der API-Aufruf stattfinden
        await new Promise(resolve => setTimeout(resolve, 1000));

        // Antrag aus der Liste der offenen Anträge entfernen
        const index = pendingRequests.value.findIndex(req => req.id === selectedRequest.value.id);
        if (index !== -1) {
            const approvedRequest = {
                ...pendingRequests.value[index],
                status: 'approved',
                approvedBy: 'Aktueller Benutzer', // Würde normalerweise der eingeloggte Benutzer sein
                approvedDate: new Date(),
                notes: approvalNotes.value || pendingRequests.value[index].notes
            };

            pendingRequests.value.splice(index, 1);
            approvedRequests.value.push(approvedRequest);
        }

        toast.add({
            severity: 'success',
            summary: 'Erfolg',
            detail: `Urlaubsantrag von ${selectedRequest.value.employee.name} wurde genehmigt.`,
            life: 3000
        });

        closeApproveDialog();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.',
            life: 3000
        });
    } finally {
        processingRequest.value = false;
    }
};

const confirmReject = async () => {
    if (!selectedRequest.value) return;

    if (!rejectionReason.value) {
        toast.add({
            severity: 'warn',
            summary: 'Warnung',
            detail: 'Bitte geben Sie einen Grund für die Ablehnung an.',
            life: 3000
        });
        return;
    }

    processingRequest.value = true;

    try {
        // Hier würde normalerweise der API-Aufruf stattfinden
        await new Promise(resolve => setTimeout(resolve, 1000));

        // Antrag aus der Liste der offenen Anträge entfernen
        const index = pendingRequests.value.findIndex(req => req.id === selectedRequest.value.id);
        if (index !== -1) {
            const rejectedRequest = {
                ...pendingRequests.value[index],
                status: 'rejected',
                rejectedBy: 'Aktueller Benutzer', // Würde normalerweise der eingeloggte Benutzer sein
                rejectedDate: new Date(),
                rejectionReason: rejectionReason.value
            };

            pendingRequests.value.splice(index, 1);
            rejectedRequests.value.push(rejectedRequest);
        }

        toast.add({
            severity: 'info',
            summary: 'Information',
            detail: `Urlaubsantrag von ${selectedRequest.value.employee.name} wurde abgelehnt.`,
            life: 3000
        });

        closeRejectDialog();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.',
            life: 3000
        });
    } finally {
        processingRequest.value = false;
    }
};

const showCalendarView = () => {
    // Hier würde normalerweise die Navigation zur Kalenderansicht erfolgen
    // Für dieses Beispiel zeigen wir eine Toast-Nachricht an
    toast.add({
        severity: 'info',
        summary: 'Kalenderansicht',
        detail: 'Wechsel zur Kalenderansicht',
        life: 3000
    });

    // In einer echten Implementierung würde hier die Navigation erfolgen
    // z.B. mit router.push('/vacation/calendar') oder ähnlichem
};

// Komponente initialisieren
onMounted(() => {
    // Hier könnten Daten vom Server geladen werden
    loading.value = true;

    setTimeout(() => {
        // Simuliere Laden der Daten
        loading.value = false;
    }, 500);
});
</script>

<style scoped>
.card {
    background-color: var(--surface-card);
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
}

:deep(.modern-datatable) {
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

:deep(.p-tabview .p-tabview-nav) {
    border-bottom: 1px solid var(--surface-border);
    padding: 0 1rem;
}

:deep(.p-tabview .p-tabview-nav li .p-tabview-nav-link) {
    border: none;
    color: var(--text-color-secondary);
    transition: all 0.2s;
    padding: 1rem 1.5rem;
    font-weight: 500;
}

:deep(.p-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: var(--primary-color);
    border-bottom: 2px solid var(--primary-color);
}

:deep(.p-tabview .p-tabview-panels) {
    padding: 0;
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

