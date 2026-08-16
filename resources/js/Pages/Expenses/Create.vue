<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    household: Object,
});

const page = usePage();
const currentUserId = page.props.auth.user.id;

const allMembers = computed(() => props.household.users);

const membersExcludingPayer = computed(() =>
    allMembers.value.filter((u) => u.id !== form.paid_by)
);

const form = useForm({
    title: '',
    amount: '',
    paid_at: '',
    payment_method: 'Cash',
    payment_method_other: '',
    proof: null,
    paid_by: currentUserId,
    split_type: 'even',
    participant_ids: allMembers.value
        .filter((u) => u.id !== currentUserId)
        .map((u) => u.id),
    splits: allMembers.value.map((member) => ({
        user_id: member.id,
        percentage: '',
    })),
});

watch(() => form.paid_by, (newPayer) => {
    form.participant_ids = allMembers.value
        .filter((u) => u.id !== newPayer)
        .map((u) => u.id);
});

const activeSplits = computed(() =>
    form.splits.filter(
        (s) => s.user_id === form.paid_by || form.participant_ids.includes(s.user_id)
    )
);

const totalPercentage = computed(() =>
    activeSplits.value.reduce((sum, split) => sum + (parseFloat(split.percentage) || 0), 0)
);

const toggleParticipant = (userId) => {
    if (form.participant_ids.includes(userId)) {
        form.participant_ids = form.participant_ids.filter((id) => id !== userId);
    } else {
        form.participant_ids.push(userId);
    }
};

const handleFileChange = (e) => {
    form.proof = e.target.files[0];
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        payment_method: data.payment_method === 'Others' ? data.payment_method_other : data.payment_method,
        splits: data.split_type === 'custom'
            ? data.splits.filter((s) => s.user_id === data.paid_by || data.participant_ids.includes(s.user_id))
            : [],
    })).post(route('households.expenses.store', props.household.id), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Add Expense" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Add Expense — {{ household.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <form @submit.prevent="submit">

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">What was paid for</label>
                            <input
                                type="text"
                                v-model="form.title"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                placeholder="e.g. Electric Bill"
                            />
                            <div v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Amount</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <span class="inline-flex items-center px-3 border border-r-0 border-gray-300 bg-gray-50 text-gray-500 rounded-l-md">
                                    ₱
                                </span>
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model="form.amount"
                                    class="block w-full border-gray-300 rounded-r-md rounded-l-none"
                                    placeholder="0.00"
                                />
                            </div>
                            <div v-if="form.errors.amount" class="text-red-600 text-sm mt-1">{{ form.errors.amount }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Paid by</label>
                            <select v-model="form.paid_by" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option v-for="member in allMembers" :key="member.id" :value="member.id">
                                    {{ member.name }}{{ member.id === currentUserId ? ' (you)' : '' }}
                                </option>
                            </select>
                            <div v-if="form.errors.paid_by" class="text-red-600 text-sm mt-1">{{ form.errors.paid_by }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                            <div class="flex flex-wrap gap-4">
                                <label class="flex items-center gap-2">
                                    <input type="radio" value="Cash" v-model="form.payment_method" />
                                    Cash
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" value="GCash" v-model="form.payment_method" />
                                    GCash
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" value="Bank Transfer" v-model="form.payment_method" />
                                    Bank Transfer
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" value="Others" v-model="form.payment_method" />
                                    Others
                                </label>
                            </div>

                            <input
                                v-if="form.payment_method === 'Others'"
                                type="text"
                                v-model="form.payment_method_other"
                                class="mt-2 block w-full border-gray-300 rounded-md shadow-sm"
                                placeholder="Specify payment method"
                            />
                            <div v-if="form.errors.payment_method" class="text-red-600 text-sm mt-1">{{ form.errors.payment_method }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Date Paid</label>
                            <input
                                type="date"
                                v-model="form.paid_at"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                            />
                            <div v-if="form.errors.paid_at" class="text-red-600 text-sm mt-1">{{ form.errors.paid_at }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Proof of Payment (optional)</label>
                            <input
                                type="file"
                                accept="image/*"
                                @change="handleFileChange"
                                class="mt-1 block w-full"
                            />
                            <div v-if="form.errors.proof" class="text-red-600 text-sm mt-1">{{ form.errors.proof }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Who's sharing this expense?</label>
                            <div class="space-y-2">
                                <label
                                    v-for="member in membersExcludingPayer"
                                    :key="member.id"
                                    class="flex items-center gap-2"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="form.participant_ids.includes(member.id)"
                                        @change="toggleParticipant(member.id)"
                                    />
                                    {{ member.name }}{{ member.id === currentUserId ? ' (you)' : '' }}
                                </label>
                            </div>
                            <div v-if="form.errors.participant_ids" class="text-red-600 text-sm mt-1">{{ form.errors.participant_ids }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Split Type</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2">
                                    <input type="radio" value="even" v-model="form.split_type" />
                                    Split Evenly
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" value="custom" v-model="form.split_type" />
                                    Custom Values
                                </label>
                            </div>
                        </div>

                        <div v-if="form.split_type === 'custom'" class="mb-4 border rounded-md p-4 bg-gray-50">
                            <p class="text-sm font-medium text-gray-700 mb-2">Set each person's percentage</p>

                            <div
                                v-for="(split, index) in form.splits"
                                v-show="split.user_id === form.paid_by || form.participant_ids.includes(split.user_id)"
                                :key="split.user_id"
                                class="flex items-center gap-2 mb-2"
                            >
                                <span class="w-40 text-sm">
                                    {{ allMembers.find(m => m.id === split.user_id)?.name }}
                                    <span v-if="split.user_id === form.paid_by" class="text-gray-400">(paid)</span>
                                </span>
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model="form.splits[index].percentage"
                                    class="w-24 border-gray-300 rounded-md shadow-sm"
                                    placeholder="%"
                                />
                                <span class="text-sm text-gray-500">%</span>
                            </div>

                            <p
                                class="text-sm mt-2"
                                :class="totalPercentage === 100 ? 'text-green-600' : 'text-red-600'"
                            >
                                Total: {{ totalPercentage.toFixed(2) }}% (must equal 100%)
                            </p>

                            <div v-if="form.errors.splits" class="text-red-600 text-sm mt-1">{{ form.errors.splits }}</div>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Post Expense
                        </button>

                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>