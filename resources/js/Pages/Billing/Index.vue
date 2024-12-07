<template>
    <AppLayout title="Billing & Usage">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h2 class="text-2xl font-semibold mb-4">Current Balance</h2>
                    <p class="text-3xl font-bold mb-6">{{ balance.amount }} credits</p>

                    <h3 class="text-xl font-semibold mb-4">Top Up Credits</h3>
                    <form @submit.prevent="topup">
                        <div class="mb-4">
                            <InputLabel for="amount" value="Amount" />
                            <TextInput id="amount" v-model="form.amount" type="number" min="10" required />
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
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
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
                                    {{ transaction.amount }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ transaction.description }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
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
    amount: 10,
});

const topup = () => {
    form.post(route('billing.topup'));
};
</script>
