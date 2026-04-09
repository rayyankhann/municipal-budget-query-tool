<script setup>
import { ref, onMounted } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link } from '@inertiajs/vue3';

const showMobileMenu = ref(false);
const darkMode = ref(false);

onMounted(() => {
    darkMode.value = localStorage.getItem('darkMode') === 'true';
    applyDarkMode();
});

const toggleDarkMode = () => {
    darkMode.value = !darkMode.value;
    localStorage.setItem('darkMode', darkMode.value);
    applyDarkMode();
};

const applyDarkMode = () => {
    document.documentElement.classList.toggle('dark', darkMode.value);
};

const isAdmin = $page => $page.props.auth.user.role === 'admin';
</script>

<template>
    <div class="min-h-screen bg-gray-50 transition-colors dark:bg-gray-900">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 border-r border-gray-200 bg-slate-800 dark:border-gray-700 dark:bg-gray-950 sm:block">
            <div class="flex h-16 items-center px-6">
                <Link :href="route('dashboard')" class="flex items-center space-x-2">
                    <svg class="h-8 w-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span class="text-lg font-bold text-white">Budget Tool</span>
                </Link>
            </div>

            <nav class="mt-4 px-4 space-y-1">
                <Link
                    :href="route('dashboard')"
                    class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition"
                    :class="route().current('dashboard') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white'"
                >
                    <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </Link>
                <Link
                    :href="route('query')"
                    class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition"
                    :class="route().current('query') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white'"
                >
                    <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16l2.879-2.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Budget Query
                </Link>
                <Link
                    :href="route('alerts')"
                    class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition"
                    :class="route().current('alerts') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white'"
                >
                    <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    Budget Alerts
                </Link>
                <Link
                    v-if="$page.props.auth.user.role === 'admin'"
                    :href="route('audit-log')"
                    class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition"
                    :class="route().current('audit-log') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white'"
                >
                    <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Audit Log
                </Link>
            </nav>

            <!-- User info at bottom of sidebar -->
            <div class="absolute bottom-0 left-0 right-0 border-t border-slate-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-600 text-sm font-medium text-white">
                            {{ $page.props.auth.user.name.charAt(0) }}
                        </div>
                    </div>
                    <div class="ml-3 min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ $page.props.auth.user.name }}</p>
                        <span
                            class="inline-block rounded px-1.5 py-0.5 text-xs font-medium"
                            :class="$page.props.auth.user.role === 'admin' ? 'bg-indigo-500/20 text-indigo-300' : 'bg-green-500/20 text-green-300'"
                        >
                            {{ $page.props.auth.user.role === 'admin' ? 'Administrator' : 'Department Head' }}
                        </span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Mobile header -->
        <div class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 dark:border-gray-700 dark:bg-gray-800 sm:hidden">
            <button @click="showMobileMenu = !showMobileMenu" class="text-gray-500 dark:text-gray-400">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <span class="text-lg font-bold text-gray-800 dark:text-white">Budget Tool</span>
            <button @click="toggleDarkMode" class="text-gray-500 dark:text-gray-400">
                <svg v-if="darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            </button>
        </div>

        <!-- Mobile menu overlay -->
        <div v-if="showMobileMenu" class="fixed inset-0 z-40 sm:hidden">
            <div class="fixed inset-0 bg-black/50" @click="showMobileMenu = false"></div>
            <div class="fixed inset-y-0 left-0 w-64 bg-slate-800 p-4">
                <nav class="mt-8 space-y-1">
                    <Link :href="route('dashboard')" class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-700 hover:text-white" @click="showMobileMenu = false">Dashboard</Link>
                    <Link :href="route('query')" class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-700 hover:text-white" @click="showMobileMenu = false">Budget Query</Link>
                    <Link :href="route('alerts')" class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-700 hover:text-white" @click="showMobileMenu = false">Budget Alerts</Link>
                    <Link v-if="$page.props.auth.user.role === 'admin'" :href="route('audit-log')" class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-700 hover:text-white" @click="showMobileMenu = false">Audit Log</Link>
                </nav>
                <div class="absolute bottom-4 left-4 right-4">
                    <Link :href="route('logout')" method="post" as="button" class="w-full rounded-lg bg-slate-700 px-3 py-2 text-sm text-slate-300 hover:text-white">Log Out</Link>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="sm:pl-64">
            <!-- Top bar -->
            <header class="sticky top-0 z-10 hidden h-16 items-center justify-between border-b border-gray-200 bg-white px-6 dark:border-gray-700 dark:bg-gray-800 sm:flex">
                <slot name="header">
                    <div></div>
                </slot>
                <div class="flex items-center gap-3">
                    <!-- Dark mode toggle -->
                    <button
                        @click="toggleDarkMode"
                        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                        title="Toggle dark mode"
                    >
                        <svg v-if="darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>
                    <!-- Print button -->
                    <button
                        @click="window.print()"
                        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 print:hidden"
                        title="Print page"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    </button>
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="flex items-center text-sm font-medium text-gray-500 transition hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                {{ $page.props.auth.user.name }}
                                <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                        </template>
                        <template #content>
                            <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <main class="p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
