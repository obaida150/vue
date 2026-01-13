<template>
    <AppLayout title="Kalender">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Mitarbeiter Kalender
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-[100rem] mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <StaffCalendar />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
// Korrigiere den Import-Pfad
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StaffCalendar from '@/Components/Calendar/StaffCalendar.vue';
</script>

