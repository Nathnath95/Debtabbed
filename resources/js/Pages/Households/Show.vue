<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    household: Object,
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
        </div>
    </AuthenticatedLayout>
</template>