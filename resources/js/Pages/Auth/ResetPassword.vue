<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from 'primevue/button'
import InputGroup from 'primevue/inputgroup'
import InputGroupAddon from 'primevue/inputgroupaddon'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Message from 'primevue/message'

const props = defineProps({
    email: String,
    token: String,
})

const passwordRef = ref(null)

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <Head title="Passwort zurücksetzen" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center px-4 py-12">
        <!-- Decorative elements -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-blue-200 rounded-full opacity-40 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-blue-300 rounded-full opacity-30 blur-3xl"></div>

        <div class="relative w-full max-w-md">
            <!-- Card -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-xl p-8">
                <!-- Logo and Title -->
                <div class="text-center mb-8">
                    <img src="/images/logo.png" alt="Dittmeier Mitarbeiterportal" class="h-24 mx-auto mb-4">
                    <h1 class="text-3xl font-bold text-slate-900 mb-2">Passwort zurücksetzen</h1>
                    <p class="text-slate-600">Geben Sie Ihr neues Passwort ein</p>
                </div>

                <!-- Info Box -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-blue-700">Bitte geben Sie ein starkes Passwort ein, das mindestens 8 Zeichen lang ist und Groß- und Kleinbuchstaben sowie Zahlen enthält.</p>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email-Adresse</label>
                        <InputGroup>
                            <InputGroupAddon class="bg-slate-100 border-slate-300">
                                <svg class="w-5 h-5 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                </svg>
                            </InputGroupAddon>
                            <InputText
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="name@example.com"
                                class="bg-white border-slate-300 text-slate-900 placeholder:text-slate-400"
                                required
                                autofocus
                                autocomplete="username"
                            />
                        </InputGroup>
                        <small v-if="form.errors.email" class="text-red-600 block mt-1">{{ form.errors.email }}</small>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Neues Passwort</label>
                        <InputGroup>
                            <InputGroupAddon class="bg-slate-100 border-slate-300">
                                <svg class="w-5 h-5 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                </svg>
                            </InputGroupAddon>
                            <Password
                                id="password"
                                ref="passwordRef"
                                v-model="form.password"
                                placeholder="Mindestens 8 Zeichen"
                                toggle-mask
                                class="bg-white border-slate-300 text-slate-900"
                                input-class="bg-white border-slate-300 text-slate-900 placeholder:text-slate-400"
                                required
                                autocomplete="new-password"
                            />
                        </InputGroup>
                        <small v-if="form.errors.password" class="text-red-600 block mt-1">{{ form.errors.password }}</small>
                    </div>

                    <!-- Confirm Password Field -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-2">Passwort bestätigen</label>
                        <InputGroup>
                            <InputGroupAddon class="bg-slate-100 border-slate-300">
                                <svg class="w-5 h-5 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                </svg>
                            </InputGroupAddon>
                            <Password
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                placeholder="Passwort wiederholen"
                                toggle-mask
                                class="bg-white border-slate-300 text-slate-900"
                                input-class="bg-white border-slate-300 text-slate-900 placeholder:text-slate-400"
                                required
                                autocomplete="new-password"
                            />
                        </InputGroup>
                        <small v-if="form.errors.password_confirmation" class="text-red-600 block mt-1">{{ form.errors.password_confirmation }}</small>
                    </div>

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        label="Passwort zurücksetzen"
                        class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 border-0 shadow-lg font-semibold py-3"
                        :loading="form.processing"
                    />
                </form>

                <!-- Back Link -->
                <div class="mt-6 pt-6 border-t border-slate-200 text-center">
                    <router-link to="/login" class="text-sm text-blue-600 hover:text-blue-700 transition-colors">
                        Zurück zur Anmeldung
                    </router-link>
                </div>
            </div>

            <!-- Footer Text -->
            <p class="text-center text-slate-600 text-sm mt-6">
                Sichere Passwort-Zurückstellung
            </p>
        </div>
    </div>
</template>

<style scoped>
:deep(.p-inputgroup .p-inputtext) {
    background-color: white;
    border-color: rgb(203 213 225);
    color: rgb(15 23 42);
    padding: 0.75rem;
}

:deep(.p-inputgroup .p-inputtext:focus) {
    border-color: rgb(59 130 246);
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
}

:deep(.p-password .p-inputtext) {
    background-color: white;
    border-color: rgb(203 213 225);
    color: rgb(15 23 42);
}

:deep(.p-password .p-inputtext:focus) {
    border-color: rgb(59 130 246);
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
}

:deep(.p-password .p-icon-password-hide,
.p-password .p-icon-password-show) {
    color: rgb(100 116 139);
}
</style>
