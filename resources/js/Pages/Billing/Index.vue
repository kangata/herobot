<template>
    <AppLayout title="Billing & Usage">
        <div class="bg-white overflow-hidden sm:rounded-lg p-6">
            <h2 class="text-2xl font-semibold mb-4">Current Balance</h2>
            <p class="text-3xl font-bold mb-6">
                {{ balance.amount }} {{ balance.amount <= 1 ? 'credit' : 'credits' }}
            </p>
            <h3 class="text-xl font-semibold mb-4">Top Up Credits</h3>
            <form @submit.prevent="topup">
                <div class="mb-4">
                    <InputLabel value="Select Amount" />
                    <div class="grid grid-cols-3 gap-4 mt-2">
                        <button v-for="amount in [50000, 100000, 200000, 500000, 1000000, 2000000]" :key="amount"
                            type="button"
                            class="p-4 border rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500"
                            :class="{ 'bg-indigo-50 border-indigo-500': form.amount === amount }"
                            @click="form.amount = amount">
                            Rp {{ amount.toLocaleString('id-ID') }}
                        </button>
                    </div>
                    <InputError :message="form.errors.amount" class="mt-2" />
                </div>
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Top Up
                </PrimaryButton>
            </form>
            <h3 class="text-xl font-semibold mt-8 mb-4">Recent Transactions</h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th
                            class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date</th>
                        <th
                            class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Type</th>
                        <th
                            class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Amount</th>
                        <th
                            class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Description</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="transaction in transactions" :key="transaction.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ new Date(transaction.created_at).toLocaleString() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ transaction.type }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            Rp {{ Number(transaction.amount).toLocaleString('id-ID') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ transaction.description }}
                        </td>
                    </tr>
                    <tr v-if="transactions.length === 0">
                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                            No transactions found
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    balance: Object,
    transactions: Array,
});

const form = useForm({
    amount: 50000,
});

const topup = () => {
    form.post(route('billing.topup'));
};
</script>
