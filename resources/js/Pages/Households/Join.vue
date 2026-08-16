<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    household: Object,
    code: String,
});

const form = useForm({});

const submit = () => {
    form.post(route('households.join', props.code));
};
</script>

<template>
    <Head title="Join Household" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow sm:rounded-lg text-center">
                    <h2 class="text-xl font-semibold mb-4">
                        Join "{{ household.name }}"?
                    </h2>

                    <button
                        @click="submit"
                        :disabled="form.processing"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                    >
                        Confirm Join
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>