<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
});

const submit = () => {
    form.post(route('households.store'));
};
</script>

<template>
    <Head title="Create Household" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Household
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <form @submit.prevent="submit">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Household Name
                            </label>
                            <input
                                id="name"
                                type="text"
                                v-model="form.name"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                placeholder="e.g. The Apartment"
                            />
                            <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <div class="mt-4">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                            >
                                Create Household
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>