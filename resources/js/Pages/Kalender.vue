<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import KalenderComponent from "@/Components/KalenderComponent.vue";

</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-[90rem] mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <KalenderComponent />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
