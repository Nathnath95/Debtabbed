<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    household: Object,
    expenses: Array,
});

</script>

<template>
    <Head :title="household.name" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ household.name }}
            </h2>
        </template>
        
        <!-- Household Details -->
        <div class="py-12">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <div class="mb-4 p-3 bg-gray-50 rounded-md">
                        <p class="text-sm text-gray-600">Invite Code:</p>
                        <p class="font-mono font-semibold">{{ household.invite_code }}</p>
                    </div>
                    <h3 class="font-semibold text-lg mb-4">Members</h3>

                    <ul>
                        <li
                            v-for="member in household.users"
                            :key="member.id"
                            class="py-2 border-b last:border-0"
                        >
                            {{ member.name }} ({{ member.email }})
                        </li>
                    </ul>
                </div>
            </div>
        
        <!-- Expenses List -->
            <div class="mt-6 bg-white p-6 shadow sm:rounded-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-lg">Expenses</h3>
                    <Link
                        :href="route('households.expenses.create', household.id)"
                        class="bg-indigo-600 text-white px-3 py-1.5 rounded-md text-sm hover:bg-indigo-700"
                    >
                        + Add Expense
                    </Link>
                </div>

                <div v-if="expenses.length === 0" class="text-gray-500 text-sm">
                    No expenses posted yet.
                </div>

                <div
                    v-for="expense in expenses"
                    :key="expense.id"
                    class="border-b last:border-0 py-3"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-medium">{{ expense.title }}</p>
                            <p class="text-sm text-gray-500">
                                Paid by {{ expense.paid_by?.name }} · {{ expense.payment_method }} · {{ expense.paid_at }}
                            </p>
                        </div>
                        <p class="font-semibold">₱{{ Number(expense.amount).toFixed(2) }}</p>
                    </div>

                    <div class="mt-2 space-y-1">
                        <div
                            v-for="split in expense.splits"
                            :key="split.id"
                            class="flex justify-between text-sm"
                        >
                            <span>{{ split.user.name }}</span>
                            <span
                                :class="{
                                    'text-green-600': split.status === 'paid',
                                    'text-yellow-600': split.status === 'partial',
                                    'text-red-600': split.status === 'unpaid',
                                }"
                            >
                                {{ split.status }} (₱{{ Number(split.amount_owed).toFixed(2) }})
                            </span>
                        </div>
                    </div>
                </div>
            </div> 
        </div>
    </AuthenticatedLayout>
</template>