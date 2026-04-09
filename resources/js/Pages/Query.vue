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
import axios from 'axios';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const question = ref('');
const loading = ref(false);
const result = ref(null);
const error = ref(null);
const suggestions = ref([
    'How much did we spend on contractors in Q3?',
    'Which department has the highest total spend?',
    'Show all transactions over $10,000',
    'What is the total spend by vendor?',
    'Which budget categories are over their allocated amount?',
    'Show spending trends by quarter for each department',
]);

onMounted(async () => {
    try {
        const res = await axios.get(route('query.suggestions'));
        if (res.data.suggestions) {
            suggestions.value = res.data.suggestions;
        }
    } catch (e) {
        // Use default suggestions
    }
});

const useSuggestion = (s) => {
    question.value = s;
};

const submitQuery = async () => {
    if (!question.value.trim()) return;
    loading.value = true;
    result.value = null;
    error.value = null;

    try {
        const res = await axios.post(route('query.run'), {
            question: question.value,
        });

        if (res.data.error) {
            error.value = res.data.error;
            if (res.data.sql) {
                result.value = { sql: res.data.sql, results: [], columns: [] };
            }
        } else {
            result.value = res.data;
        }
    } catch (e) {
        error.value = 'An error occurred. Please try again.';
    } finally {
        loading.value = false;
    }
};

const hasNumericAmountColumn = computed(() => {
    if (!result.value || !result.value.results || result.value.results.length === 0) return false;
    return result.value.columns.some(col =>
        /amount|total|sum|spent|allocated|count|avg/i.test(col)
    );
});

const chartData = computed(() => {
    if (!result.value || !result.value.results || result.value.results.length === 0) return null;

    const numericCol = result.value.columns.find(col =>
        /amount|total|sum|spent|allocated|count|avg/i.test(col)
    );
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
        tooltip: {
            callbacks: {
                label: (ctx) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(ctx.raw),
            },
        },
    },
    scales: {
        y: {
            ticks: {
                callback: (val) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val),
            },
        },
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
</script>

<template>
    <Head title="Budget Query" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Budget Query</h1>
                <p class="mt-1 text-sm text-gray-500">Ask questions about the municipal budget using natural language</p>
            </div>
        </template>

        <div class="mx-auto max-w-5xl">
            <!-- Query Input -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <form @submit.prevent="submitQuery">
                    <label for="question" class="block text-sm font-medium text-gray-700">Ask a question about the budget...</label>
                    <div class="mt-2 flex gap-3">
                        <input
                            id="question"
                            v-model="question"
                            type="text"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            placeholder="e.g. How much did we spend on contractors in Q3?"
                            :disabled="loading"
                        />
                        <button
                            type="submit"
                            :disabled="loading || !question.trim()"
                            class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <svg v-if="loading" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ loading ? 'Querying...' : 'Ask' }}
                        </button>
                    </div>
                </form>

                <!-- Suggestion Chips -->
                <div class="mt-4">
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-400">Try these questions</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="s in suggestions"
                            :key="s"
                            @click="useSuggestion(s)"
                            class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                        >
                            {{ s }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="mt-6 flex items-center justify-center rounded-xl border border-gray-200 bg-white p-12 shadow-sm">
                <div class="text-center">
                    <svg class="mx-auto h-10 w-10 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="mt-3 text-sm text-gray-500">Generating SQL and fetching results...</p>
                </div>
            </div>

            <!-- Error State -->
            <div v-if="error && !loading" class="mt-6 rounded-xl border border-red-200 bg-red-50 p-6">
                <div class="flex">
                    <svg class="h-5 w-5 flex-shrink-0 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Query Error</h3>
                        <p class="mt-1 text-sm text-red-700">{{ error }}</p>
                        <p v-if="error.includes('Ollama')" class="mt-2 text-xs text-red-600">
                            Make sure Ollama is running: <code class="rounded bg-red-100 px-1.5 py-0.5">ollama serve</code>
                            and the model is pulled: <code class="rounded bg-red-100 px-1.5 py-0.5">ollama pull llama3.2</code>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div v-if="result && !loading" class="mt-6 space-y-6">
                <!-- Generated SQL -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-3 text-sm font-semibold text-gray-700">Generated SQL</h3>
                    <pre class="overflow-x-auto rounded-lg bg-slate-800 p-4 text-sm text-green-400"><code>{{ result.sql }}</code></pre>
                </div>

                <!-- Chart (if applicable) -->
                <div v-if="hasNumericAmountColumn && chartData && result.results.length > 0" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-3 text-sm font-semibold text-gray-700">Visualization</h3>
                    <div class="h-72">
                        <Bar :data="chartData" :options="chartOptions" />
                    </div>
                </div>

                <!-- Results Table -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <h3 class="text-sm font-semibold text-gray-700">Results</h3>
                        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                            {{ result.rowCount || result.results.length }} {{ (result.rowCount || result.results.length) === 1 ? 'row' : 'rows' }}
                        </span>
                    </div>
                    <div v-if="result.results.length > 0" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 bg-gray-50">
                                    <th
                                        v-for="col in result.columns"
                                        :key="col"
                                        class="whitespace-nowrap px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                    >
                                        {{ col.replace(/_/g, ' ') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(row, i) in result.results" :key="i" class="hover:bg-gray-50">
                                    <td
                                        v-for="col in result.columns"
                                        :key="col"
                                        class="whitespace-nowrap px-6 py-3 text-gray-700"
                                    >
                                        {{ formatCell(row[col]) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="px-6 py-8 text-center text-sm text-gray-500">
                        No results returned for this query.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
