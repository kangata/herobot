<template>
    <AppLayout title="Billing">
        <div class="bg-white overflow-hidden sm:rounded-lg">
            <!-- Show success message -->
            <TransitionGroup name="fade">
                <div v-if="showSuccessFlash" key="success"
                    class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                    role="alert">
                    <span class="block sm:inline">{{ flash.success }}</span>
                    <button @click="showSuccessFlash = false" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                        <svg class="fill-current h-6 w-6 text-green-500" role="button"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <title>Close</title>
                            <path
                                d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                        </svg>
                    </button>
                </div>

                <!-- Show error message -->
                <div v-if="showErrorFlash" key="error"
                    class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ flash.error }}</span>
                    <button @click="showErrorFlash = false" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                        <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20">
                            <title>Close</title>
                            <path
                                d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                        </svg>
                    </button>
                </div>
            </TransitionGroup>

            <!-- Header Section -->
            <div class="mb-8">
                <div class="sm:flex-auto">
                    <h1 class="text-base font-semibold leading-6 text-gray-900">Billing & Credits</h1>
                    <p class="mt-2 text-sm text-gray-700">Manage your credit balance and billing information.</p>
                </div>
            </div>

            <!-- Current Balance Section -->
            <div class="mb-8">
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full mr-3 bg-indigo-500"></div>
                            <h2 class="text-lg font-semibold text-gray-900">Current Balance</h2>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-green-200 rounded-md p-2">
                                        <CreditCardIcon class="h-6 w-6 text-green-700" />
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-xs font-medium text-gray-500 truncate">Available Credits</p>
                                        <p class="text-lg font-semibold text-gray-900">{{ formatNumber(balance.amount / 1000000) }} credits</p>
                                    </div>
                                </div>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-blue-200 rounded-md p-2">
                                        <CurrencyDollarIcon class="h-6 w-6 text-blue-700" />
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-xs font-medium text-gray-500 truncate">USD Equivalent</p>
                                        <p class="text-lg font-semibold text-gray-900">${{ formatUSD((balance.amount / 1000000) / 16500) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Credit Usage Info Box -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                            <div class="flex items-center">
                                <InformationCircleIcon class="h-5 w-5 text-blue-600" />
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-blue-800">Exchange Rate</p>
                                    <p class="text-xs text-blue-600">1 USD = {{ formatNumber(16500) }} Credits</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Up Section -->
            <div class="mb-8">
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full mr-3 bg-green-500"></div>
                            <h2 class="text-lg font-semibold text-gray-900">Top Up Credits</h2>
                        </div>
                    </div>
                    <div class="p-6">
                        <form @submit.prevent="topup">
                            <div class="mb-4">
                                <InputLabel value="Select Amount" />
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-2">
                                    <button v-for="amount in topupAmounts" :key="amount" type="button"
                                        class="p-4 border rounded-lg hover:bg-gray-50 transition-colors duration-150"
                                        :class="{ 'bg-indigo-50 border-indigo-500 ring-2 ring-indigo-500': form.amount === amount }"
                                        @click="form.amount = amount">
                                        <div class="font-semibold">{{ formatNumber(amount) }} credits</div>
                                        <div class="text-sm text-gray-500">Rp {{ formatNumber(amount) }}</div>
                                    </button>
                                </div>
                                <InputError :message="form.errors.amount" class="mt-2" />
                            </div>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Proceed to Payment
                            </PrimaryButton>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Transactions Section -->
            <div>
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full mr-3 bg-orange-500"></div>
                            <h2 class="text-lg font-semibold text-gray-900">Recent Transactions</h2>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="transaction in transactions" :key="transaction.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(transaction.type === 'usage' ? transaction.updated_at : transaction.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            :class="`bg-${transaction.type_color}-100 text-${transaction.type_color}-800`">
                                            {{ transaction.formatted_type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="transaction.transaction_type === 'debit' ? 'text-red-600' : 'text-green-600'">
                                            {{ transaction.transaction_type === 'debit' ? '-' : '+' }}
                                            {{ formatNumber(transaction.amount / 1000000) }} credits
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="{
                                                'bg-green-100 text-green-800': transaction.status_color === 'green',
                                                'bg-yellow-100 text-yellow-800': transaction.status_color === 'yellow', 
                                                'bg-red-100 text-red-800': transaction.status_color === 'red',
                                                'bg-gray-100 text-gray-800': transaction.status_color === 'gray'
                                            }">
                                            {{ transaction.formatted_status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ transaction.description }}
                                        <template v-if="transaction.expired_at && transaction.status === 'pending'">
                                            (Expires {{ formatDate(transaction.expired_at) }})
                                        </template>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <PrimaryButton v-if="transaction.payment_details && transaction.payment_details.invoice_url"
                                            class="text-indigo-600 hover:underline"
                                            :href="transaction.payment_details.invoice_url" target="_blank">
                                            {{ transaction.status === 'pending' ? 'Pay Now' : 'View Invoice' }}
                                        </PrimaryButton>
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
import { 
    CreditCardIcon, 
    CurrencyDollarIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline';

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

const formatNumber = (number) => {
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(number);
};

const formatUSD = (amount) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 4
    }).format(amount);
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
