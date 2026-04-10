<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
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
            <h2 class="text-xl font-bold text-white">Municipal Budget Tool</h2>
            <p class="mt-1 text-sm text-gray-400">Sign in to access the budget dashboard</p>
        </div>

        <div class="mb-6 rounded-lg border border-blue-800 bg-blue-950/50 p-4">
            <p class="mb-3 text-sm font-semibold text-blue-300">Demo Credentials</p>
            <div class="space-y-2">
                <button
                    type="button"
                    @click="fillCredentials('admin@city.gov')"
                    class="w-full rounded-md border border-gray-600 bg-gray-700 px-3 py-2 text-left text-sm transition hover:bg-gray-600"
                >
                    <span class="font-medium text-white">Admin</span>
                    <span class="ml-2 text-gray-400">admin@city.gov / password</span>
                    <span class="ml-1 inline-block rounded bg-indigo-900/50 px-2 py-0.5 text-xs text-indigo-300">All Departments</span>
                </button>
                <button
                    type="button"
                    @click="fillCredentials('parks@city.gov')"
                    class="w-full rounded-md border border-gray-600 bg-gray-700 px-3 py-2 text-left text-sm transition hover:bg-gray-600"
                >
                    <span class="font-medium text-white">Dept Head</span>
                    <span class="ml-2 text-gray-400">parks@city.gov / password</span>
                    <span class="ml-1 inline-block rounded bg-green-900/50 px-2 py-0.5 text-xs text-green-300">Parks & Rec</span>
                </button>
            </div>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" class="text-gray-300" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full border-gray-600 bg-gray-700 text-white placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" class="text-gray-300" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full border-gray-600 bg-gray-700 text-white placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-400">Remember me</span>
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
