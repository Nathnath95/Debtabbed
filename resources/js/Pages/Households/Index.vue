<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    households: Array,
});
</script>

<template>
    <Head title="Your Households" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Your Households
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-4">
                    <Link
                        :href="route('households.create')"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"
                    >
                        + Create Household
                    </Link>
                </div>

                <div class="bg-white shadow sm:rounded-lg">
                    <ul>
                        <li
                            v-for="household in households"
                            :key="household.id"
                            class="border-b last:border-0"
                        >
                            <Link
                                :href="route('households.show', household.id)"
                                class="block p-4 hover:bg-gray-50"
                            >
                                {{ household.name }}
                            </Link>
                        </li>
                    </ul>

                    <div v-if="households.length === 0" class="p-4 text-gray-500">
                        You're not part of any households yet.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>