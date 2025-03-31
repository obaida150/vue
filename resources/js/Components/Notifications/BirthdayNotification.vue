<template>
    <Dialog
        v-model:visible="visible"
        :modal="true"
        :closable="true"
        :header="'Geburtstage heute'"
        :style="{ width: '450px' }"
        :closeOnEscape="true"
    >
        <div v-if="birthdayUsers.length > 0" class="birthday-notification">
            <div v-for="user in birthdayUsers" :key="user.id" class="birthday-user">
                <div class="birthday-icon">🎂</div>
                <div class="birthday-details">
                    <h3 class="birthday-name">{{ user.name }}</h3>
                    <p class="birthday-age">wird heute {{ user.age }} Jahre alt</p>
                </div>
            </div>

            <div class="birthday-message">
                <p>Vergiss nicht, deinen Kollegen zu gratulieren!</p>
            </div>
        </div>
        <div v-else class="no-birthdays">
            <p>Heute hat niemand in deiner Abteilung Geburtstag.</p>
        </div>

        <template #footer>
            <div class="flex justify-content-end">
                <Button
                    label="Verstanden"
                    icon="pi pi-check"
                    @click="closeDialog"
                    autofocus
                />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import axios from 'axios';
import NotificationService from '@/Services/NotificationService';

const visible = ref(false);
const birthdayUsers = ref([]);

// Prüfen, ob das Popup heute bereits angezeigt wurde
const hasShownToday = () => {
    const lastShown = localStorage.getItem('birthdayNotificationLastShown');
    const today = new Date().toISOString().split('T')[0];
    return lastShown === today;
};

// Markieren, dass das Popup heute angezeigt wurde
const markAsShownToday = () => {
    const today = new Date().toISOString().split('T')[0];
    localStorage.setItem('birthdayNotificationLastShown', today);
};

const closeDialog = () => {
    visible.value = false;
    markAsShownToday();
};

const checkBirthdayNotifications = async () => {
    try {
        // console.log('Prüfe Geburtstags-Benachrichtigungen...');

        // Für Testzwecke: Entferne den gespeicherten Wert, um das Popup immer anzuzeigen
        // localStorage.removeItem('birthdayNotificationLastShown');

        // Nur prüfen, wenn das Popup heute noch nicht angezeigt wurde
        if (!hasShownToday()) {
            // console.log('Popup wurde heute noch nicht angezeigt, lade Daten...');

            const response = await NotificationService.getBirthdayNotifications();
            // console.log('Geburtstags-Benachrichtigungen geladen:', response.data);

            birthdayUsers.value = response.data.notifications || [];

            // Popup nur anzeigen, wenn es Geburtstage gibt
            if (birthdayUsers.value.length > 0) {
                // console.log(`${birthdayUsers.value.length} Geburtstage gefunden, zeige Popup...`);
                visible.value = true;
            } else {
                // console.log('Keine Geburtstage heute.');
            }
        } else {
            // console.log('Popup wurde heute bereits angezeigt.');
        }
    } catch (error) {
        console.error('Fehler beim Laden der Geburtstags-Benachrichtigungen:', error);
    }
};

onMounted(() => {
    // Kurze Verzögerung, damit die Seite zuerst geladen wird
    setTimeout(() => {
        checkBirthdayNotifications();
    }, 1000);
});
</script>

<style scoped>
.birthday-notification {
    padding: 1rem;
}

.birthday-user {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
    padding: 1rem;
    background-color: #FFF0F0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.birthday-icon {
    font-size: 2rem;
    margin-right: 1rem;
    animation: bounce 2s infinite;
}

.birthday-details {
    flex: 1;
}

.birthday-name {
    font-size: 1.2rem;
    font-weight: bold;
    margin: 0 0 0.5rem 0;
    color: #333;
}

.birthday-age {
    margin: 0;
    color: #666;
}

.birthday-message {
    margin-top: 1rem;
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 8px;
    text-align: center;
    font-style: italic;
}

.no-birthdays {
    padding: 1rem;
    text-align: center;
    color: #666;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
    40% {transform: translateY(-10px);}
    60% {transform: translateY(-5px);}
}

/* Dark mode support */
:deep(.dark) .birthday-user {
    background-color: #3a2a2a;
}

:deep(.dark) .birthday-name {
    color: #f8f9fa;
}

:deep(.dark) .birthday-age {
    color: #e2e8f0;
}

:deep(.dark) .birthday-message {
    background-color: #2d3748;
    color: #e2e8f0;
}

:deep(.dark) .no-birthdays {
    color: #e2e8f0;
}
</style>

