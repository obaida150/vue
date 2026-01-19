<template>
    <AppLayout title="Urlaubswünsche">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Urlaubswünsche
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-[100rem] mx-auto sm:px-6 lg:px-8">
                <VacationWishCalendar />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import VacationWishCalendar from '@/Components/Calendar/VacationWishCalendar.vue';
</script>
