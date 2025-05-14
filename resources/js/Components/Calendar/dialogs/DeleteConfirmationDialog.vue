<template>
    <Dialog
        :visible="localVisible"
        @update:visible="updateVisible"
        :style="{ width: '450px' }"
        header="Ereignis löschen"
        :modal="true"
        :closable="true"
        class="p-fluid"
    >
        <div class="mb-4">
            <p>Sind Sie sicher, dass Sie dieses Ereignis löschen möchten?</p>
            <p class="text-red-500 mt-2">Diese Aktion kann nicht rückgängig gemacht werden.</p>
        </div>

        <template #footer>
            <Button
                label="Abbrechen"
                icon="pi pi-times"
                class="p-button-text"
                @click="onCancel"
            />
            <Button
                label="Löschen"
                icon="pi pi-trash"
                class="p-button-danger"
                :loading="deleting"
                @click="onDelete"
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
    deleting: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:visible', 'update:modelValue', 'cancel', 'delete']);

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
        emit('cancel');
    }
};

const onCancel = () => {
    updateVisible(false);
};

const onDelete = () => {
    emit('delete');
};
</script>
