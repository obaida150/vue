<template>
    <AppLayout title="Benutzerverwaltung">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Benutzerverwaltung
                </h2>
                <Button icon="pi pi-plus" label="Neuen Benutzer anlegen" @click="openNewUserDialog" />
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-[90rem] mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <DataTable
                            :value="users"
                            :paginator="true"
                            :rows="10"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            dataKey="id"
                            :rowHover="true"
                            responsiveLayout="stack"
                            breakpoint="960px"
                            class="p-datatable-sm"
                            :loading="loading"
                            v-model:filters="filters"
                            filterDisplay="menu"
                            :globalFilterFields="['name', 'email', 'department.name', 'role.name']"
                            stripedRows
                        >
                            <template #header>
                                <div class="flex justify-between items-center flex-wrap gap-2">
                                    <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">Benutzer</h3>
                                    <span class="p-input-icon-left">
                  <i class="pi pi-search" />
                  <InputText v-model="filters['global'].value" placeholder="Suchen..." class="p-inputtext-sm" />
                </span>
                                </div>
                            </template>

                            <template #empty>
                                <div class="text-center p-6">
                                    <i class="pi pi-users text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                    <p class="text-gray-500 dark:text-gray-400">Keine Benutzer gefunden</p>
                                </div>
                            </template>

                            <Column field="name" header="Name" :sortable="true">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-3">
                                        <Avatar :label="getInitials(data.name)" shape="circle" size="large" :style="{ backgroundColor: getInitialsColor(data.name) }" />
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ data.name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ data.email }}</div>
                                        </div>
                                    </div>
                                </template>
                                <template #filter="{ filterModel, filterCallback }">
                                    <InputText v-model="filterModel.value" @input="filterCallback()" placeholder="Suche nach Name" class="p-column-filter" />
                                </template>
                            </Column>

                            <Column field="department.name" header="Abteilung" :sortable="true">
                                <template #body="{ data }">
                                    <Tag v-if="data.department" :value="data.department.name" :severity="getDepartmentSeverity(data.department.name)" />
                                    <Tag v-else value="Keine Abteilung" severity="secondary" />
                                </template>
<!--                                <template #filter="{ filterModel, filterCallback }">-->
<!--                                    <Select-->
<!--                                        v-model="filterModel.constraints[0].value"-->
<!--                                        @change="filterCallback()"-->
<!--                                        :options="departments"-->
<!--                                        optionLabel="name"-->
<!--                                        optionValue="name"-->
<!--                                        placeholder="Alle Abteilungen"-->
<!--                                        class="p-column-filter"-->
<!--                                        :showClear="true"-->
<!--                                    />-->
<!--                                </template>-->
                            </Column>

                            <Column field="role.name" header="Rolle" :sortable="true">
                                <template #body="{ data }">
                                    <Tag :value="data.role.name" :severity="getRoleSeverity(data.role.name)" />
                                </template>
<!--                                <template #filter="{ filterModel, filterCallback }">-->
<!--                                    <Select-->
<!--                                        v-model="filterModel.constraints[0].value"-->
<!--                                        @change="filterCallback()"-->
<!--                                        :options="roles"-->
<!--                                        optionLabel="name"-->
<!--                                        optionValue="name"-->
<!--                                        placeholder="Alle Rollen"-->
<!--                                        class="p-column-filter"-->
<!--                                        :showClear="true"-->
<!--                                    />-->
<!--                                </template>-->
                            </Column>

                            <Column field="status" header="Status" :sortable="true">
                                <template #body="{ data }">
                                    <Tag :value="data.status ? 'Aktiv' : 'Inaktiv'" :severity="data.status ? 'success' : 'danger'" />
                                </template>
<!--                                <template #filter="{ filterModel, filterCallback }">-->
<!--                                    <Select-->
<!--                                        v-model="filterModel.constraints[0].value"-->
<!--                                        @change="filterCallback()"-->
<!--                                        :options="[{name: 'Aktiv', value: true}, {name: 'Inaktiv', value: false}]"-->
<!--                                        optionLabel="name"-->
<!--                                        optionValue="value"-->
<!--                                        placeholder="Alle Status"-->
<!--                                        class="p-column-filter"-->
<!--                                        :showClear="true"-->
<!--                                    />-->
<!--                                </template>-->
                            </Column>

                            <Column field="employee_number" header="Personal Nummer" :sortable="true">
                                <template #body="{ data }">
                                    <Tag v-if="data.employee_number" :value="data.employee_number" :severity="getDepartmentSeverity(data.employee_number)" />
                                    <Tag v-else value="Keine Personalnummer" severity="secondary" />
                                </template>
                            </Column>

                            <Column field="initials" header="Kürzel" :sortable="true">
                                <template #body="{ data }">
                                    <Tag v-if="data.initials" :value="data.initials" :severity="getDepartmentSeverity(data.initials)" />
                                    <Tag v-else value="Kein Kürzel" severity="secondary" />
                                </template>
                            </Column>

                            <Column header="Aktionen" :exportable="false" style="min-width: 12rem">
                                <template #body="{ data }">
                                    <div class="flex gap-2 flex-wrap">
                                        <Button
                                            icon="pi pi-pencil"
                                            class="p-button-rounded p-button-primary"
                                            @click="editUser(data)"
                                            tooltip="Bearbeiten"
                                        />
<!--                                        <Button-->
<!--                                          icon="pi pi-key"-->
<!--                                          class="p-button-rounded p-button-warning"-->
<!--                                          @click="resetPassword(data)"-->
<!--                                          tooltip="Passwort zurücksetzen"-->
<!--                                        />-->
<!--                                        <Button-->
<!--                                          icon="pi pi-trash"-->
<!--                                          class="p-button-rounded p-button-danger"-->
<!--                                          @click="confirmDeleteUser(data)"-->
<!--                                          tooltip="Löschen"-->
<!--                                        />-->

                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog für neuen/bearbeiten Benutzer -->
        <Dialog
            v-model:visible="userDialogVisible"
            :header="editMode ? 'Benutzer bearbeiten' : 'Neuen Benutzer anlegen'"
            :style="{ width: '650px' }"
            :modal="true"
            :closable="true"
            class="user-dialog"
        >
            <div class="p-fluid">
                <div class="grid">
                    <div class="col-12 md:col-6 field mb-4">
                        <label for="first_name" class="block text-sm font-medium mb-1">Vorname <span class="text-red-500">*</span></label>
                        <span class="p-input-icon-left w-full">
            <i class="pi pi-user"></i>
            <InputText id="first_name" v-model="user.first_name" :class="{'p-invalid': submitted && !user.first_name}" class="w-full" />
          </span>
                        <small v-if="submitted && !user.first_name" class="p-error">Vorname ist erforderlich.</small>
                    </div>

                    <div class="col-12 md:col-6 field mb-4">
                        <label for="last_name" class="block text-sm font-medium mb-1">Name <span class="text-red-500">*</span></label>
                        <span class="p-input-icon-left w-full">
            <i class="pi pi-user"></i>
            <InputText id="last_name" v-model="user.last_name" :class="{'p-invalid': submitted && !user.last_name}" class="w-full" />
          </span>
                        <small v-if="submitted && !user.last_name" class="p-error">Name ist erforderlich.</small>
                    </div>

                    <div class="col-12 field mb-4">
                        <label for="email" class="block text-sm font-medium mb-1">E-Mail <span class="text-red-500">*</span></label>
                        <span class="p-input-icon-left w-full">
            <i class="pi pi-envelope"></i>
            <InputText id="email" v-model="user.email" :class="{'p-invalid': submitted && !user.email}" class="w-full" />
          </span>
                        <small v-if="submitted && !user.email" class="p-error">E-Mail ist erforderlich.</small>
                    </div>

                    <div class="col-12 md:col-6 field mb-4">
                        <label for="department" class="block text-sm font-medium mb-1">Abteilung <span class="text-red-500">*</span></label>
                        <Select
                            id="department"
                            v-model="user.department"
                            :options="departments"
                            optionLabel="name"
                            placeholder="Abteilung auswählen"
                            :class="{'p-invalid': submitted && !user.department}"
                            class="w-full"
                        />
                        <small v-if="submitted && !user.department" class="p-error">Abteilung ist erforderlich.</small>
                    </div>

                    <div class="col-12 md:col-6 field mb-4">
                        <label for="role" class="block text-sm font-medium mb-1">Rolle <span class="text-red-500">*</span></label>
                        <Select
                            id="role"
                            v-model="user.role"
                            :options="roles"
                            optionLabel="name"
                            placeholder="Rolle auswählen"
                            :class="{'p-invalid': submitted && !user.role}"
                            class="w-full"
                        />
                        <small v-if="submitted && !user.role" class="p-error">Rolle ist erforderlich.</small>
                    </div>

                    <div class="col-12 md:col-6 field mb-4">
                        <label for="initials" class="block text-sm font-medium mb-1">Kürzel</label>
                        <InputText id="initials" v-model="user.initials" maxlength="3" class="w-full" />
                    </div>

                    <div class="col-12 md:col-6 field mb-4">
                        <label for="employee_number" class="block text-sm font-medium mb-1">Personalnummer</label>
                        <InputText id="employee_number" v-model="user.employee_number" class="w-full" />
                    </div>

                    <div class="col-12 md:col-6 field mb-4">
                        <label for="vacation_days" class="block text-sm font-medium mb-1">Urlaubstage pro Jahr</label>
                        <InputNumber id="vacation_days" v-model="user.vacation_days_per_year" :min="0" :max="50" class="w-full" />
                    </div>

                    <div class="col-12 md:col-6 field mb-4">
                        <label for="entry_date" class="block text-sm font-medium mb-1">Eintrittsdatum</label>
                        <DatePicker id="entry_date" v-model="user.entry_date" dateFormat="dd.mm.yy" class="w-full" showIcon />
                    </div>

                    <div class="col-12 md:col-6 field mb-4">
                        <label for="birth_date" class="block text-sm font-medium mb-1">Geburtsdatum</label>
                        <DatePicker id="birth_date" v-model="user.birth_date" dateFormat="dd.mm.yy" class="w-full" showIcon />
                    </div>

                    <div class="col-12 field mb-4">
                        <label for="status" class="block text-sm font-medium mb-1">Status</label>
                        <div class="flex align-items-center gap-2">
                            <ToggleSwitch  v-model="user.status" />
                            <span>{{ user.status ? 'Aktiv' : 'Inaktiv' }}</span>
                        </div>
                    </div>

                    <div v-if="!editMode" class="col-12 field mb-4">
                        <label for="password" class="block text-sm font-medium mb-1">Passwort <span class="text-red-500">*</span></label>
                        <Password id="password" v-model="user.password" toggleMask :class="{'p-invalid': submitted && !user.password}" class="w-full" />
                        <small v-if="submitted && !user.password" class="p-error">Passwort ist erforderlich.</small>
                    </div>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Abbrechen" icon="pi pi-times" class="p-button-text" @click="hideDialog" />
                    <Button label="Speichern" icon="pi pi-check" class="p-button-primary" @click="saveUser" :loading="saving" />
                </div>
            </template>
        </Dialog>

        <!-- Dialog für Passwort zurücksetzen -->
        <Dialog
            v-model:visible="resetPasswordDialogVisible"
            header="Passwort zurücksetzen"
            :style="{ width: '500px' }"
            :modal="true"
            :closable="true"
            class="user-dialog"
        >
            <div class="p-fluid">
                <div class="field mb-4">
                    <label for="newPassword" class="block text-sm font-medium mb-1">Neues Passwort <span class="text-red-500">*</span></label>
                    <Password id="newPassword" v-model="newPassword" toggleMask :class="{'p-invalid': resetSubmitted && !newPassword}" />
                    <small v-if="resetSubmitted && !newPassword" class="p-error">Passwort ist erforderlich.</small>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Abbrechen" icon="pi pi-times" class="p-button-text" @click="hideResetPasswordDialog" />
                    <Button label="Zurücksetzen" icon="pi pi-check" @click="confirmResetPassword" :loading="resettingPassword" />
                </div>
            </template>
        </Dialog>

        <!-- Dialog für Benutzer löschen -->
        <Dialog
            v-model:visible="deleteUserDialogVisible"
            header="Benutzer löschen"
            :style="{ width: '450px' }"
            :modal="true"
            :closable="true"
            class="user-dialog"
        >
            <div class="confirmation-content p-4">
                <div class="bg-red-50 dark:bg-red-900/30 p-4 rounded-lg mb-6 flex items-start">
                    <i class="pi pi-exclamation-triangle text-red-500 dark:text-red-400 text-2xl mr-4 mt-1"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-red-800 dark:text-red-300 mb-2">Benutzer löschen</h3>
                        <p class="text-red-700 dark:text-red-400">
                            Sind Sie sicher, dass Sie den Benutzer <strong>{{ selectedUser?.name }}</strong> löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.
                        </p>
                    </div>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Nein" icon="pi pi-times" class="p-button-text" @click="hideDeleteUserDialog" />
                    <Button label="Ja" icon="pi pi-check" class="p-button-danger" @click="deleteUser" :loading="deleting" />
                </div>
            </template>
        </Dialog>

        <Toast />
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import Password from 'primevue/password';
import InputSwitch from 'primevue/inputswitch';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import UserService from '@/Services/UserService';
import InputNumber from 'primevue/inputnumber';
import DatePicker from 'primevue/datepicker';
import dayjs from 'dayjs';

const toast = useToast();

// Zustand
const loading = ref(false);
const users = ref([]);
const departments = ref([]);
const roles = ref([]);

// Filter für DataTable
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    'name': { value: null, matchMode: FilterMatchMode.CONTAINS },
    'email': { value: null, matchMode: FilterMatchMode.CONTAINS },
    'department.name': {
        operator: FilterOperator.AND,
        constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }]
    },
    'role.name': {
        operator: FilterOperator.AND,
        constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }]
    },
    'status': {
        operator: FilterOperator.AND,
        constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }]
    }
});

// Dialog-Zustände
const userDialogVisible = ref(false);
const resetPasswordDialogVisible = ref(false);
const deleteUserDialogVisible = ref(false);
const editMode = ref(false);
const submitted = ref(false);
const resetSubmitted = ref(false);
const saving = ref(false);
const resettingPassword = ref(false);
const deleting = ref(false);

// Benutzer-Objekt
const user = ref({
    id: null,
    first_name: '',
    last_name: '',
    email: '',
    department: null,
    role: null,
    status: true,
    password: '',
    initials: '',
    employee_number: '',
    vacation_days_per_year: 30,
    entry_date: null,
    birth_date: null
});

const selectedUser = ref(null);
const newPassword = ref('');

// Daten laden
const fetchUsers = async () => {
    loading.value = true;
    try {
        const response = await UserService.getUsers();
        users.value = response.data;
    } catch (error) {
        console.error('Fehler beim Laden der Benutzer:', error);
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Die Benutzer konnten nicht geladen werden.',
            life: 3000
        });

        // Keine Fallback-Daten mehr, stattdessen leeres Array
        users.value = [];
    } finally {
        loading.value = false;
    }
};

const fetchDepartments = async () => {
    try {
        const response = await UserService.getDepartments();
        departments.value = response.data;
    } catch (error) {
        console.error('Fehler beim Laden der Abteilungen:', error);
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Die Abteilungen konnten nicht geladen werden.',
            life: 3000
        });

        // Keine Fallback-Daten mehr, stattdessen leeres Array
        departments.value = [];
    }
};

const fetchRoles = async () => {
    try {
        const response = await UserService.getRoles();
        roles.value = response.data;
    } catch (error) {
        console.error('Fehler beim Laden der Rollen:', error);
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Die Rollen konnten nicht geladen werden.',
            life: 3000
        });

        // Keine Fallback-Daten mehr, stattdessen leeres Array
        roles.value = [];
    }
};

// Hilfsfunktionen
const getInitials = (name) => {
    if (!name) return '';
    return name
        .split(' ')
        .map(part => part.charAt(0))
        .join('')
        .toUpperCase();
};

const getInitialsColor = (name) => {
    if (!name) return 'hsl(0, 0%, 75%)';
    // Generate a deterministic color based on the name
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }

    const hue = hash % 360;
    return `hsl(${hue}, 70%, 60%)`;
};

const getDepartmentSeverity = (department) => {
    switch (department) {
        case 'Entwicklung': return 'info';
        case 'Marketing': return 'success';
        case 'Vertrieb': return 'warning';
        case 'Personal': return 'danger';
        case 'Finanzen': return 'primary';
        default: return 'secondary';
    }
};

const getRoleSeverity = (role) => {
    switch (role) {
        case 'Admin': return 'danger';
        case 'Manager': return 'warning';
        case 'HR': return 'info';
        case 'Mitarbeiter': return 'success';
        default: return 'secondary';
    }
};

// Dialog-Funktionen
const openNewUserDialog = () => {
    user.value = {
        id: null,
        first_name: '',
        last_name: '',
        email: '',
        department: null,
        role: null,
        status: true,
        password: '',
        initials: '',
        employee_number: '',
        vacation_days_per_year: 30,
        entry_date: null,
        birth_date: null
    };
    submitted.value = false;
    editMode.value = false;
    userDialogVisible.value = true;
};

const editUser = (data) => {
    // Konvertiere Datumsangaben in Date-Objekte für den Kalender
    const entryDate = data.entry_date ? new Date(data.entry_date) : null;
    const birthDate = data.birth_date ? new Date(data.birth_date) : null;

    // Finde die entsprechende Abteilung und Rolle anhand der IDs
    const department = data.department ? departments.value.find(d => d.id === data.department.id) : null;
    const role = data.role ? roles.value.find(r => r.id === data.role.id) : null;

    user.value = {
        ...data,
        entry_date: entryDate,
        birth_date: birthDate,
        first_name: data.name.split(' ')[0] || '',
        last_name: data.name.split(' ').slice(1).join(' ') || '',
        department: department || null,
        role: role || null,
        vacation_days_per_year: data.vacation_days || 30,
        initials: data.initials || ''
    };

    editMode.value = true;
    userDialogVisible.value = true;
};

// Überwache Änderungen an den Rollen und Abteilungen, um den Benutzer zu aktualisieren, wenn nötig
watch([roles, departments], ([newRoles, newDepartments], [oldRoles, oldDepartments]) => {
    if (editMode.value && user.value.id) {
        // Wenn wir im Bearbeitungsmodus sind und die Rollen oder Abteilungen geladen wurden
        if ((newRoles.length > 0 && oldRoles.length === 0) || (newDepartments.length > 0 && oldDepartments.length === 0)) {
            // Finde den Benutzer in der Benutzerliste
            const userData = users.value.find(u => u.id === user.value.id);
            if (userData) {
                // Aktualisiere die Rolle und Abteilung
                const department = userData.department ? newDepartments.find(d => d.id === userData.department.id) : null;
                const role = userData.role ? newRoles.find(r => r.id === userData.role.id) : null;

                if (department) {
                    user.value.department = department;
                }

                if (role) {
                    user.value.role = role;
                }
            }
        }
    }
}, { immediate: false });

const hideDialog = () => {
    userDialogVisible.value = false;
    submitted.value = false;
};

const saveUser = async () => {
    submitted.value = true;

    if (!user.value.first_name || !user.value.last_name || !user.value.email || !user.value.department || !user.value.role || (!editMode.value && !user.value.password)) {
        return;
    }

    saving.value = true;

    try {
        // Bereite die Daten für die API vor
        const userData = {
            first_name: user.value.first_name,
            last_name: user.value.last_name,
            name: `${user.value.first_name} ${user.value.last_name}`, // Kombiniere für das name-Feld
            email: user.value.email,
            current_team_id: typeof user.value.department === 'object' ? user.value.department.id : user.value.department,
            role_id: typeof user.value.role === 'object' ? user.value.role.id : user.value.role,
            status: user.value.status,
            initials: user.value.initials || `${user.value.first_name.charAt(0)}${user.value.last_name.charAt(0)}`.toUpperCase(),
            employee_number: user.value.employee_number || null,
            vacation_days_per_year: user.value.vacation_days_per_year || 30,
            entry_date: user.value.entry_date ? dayjs(user.value.entry_date).format('YYYY-MM-DD') : null,
            birth_date: user.value.birth_date ? dayjs(user.value.birth_date).format('YYYY-MM-DD') : null
        };

        // Füge das Passwort nur hinzu, wenn es sich um einen neuen Benutzer handelt oder wenn es geändert wurde
        if (!editMode.value && user.value.password) {
            userData.password = user.value.password;
        }

        if (editMode.value) {
            const response = await UserService.updateUser(user.value.id, userData);
            toast.add({
                severity: 'success',
                summary: 'Erfolg',
                detail: 'Benutzer wurde aktualisiert.',
                life: 3000
            });
        } else {
            const response = await UserService.createUser(userData);
            toast.add({
                severity: 'success',
                summary: 'Erfolg',
                detail: 'Benutzer wurde erstellt.',
                life: 3000
            });
        }

        userDialogVisible.value = false;
        fetchUsers();
    } catch (error) {
        console.error('Fehler beim Speichern des Benutzers:', error);

        // Verbesserte Fehlerbehandlung
        let errorMessage = 'Der Benutzer konnte nicht gespeichert werden.';

        if (error.response) {

            if (error.response.data && error.response.data.message) {
                errorMessage = error.response.data.message;
            } else if (error.response.data && error.response.data.error) {
                errorMessage = error.response.data.error;
            } else if (error.response.data && error.response.data.errors) {
                // Laravel-Validierungsfehler
                const errors = error.response.data.errors;
                const errorMessages = [];
                for (const field in errors) {
                    errorMessages.push(`${field}: ${errors[field].join(', ')}`);
                }
                errorMessage = errorMessages.join('\n');
            }
        }

        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: errorMessage,
            life: 5000
        });
    } finally {
        saving.value = false;
    }
};

const resetPassword = (data) => {
    selectedUser.value = data;
    newPassword.value = '';
    resetSubmitted.value = false;
    resetPasswordDialogVisible.value = true;
};

const hideResetPasswordDialog = () => {
    resetPasswordDialogVisible.value = false;
    resetSubmitted.value = false;
};

const confirmResetPassword = async () => {
    resetSubmitted.value = true;

    if (!newPassword.value) {
        return;
    }

    resettingPassword.value = true;

    try {
        await UserService.updateUser(selectedUser.value.id, { password: newPassword.value });
        toast.add({
            severity: 'success',
            summary: 'Erfolg',
            detail: 'Passwort wurde zurückgesetzt.',
            life: 3000
        });
        resetPasswordDialogVisible.value = false;
    } catch (error) {
        console.error('Fehler beim Zurücksetzen des Passworts:', error);
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Das Passwort konnte nicht zurückgesetzt werden.',
            life: 3000
        });
    } finally {
        resettingPassword.value = false;
    }
};

const confirmDeleteUser = (data) => {
    selectedUser.value = data;
    deleteUserDialogVisible.value = true;
};

const hideDeleteUserDialog = () => {
    deleteUserDialogVisible.value = false;
};

const deleteUser = async () => {
    deleting.value = true;

    try {
        await UserService.deleteUser(selectedUser.value.id);
        toast.add({
            severity: 'success',
            summary: 'Erfolg',
            detail: 'Benutzer wurde gelöscht.',
            life: 3000
        });
        deleteUserDialogVisible.value = false;
        fetchUsers();
    } catch (error) {
        console.error('Fehler beim Löschen des Benutzers:', error);
        toast.add({
            severity: 'error',
            summary: 'Fehler',
            detail: 'Der Benutzer konnte nicht gelöscht werden.',
            life: 3000
        });
    } finally {
        deleting.value = false;
    }
};

// Komponente initialisieren
onMounted(() => {
    fetchUsers();
    fetchDepartments();
    fetchRoles();
});
</script>

<style scoped>
.user-dialog :deep(.p-select) {
    width: 100%;
}

.user-dialog :deep(.p-password) {
    width: 100%;
}

.user-dialog :deep(.p-dialog-header) {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 1.5rem;
}

.user-dialog :deep(.p-dialog-content) {
    padding: 1.5rem;
}

.user-dialog :deep(.p-dialog-footer) {
    background-color: #f8f9fa;
    border-top: 1px solid #e9ecef;
    padding: 1rem 1.5rem;
}

.user-dialog :deep(.p-inputtext),
.user-dialog :deep(.p-select),
.user-dialog :deep(.p-datepicker),
.user-dialog :deep(.p-inputnumber) {
    border-radius: 0.375rem;
}

.user-dialog :deep(.p-button) {
    border-radius: 0.375rem;
}

.user-dialog :deep(.p-datepicker .p-button) {
    border-radius: 0;
}

@media screen and (max-width: 960px) {
    :deep(.p-datatable-tbody > tr > td .p-column-title) {
        font-weight: 600;
        margin-right: 0.5rem;
    }
}
</style>
