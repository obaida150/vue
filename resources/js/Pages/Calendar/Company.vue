<template>
    <AppLayout title="Firmenkalender">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Firmenkalender
            </h2>
        </template>
        <div class="py-12">
            <div class="max-w-[90rem] mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <CompanyCalendar />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CompanyCalendar from '@/Components/Calendar/CompanyCalendar.vue';
</script>

