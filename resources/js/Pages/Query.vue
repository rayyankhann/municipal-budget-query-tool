<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
} from 'chart.js';
const axios = window.axios;

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({ history: Array });

const question = ref('');
const loading = ref(false);
const result = ref(null);
const error = ref(null);
const showHistory = ref(false);
const queryHistory = ref(props.history || []);
const suggestions = ref([
    'What is the total amount spent by each department?',
    'Show the top 10 largest transactions with department and vendor names',
    'Which budget categories have spent more than their allocated amount?',
    'What is the total spend per quarter for the Police Department?',
    'List all vendors and how much we paid them in total, sorted highest first',
    'What is the average transaction amount by department?',
]);

onMounted(async () => {
    try {
        const res = await axios.get(route('query.suggestions'));
        if (res.data.suggestions) suggestions.value = res.data.suggestions;
    } catch (e) {}
});

const useSuggestion = (s) => { question.value = s; };

const replayQuery = (q) => {
    question.value = q;
    showHistory.value = false;
    submitQuery();
};

const submitQuery = async () => {
    if (!question.value.trim()) return;
    loading.value = true;
    result.value = null;
    error.value = null;

    const timeout = setTimeout(() => {
        if (loading.value) {
            loading.value = false;
            error.value = 'Query timed out. Try a simpler question.';
        }
    }, 15000);

    try {
        const res = await axios.post(route('query.run'), { question: question.value });
        clearTimeout(timeout);
        if (res.data.error) {
            error.value = res.data.error;
            if (res.data.sql) result.value = { sql: res.data.sql, results: [], columns: [] };
        } else {
            result.value = res.data;
        }
        const histRes = await axios.get(route('query.history'));
        queryHistory.value = histRes.data.history;
    } catch (e) {
        clearTimeout(timeout);
        if (e.response && e.response.status === 429) {
            error.value = 'Rate limit reached. Please wait a moment before trying again.';
        } else if (e.code === 'ECONNABORTED') {
            error.value = 'Query timed out. Try a simpler question.';
        } else {
            error.value = 'An error occurred. Please try again.';
        }
    } finally {
        loading.value = false;
    }
};

const exportCsv = () => {
    if (!result.value || !result.value.results.length) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('query.export');
    form.style.display = 'none';

    const csrf = document.createElement('input');
    csrf.name = '_token';
    csrf.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
    form.appendChild(csrf);

    const colsInput = document.createElement('input');
    colsInput.name = 'columns';
    colsInput.value = JSON.stringify(result.value.columns);
    form.appendChild(colsInput);

    const resultsInput = document.createElement('input');
    resultsInput.name = 'results';
    resultsInput.value = JSON.stringify(result.value.results);
    form.appendChild(resultsInput);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

const hasNumericAmountColumn = computed(() => {
    if (!result.value || !result.value.results || result.value.results.length === 0) return false;
    return result.value.columns.some(col => /amount|total|sum|spent|allocated|count|avg/i.test(col));
});

const chartData = computed(() => {
    if (!result.value || !result.value.results || result.value.results.length === 0) return null;
    const numericCol = result.value.columns.find(col => /amount|total|sum|spent|allocated|count|avg/i.test(col));
    if (!numericCol) return null;
    const labelCol = result.value.columns.find(col => col !== numericCol) || result.value.columns[0];
    const data = result.value.results.slice(0, 20);
    return {
        labels: data.map((row, i) => row[labelCol] || `Row ${i + 1}`),
        datasets: [{
            label: numericCol,
            data: data.map(row => parseFloat(row[numericCol]) || 0),
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderRadius: 6,
        }],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (ctx) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(ctx.raw) } },
    },
    scales: {
        x: { ticks: { maxRotation: 45, minRotation: 45 } },
        y: { ticks: { callback: (val) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val) } },
    },
};

const formatCell = (val) => {
    if (val === null || val === undefined) return '-';
    const num = parseFloat(val);
    if (!isNaN(num) && num > 100 && String(val).match(/^\d+\.?\d*$/)) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(num);
    }
    return val;
};

const timeAgo = (dateStr) => {
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'just now';
    if (mins < 60) return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs}h ago`;
    return `${Math.floor(hrs / 24)}d ago`;
};
</script>

<template>
    <Head title="Budget Query" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Budget Query</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ask questions about the municipal budget using natural language</p>
            </div>
        </template>

        <div class="mx-auto max-w-5xl">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <form @submit.prevent="submitQuery">
                    <label for="question" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ask a question about the budget...</label>
                    <div class="mt-2 flex gap-3">
                        <input id="question" v-model="question" type="text"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 sm:text-sm"
                            placeholder="e.g. How much did we spend on contractors in Q3?" :disabled="loading" />
                        <button type="button" @click="showHistory = !showHistory"
                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600" title="Query history">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </button>
                        <button type="submit" :disabled="loading || !question.trim()"
                            class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                            <svg v-if="loading" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            {{ loading ? 'Querying...' : 'Ask' }}
                        </button>
                    </div>
                </form>

                <!-- History Panel -->
                <div v-if="showHistory && queryHistory.length > 0" class="mt-4 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700">
                    <div class="border-b border-gray-200 px-4 py-2 dark:border-gray-600">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Recent Queries</p>
                    </div>
                    <div class="max-h-48 overflow-y-auto divide-y divide-gray-200 dark:divide-gray-600">
                        <button v-for="h in queryHistory" :key="h.id" @click="replayQuery(h.question)"
                            class="flex w-full items-center justify-between px-4 py-2.5 text-left text-sm transition hover:bg-gray-100 dark:hover:bg-gray-600">
                            <span class="truncate text-gray-700 dark:text-gray-300">{{ h.question }}</span>
                            <span class="ml-3 flex-shrink-0 text-xs text-gray-400">{{ h.result_count }} rows &middot; {{ timeAgo(h.created_at) }}</span>
                        </button>
                    </div>
                </div>
                <div v-else-if="showHistory" class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-center text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">
                    No query history yet. Run a query to get started.
                </div>

                <!-- Suggestions -->
                <div class="mt-4">
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-400">Try these questions</p>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="s in suggestions" :key="s" @click="useSuggestion(s)"
                            class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:border-blue-500 dark:hover:bg-blue-900/30 dark:hover:text-blue-300">
                            {{ s }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="mt-6 flex items-center justify-center rounded-xl border border-gray-200 bg-white p-12 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="text-center">
                    <svg class="mx-auto h-10 w-10 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Generating SQL and fetching results...</p>
                </div>
            </div>

            <!-- Error -->
            <div v-if="error && !loading" class="mt-6 rounded-xl border border-red-200 bg-red-50 p-6 dark:border-red-800 dark:bg-red-950">
                <div class="flex">
                    <svg class="h-5 w-5 flex-shrink-0 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-300">Query Error</h3>
                        <p class="mt-1 text-sm text-red-700 dark:text-red-400">{{ error }}</p>
                        <p v-if="error.includes('offline') || error.includes('Ollama')" class="mt-2 text-xs text-red-600 dark:text-red-400">
                            Run <code class="rounded bg-red-100 px-1.5 py-0.5 dark:bg-red-900">ollama serve</code> then
                            <code class="rounded bg-red-100 px-1.5 py-0.5 dark:bg-red-900">ollama pull llama3.2</code>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div v-if="result && !loading" class="mt-6 space-y-6">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Generated SQL</h3>
                        <div class="flex items-center gap-2">
                            <span v-if="result.cached" class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-300">Cached</span>
                            <span v-if="result.executionTime" class="text-xs text-gray-400">{{ result.executionTime }}ms</span>
                        </div>
                    </div>
                    <pre class="overflow-x-auto rounded-lg bg-slate-800 p-4 text-sm text-green-400"><code>{{ result.sql }}</code></pre>
                </div>

                <div v-if="hasNumericAmountColumn && chartData && result.results.length > 0" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Visualization</h3>
                    <div class="h-72">
                        <Bar :data="chartData" :options="chartOptions" />
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Results</h3>
                        <div class="flex items-center gap-3">
                            <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                {{ result.rowCount || result.results.length }} {{ (result.rowCount || result.results.length) === 1 ? 'row' : 'rows' }}
                            </span>
                            <button v-if="result.results.length > 0" @click="exportCsv"
                                class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Export CSV
                            </button>
                        </div>
                    </div>
                    <div v-if="result.results.length > 0" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700">
                                    <th v-for="col in result.columns" :key="col" class="whitespace-nowrap px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ col.replace(/_/g, ' ') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="(row, i) in result.results" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td v-for="col in result.columns" :key="col" class="whitespace-nowrap px-6 py-3 text-gray-700 dark:text-gray-300">{{ formatCell(row[col]) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        No results found for that query.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
