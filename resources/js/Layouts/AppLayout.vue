<!--<script setup>-->
<!--import { ref, onMounted, watch } from 'vue';-->
<!--import { Head, Link, router } from '@inertiajs/vue3';-->
<!--import ApplicationMark from '@/Components/ApplicationMark.vue';-->
<!--import Banner from '@/Components/Banner.vue';-->
<!--import Dropdown from '@/Components/Dropdown.vue';-->
<!--import DropdownLink from '@/Components/DropdownLink.vue';-->
<!--import NavLink from '@/Components/NavLink.vue';-->
<!--import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';-->
<!--import Kalender from "@/Pages/Kalender.vue";-->

<!--defineProps({-->
<!--    title: String,-->
<!--});-->

<!--const showingNavigationDropdown = ref(false);-->
<!--const darkMode = ref(false);-->

<!--// Initialize the darkMode ref to match the actual state-->
<!--onMounted(() => {-->
<!--    darkMode.value = document.documentElement.classList.contains('dark');-->
<!--});-->

<!--const toggleDarkMode = () => {-->
<!--    // Toggle the dark mode state-->
<!--    darkMode.value = !darkMode.value;-->

<!--    // Apply the change to the document-->
<!--    if (darkMode.value) {-->
<!--        document.documentElement.classList.add('dark');-->
<!--        localStorage.setItem('theme', 'dark');-->
<!--    } else {-->
<!--        document.documentElement.classList.remove('dark');-->
<!--        localStorage.setItem('theme', 'light');-->
<!--    }-->
<!--};-->

<!--const switchToTeam = (team) => {-->
<!--    router.put(route('current-team.update'), {-->
<!--        team_id: team.id,-->
<!--    }, {-->
<!--        preserveState: false,-->
<!--    });-->
<!--};-->

<!--const logout = () => {-->
<!--    router.post(route('logout'));-->
<!--};-->
<!--</script>-->

<!--<template>-->
<!--    <div>-->
<!--        <Head :title="title" />-->

<!--        <Banner />-->

<!--        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">-->
<!--            <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">-->
<!--                &lt;!&ndash; Primary Navigation Menu &ndash;&gt;-->
<!--                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">-->
<!--                    <div class="flex justify-between h-16">-->
<!--                        <div class="flex">-->
<!--                            &lt;!&ndash; Logo &ndash;&gt;-->
<!--                            <div class="shrink-0 flex items-center">-->
<!--                                <Link :href="route('dashboard')">-->
<!--                                    <ApplicationMark class="block h-9 w-auto" />-->
<!--                                </Link>-->
<!--                            </div>-->

<!--                            &lt;!&ndash; Navigation Links &ndash;&gt;-->
<!--                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">-->
<!--                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">-->
<!--                                    Dashboard-->
<!--                                </NavLink>-->
<!--                                <NavLink :href="route('kalender', { year: new Date().getFullYear() })"-->
<!--                                         :active="route().current('kalender') || route().current().startsWith('kalender')">-->
<!--                                    Kalender-->
<!--                                </NavLink>-->

<!--&lt;!&ndash;                                <NavLink :href="route('kalender')" :active="route().current('kalender')">&ndash;&gt;-->
<!--&lt;!&ndash;                                    Kalender&ndash;&gt;-->
<!--&lt;!&ndash;                                </NavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                <Dropdown align="right" width="60">&ndash;&gt;-->
<!--&lt;!&ndash;                                    <template #trigger>&ndash;&gt;-->
<!--&lt;!&ndash;                                        <span class="inline-flex rounded-md">&ndash;&gt;-->
<!--&lt;!&ndash;                                          <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-bold rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">&ndash;&gt;-->
<!--&lt;!&ndash;                                            Kalender&ndash;&gt;-->
<!--&lt;!&ndash;                                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">&ndash;&gt;-->
<!--&lt;!&ndash;                                              <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />&ndash;&gt;-->
<!--&lt;!&ndash;                                            </svg>&ndash;&gt;-->
<!--&lt;!&ndash;                                          </button>&ndash;&gt;-->
<!--&lt;!&ndash;                                        </span>&ndash;&gt;-->
<!--&lt;!&ndash;                                    </template>&ndash;&gt;-->

<!--&lt;!&ndash;                                    <template #content>&ndash;&gt;-->
<!--&lt;!&ndash;                                        <div class="w-60">&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                                Kalender&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->

<!--&lt;!&ndash;                                            <DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                                Homeoffice&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->

<!--&lt;!&ndash;                                            <DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                                Außendienst&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->

<!--&lt;!&ndash;                                            <DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                                Betrieb-Kalender&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->

<!--&lt;!&ndash;                                            <DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                                Azubikalender&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                        </div>&ndash;&gt;-->
<!--&lt;!&ndash;                                    </template>&ndash;&gt;-->
<!--&lt;!&ndash;                                </Dropdown>&ndash;&gt;-->

<!--&lt;!&ndash;                                <Dropdown align="right" width="60">&ndash;&gt;-->
<!--&lt;!&ndash;                                    <template #trigger>&ndash;&gt;-->
<!--&lt;!&ndash;                                        <span class="inline-flex rounded-md">&ndash;&gt;-->
<!--&lt;!&ndash;                                          <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-bold rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">&ndash;&gt;-->
<!--&lt;!&ndash;                                            Urlaubsverwaltung&ndash;&gt;-->
<!--&lt;!&ndash;                                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">&ndash;&gt;-->
<!--&lt;!&ndash;                                              <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />&ndash;&gt;-->
<!--&lt;!&ndash;                                            </svg>&ndash;&gt;-->
<!--&lt;!&ndash;                                          </button>&ndash;&gt;-->
<!--&lt;!&ndash;                                        </span>&ndash;&gt;-->
<!--&lt;!&ndash;                                    </template>&ndash;&gt;-->

<!--&lt;!&ndash;                                    <template #content>&ndash;&gt;-->
<!--&lt;!&ndash;                                        <div class="w-60">&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                                Anträge bearbeiten&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                                Anträge&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                                Übersichtsliste&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                                Antragshistorie&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                                Urlaubswunsch&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                        </div>&ndash;&gt;-->
<!--&lt;!&ndash;                                    </template>&ndash;&gt;-->
<!--&lt;!&ndash;                                </Dropdown>&ndash;&gt;-->
<!--&lt;!&ndash;                                <Dropdown align="right" width="60">&ndash;&gt;-->
<!--&lt;!&ndash;                                    <template #trigger>&ndash;&gt;-->
<!--&lt;!&ndash;                                        <span class="inline-flex rounded-md">&ndash;&gt;-->
<!--&lt;!&ndash;                                          <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-bold rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">&ndash;&gt;-->
<!--&lt;!&ndash;                                            Sonstiges&ndash;&gt;-->
<!--&lt;!&ndash;                                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">&ndash;&gt;-->
<!--&lt;!&ndash;                                              <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />&ndash;&gt;-->
<!--&lt;!&ndash;                                            </svg>&ndash;&gt;-->
<!--&lt;!&ndash;                                          </button>&ndash;&gt;-->
<!--&lt;!&ndash;                                        </span>&ndash;&gt;-->
<!--&lt;!&ndash;                                    </template>&ndash;&gt;-->

<!--&lt;!&ndash;                                    <template #content>&ndash;&gt;-->
<!--&lt;!&ndash;                                        <div class="w-60">&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                                Sonstige Ereignisse&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                                MA-Zeitung&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                                MA-Parkplatz&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                                Elektronische Personalakte&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                        </div>&ndash;&gt;-->
<!--&lt;!&ndash;                                    </template>&ndash;&gt;-->
<!--&lt;!&ndash;                                </Dropdown>&ndash;&gt;-->

<!--&lt;!&ndash;                                &lt;!&ndash; Ausbildung Dropdown (conditional) &ndash;&gt;&ndash;&gt;-->
<!--&lt;!&ndash;                                <Dropdown >&ndash;&gt;-->
<!--&lt;!&ndash;                                    <template #trigger>&ndash;&gt;-->
<!--&lt;!&ndash;                                        <span class="inline-flex rounded-md">&ndash;&gt;-->
<!--&lt;!&ndash;                                          <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-bold rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">&ndash;&gt;-->
<!--&lt;!&ndash;                                            Ausbildung&ndash;&gt;-->
<!--&lt;!&ndash;                                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">&ndash;&gt;-->
<!--&lt;!&ndash;                                              <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />&ndash;&gt;-->
<!--&lt;!&ndash;                                            </svg>&ndash;&gt;-->
<!--&lt;!&ndash;                                          </button>&ndash;&gt;-->
<!--&lt;!&ndash;                                        </span>&ndash;&gt;-->
<!--&lt;!&ndash;                                    </template>&ndash;&gt;-->

<!--&lt;!&ndash;                                    <template #content>&ndash;&gt;-->
<!--&lt;!&ndash;                                        <div class="w-60">&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                                Fächer verwalten&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                                Berichte verwalten&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                                Tabelle&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                                Azubikalender&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                        </div>&ndash;&gt;-->
<!--&lt;!&ndash;                                    </template>&ndash;&gt;-->
<!--&lt;!&ndash;                                </Dropdown>&ndash;&gt;-->

<!--&lt;!&ndash;                                &lt;!&ndash; Personal Dropdown (conditional) &ndash;&gt;&ndash;&gt;-->
<!--&lt;!&ndash;                                <Dropdown >&ndash;&gt;-->
<!--&lt;!&ndash;                                    <template #trigger>&ndash;&gt;-->
<!--&lt;!&ndash;                                        <span class="inline-flex rounded-md">&ndash;&gt;-->
<!--&lt;!&ndash;                                          <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-bold rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">&ndash;&gt;-->
<!--&lt;!&ndash;                                            Personal&ndash;&gt;-->
<!--&lt;!&ndash;                                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">&ndash;&gt;-->
<!--&lt;!&ndash;                                              <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />&ndash;&gt;-->
<!--&lt;!&ndash;                                            </svg>&ndash;&gt;-->
<!--&lt;!&ndash;                                          </button>&ndash;&gt;-->
<!--&lt;!&ndash;                                        </span>&ndash;&gt;-->
<!--&lt;!&ndash;                                    </template>&ndash;&gt;-->

<!--&lt;!&ndash;                                    <template #content>&ndash;&gt;-->
<!--&lt;!&ndash;                                        <div class="w-60">&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                                Urlaubinfo-Liste&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                                Liste&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                                Veranstaltungsübersicht&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                                User-Liste&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                            <DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                                Abwesenheiten&ndash;&gt;-->
<!--&lt;!&ndash;                                            </DropdownLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                        </div>&ndash;&gt;-->
<!--&lt;!&ndash;                                    </template>&ndash;&gt;-->
<!--&lt;!&ndash;                                </Dropdown>&ndash;&gt;-->
<!--                            </div>-->
<!--                        </div>-->

<!--                        <div class="hidden sm:flex sm:items-center sm:ms-6">-->
<!--                            &lt;!&ndash; Dark Mode Toggle &ndash;&gt;-->
<!--                            <button-->
<!--                                @click="toggleDarkMode"-->
<!--                                class="p-2 rounded-md text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 transition ease-in-out duration-150"-->
<!--                                aria-label="Toggle Dark Mode"-->
<!--                            >-->
<!--                                <svg-->
<!--                                    v-if="darkMode"-->
<!--                                    xmlns="http://www.w3.org/2000/svg"-->
<!--                                    class="h-5 w-5"-->
<!--                                    viewBox="0 0 20 20"-->
<!--                                    fill="currentColor"-->
<!--                                >-->
<!--                                    <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd" />-->
<!--                                </svg>-->
<!--                                <svg-->
<!--                                    v-else-->
<!--                                    xmlns="http://www.w3.org/2000/svg"-->
<!--                                    class="h-5 w-5"-->
<!--                                    viewBox="0 0 20 20"-->
<!--                                    fill="currentColor"-->
<!--                                >-->
<!--                                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />-->
<!--                                </svg>-->
<!--                            </button>-->

<!--                            <div class="ms-3 relative">-->
<!--                                &lt;!&ndash; Teams Dropdown &ndash;&gt;-->
<!--                                <Dropdown v-if="$page.props.jetstream.hasTeamFeatures" align="right" width="60">-->
<!--                                    <template #trigger>-->
<!--                                        <span class="inline-flex rounded-md">-->
<!--                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">-->
<!--                                                {{ $page.props.auth.user.current_team.name }}-->

<!--                                                <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">-->
<!--                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />-->
<!--                                                </svg>-->
<!--                                            </button>-->
<!--                                        </span>-->
<!--                                    </template>-->

<!--                                    <template #content>-->
<!--                                        <div class="w-60">-->
<!--                                            &lt;!&ndash; Team Management &ndash;&gt;-->
<!--                                            <div class="block px-4 py-2 text-xs text-gray-400">-->
<!--                                                Manage Team-->
<!--                                            </div>-->

<!--                                            &lt;!&ndash; Team Settings &ndash;&gt;-->
<!--                                            <DropdownLink :href="route('teams.show', $page.props.auth.user.current_team)">-->
<!--                                                Team Settings-->
<!--                                            </DropdownLink>-->

<!--                                            <DropdownLink v-if="$page.props.jetstream.canCreateTeams" :href="route('teams.create')">-->
<!--                                                Create New Team-->
<!--                                            </DropdownLink>-->

<!--                                            &lt;!&ndash; Team Switcher &ndash;&gt;-->
<!--                                            <template v-if="$page.props.auth.user.all_teams.length > 1">-->
<!--                                                <div class="border-t border-gray-200 dark:border-gray-600" />-->

<!--                                                <div class="block px-4 py-2 text-xs text-gray-400">-->
<!--                                                    Switch Teams-->
<!--                                                </div>-->

<!--                                                <template v-for="team in $page.props.auth.user.all_teams" :key="team.id">-->
<!--                                                    <form @submit.prevent="switchToTeam(team)">-->
<!--                                                        <DropdownLink as="button">-->
<!--                                                            <div class="flex items-center">-->
<!--                                                                <svg v-if="team.id == $page.props.auth.user.current_team_id" class="me-2 size-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">-->
<!--                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />-->
<!--                                                                </svg>-->

<!--                                                                <div>{{ team.name }}</div>-->
<!--                                                            </div>-->
<!--                                                        </DropdownLink>-->
<!--                                                    </form>-->
<!--                                                </template>-->
<!--                                            </template>-->
<!--                                        </div>-->
<!--                                    </template>-->
<!--                                </Dropdown>-->
<!--                            </div>-->

<!--                            &lt;!&ndash; Settings Dropdown &ndash;&gt;-->
<!--                            <div class="ms-3 relative">-->
<!--                                <Dropdown align="right" width="48">-->
<!--                                    <template #trigger>-->
<!--                                        <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">-->
<!--                                            <img class="size-8 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">-->
<!--                                        </button>-->

<!--                                        <span v-else class="inline-flex rounded-md">-->
<!--                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">-->
<!--                                                {{ $page.props.auth.user.name }}-->

<!--                                                <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">-->
<!--                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />-->
<!--                                                </svg>-->
<!--                                            </button>-->
<!--                                        </span>-->
<!--                                    </template>-->

<!--                                    <template #content>-->
<!--                                        &lt;!&ndash; Account Management &ndash;&gt;-->
<!--                                        <div class="block px-4 py-2 text-xs text-gray-400">-->
<!--                                            Manage Account-->
<!--                                        </div>-->

<!--                                        <DropdownLink :href="route('profile.show')">-->
<!--                                            Profile-->
<!--                                        </DropdownLink>-->

<!--                                        <DropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">-->
<!--                                            API Tokens-->
<!--                                        </DropdownLink>-->

<!--                                        <div class="border-t border-gray-200 dark:border-gray-600" />-->

<!--                                        &lt;!&ndash; Authentication &ndash;&gt;-->
<!--                                        <form @submit.prevent="logout">-->
<!--                                            <DropdownLink as="button">-->
<!--                                                Log Out-->
<!--                                            </DropdownLink>-->
<!--                                        </form>-->
<!--                                    </template>-->
<!--                                </Dropdown>-->
<!--                            </div>-->
<!--                        </div>-->

<!--                        &lt;!&ndash; Hamburger &ndash;&gt;-->
<!--                        <div class="-me-2 flex items-center sm:hidden">-->
<!--                            &lt;!&ndash; Mobile Dark Mode Toggle &ndash;&gt;-->
<!--                            <button-->
<!--                                @click="toggleDarkMode"-->
<!--                                class="p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out mr-2"-->
<!--                                aria-label="Toggle Dark Mode"-->
<!--                            >-->
<!--                                &lt;!&ndash; Sun icon for dark mode &ndash;&gt;-->
<!--                                <svg-->
<!--                                    v-if="darkMode"-->
<!--                                    xmlns="http://www.w3.org/2000/svg"-->
<!--                                    class="h-5 w-5"-->
<!--                                    viewBox="0 0 20 20"-->
<!--                                    fill="currentColor"-->
<!--                                >-->
<!--                                    <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd" />-->
<!--                                </svg>-->
<!--                                &lt;!&ndash; Moon icon for light mode &ndash;&gt;-->
<!--                                <svg-->
<!--                                    v-else-->
<!--                                    xmlns="http://www.w3.org/2000/svg"-->
<!--                                    class="h-5 w-5"-->
<!--                                    viewBox="0 0 20 20"-->
<!--                                    fill="currentColor"-->
<!--                                >-->
<!--                                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />-->
<!--                                </svg>-->
<!--                            </button>-->

<!--                            <button class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out" @click="showingNavigationDropdown = ! showingNavigationDropdown">-->
<!--                                <svg-->
<!--                                    class="size-6"-->
<!--                                    stroke="currentColor"-->
<!--                                    fill="none"-->
<!--                                    viewBox="0 0 24 24"-->
<!--                                >-->
<!--                                    <path-->
<!--                                        :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }"-->
<!--                                        stroke-linecap="round"-->
<!--                                        stroke-linejoin="round"-->
<!--                                        stroke-width="2"-->
<!--                                        d="M4 6h16M4 12h16M4 18h16"-->
<!--                                    />-->
<!--                                    <path-->
<!--                                        :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"-->
<!--                                        stroke-linecap="round"-->
<!--                                        stroke-linejoin="round"-->
<!--                                        stroke-width="2"-->
<!--                                        d="M6 18L18 6M6 6l12 12"-->
<!--                                    />-->
<!--                                </svg>-->
<!--                            </button>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->

<!--                &lt;!&ndash; Responsive Navigation Menu &ndash;&gt;-->
<!--                <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">-->
<!--                    <div class="pt-2 pb-3 space-y-1">-->
<!--                        <ResponsiveNavLink >-->
<!--                            Dashboard-->
<!--                        </ResponsiveNavLink>-->

<!--&lt;!&ndash;                        <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">&ndash;&gt;-->
<!--&lt;!&ndash;                            &lt;!&ndash; ... existing responsive navigation ... &ndash;&gt;&ndash;&gt;-->

<!--&lt;!&ndash;                            &lt;!&ndash; Kalender Links &ndash;&gt;&ndash;&gt;-->
<!--&lt;!&ndash;                            <div class="pt-2 pb-3 space-y-1">&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Kalender&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Homeoffice&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Außendienst&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                            </div>&ndash;&gt;-->

<!--&lt;!&ndash;                            &lt;!&ndash; Urlaubsverwaltung Links &ndash;&gt;&ndash;&gt;-->
<!--&lt;!&ndash;                            <div class="pt-2 pb-3 space-y-1 border-t border-gray-200 dark:border-gray-600">&ndash;&gt;-->
<!--&lt;!&ndash;                                <div class="block px-4 py-2 text-xs text-gray-400">&ndash;&gt;-->
<!--&lt;!&ndash;                                    Urlaubsverwaltung&ndash;&gt;-->
<!--&lt;!&ndash;                                </div>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Anträge bearbeiten&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Anträge&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Übersichtsliste&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Antragshistorie&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Urlaubswunsch&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                            </div>&ndash;&gt;-->

<!--&lt;!&ndash;                            &lt;!&ndash; Sonstiges Links &ndash;&gt;&ndash;&gt;-->
<!--&lt;!&ndash;                            <div class="pt-2 pb-3 space-y-1 border-t border-gray-200 dark:border-gray-600">&ndash;&gt;-->
<!--&lt;!&ndash;                                <div class="block px-4 py-2 text-xs text-gray-400">&ndash;&gt;-->
<!--&lt;!&ndash;                                    Sonstiges&ndash;&gt;-->
<!--&lt;!&ndash;                                </div>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Sonstige Ereignisse&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    MA-Zeitung&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    MA-Parkplatz&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Elektronische Personalakte&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                            </div>&ndash;&gt;-->

<!--&lt;!&ndash;                            &lt;!&ndash; Ausbildung Links (conditional) &ndash;&gt;&ndash;&gt;-->
<!--&lt;!&ndash;                            <div >&ndash;&gt;-->
<!--&lt;!&ndash;                                <div class="block px-4 py-2 text-xs text-gray-400">&ndash;&gt;-->
<!--&lt;!&ndash;                                    Ausbildung&ndash;&gt;-->
<!--&lt;!&ndash;                                </div>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Fächer verwalten&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Berichte verwalten&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Tabelle&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Azubikalender&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                            </div>&ndash;&gt;-->

<!--&lt;!&ndash;                            &lt;!&ndash; Personal Links (conditional) &ndash;&gt;&ndash;&gt;-->
<!--&lt;!&ndash;                            <div >&ndash;&gt;-->
<!--&lt;!&ndash;                                <div class="block px-4 py-2 text-xs text-gray-400">&ndash;&gt;-->
<!--&lt;!&ndash;                                    Personal&ndash;&gt;-->
<!--&lt;!&ndash;                                </div>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Urlaubinfo-Liste&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Liste&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Veranstaltungsübersicht&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    User-Liste&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                                <ResponsiveNavLink >&ndash;&gt;-->
<!--&lt;!&ndash;                                    Abwesenheiten&ndash;&gt;-->
<!--&lt;!&ndash;                                </ResponsiveNavLink>&ndash;&gt;-->
<!--&lt;!&ndash;                            </div>&ndash;&gt;-->
<!--&lt;!&ndash;                        </div>&ndash;&gt;-->


<!--                    </div>-->

<!--                    &lt;!&ndash; Responsive Settings Options &ndash;&gt;-->
<!--                    <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">-->
<!--                        <div class="flex items-center px-4">-->
<!--                            <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 me-3">-->
<!--                                <img class="size-10 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">-->
<!--                            </div>-->

<!--                            <div>-->
<!--                                <div class="font-medium text-base text-gray-800 dark:text-gray-200">-->
<!--                                    {{ $page.props.auth.user.name }}-->
<!--                                </div>-->
<!--                                <div class="font-medium text-sm text-gray-500">-->
<!--                                    {{ $page.props.auth.user.email }}-->
<!--                                </div>-->
<!--                            </div>-->
<!--                        </div>-->

<!--                        <div class="mt-3 space-y-1">-->
<!--                            <ResponsiveNavLink :href="route('profile.show')" :active="route().current('profile.show')">-->
<!--                                Profile-->
<!--                            </ResponsiveNavLink>-->

<!--                            <ResponsiveNavLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')" :active="route().current('api-tokens.index')">-->
<!--                                API Tokens-->
<!--                            </ResponsiveNavLink>-->

<!--                            &lt;!&ndash; Authentication &ndash;&gt;-->
<!--                            <form method="POST" @submit.prevent="logout">-->
<!--                                <ResponsiveNavLink as="button">-->
<!--                                    Log Out-->
<!--                                </ResponsiveNavLink>-->
<!--                            </form>-->

<!--                            &lt;!&ndash; Team Management &ndash;&gt;-->
<!--                            <template v-if="$page.props.jetstream.hasTeamFeatures">-->
<!--                                <div class="border-t border-gray-200 dark:border-gray-600" />-->

<!--                                <div class="block px-4 py-2 text-xs text-gray-400">-->
<!--                                    Manage Team-->
<!--                                </div>-->

<!--                                &lt;!&ndash; Team Settings &ndash;&gt;-->
<!--                                <ResponsiveNavLink :href="route('teams.show', $page.props.auth.user.current_team)" :active="route().current('teams.show')">-->
<!--                                    Team Settings-->
<!--                                </ResponsiveNavLink>-->

<!--                                <ResponsiveNavLink v-if="$page.props.jetstream.canCreateTeams" :href="route('teams.create')" :active="route().current('teams.create')">-->
<!--                                    Create New Team-->
<!--                                </ResponsiveNavLink>-->

<!--                                &lt;!&ndash; Team Switcher &ndash;&gt;-->
<!--                                <template v-if="$page.props.auth.user.all_teams.length > 1">-->
<!--                                    <div class="border-t border-gray-200 dark:border-gray-600" />-->

<!--                                    <div class="block px-4 py-2 text-xs text-gray-400">-->
<!--                                        Switch Teams-->
<!--                                    </div>-->

<!--                                    <template v-for="team in $page.props.auth.user.all_teams" :key="team.id">-->
<!--                                        <form @submit.prevent="switchToTeam(team)">-->
<!--                                            <ResponsiveNavLink as="button">-->
<!--                                                <div class="flex items-center">-->
<!--                                                    <svg v-if="team.id == $page.props.auth.user.current_team_id" class="me-2 size-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">-->
<!--                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />-->
<!--                                                    </svg>-->
<!--                                                    <div>{{ team.name }}</div>-->
<!--                                                </div>-->
<!--                                            </ResponsiveNavLink>-->
<!--                                        </form>-->
<!--                                    </template>-->
<!--                                </template>-->
<!--                            </template>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </nav>-->

<!--            &lt;!&ndash; Page Heading &ndash;&gt;-->
<!--            <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow">-->
<!--                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">-->
<!--                    <slot name="header" />-->
<!--                </div>-->
<!--            </header>-->

<!--            &lt;!&ndash; Page Content &ndash;&gt;-->
<!--            <main>-->
<!--                <slot />-->
<!--            </main>-->
<!--        </div>-->
<!--    </div>-->
<!--</template>-->
<template>
    <div>
        <Head :title="title" />

        <Banner />

        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationMark class="block h-9 w-auto" />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    Dashboard
                                </NavLink>
                                <NavLink :href="route('calendar')" :active="route().current('calendar')">
                                    Kalender
                                </NavLink>
                                <NavLink :href="route('company-calendar')" :active="route().current('company-calendar')">
                                    Firmenkalender
                                </NavLink>
                                <NavLink :href="route('vacation')" :active="route().current('vacation')">
                                    Mein Urlaub
                                </NavLink>
                                <NavLink  :href="route('vacation.management')" :active="route().current('vacation.management')">
                                    Urlaubsverwaltung
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ml-6">
                            <div class="ml-3 relative">
                                <!-- Teams Dropdown -->
                                <Dropdown v-if="$page.props.jetstream.hasTeamFeatures" align="right" width="60">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                                {{ $page.props.auth.user.current_team.name }}

                                                <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <div class="w-60">
                                            <!-- Team Management -->
                                            <template v-if="$page.props.jetstream.hasTeamFeatures">
                                                <div class="block px-4 py-2 text-xs text-gray-400">
                                                    Manage Team
                                                </div>

                                                <!-- Team Settings -->
                                                <DropdownLink :href="route('teams.show', $page.props.auth.user.current_team)">
                                                    Team Settings
                                                </DropdownLink>

                                                <DropdownLink v-if="$page.props.jetstream.canCreateTeams" :href="route('teams.create')">
                                                    Create New Team
                                                </DropdownLink>

                                                <div class="border-t border-gray-200 dark:border-gray-600" />

                                                <!-- Team Switcher -->
                                                <div class="block px-4 py-2 text-xs text-gray-400">
                                                    Switch Teams
                                                </div>

                                                <template v-for="team in $page.props.auth.user.all_teams" :key="team.id">
                                                    <form @submit.prevent="switchToTeam(team)">
                                                        <DropdownLink as="button">
                                                            <div class="flex items-center">
                                                                <svg v-if="team.id == $page.props.auth.user.current_team_id" class="mr-2 h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>

                                                                <div>{{ team.name }}</div>
                                                            </div>
                                                        </DropdownLink>
                                                    </form>
                                                </template>
                                            </template>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>

                            <!-- Settings Dropdown -->
                            <div class="ml-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                            <img class="h-8 w-8 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                                        </button>

                                        <span v-else class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                                {{ $page.props.auth.user.name }}

                                                <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <!-- Account Management -->
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            Manage Account
                                        </div>

                                        <DropdownLink :href="route('profile.show')">
                                            Profile
                                        </DropdownLink>

                                        <DropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">
                                            API Tokens
                                        </DropdownLink>

                                        <div class="border-t border-gray-200 dark:border-gray-600" />

                                        <!-- Authentication -->
                                        <form @submit.prevent="logout">
                                            <DropdownLink as="button">
                                                Log Out
                                            </DropdownLink>
                                        </form>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-mr-2 flex items-center sm:hidden">
                            <button class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out" @click="showingNavigationDropdown = ! showingNavigationDropdown">
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('calendar')" :active="route().current('calendar')">
                            Kalender
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('company-calendar')" :active="route().current('company-calendar')">
                            Firmenkalender
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('vacation')" :active="route().current('vacation')">
                            Mein Urlaub
                        </ResponsiveNavLink>
                        <ResponsiveNavLink  :href="route('vacation.management')" :active="route().current('vacation.management')">
                            Urlaubsverwaltung
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex items-center px-4">
                            <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 mr-3">
                                <img class="h-10 w-10 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                            </div>

                            <div>
                                <div class="font-medium text-base text-gray-800 dark:text-gray-200">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="font-medium text-sm text-gray-500">
                                    {{ $page.props.auth.user.email }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.show')" :active="route().current('profile.show')">
                                Profile
                            </ResponsiveNavLink>

                            <ResponsiveNavLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')" :active="route().current('api-tokens.index')">
                                API Tokens
                            </ResponsiveNavLink>

                            <!-- Authentication -->
                            <form method="POST" @submit.prevent="logout">
                                <ResponsiveNavLink as="button">
                                    Log Out
                                </ResponsiveNavLink>
                            </form>

                            <!-- Team Management -->
                            <template v-if="$page.props.jetstream.hasTeamFeatures">
                                <div class="border-t border-gray-200 dark:border-gray-600" />

                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    Manage Team
                                </div>

                                <!-- Team Settings -->
                                <ResponsiveNavLink :href="route('teams.show', $page.props.auth.user.current_team)" :active="route().current('teams.show')">
                                    Team Settings
                                </ResponsiveNavLink>

                                <ResponsiveNavLink v-if="$page.props.jetstream.canCreateTeams" :href="route('teams.create')" :active="route().current('teams.create')">
                                    Create New Team
                                </ResponsiveNavLink>

                                <div class="border-t border-gray-200 dark:border-gray-600" />

                                <!-- Team Switcher -->
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    Switch Teams
                                </div>

                                <template v-for="team in $page.props.auth.user.all_teams" :key="team.id">
                                    <form @submit.prevent="switchToTeam(team)">
                                        <ResponsiveNavLink as="button">
                                            <div class="flex items-center">
                                                <svg v-if="team.id == $page.props.auth.user.current_team_id" class="mr-2 h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <div>{{ team.name }}</div>
                                            </div>
                                        </ResponsiveNavLink>
                                    </form>
                                </template>
                            </template>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>

<script>
import { defineComponent } from 'vue'
import { Head, Link } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import NavLink from '@/Components/NavLink.vue'
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'
import ApplicationMark from '@/Components/ApplicationMark.vue'

export default defineComponent({
    props: {
        title: String,
    },

    components: {
        Head,
        Link,
        Banner,
        Dropdown,
        DropdownLink,
        NavLink,
        ResponsiveNavLink,
        ApplicationMark,
    },

    data() {
        return {
            showingNavigationDropdown: false,
        }
    },

    methods: {
        switchToTeam(team) {
            this.$inertia.put(route('current-team.update'), {
                team_id: team.id,
            }, {
                preserveState: false,
            })
        },

        logout() {
            this.$inertia.post(route('logout'));
        },
    },
})
</script>

