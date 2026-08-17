<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    households: Array,
    totalYouOwe: Number,
    totalOwedToYou: Number,
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Stat Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="bg-white p-6 shadow sm:rounded-lg">
                        <p class="text-sm text-gray-500">Total You Owe</p>
                        <p class="text-3xl font-semibold text-red-600 mt-1">
                            ₱{{ Number(totalYouOwe).toFixed(2) }}
                        </p>
                    </div>

                    <div class="bg-white p-6 shadow sm:rounded-lg">
                        <p class="text-sm text-gray-500">Total Owed to You</p>
                        <p class="text-3xl font-semibold text-green-600 mt-1">
                            ₱{{ Number(totalOwedToYou).toFixed(2) }}
                        </p>
                    </div>
                </div>

                <!-- Households -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-lg">Your Households</h3>
                        <Link
                            :href="route('households.index')"
                            class="bg-indigo-600 text-white px-3 py-1.5 rounded-md text-sm hover:bg-indigo-700"
                        >
                            Create/Join a Household
                        </Link>
                    </div>

                    <div v-if="households.length === 0" class="text-gray-500 text-sm">
                        You're not part of any households yet.
                    </div>

                    <ul>
                        <li
                            v-for="household in households"
                            :key="household.id"
                            class="border-b last:border-0"
                        >
                            <Link
                                :href="route('households.show', household.id)"
                                class="block py-3 hover:bg-gray-50"
                            >
                                {{ household.name }}
                            </Link>
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>