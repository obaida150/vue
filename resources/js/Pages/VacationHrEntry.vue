<template>
    <AppLayout title="HR - Urlaub für Mitarbeiter eintragen">
        <div class="py-12">
            <div class="max-w-[100rem] mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                    <!-- Übergebe employees und holidays als Props an die Komponente -->
                    <HrVacationEntry
                        :employees="employees"
                        :holidays="holidays"
                        @submitted="handleVacationCreated"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import HrVacationEntry from '@/Components/HrVacationEntry.vue';

const props = defineProps({
    employees: {
        type: Array,
        required: true
    },
    holidays: {
        type: Array,
        default: () => []
    },
    currentYear: {
        type: Number,
        default: () => new Date().getFullYear()
    }
});

const handleVacationCreated = () => {
    // Nach erfolgreicher Erstellung Seite neu laden, um aktualisierte Daten zu erhalten
    router.reload({ only: ['employees'] });
};
</script>
