<template>
    <AppLayout title="Neuer Bericht">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Neuen Bericht erstellen
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <ReportCreateForm @saved="onReportSaved" @cancelled="goBack" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ReportCreateForm from '@/Components/Reports/ReportCreateForm.vue'
import { useToast } from 'primevue/usetoast'

const toast = useToast()

const onReportSaved = () => {
    toast.add({
        severity: 'success',
        summary: 'Erfolg',
        detail: 'Bericht wurde erfolgreich erstellt',
        life: 3000
    })
    router.visit(route('reports.index'))
}

const goBack = () => {
    router.visit(route('reports.index'))
}
</script>
