<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-slate-900 pt-6 sm:justify-center sm:pt-0">
        <div>
            <Link href="/" class="flex items-center space-x-2">
                <svg class="h-12 w-12 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </Link>
        </div>

        <div class="mt-6 w-full overflow-hidden rounded-xl border border-gray-700 bg-gray-800 px-6 py-6 shadow-xl sm:max-w-md">
            <slot />
        </div>
    </div>
</template>
