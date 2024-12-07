<template>
    <AppLayout title="Billing & Usage">
        <div class="bg-white overflow-hidden sm:rounded-lg p-6">
            <!-- Show success message -->
            <TransitionGroup name="fade">
                <div v-if="showSuccessFlash" 
                     key="success"
                     class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" 
                     role="alert">
                    <span class="block sm:inline">{{ flash.success }}</span>
                    <button @click="showSuccessFlash = false" 
                            class="absolute top-0 bottom-0 right-0 px-4 py-3">
                        <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <title>Close</title>
                            <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                        </svg>
                    </button>
                </div>

                <!-- Show error message -->
                <div v-if="showErrorFlash"
                     key="error"
                     class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" 
                     role="alert">
                    <span class="block sm:inline">{{ flash.error }}</span>
                    <button @click="showErrorFlash = false" 
                            class="absolute top-0 bottom-0 right-0 px-4 py-3">
                        <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <title>Close</title>
                            <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                        </svg>
                    </button>
                </div>
            </TransitionGroup>

            <!-- Current Balance Section -->
            <div class="mb-8">
                <h2 class="text-2xl font-semibold mb-4">Current Balance</h2>
                <div class="bg-gray-50 rounded-lg p-6">
                    <p class="text-4xl font-bold text-indigo-600">
                        {{ Number(balance.amount).toLocaleString('id-ID') }}
                    </p>
                    <p class="text-gray-500 mt-1">Available Credits</p>
                </div>
            </div>

            <!-- Top Up Section -->
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-4">Top Up Credits</h3>
                <form @submit.prevent="topup">
                    <div class="mb-4">
                        <InputLabel value="Select Amount" />
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-2">
                            <button v-for="amount in topupAmounts" :key="amount"
                                type="button"
                                class="p-4 border rounded-lg hover:bg-gray-50 transition-colors duration-150"
                                :class="{ 'bg-indigo-50 border-indigo-500 ring-2 ring-indigo-500': form.amount === amount }"
                                @click="form.amount = amount">
                                <div class="font-semibold">Rp {{ amount.toLocaleString('id-ID') }}</div>
                                <div class="text-sm text-gray-500">{{ formatCredits(amount) }}</div>
                            </button>
                        </div>
                        <InputError :message="form.errors.amount" class="mt-2" />
                    </div>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Proceed to Payment
                    </PrimaryButton>
                </form>
            </div>

            <!-- Transactions Section -->
            <div>
                <h3 class="text-xl font-semibold mb-4">Recent Transactions</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Payment Method</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="transaction in transactions" :key="transaction.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(transaction.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="`bg-${transaction.type_color}-100 text-${transaction.type_color}-800`">
                                        {{ transaction.formatted_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span :class="transaction.type === 'usage' ? 'text-red-600' : 'text-green-600'">
                                        {{ transaction.type === 'usage' ? '-' : '+' }}
                                        Rp {{ Number(transaction.amount).toLocaleString('id-ID') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800': transaction.status_color === 'green',
                                            'bg-yellow-100 text-yellow-800': transaction.status_color === 'yellow', 
                                            'bg-red-100 text-red-800': transaction.status_color === 'red',
                                            'bg-gray-100 text-gray-800': transaction.status_color === 'gray'
                                        }">
                                        {{ transaction.formatted_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ transaction.payment_method || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ transaction.description }}
                                </td>
                            </tr>
                            <tr v-if="transactions.length === 0">
                                <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    No transactions found
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
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    balance: Object,
    transactions: Array,
    flash: Object,
});

const topupAmounts = [50000, 100000, 200000, 500000, 1000000, 2000000];

const form = useForm({
    amount: 50000,
});

const topup = () => {
    form.post(route('billing.topup'));
};

const formatDate = (date) => {
    return new Date(date).toLocaleString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatCredits = (amount) => {
    return `${amount.toLocaleString('id-ID')} credits`;
};

// Add these new refs
const showSuccessFlash = ref(false);
const showErrorFlash = ref(false);

// Watch for changes in flash messages
watch(() => props.flash.success, (newVal) => {
    if (newVal) {
        showSuccessFlash.value = true;
        setTimeout(() => {
            showSuccessFlash.value = false;
        }, 5000); // Disappear after 5 seconds
    }
}, { immediate: true });

watch(() => props.flash.error, (newVal) => {
    if (newVal) {
        showErrorFlash.value = true;
        setTimeout(() => {
            showErrorFlash.value = false;
        }, 5000); // Disappear after 5 seconds
    }
}, { immediate: true });
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
