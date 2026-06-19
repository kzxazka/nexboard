<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    transactions: Object,
    summary: Object,
    monthlyTrend: Object,
    filters: Object,
});

const showCreateModal = ref(false);

const form = useForm({
    type: 'income',
    amount: '',
    description: '',
    category: '',
    date: new Date().toISOString().split('T')[0],
});

const submit = () => {
    form.post(route('finance.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
            form.date = new Date().toISOString().split('T')[0];
        },
    });
};

const deleteTransaction = (id) => {
    if (confirm('Delete this transaction?')) {
        router.delete(route('finance.destroy', id), { preserveScroll: true });
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Finance" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-semibold text-nexboard-on-surface">Finance</h1>
                    <p class="text-sm text-nexboard-on-surface-variant mt-0.5">Track your income & expenses</p>
                </div>
                <button @click="showCreateModal = true" class="btn-primary gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Transaction
                </button>
            </div>
        </template>

        <div class="page-transition space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="metric-card animate-slide-up">
                    <span class="text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant">Total Income</span>
                    <div class="text-2xl font-bold text-emerald-400 mt-2">{{ formatCurrency(summary.totalIncome) }}</div>
                </div>
                <div class="metric-card animate-slide-up" style="animation-delay: 100ms">
                    <span class="text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant">Total Expense</span>
                    <div class="text-2xl font-bold text-red-400 mt-2">{{ formatCurrency(summary.totalExpense) }}</div>
                </div>
                <div class="metric-card animate-slide-up" style="animation-delay: 200ms">
                    <span class="text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant">Net Profit</span>
                    <div :class="['text-2xl font-bold mt-2', summary.netProfit >= 0 ? 'text-emerald-400' : 'text-red-400']">
                        {{ formatCurrency(summary.netProfit) }}
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="glass-card animate-slide-up" style="animation-delay: 300ms">
                <div class="px-5 py-4 border-b border-white/10">
                    <h2 class="text-base font-semibold text-white">Transactions</h2>
                </div>

                <div v-if="transactions.data.length === 0" class="px-5 py-12 text-center">
                    <p class="text-sm text-nexboard-on-surface-variant">No transactions yet.</p>
                    <button @click="showCreateModal = true" class="btn-primary mt-3 text-xs">Record First Transaction</button>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant">Date</th>
                                <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant">Description</th>
                                <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant">Category</th>
                                <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant">Project</th>
                                <th class="text-right px-5 py-3 text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant">Amount</th>
                                <th class="px-5 py-3 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.03]">
                            <tr v-for="tx in transactions.data" :key="tx.id" class="hover:bg-white/[0.02] transition-colors group">
                                <td class="px-5 py-3 text-nexboard-on-surface-variant whitespace-nowrap">{{ formatDate(tx.date) }}</td>
                                <td class="px-5 py-3 text-white">{{ tx.description }}</td>
                                <td class="px-5 py-3 text-nexboard-on-surface-variant">{{ tx.category || '-' }}</td>
                                <td class="px-5 py-3 text-nexboard-on-surface-variant">{{ tx.project?.name || '-' }}</td>
                                <td class="px-5 py-3 text-right font-mono whitespace-nowrap"
                                    :class="tx.type === 'income' ? 'text-emerald-400' : 'text-red-400'"
                                >
                                    {{ tx.type === 'income' ? '+' : '-' }}{{ formatCurrency(tx.amount) }}
                                </td>
                                <td class="px-5 py-3">
                                    <button
                                        @click="deleteTransaction(tx.id)"
                                        class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-red-500/20 transition-all"
                                    >
                                        <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.last_page > 1" class="flex justify-center gap-1 px-5 py-4 border-t border-white/10">
                    <template v-for="link in transactions.links" :key="link.label">
                        <button
                            v-if="link.url"
                            @click="router.get(link.url, {}, { preserveScroll: true })"
                            :class="link.active ? 'bg-indigo-500 text-white' : 'text-nexboard-on-surface-variant hover:bg-white/5'"
                            class="px-3 py-1.5 rounded-nex text-xs font-medium transition-colors"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- Create Transaction Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300"
                leave-active-class="transition-all duration-200"
                enter-from-class="opacity-0"
                leave-to-class="opacity-0"
            >
                <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = false">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" />
                    <div class="glass-card-elevated w-full max-w-md p-6 relative animate-slide-up z-10">
                        <h2 class="text-lg font-semibold text-white mb-4">Add Transaction</h2>

                        <form @submit.prevent="submit" class="space-y-4">
                            <!-- Type Toggle -->
                            <div class="flex rounded-nex border border-white/10 overflow-hidden">
                                <button type="button"
                                    @click="form.type = 'income'"
                                    :class="form.type === 'income' ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500' : 'text-nexboard-on-surface-variant hover:bg-white/5'"
                                    class="flex-1 py-2.5 text-sm font-medium transition-all border-b-2 border-transparent"
                                >
                                    💰 Income
                                </button>
                                <button type="button"
                                    @click="form.type = 'expense'"
                                    :class="form.type === 'expense' ? 'bg-red-500/20 text-red-400 border-red-500' : 'text-nexboard-on-surface-variant hover:bg-white/5'"
                                    class="flex-1 py-2.5 text-sm font-medium transition-all border-b-2 border-transparent"
                                >
                                    💸 Expense
                                </button>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Amount *</label>
                                <input v-model="form.amount" type="number" step="0.01" class="input-dark" placeholder="0" required />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Description *</label>
                                <input v-model="form.description" type="text" class="input-dark" placeholder="e.g., Payment from client" required />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Category</label>
                                    <input v-model="form.category" type="text" class="input-dark" placeholder="e.g., Hosting" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-nexboard-on-surface-variant mb-1.5">Date *</label>
                                    <input v-model="form.date" type="date" class="input-dark" required />
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="showCreateModal = false" class="btn-secondary">Cancel</button>
                                <button type="submit" :disabled="form.processing" class="btn-primary">
                                    {{ form.processing ? 'Saving...' : 'Save Transaction' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
