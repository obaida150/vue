<template>
    <!-- Bestehender Code... -->

    <!-- Geburtsdatum-Feld hinzufügen -->
    <div class="col-span-6 sm:col-span-4">
        <InputLabel for="birth_date" value="Geburtsdatum" />
        <Calendar
            id="birth_date"
            v-model="form.birth_date"
            dateFormat="dd.mm.yy"
            :showIcon="true"
            :maxDate="maxDate"
            class="w-full"
        />
        <InputError :message="form.errors.birth_date" class="mt-2" />
    </div>

    <!-- Bestehender Code... -->
</template>

<script setup>
// Bestehende Imports...
import Calendar from 'primevue/calendar';
import { computed } from 'vue';

// Bestehender Code...

// Maximales Datum für das Geburtsdatum (18 Jahre in der Vergangenheit)
const maxDate = computed(() => {
    const date = new Date();
    date.setFullYear(date.getFullYear() - 18);
    return date;
});

// Stelle sicher, dass birth_date im Formular enthalten ist
const form = useForm({
    // Bestehende Felder...
    birth_date: props.user?.birth_date || null,
    // Weitere Felder...
});
</script>

