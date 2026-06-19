<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-xl font-semibold text-nexboard-on-surface">Profile Settings</h1>
                <p class="text-sm text-nexboard-on-surface-variant mt-0.5">Manage your account details and preferences</p>
            </div>
        </template>

        <div class="page-transition">
            <div class="mx-auto space-y-6">
                <div class="glass-card p-6 sm:p-8 animate-slide-up" style="animation-delay: 0ms">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div class="glass-card p-6 sm:p-8 animate-slide-up" style="animation-delay: 50ms">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="glass-card p-6 sm:p-8 border-red-500/20 animate-slide-up" style="animation-delay: 100ms">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
