<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref, watch, reactive } from 'vue';

const props = defineProps({
    expense: Object,
});
console.log('Splits:', props.expense.splits);
const page = usePage();
const currentUserId = page.props.auth.user.id;

const canEditDetails = computed(() =>
    currentUserId === props.expense.posted_by.id || currentUserId === props.expense.paid_by.id
);
const isPayer = computed(() => currentUserId === props.expense.paid_by.id);

const isEditing = ref(false);

const allMembers = computed(() => props.expense.household.users);

// Everyone currently sharing this expense (from existing splits) + the current payer
const currentParticipantIds = props.expense.splits.map((s) => s.user_id);

const editForm = useForm({
    title: props.expense.title,
    amount: props.expense.amount,
    paid_at: props.expense.paid_at,
    payment_method: ['Cash', 'GCash', 'Bank Transfer'].includes(props.expense.payment_method)
        ? props.expense.payment_method
        : 'Others',
    payment_method_other: ['Cash', 'GCash', 'Bank Transfer'].includes(props.expense.payment_method)
        ? ''
        : props.expense.payment_method,
    proof: null,
    paid_by: props.expense.paid_by.id,
    participant_ids: currentParticipantIds,
    split_type: props.expense.split_type,
    splits: allMembers.value.map((member) => {
        const existing = props.expense.splits.find((s) => s.user_id === member.id);
        return {
            user_id: member.id,
            percentage: existing ? existing.percentage : (member.id === props.expense.paid_by.id
                ? (100 / (currentParticipantIds.length + 1)).toFixed(2)
                : ''),
        };
    }),
});

const membersExcludingPayer = computed(() =>
    allMembers.value.filter((u) => u.id !== editForm.paid_by)
);

watch(() => editForm.paid_by, (newPayer) => {
    editForm.participant_ids = allMembers.value
        .filter((u) => u.id !== newPayer)
        .map((u) => u.id);
});

const toggleParticipant = (userId) => {
    if (editForm.participant_ids.includes(userId)) {
        editForm.participant_ids = editForm.participant_ids.filter((id) => id !== userId);
    } else {
        editForm.participant_ids.push(userId);
    }
};

const activeSplits = computed(() =>
    editForm.splits.filter(
        (s) => s.user_id === editForm.paid_by || editForm.participant_ids.includes(s.user_id)
    )
);

const totalPercentage = computed(() =>
    activeSplits.value.reduce((sum, split) => sum + (parseFloat(split.percentage) || 0), 0)
);

const handleFileChange = (e) => {
    editForm.proof = e.target.files[0];
};

const submitEdit = () => {
    editForm.transform((data) => ({
        ...data,
        payment_method: data.payment_method === 'Others' ? data.payment_method_other : data.payment_method,
        splits: data.split_type === 'custom'
            ? data.splits.filter((s) => s.user_id === data.paid_by || data.participant_ids.includes(s.user_id))
            : [],
        _method: 'put',
    })).post(route('expenses.update', props.expense.id), {
        forceFormData: true,
        onSuccess: () => {
            isEditing.value = false;
        },
    });
};

const localSplits = reactive(
    props.expense.splits.map((s) => ({
        ...s,
        amount_paid_input: s.amount_paid || '',
    }))
);

const statusForm = useForm({
    splits: [],
});

const applyStatusChanges = () => {
    for (const split of localSplits) {
        if (split.status === 'partial' && (!split.amount_paid_input || split.amount_paid_input <= 0)) {
            alert(`Please enter a valid amount for ${split.user.name}.`);
            return;
        }
    }

    statusForm.transform(() => ({
        splits: localSplits.map((s) => ({
            id: s.id,
            status: s.status,
            amount_paid: s.status === 'partial' ? s.amount_paid_input : null,
        })),
    })).patch(route('expenses.splits.update', props.expense.id), {
        preserveScroll: true,
    });
};


</script>

<template>
    <Head :title="expense.title" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ expense.title }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-lg">Details</h3>
                        <button
                            v-if="canEditDetails && !isEditing"
                            @click="isEditing = true"
                            class="text-sm text-indigo-600 hover:underline"
                        >
                            Edit
                        </button>
                    </div>

                    <!-- VIEW MODE -->
                    <div v-if="!isEditing" class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-500">Amount</p>
                            <p class="font-semibold">₱{{ Number(expense.amount).toFixed(2) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Paid by</p>
                            <p>{{ expense.paid_by.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Payment Method</p>
                            <p>{{ expense.payment_method }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Date Paid</p>
                            <p>{{ expense.paid_at }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Sharing this expense</p>
                            <p>{{ expense.splits.map(s => s.user.name).join(', ') }}</p>
                        </div>
                        <div v-if="expense.proof_path">
                            <p class="text-sm text-gray-500 mb-1">Proof of Payment</p>
                            <img :src="`/storage/${expense.proof_path}`" class="max-w-full rounded-md border" />
                        </div>
                    </div>

                    <!-- EDIT MODE -->
                    <form v-else @submit.prevent="submitEdit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" v-model="editForm.title" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
                            <div v-if="editForm.errors.title" class="text-red-600 text-sm mt-1">{{ editForm.errors.title }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Amount</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <span class="inline-flex items-center px-3 border border-r-0 border-gray-300 bg-gray-50 text-gray-500 rounded-l-md">₱</span>
                                <input type="number" step="0.01" v-model="editForm.amount" class="block w-full border-gray-300 rounded-r-md rounded-l-none" />
                            </div>
                            <div v-if="editForm.errors.amount" class="text-red-600 text-sm mt-1">{{ editForm.errors.amount }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Paid by</label>
                            <select v-model="editForm.paid_by" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option v-for="member in allMembers" :key="member.id" :value="member.id">
                                    {{ member.name }}{{ member.id === currentUserId ? ' (you)' : '' }}
                                </option>
                            </select>
                            <div v-if="editForm.errors.paid_by" class="text-red-600 text-sm mt-1">{{ editForm.errors.paid_by }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                            <div class="flex flex-wrap gap-4">
                                <label class="flex items-center gap-2">
                                    <input type="radio" value="Cash" v-model="editForm.payment_method" /> Cash
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" value="GCash" v-model="editForm.payment_method" /> GCash
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" value="Bank Transfer" v-model="editForm.payment_method" /> Bank Transfer
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" value="Others" v-model="editForm.payment_method" /> Others
                                </label>
                            </div>
                            <input
                                v-if="editForm.payment_method === 'Others'"
                                type="text"
                                v-model="editForm.payment_method_other"
                                class="mt-2 block w-full border-gray-300 rounded-md shadow-sm"
                                placeholder="Specify payment method"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date Paid</label>
                            <input type="date" v-model="editForm.paid_at" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Replace Proof of Payment (optional)</label>
                            <input type="file" accept="image/*" @change="handleFileChange" class="mt-1 block w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Who's sharing this expense?</label>
                            <div class="space-y-2">
                                <label
                                    v-for="member in membersExcludingPayer"
                                    :key="member.id"
                                    class="flex items-center gap-2"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="editForm.participant_ids.includes(member.id)"
                                        @change="toggleParticipant(member.id)"
                                    />
                                    {{ member.name }}{{ member.id === currentUserId ? ' (you)' : '' }}
                                </label>
                            </div>
                            <div v-if="editForm.errors.participant_ids" class="text-red-600 text-sm mt-1">{{ editForm.errors.participant_ids }}</div>
                            <p class="text-xs text-gray-400 mt-1">Changing who's included will reset everyone's payment status to unpaid.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Split Type</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2">
                                    <input type="radio" value="even" v-model="editForm.split_type" /> Split Evenly
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" value="custom" v-model="editForm.split_type" /> Custom Values
                                </label>
                            </div>
                        </div>

                        <div v-if="editForm.split_type === 'custom'" class="border rounded-md p-4 bg-gray-50">
                            <p class="text-sm font-medium text-gray-700 mb-2">Set each person's percentage</p>

                            <div
                                v-for="(split, index) in editForm.splits"
                                v-show="split.user_id === editForm.paid_by || editForm.participant_ids.includes(split.user_id)"
                                :key="split.user_id"
                                class="flex items-center gap-2 mb-2"
                            >
                                <span class="w-40 text-sm">
                                    {{ allMembers.find(m => m.id === split.user_id)?.name }}
                                    <span v-if="split.user_id === editForm.paid_by" class="text-gray-400">(paid)</span>
                                </span>
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model="editForm.splits[index].percentage"
                                    class="w-24 border-gray-300 rounded-md shadow-sm"
                                    placeholder="%"
                                />
                                <span class="text-sm text-gray-500">%</span>
                            </div>

                            <p class="text-sm mt-2" :class="totalPercentage === 100 ? 'text-green-600' : 'text-red-600'">
                                Total: {{ totalPercentage.toFixed(2) }}% (must equal 100%)
                            </p>

                            <div v-if="editForm.errors.splits" class="text-red-600 text-sm mt-1">{{ editForm.errors.splits }}</div>
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" :disabled="editForm.processing" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50">
                                Save Changes
                            </button>
                            <button type="button" @click="isEditing = false" class="px-4 py-2 rounded-md border">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="font-semibold text-lg mb-4">Who Owes What</h3>

                    <div v-for="split in localSplits" :key="split.id" class="py-3 border-b last:border-0">
                        <div class="flex justify-between items-center">
                            <span class="font-medium">{{ split.user.name }}</span>
                            <span class="text-sm">₱{{ Number(split.amount_owed).toFixed(2) }} owed</span>
                        </div>

                        <div v-if="!isPayer" class="text-sm mt-1" :class="{
                            'text-green-600': split.status === 'paid',
                            'text-yellow-600': split.status === 'partial',
                            'text-red-600': split.status === 'unpaid',
                        }">
                            {{ split.status }}
                            <span v-if="split.status === 'partial'">(paid ₱{{ Number(split.amount_paid).toFixed(2) }} so far)</span>
                        </div>

                        <div v-else class="mt-2 flex items-center gap-2 flex-wrap">
                            <button
                                @click="split.status = 'unpaid'"
                                type="button"
                                class="text-xs px-2 py-1 rounded border"
                                :class="split.status === 'unpaid' ? 'bg-red-100 border-red-300 text-red-700' : 'border-gray-300'"
                            >
                                Unpaid
                            </button>
                            <button
                                @click="split.status = 'paid'"
                                type="button"
                                class="text-xs px-2 py-1 rounded border"
                                :class="split.status === 'paid' ? 'bg-green-100 border-green-300 text-green-700' : 'border-gray-300'"
                            >
                                Paid
                            </button>
                            <button
                                @click="split.status = 'partial'"
                                type="button"
                                class="text-xs px-2 py-1 rounded border"
                                :class="split.status === 'partial' ? 'bg-yellow-100 border-yellow-300 text-yellow-700' : 'border-gray-300'"
                            >
                                Partial
                            </button>
                            <input
                                v-if="split.status === 'partial'"
                                type="number"
                                step="0.01"
                                v-model="split.amount_paid_input"
                                placeholder="Amount"
                                class="text-xs w-24 border-gray-300 rounded-md"
                            />
                        </div>
                    </div>

                    <div v-if="isPayer" class="mt-4 pt-4 border-t flex items-center gap-3">
                        <button
                            @click="applyStatusChanges"
                            :disabled="statusForm.processing"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Apply Changes
                        </button>
                        <span v-if="statusForm.recentlySuccessful" class="text-sm text-green-600">Saved!</span>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>