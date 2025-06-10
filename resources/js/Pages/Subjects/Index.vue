<template>
  <AppLayout title="Fächerverwaltung">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Fächerverwaltung
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Meine Fächer
              </h3>
              <Button
                label="Neues Fach"
                icon="pi pi-plus"
                @click="showCreateDialog = true"
              />
            </div>

            <!-- Fächer nach Lehrjahr gruppiert -->
            <div class="space-y-6">
              <div v-for="year in [1, 2, 3]" :key="year">
                <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3">
                  {{ year }}. Lehrjahr
                </h4>

                <div v-if="getSubjectsByYear(year).length === 0" class="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
                  <i class="pi pi-book text-3xl text-gray-400 mb-2"></i>
                  <p class="text-gray-500 dark:text-gray-400">Keine Fächer für das {{ year }}. Lehrjahr</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <Card v-for="subject in getSubjectsByYear(year)" :key="subject.id" class="subject-card">
                    <template #content>
                      <div class="flex justify-between items-start">
                        <div>
                          <h5 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                            {{ subject.name }}
                          </h5>
                          <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ subject.year }}. Lehrjahr
                          </p>
                        </div>
                        <div class="flex gap-1">
                          <Button
                            icon="pi pi-pencil"
                            class="p-button-rounded p-button-text p-button-sm"
                            v-tooltip.top="'Bearbeiten'"
                            @click="editSubject(subject)"
                          />
                          <Button
                            icon="pi pi-trash"
                            class="p-button-rounded p-button-text p-button-danger p-button-sm"
                            v-tooltip.top="'Löschen'"
                            @click="confirmDelete(subject)"
                          />
                        </div>
                      </div>
                    </template>
                  </Card>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog
      v-model:visible="showCreateDialog"
      :header="editingSubject ? 'Fach bearbeiten' : 'Neues Fach'"
      :modal="true"
      class="p-fluid"
      :style="{ width: '450px' }"
    >
      <form @submit.prevent="saveSubject" class="space-y-4">
        <div class="field">
          <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Fachname *
          </label>
          <InputText
            id="name"
            v-model="subjectForm.name"
            placeholder="z.B. Mathematik"
            class="w-full"
            :class="{ 'p-invalid': errors.name }"
            autofocus
          />
          <small v-if="errors.name" class="p-error">{{ errors.name }}</small>
        </div>

        <div class="field">
          <label for="year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Lehrjahr *
          </label>
          <Select
            id="year"
            v-model="subjectForm.year"
            :options="yearOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Lehrjahr wählen"
            class="w-full"
            :class="{ 'p-invalid': errors.year }"
          />
          <small v-if="errors.year" class="p-error">{{ errors.year }}</small>
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <Button
            label="Abbrechen"
            icon="pi pi-times"
            class="p-button-secondary"
            @click="cancelEdit"
          />
          <Button
            label="Speichern"
            icon="pi pi-check"
            type="submit"
            :loading="saving"
          />
        </div>
      </form>
    </Dialog>

    <!-- Delete Confirmation -->
    <ConfirmDialog />
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Card from 'primevue/card'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import axios from 'axios' // Import axios

const confirm = useConfirm()
const toast = useToast()

// Data
const subjects = ref([])
const loading = ref(false)
const showCreateDialog = ref(false)
const editingSubject = ref(null)
const saving = ref(false)
const errors = ref({})

const subjectForm = ref({
  name: '',
  year: null
})

const yearOptions = [
  { label: '1. Lehrjahr', value: 1 },
  { label: '2. Lehrjahr', value: 2 },
  { label: '3. Lehrjahr', value: 3 }
]

// Computed
const getSubjectsByYear = (year) => {
  return subjects.value.filter(subject => subject.year === year)
}

// Methods
const loadSubjects = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/subjects')
    subjects.value = response.data
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Fehler',
      detail: 'Fächer konnten nicht geladen werden',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const saveSubject = async () => {
  saving.value = true
  errors.value = {}

  try {
    if (editingSubject.value) {
      await axios.put(`/api/subjects/${editingSubject.value.id}`, subjectForm.value)
      toast.add({
        severity: 'success',
        summary: 'Erfolg',
        detail: 'Fach wurde aktualisiert',
        life: 3000
      })
    } else {
      await axios.post('/api/subjects', subjectForm.value)
      toast.add({
        severity: 'success',
        summary: 'Erfolg',
        detail: 'Fach wurde erstellt',
        life: 3000
      })
    }

    showCreateDialog.value = false
    resetForm()
    loadSubjects()
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors
    } else {
      toast.add({
        severity: 'error',
        summary: 'Fehler',
        detail: 'Fach konnte nicht gespeichert werden',
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

const editSubject = (subject) => {
  editingSubject.value = subject
  subjectForm.value = {
    name: subject.name,
    year: subject.year
  }
  showCreateDialog.value = true
}

const confirmDelete = (subject) => {
  confirm.require({
    message: `Möchten Sie das Fach "${subject.name}" wirklich löschen?`,
    header: 'Löschen bestätigen',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Löschen',
    rejectLabel: 'Abbrechen',
    accept: () => deleteSubject(subject.id)
  })
}

const deleteSubject = async (subjectId) => {
  try {
    await axios.delete(`/api/subjects/${subjectId}`)
    subjects.value = subjects.value.filter(subject => subject.id !== subjectId)
    toast.add({
      severity: 'success',
      summary: 'Erfolg',
      detail: 'Fach wurde gelöscht',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Fehler',
      detail: 'Fach konnte nicht gelöscht werden',
      life: 3000
    })
  }
}

const cancelEdit = () => {
  showCreateDialog.value = false
  resetForm()
}

const resetForm = () => {
  editingSubject.value = null
  subjectForm.value = {
    name: '',
    year: null
  }
  errors.value = {}
}

// Lifecycle
onMounted(() => {
  loadSubjects()
})
</script>

<style scoped>
.subject-card {
  transition: transform 0.2s ease-in-out;
}

.subject-card:hover {
  transform: translateY(-2px);
}
</style>
