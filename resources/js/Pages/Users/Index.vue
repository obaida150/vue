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
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6">
            <DataTable
                :value="users"
                :paginator="users.length > 10"
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
                <template #filter="{ filterModel, filterCallback }">
                  <Dropdown
                      v-model="filterModel.value"
                      @change="filterCallback()"
                      :options="departments"
                      optionLabel="name"
                      optionValue="name"
                      placeholder="Alle Abteilungen"
                      class="p-column-filter"
                      :showClear="true"
                  />
                </template>
              </Column>

              <Column field="role.name" header="Rolle" :sortable="true">
                <template #body="{ data }">
                  <Tag :value="data.role.name" :severity="getRoleSeverity(data.role.name)" />
                </template>
                <template #filter="{ filterModel, filterCallback }">
                  <Dropdown
                      v-model="filterModel.value"
                      @change="filterCallback()"
                      :options="roles"
                      optionLabel="name"
                      optionValue="name"
                      placeholder="Alle Rollen"
                      class="p-column-filter"
                      :showClear="true"
                  />
                </template>
              </Column>

              <Column field="status" header="Status" :sortable="true">
                <template #body="{ data }">
                  <Tag :value="data.status ? 'Aktiv' : 'Inaktiv'" :severity="data.status ? 'success' : 'danger'" />
                </template>
                <template #filter="{ filterModel, filterCallback }">
                  <Dropdown
                      v-model="filterModel.value"
                      @change="filterCallback()"
                      :options="[{name: 'Aktiv', value: true}, {name: 'Inaktiv', value: false}]"
                      optionLabel="name"
                      optionValue="value"
                      placeholder="Alle Status"
                      class="p-column-filter"
                      :showClear="true"
                  />
                </template>
              </Column>

              <Column header="Aktionen" :exportable="false" style="min-width: 12rem">
                <template #body="{ data }">
                  <div class="flex gap-2 flex-wrap">
                    <Button
                        icon="pi pi-pencil"
                        class="p-button-rounded p-button-success"
                        @click="editUser(data)"
                        tooltip="Bearbeiten"
                    />
                    <Button
                        icon="pi pi-key"
                        class="p-button-rounded p-button-warning"
                        @click="resetPassword(data)"
                        tooltip="Passwort zurücksetzen"
                    />
                    <Button
                        icon="pi pi-trash"
                        class="p-button-rounded p-button-danger"
                        @click="confirmDeleteUser(data)"
                        tooltip="Löschen"
                    />
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
        :style="{ width: '500px' }"
        :modal="true"
        :closable="true"
        class="user-dialog"
    >
      <div class="p-fluid">
        <div class="field mb-4">
          <label for="name" class="block text-sm font-medium mb-1">Name <span class="text-red-500">*</span></label>
          <InputText id="name" v-model="user.name" :class="{'p-invalid': submitted && !user.name}" />
          <small v-if="submitted && !user.name" class="p-error">Name ist erforderlich.</small>
        </div>

        <div class="field mb-4">
          <label for="email" class="block text-sm font-medium mb-1">E-Mail <span class="text-red-500">*</span></label>
          <InputText id="email" v-model="user.email" :class="{'p-invalid': submitted && !user.email}" />
          <small v-if="submitted && !user.email" class="p-error">E-Mail ist erforderlich.</small>
        </div>

        <div class="field mb-4">
          <label for="department" class="block text-sm font-medium mb-1">Abteilung <span class="text-red-500">*</span></label>
          <Dropdown
              id="department"
              v-model="user.department"
              :options="departments"
              optionLabel="name"
              placeholder="Abteilung auswählen"
              :class="{'p-invalid': submitted && !user.department}"
          />
          <small v-if="submitted && !user.department" class="p-error">Abteilung ist erforderlich.</small>
        </div>

        <div class="field mb-4">
          <label for="role" class="block text-sm font-medium mb-1">Rolle <span class="text-red-500">*</span></label>
          <Dropdown
              id="role"
              v-model="user.role"
              :options="roles"
              optionLabel="name"
              placeholder="Rolle auswählen"
              :class="{'p-invalid': submitted && !user.role}"
          />
          <small v-if="submitted && !user.role" class="p-error">Rolle ist erforderlich.</small>
        </div>

        <div class="field mb-4">
          <label for="status" class="block text-sm font-medium mb-1">Status</label>
          <div class="flex align-items-center gap-2">
            <InputSwitch v-model="user.status" />
            <span>{{ user.status ? 'Aktiv' : 'Inaktiv' }}</span>
          </div>
        </div>

        <div v-if="!editMode" class="field mb-4">
          <label for="password" class="block text-sm font-medium mb-1">Passwort <span class="text-red-500">*</span></label>
          <Password id="password" v-model="user.password" toggleMask :class="{'p-invalid': submitted && !user.password}" />
          <small v-if="submitted && !user.password" class="p-error">Passwort ist erforderlich.</small>
        </div>
      </div>
      <template #footer>
        <Button label="Abbrechen" icon="pi pi-times" class="p-button-text" @click="hideDialog" />
        <Button label="Speichern" icon="pi pi-check" @click="saveUser" :loading="saving" />
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
        <Button label="Abbrechen" icon="pi pi-times" class="p-button-text" @click="hideResetPasswordDialog" />
        <Button label="Zurücksetzen" icon="pi pi-check" @click="confirmResetPassword" :loading="resettingPassword" />
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
        <Button label="Nein" icon="pi pi-times" class="p-button-text" @click="hideDeleteUserDialog" />
        <Button label="Ja" icon="pi pi-check" class="p-button-danger" @click="deleteUser" :loading="deleting" />
      </template>
    </Dialog>

    <Toast />
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
import Dropdown from 'primevue/dropdown';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import Password from 'primevue/password';
import InputSwitch from 'primevue/inputswitch';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import UserService from '@/Services/UserService';

const toast = useToast();

// Zustand
const loading = ref(false);
const users = ref([]);
const departments = ref([]);
const roles = ref([]);

// Filter für DataTable
const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  'department.name': { value: null, matchMode: FilterMatchMode.EQUALS },
  'role.name': { value: null, matchMode: FilterMatchMode.EQUALS },
  status: { value: null, matchMode: FilterMatchMode.EQUALS }
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
  name: '',
  email: '',
  department: null,
  role: null,
  status: true,
  password: ''
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

    // Fallback-Daten
    users.value = [
      {
        id: 1,
        name: 'Max Mustermann',
        email: 'max.mustermann@example.com',
        department: { id: 1, name: 'Entwicklung' },
        role: { id: 1, name: 'Admin' },
        status: true
      },
      {
        id: 2,
        name: 'Anna Schmidt',
        email: 'anna.schmidt@example.com',
        department: { id: 2, name: 'Marketing' },
        role: { id: 2, name: 'Manager' },
        status: true
      },
      {
        id: 3,
        name: 'Thomas Müller',
        email: 'thomas.mueller@example.com',
        department: { id: 3, name: 'Vertrieb' },
        role: { id: 3, name: 'Mitarbeiter' },
        status: false
      }
    ];
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

    // Fallback-Daten
    departments.value = [
      { id: 1, name: 'Entwicklung' },
      { id: 2, name: 'Marketing' },
      { id: 3, name: 'Vertrieb' },
      { id: 4, name: 'Personal' },
      { id: 5, name: 'Finanzen' }
    ];
  }
};

const fetchRoles = async () => {
  try {
    const response = await UserService.getRoles();
    roles.value = response.data;
  } catch (error) {
    console.error('Fehler beim Laden der Rollen:', error);

    // Fallback-Daten
    roles.value = [
      { id: 1, name: 'Admin' },
      { id: 2, name: 'Manager' },
      { id: 3, name: 'HR' },
      { id: 4, name: 'Mitarbeiter' }
    ];
  }
};

// Hilfsfunktionen
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
    name: '',
    email: '',
    department: null,
    role: null,
    status: true,
    password: ''
  };
  submitted.value = false;
  editMode.value = false;
  userDialogVisible.value = true;
};

const editUser = (data) => {
  user.value = { ...data };
  editMode.value = true;
  userDialogVisible.value = true;
};

const hideDialog = () => {
  userDialogVisible.value = false;
  submitted.value = false;
};

const saveUser = async () => {
  submitted.value = true;

  if (!user.value.name || !user.value.email || !user.value.department || !user.value.role || (!editMode.value && !user.value.password)) {
    return;
  }

  saving.value = true;

  try {
    if (editMode.value) {
      await UserService.updateUser(user.value.id, user.value);
      toast.add({
        severity: 'success',
        summary: 'Erfolg',
        detail: 'Benutzer wurde aktualisiert.',
        life: 3000
      });
    } else {
      await UserService.createUser(user.value);
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
    toast.add({
      severity: 'error',
      summary: 'Fehler',
      detail: 'Der Benutzer konnte nicht gespeichert werden.',
      life: 3000
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
.user-dialog :deep(.p-dropdown) {
  width: 100%;
}

.user-dialog :deep(.p-password) {
  width: 100%;
}

@media screen and (max-width: 960px) {
  :deep(.p-datatable-tbody > tr > td .p-column-title) {
    font-weight: 600;
    margin-right: 0.5rem;
  }
}
</style>

