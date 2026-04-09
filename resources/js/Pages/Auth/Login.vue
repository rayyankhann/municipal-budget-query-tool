<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const fillCredentials = (email) => {
    form.email = email;
    form.password = 'password';
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div class="mb-6 text-center">
            <h2 class="text-xl font-bold text-gray-800">Municipal Budget Tool</h2>
            <p class="mt-1 text-sm text-gray-500">Sign in to access the budget dashboard</p>
        </div>

        <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
            <p class="mb-3 text-sm font-semibold text-blue-800">Demo Credentials</p>
            <div class="space-y-2">
                <button
                    type="button"
                    @click="fillCredentials('admin@city.gov')"
                    class="w-full rounded-md border border-blue-300 bg-white px-3 py-2 text-left text-sm transition hover:bg-blue-100"
                >
                    <span class="font-medium text-gray-800">Admin</span>
                    <span class="ml-2 text-gray-500">admin@city.gov / password</span>
                    <span class="ml-1 inline-block rounded bg-indigo-100 px-2 py-0.5 text-xs text-indigo-700">All Departments</span>
                </button>
                <button
                    type="button"
                    @click="fillCredentials('parks@city.gov')"
                    class="w-full rounded-md border border-blue-300 bg-white px-3 py-2 text-left text-sm transition hover:bg-blue-100"
                >
                    <span class="font-medium text-gray-800">Dept Head</span>
                    <span class="ml-2 text-gray-500">parks@city.gov / password</span>
                    <span class="ml-1 inline-block rounded bg-green-100 px-2 py-0.5 text-xs text-green-700">Parks & Rec</span>
                </button>
            </div>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">Remember me</span>
                </label>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
