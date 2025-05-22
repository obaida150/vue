<template>
    <AppLayout title="Urlaubsverwaltung">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Urlaubsverwaltung
                </h2>
                <div class="flex items-center gap-2">
                    <Button icon="pi pi-calendar" label="Kalenderansicht" class="p-button-outlined" @click="showCalendarView" />
                    <Button icon="pi pi-download" label="Exportieren" class="p-button-outlined" @click="exportData" />
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-[90rem] mx-auto sm:px-6 lg:px-8">
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

                                        <Column field="employee.name" header="Mitarbeiter" :sortable="true" :filter="true" filterMatchMode="contains">
                                            <template #body="{ data }">
                                                <div class="flex items-center gap-3">
                                                    <Avatar :label="getInitials(data.employee.name)" shape="circle" size="large" :style="{ backgroundColor: getInitialsColor(data.employee.name) }" />
                                                    <div>
                                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ data.employee.name }}</div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ data.department }}</div>
                                                    </div>
                                                </div>
                                            </template>
                                            <template #filter="{ filterModel, filterCallback }">
                                                <InputText v-model="filterModel.value" @input="filterCallback()" placeholder="Nach Name suchen" class="p-column-filter w-full" />
                                            </template>
                                        </Column>

                                        <Column field="department" header="Abteilung" :sortable="true" :filter="true" filterMatchMode="equals">
                                            <template #filter="{ filterModel, filterCallback }">
                                                <Dropdown
                                                    v-model="filterModel.value"
                                                    @change="filterCallback()"
                                                    :options="departmentOptions"
                                                    placeholder="Alle Abteilungen"
                                                    class="p-column-filter w-full"
                                                    :showClear="true"
                                                />
                                            </template>
                                        </Column>

                                        <Column field="startDate" header="Zeitraum" :sortable="true" :filter="true" filterMatchMode="dateRange">
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
                                            <template #filter="{ filterModel, filterCallback }">
                                                <Calendar
                                                    v-model="filterModel.value"
                                                    dateFormat="dd.mm.yy"
                                                    placeholder="Zeitraum"
                                                    @date-select="filterCallback()"
                                                    class="p-column-filter w-full"
                                                    selectionMode="range"
                                                />
                                            </template>
                                        </Column>

                                        <Column field="substitute.name" header="Vertretung" :sortable="true" :filter="true" filterMatchMode="contains">
                                            <template #body="{ data }">
                                                <div v-if="data.substitute" class="flex items-center gap-2">
                                                    <Avatar :label="getInitials(data.substitute.name)" shape="circle" size="small" :style="{ backgroundColor: getInitialsColor(data.substitute.name) }" />
                                                    <span>{{ data.substitute.name }}</span>
                                                </div>
                                                <div v-else class="text-gray-500 dark:text-gray-400 italic">
                                                    Keine Vertretung
                                                </div>
                                            </template>
                                            <template #filter="{ filterModel, filterCallback }">
                                                <InputText v-model="filterModel.value" @input="filterCallback()" placeholder="Nach Vertretung suchen" class="p-column-filter w-full" />
                                            </template>
                                        </Column>

                                        <Column field="requestDate" header="Beantragt am" :sortable="true" :filter="true" filterMatchMode="dateRange">
                                            <template #body="{ data }">
                                                <div class="flex items-center gap-2">
                                                    <i class="pi pi-clock text-gray-500 dark:text-gray-400"></i>
                                                    <span>{{ formatDateTime(data.requestDate) }}</span>
                                                </div>
                                            </template>
                                            <template #filter="{ filterModel, filterCallback }">
                                                <Calendar
                                                    v-model="filterModel.value"
                                                    dateFormat="dd.mm.yy"
                                                    placeholder="Datum"
                                                    @date-select="filterCallback()"
                                                    class="p-column-filter w-full"
                                                    selectionMode="range"
                                                />
                                            </template>
                                        </Column>

                                        <Column field="notes" header="Anmerkungen" :filter="true" filterMatchMode="contains">
                                            <template #body="{ data }">
                                                <div v-if="data.notes" class="max-w-xs truncate" :title="data.notes">
                                                    <i class="pi pi-comment text-gray-500 dark:text-gray-400 mr-2"></i>
                                                    {{ data.notes }}
                                                </div>
                                                <div v-else class="text-gray-400 dark:text-gray-500 italic">
                                                    Keine Anmerkungen
                                                </div>
                                            </template>
                                            <template #filter="{ filterModel, filterCallback }">
                                                <InputText v-model="filterModel.value" @input="filterCallback()" placeholder="Nach Anmerkungen suchen" class="p-column-filter w-full" />
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

                                        <Column field="employee.name" header="Mitarbeiter" :sortable="true" :filter="true" filterMatchMode="contains">
                                            <template #body="{ data }">
                                                <div class="flex items-center gap-3">
                                                    <Avatar :label="getInitials(data.employee.name)" shape="circle" size="large" :style="{ backgroundColor: getInitialsColor(data.employee.name) }" />
                                                    <div>
                                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ data.employee.name }}</div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ data.department }}</div>
                                                    </div>
                                                </div>
                                            </template>
                                            <template #filter="{ filterModel, filterCallback }">
                                                <InputText v-model="filterModel.value" @input="filterCallback()" placeholder="Nach Name suchen" class="p-column-filter w-full" />
                                            </template>
                                        </Column>

                                        <Column field="department" header="Abteilung" :sortable="true" :filter="true" filterMatchMode="equals">
                                            <template #filter="{ filterModel, filterCallback }">
                                                <Dropdown
                                                    v-model="filterModel.value"
                                                    @change="filterCallback()"
                                                    :options="departmentOptions"
                                                    placeholder="Alle Abteilungen"
                                                    class="p-column-filter w-full"
                                                    :showClear="true"
                                                />
                                            </template>
                                        </Column>

                                        <Column field="startDate" header="Zeitraum" :sortable="true" :filter="true" filterMatchMode="dateRange">
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
                                            <template #filter="{ filterModel, filterCallback }">
                                                <Calendar
                                                    v-model="filterModel.value"
                                                    dateFormat="dd.mm.yy"
                                                    placeholder="Zeitraum"
                                                    @date-select="filterCallback()"
                                                    class="p-column-filter w-full"
                                                    selectionMode="range"
                                                />
                                            </template>
                                        </Column>

                                        <Column field="approvedBy" header="Genehmigt von" :sortable="true" :filter="true" filterMatchMode="contains">
                                            <template #body="{ data }">
                                                <div class="flex flex-col">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ data.approvedBy }}</div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-1">
                                                        <i class="pi pi-calendar text-xs"></i>
                                                        <span>{{ formatDate(data.approvedDate) }}</span>
                                                    </div>
                                                </div>
                                            </template>
                                            <template #filter="{ filterModel, filterCallback }">
                                                <InputText v-model="filterModel.value" @input="filterCallback()" placeholder="Nach Name suchen" class="p-column-filter w-full" />
                                            </template>
                                        </Column>

                                        <Column field="notes" header="Anmerkungen" :filter="true" filterMatchMode="contains">
                                            <template #body="{ data }">
                                                <div v-if="data.notes" class="max-w-xs truncate" :title="data.notes">
                                                    <i class="pi pi-comment text-gray-500 dark:text-gray-400 mr-2"></i>
                                                    {{ data.notes }}
                                                </div>
                                                <div v-else class="text-gray-400 dark:text-gray-500 italic">
                                                    Keine Anmerkungen
                                                </div>
                                            </template>
                                            <template #filter="{ filterModel, filterCallback }">
                                                <InputText v-model="filterModel.value" @input="filterCallback()" placeholder="Nach Anmerkungen suchen" class="p-column-filter w-full" />
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

                                        <Column field="employee.name" header="Mitarbeiter" :sortable="true" :filter="true" filterMatchMode="contains">
                                            <template #body="{ data }">
                                                <div class="flex items-center gap-3">
                                                    <Avatar :label="getInitials(data.employee.name)" shape="circle" size="large" :style="{ backgroundColor: getInitialsColor(data.employee.name) }" />
                                                    <div>
                                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ data.employee.name }}</div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ data.department }}</div>
                                                    </div>
                                                </div>
                                            </template>
                                            <template #filter="{ filterModel, filterCallback }">
                                                <InputText v-model="filterModel.value" @input="filterCallback()" placeholder="Nach Name suchen" class="p-column-filter w-full" />
                                            </template>
                                        </Column>

                                        <Column field="department" header="Abteilung" :sortable="true" :filter="true" filterMatchMode="equals">
                                            <template #filter="{ filterModel, filterCallback }">
                                                <Dropdown
                                                    v-model="filterModel.value"
                                                    @change="filterCallback()"
                                                    :options="departmentOptions"
                                                    placeholder="Alle Abteilungen"
                                                    class="p-column-filter w-full"
                                                    :showClear="true"
                                                />
                                            </template>
                                        </Column>

                                        <Column field="startDate" header="Zeitraum" :sortable="true" :filter="true" filterMatchMode="dateRange">
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
                                            <template #filter="{ filterModel, filterCallback }">
                                                <Calendar
                                                    v-model="filterModel.value"
                                                    dateFormat="dd.mm.yy"
                                                    placeholder="Zeitraum"
                                                    @date-select="filterCallback()"
                                                    class="p-column-filter w-full"
                                                    selectionMode="range"
                                                />
                                            </template>
                                        </Column>

                                        <Column field="rejectedBy" header="Abgelehnt von" :sortable="true" :filter="true" filterMatchMode="contains">
                                            <template #body="{ data }">
                                                <div class="flex flex-col">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ data.rejectedBy }}</div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-1">
                                                        <i class="pi pi-calendar text-xs"></i>
                                                        <span>{{ formatDate(data.rejectedDate) }}</span>
                                                    </div>
                                                </div>
                                            </template>
                                            <template #filter="{ filterModel, filterCallback }">
                                                <InputText v-model="filterModel.value" @input="filterCallback()" placeholder="Nach Name suchen" class="p-column-filter w-full" />
                                            </template>
                                        </Column>

                                        <Column field="rejectionReason" header="Ablehnungsgrund" :filter="true" filterMatchMode="contains">
                                            <template #body="{ data }">
                                                <div v-if="data.rejectionReason" class="max-w-xs truncate text-red-600 dark:text-red-400" :title="data.rejectionReason">
                                                    <i class="pi pi-exclamation-circle mr-2"></i>
                                                    {{ data.rejectionReason }}
                                                </div>
                                                <div v-else class="text-gray-400 dark:text-gray-500 italic">
                                                    Kein Grund angegeben
                                                </div>
                                            </template>
                                            <template #filter="{ filterModel, filterCallback }">
                                                <InputText v-model="filterModel.value" @input="filterCallback()" placeholder="Nach Grund suchen" class="p-column-filter w-full" />
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
            :header="`Urlaubsantrag Details`"
            :style="{ width: '600px' }"
            :modal="true"
            :closable="true"
            class="modern-dialog"
        >
            <div v-if="selectedRequest" class="p-4">
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg mb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <Avatar :label="getInitials(selectedRequest.employee.name)" shape="circle" size="large" :style="{ backgroundColor: getInitialsColor(selectedRequest.employee.name) }" />
                        <div>
                            <h3 class="text-xl font-bold">{{ selectedRequest.employee.name }}</h3>
                            <p class="text-gray-500">{{ selectedRequest.department }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
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
                            <div class="text-sm text-gray-500 dark:text-gray-400">Beantragt am</div>
                            <div class="font-medium">{{ formatDateTime(selectedRequest.requestDate) }}</div>
                        </div>
                        <div v-if="selectedRequest.substitute">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Vertretung</div>
                            <div class="font-medium">{{ selectedRequest.substitute.name }}</div>
                        </div>
                    </div>

                    <div v-if="selectedRequest.notes" class="mb-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Anmerkungen</div>
                        <div class="font-medium p-2 bg-white dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600">
                            {{ selectedRequest.notes }}
                        </div>
                    </div>

                    <div v-if="selectedRequest.status === 'approved'" class="p-3 bg-green-50 dark:bg-green-900/20 rounded border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-2 text-green-700 dark:text-green-400 font-medium mb-1">
                            <i class="pi pi-check-circle"></i>
                            <span>Genehmigt</span>
                        </div>
                        <div class="text-sm text-green-600 dark:text-green-300">
                            Genehmigt von {{ selectedRequest.approvedBy }} am {{ formatDateTime(selectedRequest.approvedDate) }}
                        </div>
                    </div>

                    <div v-if="selectedRequest.status === 'rejected'" class="p-3 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-800">
                        <div class="flex items-center gap-2 text-red-700 dark:text-red-400 font-medium mb-1">
                            <i class="pi pi-times-circle"></i>
                            <span>Abgelehnt</span>
                        </div>
                        <div class="text-sm text-red-600 dark:text-red-300">
                            Abgelehnt von {{ selectedRequest.rejectedBy }} am {{ formatDateTime(selectedRequest.rejectedDate) }}
                        </div>
                        <div v-if="selectedRequest.rejectionReason" class="mt-2">
                            <div class="text-sm text-red-600 dark:text-red-300">Ablehnungsgrund:</div>
                            <div class="p-2 bg-white dark:bg-gray-700 rounded border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300">
                                {{ selectedRequest.rejectionReason }}
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
                    <Button
                        v-if="selectedRequest && selectedRequest.status === 'pending'"
                        label="Genehmigen"
                        icon="pi pi-check"
                        class="p-button-success"
                        @click="approveRequest(selectedRequest)"
                    />
                    <Button
                        v-if="selectedRequest && selectedRequest.status === 'pending'"
                        label="Ablehnen"
                        icon="pi pi-times"
                        class="p-button-danger"
                        @click="rejectRequest(selectedRequest)"
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
import VacationService from '@/Services/VacationService';
import Dropdown from 'primevue/dropdown';

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
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    'employee.name': { value: null, matchMode: FilterMatchMode.CONTAINS },
    department: { value: null, matchMode: FilterMatchMode.EQUALS },
    startDate: { value: null, matchMode: FilterMatchMode.DATE_RANGE },
    requestDate: { value: null, matchMode: FilterMatchMode.DATE_RANGE },
    'substitute.name': { value: null, matchMode: FilterMatchMode.CONTAINS },
    notes: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const approvedFilters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    'employee.name': { value: null, matchMode: FilterMatchMode.CONTAINS },
    department: { value: null, matchMode: FilterMatchMode.EQUALS },
    startDate: { value: null, matchMode: FilterMatchMode.DATE_RANGE },
    approvedDate: { value: null, matchMode: FilterMatchMode.DATE_RANGE },
    'approvedBy': { value: null, matchMode: FilterMatchMode.CONTAINS },
    notes: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const rejectedFilters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    'employee.name': { value: null, matchMode: FilterMatchMode.CONTAINS },
    department: { value: null, matchMode: FilterMatchMode.EQUALS },
    startDate: { value: null, matchMode: FilterMatchMode.DATE_RANGE },
    rejectedDate: { value: null, matchMode: FilterMatchMode.DATE_RANGE },
    'rejectedBy': { value: null, matchMode: FilterMatchMode.CONTAINS },
    rejectionReason: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const departmentOptions = computed(() => {
    const uniqueDepartments = new Set();

    // Sammle alle eindeutigen Abteilungen aus allen Anfragen
    [...pendingRequests.value, ...approvedRequests.value, ...rejectedRequests.value].forEach(request => {
        if (request.department) {
            uniqueDepartments.add(request.department);
        }
    });

    // Konvertiere in ein Array von Objekten für das Dropdown
    return Array.from(uniqueDepartments).map(dept => ({
        label: dept,
        value: dept
    }));
});

// Urlaubsanträge
const pendingRequests = ref([]);
const approvedRequests = ref([]);
const rejectedRequests = ref([]);

// Daten vom Server laden
const fetchVacationRequests = async () => {
    loading.value = true;
    try {
        const response = await VacationService.getVacationRequests();
        pendingRequests.value = response.data.pending;
        approvedRequests.value = response.data.approved;
        rejectedRequests.value = response.data.rejected;
    } catch (error) {
        console.error('Fehler beim Laden der Urlaubsanträge:', error);
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Die Urlaubsanträge konnten nicht geladen werden. Verwende Beispieldaten.',
            life: 3000
        });

        // Fallback mit Beispieldaten
        pendingRequests.value = [
            {
                id: 1,
                employee: { name: 'Max Mustermann', id: 1 },
                department: 'Entwicklung',
                startDate: new Date(2025, 3, 15),
                endDate: new Date(2025, 3, 20),
                days: 5,
                requestDate: new Date(2025, 2, 1),
                status: 'pending',
                substitute: { name: 'Anna Schmidt', id: 2 },
                notes: 'Familienurlaub'
            },
            {
                id: 2,
                employee: { name: 'Julia Weber', id: 3 },
                department: 'Marketing',
                startDate: new Date(2025, 4, 10),
                endDate: new Date(2025, 4, 14),
                days: 5,
                requestDate: new Date(2025, 3, 15),
                status: 'pending',
                substitute: null,
                notes: ''
            }
        ];

        approvedRequests.value = [
            {
                id: 3,
                employee: { name: 'Thomas Müller', id: 4 },
                department: 'Vertrieb',
                startDate: new Date(2025, 5, 1),
                endDate: new Date(2025, 5, 14),
                days: 10,
                requestDate: new Date(2025, 4, 1),
                status: 'approved',
                approvedBy: 'Maria Schmidt',
                approvedDate: new Date(2025, 4, 5),
                notes: 'Sommerurlaub'
            }
        ];

        rejectedRequests.value = [
            {
                id: 4,
                employee: { name: 'Sarah Fischer', id: 5 },
                department: 'Personal',
                startDate: new Date(2025, 2, 20),
                endDate: new Date(2025, 2, 24),
                days: 5,
                requestDate: new Date(2025, 1, 15),
                status: 'rejected',
                rejectedBy: 'Maria Schmidt',
                rejectedDate: new Date(2025, 1, 20),
                rejectionReason: 'Personalmangel in diesem Zeitraum'
            }
        ];
    } finally {
        loading.value = false;
    }
};

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
        await VacationService.approveVacationRequest(selectedRequest.value.id, approvalNotes.value);

        toast.add({
            severity: 'success',
            summary: 'Erfolg',
            detail: `Urlaubsantrag von ${selectedRequest.value.employee.name} wurde genehmigt.`,
            life: 3000
        });

        closeApproveDialog();
        fetchVacationRequests(); // Daten neu laden
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
        await VacationService.rejectVacationRequest(selectedRequest.value.id, rejectionReason.value);

        toast.add({
            severity: 'info',
            summary: 'Information',
            detail: `Urlaubsantrag von ${selectedRequest.value.employee.name} wurde abgelehnt.`,
            life: 3000
        });

        closeRejectDialog();
        fetchVacationRequests(); // Daten neu laden
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
    // Navigiere zur Kalenderansicht
    window.location.href = route('company-calendar');
};

const exportData = () => {
    // Bestimme, welche Daten basierend auf dem aktiven Tab exportiert werden sollen
    let dataToExport = [];
    const activeTabIndex = document.querySelector('.p-tabview-selected')?.getAttribute('aria-selected');

    if (activeTabIndex === '0') {
        dataToExport = pendingRequests.value;
    } else if (activeTabIndex === '1') {
        dataToExport = approvedRequests.value;
    } else {
        dataToExport = rejectedRequests.value;
    }

    if (dataToExport.length === 0) {
        toast.add({
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
    const headers = ["Mitarbeiter", "Abteilung", "Startdatum", "Enddatum", "Tage", "Status", "Anmerkungen"];
    csvContent += headers.join(";") + "\r\n";

    // Daten
    dataToExport.forEach(item => {
        const row = [
            item.employee.name,
            item.department,
            formatDate(item.startDate),
            formatDate(item.endDate),
            item.days,
            item.status === 'pending' ? 'Ausstehend' : (item.status === 'approved' ? 'Genehmigt' : 'Abgelehnt'),
            item.notes || ''
        ];
        csvContent += row.join(";") + "\r\n";
    });

    // Erstelle einen Download-Link und klicke darauf
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `urlaubsantraege_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    toast.add({
        severity: 'success',
        summary: 'Export erfolgreich',
        detail: 'Die Daten wurden erfolgreich exportiert.',
        life: 3000
    });
};

// Komponente initialisieren
onMounted(() => {
    fetchVacationRequests();
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

