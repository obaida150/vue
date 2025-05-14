<template>
    <Dialog
        :visible="localVisible"
        @update:visible="updateVisible"
        :style="{ width: '450px' }"
        header="Feiertag"
        :modal="true"
        :closable="true"
        class="p-fluid"
    >
        <div class="mb-4">
            <h3 class="text-xl font-bold text-center">{{ holidayName }}</h3>
            <p class="text-center mt-2">An diesem Tag ist ein gesetzlicher Feiertag.</p>
        </div>

        <template #footer>
            <Button
                label="Schließen"
                icon="pi pi-times"
                class="p-button-text"
                @click="onClose"
            />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

const props = defineProps({
    visible: {
        type: Boolean,
        required: true
    },
    modelValue: {
        type: Boolean,
        default: false
    },
    holidayName: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['update:visible', 'update:modelValue', 'close']);

// Lokale Variable für die Sichtbarkeit
const localVisible = ref(props.visible);

// Aktualisiere die lokale Variable, wenn sich die Prop ändert
watch(() => props.visible, (newValue) => {
    localVisible.value = newValue;
});

// Aktualisiere die Prop, wenn sich die lokale Variable ändert
const updateVisible = (value) => {
    localVisible.value = value;
    emit('update:visible', value);
    if (!value) {
        emit('close');
    }
};

const onClose = () => {
    updateVisible(false);
};
</script>
