<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    logs: Object,
});

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleString('en-US', {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};

const goToPage = (url) => {
    if (url) router.get(url);
};
</script>

<template>
    <Head title="Audit Log" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Audit Log</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All natural language queries made by users</p>
            </div>
        </template>

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-750">
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">User</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Question</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Rows</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Time (ms)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="whitespace-nowrap px-6 py-3 text-gray-500 dark:text-gray-400">{{ formatDate(log.created_at) }}</td>
                            <td class="whitespace-nowrap px-6 py-3">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ log.user?.name || 'Unknown' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ log.user?.email }}</p>
                                </div>
                            </td>
                            <td class="max-w-xs truncate px-6 py-3 text-gray-700 dark:text-gray-300" :title="log.question">{{ log.question }}</td>
                            <td class="whitespace-nowrap px-6 py-3">
                                <span v-if="log.success" class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">Success</span>
                                <span v-else class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-400" :title="log.error_message">Failed</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-gray-700 dark:text-gray-300">{{ log.result_count ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-gray-700 dark:text-gray-300">{{ log.execution_time_ms ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="logs.last_page > 1" class="flex items-center justify-between border-t border-gray-200 px-6 py-3 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Showing {{ logs.from }}-{{ logs.to }} of {{ logs.total }}
                </p>
                <div class="flex gap-1">
                    <button
                        v-for="link in logs.links"
                        :key="link.label"
                        @click="goToPage(link.url)"
                        :disabled="!link.url"
                        class="rounded px-3 py-1 text-sm transition"
                        :class="link.active ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100 disabled:opacity-50 dark:text-gray-400 dark:hover:bg-gray-700'"
                        v-html="link.label"
                    ></button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
