<template>
    <AppLayout title="Urlaubsverwaltung">
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Urlaubsverwaltung
                </h2>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                    <Button
                        icon="pi pi-calendar"
                        label="Kalenderansicht"
                        class="p-button-outlined text-sm"
                        @click="showCalendarView"
                    />
                    <Button
                        icon="pi pi-download"
                        label="Exportieren"
                        class="p-button-outlined text-sm"
                        @click="exportData"
                    />
                    <Button
                        icon="pi pi-refresh"
                        label="Aktualisieren"
                        class="p-button-outlined text-sm"
                        @click="refreshData"
                    />
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-[100rem] mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Statistik-Karten -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 lg:gap-6 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 border-l-4 border-l-blue-500">
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

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 border-l-4 border-l-green-500">
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

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 border-l-4 border-l-red-500">
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

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-6 dark:text-gray-100">
                        <Tabs class="modern-tabs">
                            <TabPanel header="Offene Anträge">
                                <div class="bg-white dark:bg-gray-900 rounded-lg">
                                    <!-- Filter-Leiste -->
                                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                            <div class="space-y-2">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Globale Suche</label>
                                                <InputText
                                                    v-model="filters['global'].value"
                                                    placeholder="Alle Felder durchsuchen..."
                                                    class="w-full"
                                                    @input="onGlobalFilterChange"
                                                />
                                            </div>
                                            <div class="space-y-2">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Abteilung</label>
                                                <Select
                                                    v-model="filters['department'].value"
                                                    :options="departmentOptions"
                                                    optionLabel="label"
                                                    optionValue="value"
                                                    placeholder="Alle Abteilungen"
                                                    class="w-full"
                                                    :showClear="true"
                                                    @change="onDepartmentFilterChange"
                                                />
                                            </div>
                                            <div class="space-y-2">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Zeitraum</label>
                                                <DatePicker
                                                    v-model="dateRangeFilter"
                                                    dateFormat="dd.mm.yy"
                                                    placeholder="Datumsbereich wählen"
                                                    class="w-full"
                                                    selectionMode="range"
                                                    :showIcon="true"
                                                    @date-select="onDateRangeFilterChange"
                                                    @clear-click="clearDateRangeFilter"
                                                    :locale="de"
                                                />
                                            </div>
                                            <div class="flex items-end">
                                                <Button
                                                    label="Filter zurücksetzen"
                                                    icon="pi pi-filter-slash"
                                                    class="w-full p-button-outlined"
                                                    @click="clearAllFilters"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <DataTable
                                        :value="filteredPendingRequests"
                                        :paginator="filteredPendingRequests.length > 10"
                                        :rows="10"
                                        :rowsPerPageOptions="[5, 10, 20, 50]"
                                        dataKey="id"
                                        :rowHover="true"
                                        responsiveLayout="scroll"
                                        class="modern-datatable"
                                        :loading="loading"
                                        stripedRows
                                        :sortField="sortField"
                                        :sortOrder="sortOrder"
                                        @sort="onSort"
                                    >
                                        <template #header>
                                            <div class="flex justify-between items-center w-full p-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                                                    Offene Urlaubsanträge ({{ filteredPendingRequests.length }})
                                                </h3>
                                            </div>
                                        </template>

                                        <template #empty>
                                            <div class="text-center p-8">
                                                <i class="pi pi-inbox text-5xl text-gray-300 dark:text-gray-600 mb-4 block"></i>
                                                <p class="text-gray-500 dark:text-gray-400">
                                                    {{ filters['global'].value || hasActiveFilters ? 'Keine Anträge entsprechen den Filterkriterien' : 'Keine offenen Urlaubsanträge vorhanden' }}
                                                </p>
                                            </div>
                                        </template>

                                        <Column field="employee.name" header="Mitarbeiter" :sortable="true" class="min-w-[200px] ">
                                            <template #body="{ data }">
                                                <div class="flex items-center gap-3 ">
                                                    <Avatar
                                                        :label="data.employee.initials || getInitials(data.employee.name)"
                                                        shape="circle"
                                                        size="large"
                                                        :style="{ backgroundColor: getInitialsColor(data.employee.name) }"
                                                        class="flex-shrink-0"
                                                    />
                                                    <div>
                                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ data.employee.name }}</div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ data.department }}</div>
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="department" header="Abteilung" :sortable="true">
                                            <template #body="{ data }">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                    {{ data.department }}
                                                </span>
                                            </template>
                                        </Column>

                                        <Column field="startDate" header="Zeitraum" :sortable="true" class="min-w-[180px]">
                                            <template #body="{ data }">
                                                <div class="space-y-1">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                                        {{ formatDate(data.startDate) }} - {{ formatDate(data.endDate) }}
                                                    </div>
                                                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                                        <i class="pi pi-calendar text-xs"></i>
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

                                        <Column field="substitute.name" header="Vertretung" :sortable="true">
                                            <template #body="{ data }">
                                                <div v-if="data.substitute" class="flex items-center gap-2">
                                                    <Avatar
                                                        :label="data.substitute.initials || getInitials(data.substitute.name)"
                                                        shape="circle"
                                                        size="small"
                                                        :style="{ backgroundColor: getInitialsColor(data.substitute.name) }"
                                                    />
                                                    <span class="text-gray-900 dark:text-gray-100">{{ data.substitute.name }}</span>
                                                </div>
                                                <div v-else class="text-gray-500 dark:text-gray-400 italic">
                                                    Keine Vertretung
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="requestDate" header="Beantragt am" :sortable="true">
                                            <template #body="{ data }">
                                                <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                                    <i class="pi pi-clock text-gray-500 dark:text-gray-400"></i>
                                                    <span>{{ formatDateTime(data.requestDate) }}</span>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="notes" header="Anmerkungen" class="max-w-[200px]">
                                            <template #body="{ data }">
                                                <div v-if="data.notes" class="truncate" :title="data.notes">
                                                    <i class="pi pi-comment text-gray-500 dark:text-gray-400 mr-2"></i>
                                                    <span class="text-gray-700 dark:text-gray-300">{{ data.notes }}</span>
                                                </div>
                                                <div v-else class="text-gray-400 dark:text-gray-500 italic">
                                                    Keine Anmerkungen
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="Aktionen" :exportable="false" class="w-[140px]">
                                            <template #body="{ data }">
                                                <div class="flex gap-1">
                                                    <Button
                                                        icon="pi pi-check"
                                                        class="p-button-success p-button-rounded p-button-sm"
                                                        @click="approveRequest(data)"
                                                        v-tooltip.top="'Genehmigen'"
                                                    />
                                                    <Button
                                                        icon="pi pi-times"
                                                        class="p-button-danger p-button-rounded p-button-sm"
                                                        @click="rejectRequest(data)"
                                                        v-tooltip.top="'Ablehnen'"
                                                    />
                                                    <Button
                                                        icon="pi pi-eye"
                                                        class="p-button-secondary p-button-rounded p-button-sm"
                                                        @click="viewRequestDetails(data)"
                                                        v-tooltip.top="'Details anzeigen'"
                                                    />
                                                </div>
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                            </TabPanel>

                            <TabPanel header="Genehmigte Anträge">
                                <div class="bg-white dark:bg-gray-900 rounded-lg">
                                    <!-- Filter-Leiste für genehmigte Anträge -->
                                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                            <div class="space-y-2">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Globale Suche</label>
                                                <InputText
                                                    v-model="approvedFilters['global'].value"
                                                    placeholder="Alle Felder durchsuchen..."
                                                    class="w-full"
                                                    @input="onApprovedGlobalFilterChange"
                                                />
                                            </div>
                                            <div class="space-y-2">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Abteilung</label>
                                                <Select
                                                    v-model="approvedFilters['department'].value"
                                                    :options="departmentOptions"
                                                    optionLabel="label"
                                                    optionValue="value"
                                                    placeholder="Alle Abteilungen"
                                                    class="w-full"
                                                    :showClear="true"
                                                    @change="onApprovedDepartmentFilterChange"
                                                />
                                            </div>
                                            <div class="space-y-2">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Zeitraum</label>
                                                <DatePicker
                                                    v-model="approvedDateRangeFilter"
                                                    dateFormat="dd.mm.yy"
                                                    placeholder="Datumsbereich wählen"
                                                    class="w-full"
                                                    selectionMode="range"
                                                    :showIcon="true"
                                                    @date-select="onApprovedDateRangeFilterChange"
                                                    @clear-click="clearApprovedDateRangeFilter"
                                                    :locale="de"
                                                />
                                            </div>
                                            <div class="flex items-end">
                                                <Button
                                                    label="Filter zurücksetzen"
                                                    icon="pi pi-filter-slash"
                                                    class="w-full p-button-outlined"
                                                    @click="clearApprovedFilters"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <DataTable
                                        :value="filteredApprovedRequests"
                                        :paginator="filteredApprovedRequests.length > 10"
                                        :rows="10"
                                        :rowsPerPageOptions="[5, 10, 20, 50]"
                                        dataKey="id"
                                        :rowHover="true"
                                        responsiveLayout="scroll"
                                        class="modern-datatable"
                                        :loading="loading"
                                        stripedRows
                                    >
                                        <template #header>
                                            <div class="flex justify-between items-center w-full p-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                                                    Genehmigte Urlaubsanträge ({{ filteredApprovedRequests.length }})
                                                </h3>
                                            </div>
                                        </template>

                                        <template #empty>
                                            <div class="text-center p-8">
                                                <i class="pi pi-inbox text-5xl text-gray-300 dark:text-gray-600 mb-4 block"></i>
                                                <p class="text-gray-500 dark:text-gray-400">
                                                    {{ approvedFilters['global'].value || hasActiveApprovedFilters ? 'Keine Anträge entsprechen den Filterkriterien' : 'Keine genehmigten Urlaubsanträge vorhanden' }}
                                                </p>
                                            </div>
                                        </template>

                                        <!-- Gleiche Spalten wie bei offenen Anträgen, aber mit zusätzlicher "Genehmigt von" Spalte -->
                                        <Column field="employee.name" header="Mitarbeiter" :sortable="true" class="min-w-[200px]">
                                            <template #body="{ data }">
                                                <div class="flex items-center gap-3">
                                                    <Avatar
                                                        :label="data.employee.initials || getInitials(data.employee.name)"
                                                        shape="circle"
                                                        size="large"
                                                        :style="{ backgroundColor: getInitialsColor(data.employee.name) }"
                                                        class="flex-shrink-0"
                                                    />
                                                    <div>
                                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ data.employee.name }}</div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ data.department }}</div>
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="department" header="Abteilung" :sortable="true">
                                            <template #body="{ data }">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    {{ data.department }}
                                                </span>
                                            </template>
                                        </Column>

                                        <Column field="startDate" header="Zeitraum" :sortable="true" class="min-w-[180px]">
                                            <template #body="{ data }">
                                                <div class="space-y-1">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                                        {{ formatDate(data.startDate) }} - {{ formatDate(data.endDate) }}
                                                    </div>
                                                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                                        <i class="pi pi-calendar text-xs"></i>
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

                                        <Column field="approvedBy" header="Genehmigt von" :sortable="true">
                                            <template #body="{ data }">
                                                <div class="space-y-1">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ data.approvedBy }}</div>
                                                    <div class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                                                        <i class="pi pi-calendar text-xs"></i>
                                                        <span>{{ formatDate(data.approvedDate) }}</span>
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="notes" header="Anmerkungen" class="max-w-[200px]">
                                            <template #body="{ data }">
                                                <div v-if="data.notes" class="truncate" :title="data.notes">
                                                    <i class="pi pi-comment text-gray-500 dark:text-gray-400 mr-2"></i>
                                                    <span class="text-gray-700 dark:text-gray-300">{{ data.notes }}</span>
                                                </div>
                                                <div v-else class="text-gray-400 dark:text-gray-500 italic">
                                                    Keine Anmerkungen
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="Aktionen" :exportable="false" class="w-[60px]">
                                            <template #body="{ data }">
                                                <Button
                                                    icon="pi pi-eye"
                                                    class="p-button-secondary p-button-rounded p-button-sm"
                                                    @click="viewRequestDetails(data)"
                                                    v-tooltip.top="'Details anzeigen'"
                                                />
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                            </TabPanel>

                            <TabPanel header="Abgelehnte Anträge">
                                <div class="bg-white dark:bg-gray-900 rounded-lg">
                                    <!-- Filter-Leiste für abgelehnte Anträge -->
                                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                            <div class="space-y-2">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Globale Suche</label>
                                                <InputText
                                                    v-model="rejectedFilters['global'].value"
                                                    placeholder="Alle Felder durchsuchen..."
                                                    class="w-full"
                                                    @input="onRejectedGlobalFilterChange"
                                                />
                                            </div>
                                            <div class="space-y-2">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Abteilung</label>
                                                <Select
                                                    v-model="rejectedFilters['department'].value"
                                                    :options="departmentOptions"
                                                    optionLabel="label"
                                                    optionValue="value"
                                                    placeholder="Alle Abteilungen"
                                                    class="w-full"
                                                    :showClear="true"
                                                    @change="onRejectedDepartmentFilterChange"
                                                />
                                            </div>
                                            <div class="space-y-2">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Zeitraum</label>
                                                <DatePicker
                                                    v-model="rejectedDateRangeFilter"
                                                    dateFormat="dd.mm.yy"
                                                    placeholder="Datumsbereich wählen"
                                                    class="w-full"
                                                    selectionMode="range"
                                                    :showIcon="true"
                                                    @date-select="onRejectedDateRangeFilterChange"
                                                    @clear-click="clearRejectedDateRangeFilter"
                                                    :locale="de"
                                                />
                                            </div>
                                            <div class="flex items-end">
                                                <Button
                                                    label="Filter zurücksetzen"
                                                    icon="pi pi-filter-slash"
                                                    class="w-full p-button-outlined"
                                                    @click="clearRejectedFilters"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <DataTable
                                        :value="filteredRejectedRequests"
                                        :paginator="filteredRejectedRequests.length > 10"
                                        :rows="10"
                                        :rowsPerPageOptions="[5, 10, 20, 50]"
                                        dataKey="id"
                                        :rowHover="true"
                                        responsiveLayout="scroll"
                                        class="modern-datatable"
                                        :loading="loading"
                                        stripedRows
                                    >
                                        <template #header>
                                            <div class="flex justify-between items-center w-full p-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                                                    Abgelehnte Urlaubsanträge ({{ filteredRejectedRequests.length }})
                                                </h3>
                                            </div>
                                        </template>

                                        <template #empty>
                                            <div class="text-center p-8">
                                                <i class="pi pi-inbox text-5xl text-gray-300 dark:text-gray-600 mb-4 block"></i>
                                                <p class="text-gray-500 dark:text-gray-400">
                                                    {{ rejectedFilters['global'].value || hasActiveRejectedFilters ? 'Keine Anträge entsprechen den Filterkriterien' : 'Keine abgelehnten Urlaubsanträge vorhanden' }}
                                                </p>
                                            </div>
                                        </template>

                                        <Column field="employee.name" header="Mitarbeiter" :sortable="true" class="min-w-[200px]">
                                            <template #body="{ data }">
                                                <div class="flex items-center gap-3">
                                                    <Avatar
                                                        :label="data.employee.initials || getInitials(data.employee.name)"
                                                        shape="circle"
                                                        size="large"
                                                        :style="{ backgroundColor: getInitialsColor(data.employee.name) }"
                                                        class="flex-shrink-0"
                                                    />
                                                    <div>
                                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ data.employee.name }}</div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ data.department }}</div>
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="department" header="Abteilung" :sortable="true">
                                            <template #body="{ data }">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    {{ data.department }}
                                                </span>
                                            </template>
                                        </Column>

                                        <Column field="startDate" header="Zeitraum" :sortable="true" class="min-w-[180px]">
                                            <template #body="{ data }">
                                                <div class="space-y-1">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                                        {{ formatDate(data.startDate) }} - {{ formatDate(data.endDate) }}
                                                    </div>
                                                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                                        <i class="pi pi-calendar text-xs"></i>
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

                                        <Column field="rejectedBy" header="Abgelehnt von" :sortable="true">
                                            <template #body="{ data }">
                                                <div class="space-y-1">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ data.rejectedBy }}</div>
                                                    <div class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                                                        <i class="pi pi-calendar text-xs"></i>
                                                        <span>{{ formatDate(data.rejectedDate) }}</span>
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="rejectionReason" header="Ablehnungsgrund" class="max-w-[250px]">
                                            <template #body="{ data }">
                                                <div v-if="data.rejectionReason" class="truncate text-red-600 dark:text-red-400" :title="data.rejectionReason">
                                                    <i class="pi pi-exclamation-circle mr-2"></i>
                                                    {{ data.rejectionReason }}
                                                </div>
                                                <div v-else class="text-gray-400 dark:text-gray-500 italic">
                                                    Kein Grund angegeben
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="Aktionen" :exportable="false" class="w-[60px]">
                                            <template #body="{ data }">
                                                <Button
                                                    icon="pi pi-eye"
                                                    class="p-button-secondary p-button-rounded p-button-sm"
                                                    @click="viewRequestDetails(data)"
                                                    v-tooltip.top="'Details anzeigen'"
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
            class="w-full max-w-lg mx-4"
            :modal="true"
            :closable="true"
        >
            <div class="p-4">
                <div class="bg-green-50 dark:bg-green-900/30 p-4 rounded-lg mb-6 flex items-start gap-4">
                    <i class="pi pi-check-circle text-green-500 dark:text-green-400 text-2xl flex-shrink-0 mt-1"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-green-800 dark:text-green-300 mb-2">Urlaubsantrag genehmigen</h3>
                        <p class="text-green-700 dark:text-green-400">
                            Sie sind dabei, den Urlaubsantrag von <strong>{{ selectedRequest?.employee?.name }}</strong> zu genehmigen.
                        </p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg mb-6 border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-3">Antragsdetails</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Zeitraum</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                {{ selectedRequest ? formatDate(selectedRequest.startDate) : '' }} - {{ selectedRequest ? formatDate(selectedRequest.endDate) : '' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Art</div>
                            <div class="font-medium">
                                <Tag
                                    v-if="selectedRequest"
                                    :value="getDayTypeLabel(selectedRequest.dayType)"
                                    :severity="getDayTypeSeverity(selectedRequest.dayType)"
                                />
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Tatsächliche Tage</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ selectedRequest ? getActualDays(selectedRequest) : 0 }} {{ selectedRequest && getActualDays(selectedRequest) === 1 ? 'Tag' : 'Tage' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Abteilung</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ selectedRequest?.department }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Beantragt am</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ selectedRequest ? formatDate(selectedRequest.requestDate) : '' }}</div>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="approvalNotes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Anmerkungen (optional)</label>
                    <Textarea
                        id="approvalNotes"
                        v-model="approvalNotes"
                        rows="3"
                        autoResize
                        class="w-full"
                        placeholder="Fügen Sie hier Ihre Anmerkungen zur Genehmigung hinzu..."
                    />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2 p-4 border-t border-gray-200 dark:border-gray-700">
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
            class="w-full max-w-lg mx-4"
            :modal="true"
            :closable="true"
        >
            <div class="p-4">
                <div class="bg-red-50 dark:bg-red-900/30 p-4 rounded-lg mb-6 flex items-start gap-4">
                    <i class="pi pi-exclamation-triangle text-red-500 dark:text-red-400 text-2xl flex-shrink-0 mt-1"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-red-800 dark:text-red-300 mb-2">Urlaubsantrag ablehnen</h3>
                        <p class="text-red-700 dark:text-red-400">
                            Sie sind dabei, den Urlaubsantrag von <strong>{{ selectedRequest?.employee?.name }}</strong> abzulehnen.
                        </p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg mb-6 border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-3">Antragsdetails</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Zeitraum</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                {{ selectedRequest ? formatDate(selectedRequest.startDate) : '' }} - {{ selectedRequest ? formatDate(selectedRequest.endDate) : '' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Art</div>
                            <div class="font-medium">
                                <Tag
                                    v-if="selectedRequest"
                                    :value="getDayTypeLabel(selectedRequest.dayType)"
                                    :severity="getDayTypeSeverity(selectedRequest.dayType)"
                                />
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Tatsächliche Tage</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ selectedRequest ? getActualDays(selectedRequest) : 0 }} {{ selectedRequest && getActualDays(selectedRequest) === 1 ? 'Tag' : 'Tage' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Abteilung</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ selectedRequest?.department }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Beantragt am</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ selectedRequest ? formatDate(selectedRequest.requestDate) : '' }}</div>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="rejectionReason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Ablehnungsgrund <span class="text-red-500">*</span>
                    </label>
                    <Textarea
                        id="rejectionReason"
                        v-model="rejectionReason"
                        rows="3"
                        autoResize
                        class="w-full"
                        placeholder="Bitte geben Sie einen Grund für die Ablehnung an..."
                    />
                    <small class="text-gray-500 dark:text-gray-400 block">Diese Information wird dem Mitarbeiter angezeigt.</small>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2 p-4 border-t border-gray-200 dark:border-gray-700">
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
            class="w-full max-w-2xl mx-4"
            :modal="true"
            :closable="true"
        >
            <div v-if="selectedRequest" class="p-4">
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg mb-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-4">
                        <Avatar
                            :label="selectedRequest.employee.initials || getInitials(selectedRequest.employee.name)"
                            shape="circle"
                            size="large"
                            :style="{ backgroundColor: getInitialsColor(selectedRequest.employee.name) }"
                        />
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ selectedRequest.employee.name }}</h3>
                            <p class="text-gray-500 dark:text-gray-400">{{ selectedRequest.department }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Zeitraum</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                {{ formatDate(selectedRequest.startDate) }} - {{ formatDate(selectedRequest.endDate) }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Art</div>
                            <div class="font-medium">
                                <Tag
                                    :value="getDayTypeLabel(selectedRequest.dayType)"
                                    :severity="getDayTypeSeverity(selectedRequest.dayType)"
                                />
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Tatsächliche Tage</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ getActualDays(selectedRequest) }} {{ getActualDays(selectedRequest) === 1 ? 'Tag' : 'Tage' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Beantragt am</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ formatDateTime(selectedRequest.requestDate) }}</div>
                        </div>
                        <div v-if="selectedRequest.substitute">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Vertretung</div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ selectedRequest.substitute.name }}</div>
                        </div>
                    </div>

                    <div v-if="selectedRequest.notes" class="mb-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Anmerkungen</div>
                        <div class="p-3 bg-white dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600 text-gray-900 dark:text-gray-100">
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
                            <div class="text-sm text-red-600 dark:text-red-300 mb-1">Ablehnungsgrund:</div>
                            <div class="p-2 bg-white dark:bg-gray-700 rounded border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300">
                                {{ selectedRequest.rejectionReason }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2 p-4 border-t border-gray-200 dark:border-gray-700">
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
import { FilterMatchMode } from '@primevue/core/api';
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
import DatePicker from 'primevue/datepicker';
import Toast from 'primevue/toast';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import VacationService from '@/Services/VacationService';
import Select from 'primevue/select';
import { router } from '@inertiajs/vue3';
import { usePrimeVue } from "primevue/config";

const toast = useToast();
const primevue = usePrimeVue();
dayjs.locale('de');

// Deutsche Lokalisierung für PrimeVue
const de = {
    firstDayOfWeek: 1,
    dayNames: ["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag"],
    dayNamesShort: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
    dayNamesMin: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
    monthNames: ["Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember"],
    monthNamesShort: ["Jan", "Feb", "Mär", "Apr", "Mai", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dez"],
    today: "Heute",
    clear: "Löschen",
    weekHeader: "KW",
    dateFormat: "dd.mm.yy",
    firstDay: 1
};

// Hilfsfunktionen für Halbtags-Anzeige
const getDayTypeLabel = (dayType) => {
    switch (dayType) {
        case 'morning': return 'Vormittag'
        case 'afternoon': return 'Nachmittag'
        case 'full_day':
        default: return 'Ganzer Tag'
    }
}

const getDayTypeSeverity = (dayType) => {
    switch (dayType) {
        case 'morning': return 'info'
        case 'afternoon': return 'warning'
        case 'full_day':
        default: return 'primary'
    }
}

const getActualDays = (request) => {
    if (request.actualDays !== undefined) {
        return request.actualDays
    }

    if (request.dayType && request.dayType !== 'full_day') {
        const startDate = dayjs(request.startDate)
        const endDate = dayjs(request.endDate)
        if (startDate.isSame(endDate, 'day')) {
            return 0.5
        }
    }

    return request.days || 0
}

// Zustand
const loading = ref(false);
const processingRequest = ref(false);
const approveDialogVisible = ref(false);
const rejectDialogVisible = ref(false);
const detailsDialogVisible = ref(false);
const selectedRequest = ref(null);
const approvalNotes = ref('');
const rejectionReason = ref('');

// Sortierung
const sortField = ref('requestDate');
const sortOrder = ref(-1);

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

// Separate Datumsbereich-Filter
const dateRangeFilter = ref(null);
const approvedDateRangeFilter = ref(null);
const rejectedDateRangeFilter = ref(null);

// Urlaubsanträge
const pendingRequests = ref([]);
const approvedRequests = ref([]);
const rejectedRequests = ref([]);

// Computed Properties für gefilterte Daten
const filteredPendingRequests = computed(() => {
    return applyFilters(pendingRequests.value, filters.value, dateRangeFilter.value);
});

const filteredApprovedRequests = computed(() => {
    return applyFilters(approvedRequests.value, approvedFilters.value, approvedDateRangeFilter.value);
});

const filteredRejectedRequests = computed(() => {
    return applyFilters(rejectedRequests.value, rejectedFilters.value, rejectedDateRangeFilter.value);
});

// Computed Properties für aktive Filter
const hasActiveFilters = computed(() => {
    return filters.value['global'].value ||
        filters.value['department'].value ||
        dateRangeFilter.value;
});

const hasActiveApprovedFilters = computed(() => {
    return approvedFilters.value['global'].value ||
        approvedFilters.value['department'].value ||
        approvedDateRangeFilter.value;
});

const hasActiveRejectedFilters = computed(() => {
    return rejectedFilters.value['global'].value ||
        rejectedFilters.value['department'].value ||
        rejectedDateRangeFilter.value;
});

const departmentOptions = computed(() => {
    const uniqueDepartments = new Set();

    [...pendingRequests.value, ...approvedRequests.value, ...rejectedRequests.value].forEach(request => {
        if (request.department) {
            uniqueDepartments.add(request.department);
        }
    });

    return Array.from(uniqueDepartments).map(dept => ({
        label: dept,
        value: dept
    }));
});

// Filterfunktionen
const applyFilters = (data, filterObj, dateRange) => {
    let filtered = [...data];

    // Globaler Filter
    if (filterObj['global'].value) {
        const globalValue = filterObj['global'].value.toLowerCase();
        filtered = filtered.filter(item => {
            return (
                item.employee.name.toLowerCase().includes(globalValue) ||
                item.department.toLowerCase().includes(globalValue) ||
                (item.notes && item.notes.toLowerCase().includes(globalValue)) ||
                (item.substitute && item.substitute.name.toLowerCase().includes(globalValue)) ||
                (item.approvedBy && item.approvedBy.toLowerCase().includes(globalValue)) ||
                (item.rejectedBy && item.rejectedBy.toLowerCase().includes(globalValue)) ||
                (item.rejectionReason && item.rejectionReason.toLowerCase().includes(globalValue)) ||
                getDayTypeLabel(item.dayType).toLowerCase().includes(globalValue)
            );
        });
    }

    // Abteilungsfilter
    if (filterObj['department'].value) {
        filtered = filtered.filter(item => item.department === filterObj['department'].value);
    }

    // Datumsbereichsfilter
    if (dateRange && dateRange.length === 2 && dateRange[0] && dateRange[1]) {
        const startFilter = dayjs(dateRange[0]).startOf('day');
        const endFilter = dayjs(dateRange[1]).endOf('day');

        filtered = filtered.filter(item => {
            const itemStart = dayjs(item.startDate);
            const itemEnd = dayjs(item.endDate);

            // Prüfe ob sich die Zeiträume überschneiden
            return itemStart.isBefore(endFilter) && itemEnd.isAfter(startFilter);
        });
    }

    return filtered;
};

// Event Handler für Filter
const onGlobalFilterChange = () => {};
const onDepartmentFilterChange = () => {};
const onDateRangeFilterChange = () => {};
const onApprovedGlobalFilterChange = () => {};
const onApprovedDepartmentFilterChange = () => {};
const onApprovedDateRangeFilterChange = () => {};
const onRejectedGlobalFilterChange = () => {};
const onRejectedDepartmentFilterChange = () => {};
const onRejectedDateRangeFilterChange = () => {};

// Filter zurücksetzen
const clearAllFilters = () => {
    filters.value['global'].value = null;
    filters.value['department'].value = null;
    dateRangeFilter.value = null;
};

const clearApprovedFilters = () => {
    approvedFilters.value['global'].value = null;
    approvedFilters.value['department'].value = null;
    approvedDateRangeFilter.value = null;
};

const clearRejectedFilters = () => {
    rejectedFilters.value['global'].value = null;
    rejectedFilters.value['department'].value = null;
    rejectedDateRangeFilter.value = null;
};

const clearDateRangeFilter = () => { dateRangeFilter.value = null; };
const clearApprovedDateRangeFilter = () => { approvedDateRangeFilter.value = null; };
const clearRejectedDateRangeFilter = () => { rejectedDateRangeFilter.value = null; };

// Sortierung
const onSort = (event) => {
    sortField.value = event.sortField;
    sortOrder.value = event.sortOrder;
};

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

        // Fallback mit erweiterten Beispieldaten
        pendingRequests.value = [
            {
                id: 1,
                employee: {
                    name: 'Max Mustermann',
                    id: 1,
                    initials: 'MM' // Beispiel für vorhandene Initialen
                },
                department: 'Entwicklung',
                startDate: new Date(2025, 3, 15),
                endDate: new Date(2025, 3, 20),
                days: 6,
                dayType: 'full_day',
                actualDays: 6,
                requestDate: new Date(2025, 2, 1),
                status: 'pending',
                substitute: {
                    name: 'Anna Schmidt',
                    id: 2,
                    initials: null // Beispiel für fehlende Initialen - wird Fallback verwenden
                },
                notes: 'Familienurlaub in den Osterferien'
            },
            {
                id: 2,
                employee: {
                    name: 'Julia Weber',
                    id: 3,
                    initials: 'JW'
                },
                department: 'Marketing',
                startDate: new Date(2025, 4, 10),
                endDate: new Date(2025, 4, 10),
                days: 1,
                dayType: 'morning',
                actualDays: 0.5,
                requestDate: new Date(2025, 3, 15),
                status: 'pending',
                substitute: null,
                notes: 'Arzttermin am Vormittag'
            },
            {
                id: 5,
                employee: {
                    name: 'Peter Schneider',
                    id: 6,
                    initials: null // Wird getInitials() verwenden
                },
                department: 'Vertrieb',
                startDate: new Date(2025, 5, 1),
                endDate: new Date(2025, 5, 7),
                days: 7,
                dayType: 'full_day',
                actualDays: 5,
                requestDate: new Date(2025, 4, 10),
                status: 'pending',
                substitute: {
                    name: 'Lisa Müller',
                    id: 7,
                    initials: 'LM'
                },
                notes: 'Hochzeitsreise'
            }
        ];

        approvedRequests.value = [
            {
                id: 3,
                employee: {
                    name: 'Thomas Müller',
                    id: 4,
                    initials: 'TM'
                },
                department: 'Vertrieb',
                startDate: new Date(2025, 5, 1),
                endDate: new Date(2025, 5, 14),
                days: 10,
                dayType: 'full_day',
                actualDays: 10,
                requestDate: new Date(2025, 4, 1),
                status: 'approved',
                approvedBy: 'Maria Schmidt',
                approvedDate: new Date(2025, 4, 5),
                notes: 'Sommerurlaub mit Familie'
            },
            {
                id: 6,
                employee: {
                    name: 'Sandra Klein',
                    id: 8,
                    initials: 'SK'
                },
                department: 'Personal',
                startDate: new Date(2025, 2, 20),
                endDate: new Date(2025, 2, 20),
                days: 1,
                dayType: 'afternoon',
                actualDays: 0.5,
                requestDate: new Date(2025, 1, 15),
                status: 'approved',
                approvedBy: 'Hans Meier',
                approvedDate: new Date(2025, 1, 18),
                notes: 'Nachmittag frei für Behördengang'
            }
        ];

        rejectedRequests.value = [
            {
                id: 4,
                employee: {
                    name: 'Sarah Fischer',
                    id: 5,
                    initials: null // Wird getInitials() verwenden
                },
                department: 'Personal',
                startDate: new Date(2025, 2, 20),
                endDate: new Date(2025, 2, 24),
                days: 5,
                dayType: 'full_day',
                actualDays: 5,
                requestDate: new Date(2025, 1, 15),
                status: 'rejected',
                rejectedBy: 'Maria Schmidt',
                rejectedDate: new Date(2025, 1, 20),
                rejectionReason: 'Personalmangel in diesem Zeitraum aufgrund von Krankheitsausfällen'
            },
            {
                id: 7,
                employee: {
                    name: 'Michael Wagner',
                    id: 9,
                    initials: 'MW'
                },
                department: 'Entwicklung',
                startDate: new Date(2025, 6, 1),
                endDate: new Date(2025, 6, 21),
                days: 15,
                dayType: 'full_day',
                actualDays: 15,
                requestDate: new Date(2025, 5, 1),
                status: 'rejected',
                rejectedBy: 'Klaus Bauer',
                rejectedDate: new Date(2025, 5, 3),
                rejectionReason: 'Zu lange Abwesenheit während kritischer Projektphase'
            }
        ];
    } finally {
        loading.value = false;
    }
};

// Daten aktualisieren
const refreshData = async () => {
    await fetchVacationRequests();
    toast.add({
        severity: 'success',
        summary: 'Aktualisiert',
        detail: 'Die Daten wurden erfolgreich aktualisiert.',
        life: 2000
    });
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
    detailsDialogVisible.value = false;
};

const rejectRequest = (request) => {
    selectedRequest.value = request;
    rejectionReason.value = '';
    rejectDialogVisible.value = true;
    detailsDialogVisible.value = false;
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
            summary: 'Erfolgreich genehmigt',
            detail: `Urlaubsantrag von ${selectedRequest.value.employee.name} wurde erfolgreich genehmigt.`,
            life: 4000
        });

        closeApproveDialog();
        await fetchVacationRequests();
    } catch (error) {
        console.error('Fehler beim Genehmigen:', error);
        toast.add({
            severity: 'error',
            summary: 'Fehler beim Genehmigen',
            detail: 'Der Urlaubsantrag konnte nicht genehmigt werden. Bitte versuchen Sie es später erneut.',
            life: 5000
        });
    } finally {
        processingRequest.value = false;
    }
};

const confirmReject = async () => {
    if (!selectedRequest.value) return;

    if (!rejectionReason.value || rejectionReason.value.trim() === '') {
        toast.add({
            severity: 'warn',
            summary: 'Ablehnungsgrund erforderlich',
            detail: 'Bitte geben Sie einen Grund für die Ablehnung an.',
            life: 4000
        });
        return;
    }

    processingRequest.value = true;

    try {
        await VacationService.rejectVacationRequest(selectedRequest.value.id, rejectionReason.value);

        toast.add({
            severity: 'info',
            summary: 'Erfolgreich abgelehnt',
            detail: `Urlaubsantrag von ${selectedRequest.value.employee.name} wurde abgelehnt.`,
            life: 4000
        });

        closeRejectDialog();
        await fetchVacationRequests();
    } catch (error) {
        console.error('Fehler beim Ablehnen:', error);
        toast.add({
            severity: 'error',
            summary: 'Fehler beim Ablehnen',
            detail: 'Der Urlaubsantrag konnte nicht abgelehnt werden. Bitte versuchen Sie es später erneut.',
            life: 5000
        });
    } finally {
        processingRequest.value = false;
    }
};

const showCalendarView = () => {
    router.visit('/company-calendar');
};

const exportData = () => {
    // Bestimme, welche Daten basierend auf dem aktiven Tab exportiert werden sollen
    let dataToExport = [];
    let fileName = '';

    // Versuche den aktiven Tab zu ermitteln
    const activeTab = document.querySelector('.p-tabview-nav li.p-highlight');
    const tabIndex = activeTab ? Array.from(activeTab.parentNode.children).indexOf(activeTab) : 0;

    switch (tabIndex) {
        case 0:
            dataToExport = filteredPendingRequests.value;
            fileName = 'offene_urlaubsantraege';
            break;
        case 1:
            dataToExport = filteredApprovedRequests.value;
            fileName = 'genehmigte_urlaubsantraege';
            break;
        case 2:
            dataToExport = filteredRejectedRequests.value;
            fileName = 'abgelehnte_urlaubsantraege';
            break;
        default:
            dataToExport = filteredPendingRequests.value;
            fileName = 'offene_urlaubsantraege';
    }

    if (dataToExport.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'Keine Daten vorhanden',
            detail: 'Es sind keine Daten zum Exportieren vorhanden.',
            life: 3000
        });
        return;
    }

    // Erstelle CSV-Inhalt mit deutschen Überschriften
    let csvContent = "data:text/csv;charset=utf-8,\uFEFF"; // BOM für korrekte Umlaute

    // Header mit deutschen Bezeichnungen
    const headers = [
        "Mitarbeiter",
        "Abteilung",
        "Startdatum",
        "Enddatum",
        "Urlaubsart",
        "Tatsächliche Tage",
        "Status",
        "Vertretung",
        "Beantragt am",
        "Anmerkungen"
    ];

    // Füge zusätzliche Header je nach Tab hinzu
    if (tabIndex === 1) { // Genehmigte Anträge
        headers.push("Genehmigt von", "Genehmigt am");
    } else if (tabIndex === 2) { // Abgelehnte Anträge
        headers.push("Abgelehnt von", "Abgelehnt am", "Ablehnungsgrund");
    }

    csvContent += headers.join(";") + "\r\n";

    // Daten
    dataToExport.forEach(item => {
        const row = [
            `"${item.employee.name}"`,
            `"${item.department}"`,
            formatDate(item.startDate),
            formatDate(item.endDate),
            `"${getDayTypeLabel(item.dayType)}"`,
            getActualDays(item),
            item.status === 'pending' ? 'Ausstehend' : (item.status === 'approved' ? 'Genehmigt' : 'Abgelehnt'),
            `"${item.substitute ? item.substitute.name : 'Keine Vertretung'}"`,
            formatDateTime(item.requestDate),
            `"${item.notes || 'Keine Anmerkungen'}"`
        ];

        // Füge zusätzliche Daten je nach Tab hinzu
        if (tabIndex === 1 && item.approvedBy) {
            row.push(`"${item.approvedBy}"`, formatDateTime(item.approvedDate));
        } else if (tabIndex === 2 && item.rejectedBy) {
            row.push(`"${item.rejectedBy}"`, formatDateTime(item.rejectedDate), `"${item.rejectionReason || 'Kein Grund angegeben'}"`);
        }

        csvContent += row.join(";") + "\r\n";
    });

    // Erstelle einen Download-Link und klicke darauf
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `${fileName}_${dayjs().format('YYYY-MM-DD')}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    toast.add({
        severity: 'success',
        summary: 'Export erfolgreich',
        detail: `${dataToExport.length} Datensätze wurden erfolgreich exportiert.`,
        life: 3000
    });
};

// Komponente initialisieren
onMounted(() => {
    primevue.config.locale = de;
    fetchVacationRequests();
});
</script>

<style>
/* Modern tabs styling mit Tailwind */
.modern-tabs .p-tabview-nav {
    @apply border-b border-gray-200 dark:border-gray-700 px-4 bg-gray-50 dark:bg-gray-800;
}

.modern-tabs .p-tabview-nav li .p-tabview-nav-link {
    @apply border-none text-gray-600 dark:text-gray-400 transition-all duration-200 px-6 py-4 font-medium hover:text-gray-800 dark:hover:text-gray-200;
}

.modern-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link {
    @apply text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400 bg-white dark:bg-gray-900;
}

.modern-tabs .p-tabview-panels {
    @apply p-0;
}

/* DataTable Styling mit Tailwind */
.modern-datatable {
    @apply rounded-lg overflow-hidden shadow-sm border border-gray-200 dark:border-gray-700;
}

.modern-datatable .p-datatable-header {
    @apply bg-gray-50 dark:bg-gray-800 border-none p-0;
}

.modern-datatable .p-datatable-thead > tr > th {
    @apply bg-gray-50 dark:bg-gray-800 p-4 font-semibold text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 uppercase text-xs tracking-wide;
}

.modern-datatable .p-datatable-tbody > tr {
    @apply transition-all duration-200 border-gray-200 dark:border-gray-700 ;
}

.modern-datatable .p-datatable-tbody > tr > td {
    @apply p-4 border-gray-200 dark:border-gray-700;
}

.modern-datatable .p-datatable-tbody > tr:hover {
    @apply bg-gray-50 dark:bg-gray-800;
}

.modern-datatable .p-paginator {
    @apply p-4 bg-gray-50 dark:bg-gray-800 border-none;
}

/* Dialog Styling mit Tailwind */
.p-dialog {
    @apply rounded-lg overflow-hidden shadow-xl border border-gray-200 dark:border-gray-700;
}

.p-dialog .p-dialog-header {
    @apply px-6 py-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700;
}

.p-dialog .p-dialog-title {
    @apply font-semibold text-lg text-gray-900 dark:text-gray-100;
}

.p-dialog .p-dialog-content {
    @apply p-0 bg-white dark:bg-gray-900;
}

/* Responsive Verbesserungen */
@media (max-width: 768px) {
    .modern-tabs .p-tabview-nav {
        @apply px-2 overflow-x-auto;
    }

    .modern-tabs .p-tabview-nav li .p-tabview-nav-link {
        @apply px-4 py-3 text-sm whitespace-nowrap;
    }

    .modern-datatable .p-datatable-tbody > tr > td {
        @apply p-2 text-sm;
    }

    .modern-datatable .p-datatable-thead > tr > th {
        @apply p-3 text-xs;
    }
}

@media (max-width: 480px) {
    .modern-datatable .p-datatable-tbody > tr > td {
        @apply p-1 text-xs;
    }

    .modern-datatable .p-datatable-thead > tr > th {
        @apply p-2 text-xs;
    }
}
</style>
