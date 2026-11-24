<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/StaffController';
import FormError from '@/components/FormError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    buttonClass,
    inputClass,
    labelClass,
    mutedClass,
    panelClass,
} from '@/lib/ui';
import type { TypeOption } from '@/types/tracker';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    staff: Array<{
        id: number;
        name: string;
        email: string;
        role: string;
    }>;
    roles: TypeOption[];
}>();

const page = usePage();
const firmName = computed(
    () => page.props.auth.user?.organization?.name ?? 'your firm',
);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'staff',
});

function submit() {
    form.post(store.url(), {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Users" />
        <div>
            <h1 class="text-2xl font-semibold">Users</h1>
            <p :class="mutedClass">
                Add people to {{ firmName }}. They can sign in and see only this
                firm.
            </p>
        </div>

        <form
            :class="panelClass"
            class="flex max-w-xl flex-col gap-3"
            @submit.prevent="submit"
        >
            <h2 class="font-medium">Add a user</h2>
            <label :class="labelClass">
                Name
                <input v-model="form.name" :class="inputClass" required />
            </label>
            <label :class="labelClass">
                Email
                <input
                    v-model="form.email"
                    type="email"
                    :class="inputClass"
                    required
                />
            </label>
            <label :class="labelClass">
                Password
                <input
                    v-model="form.password"
                    type="password"
                    :class="inputClass"
                    required
                />
            </label>
            <label :class="labelClass">
                Confirm password
                <input
                    v-model="form.password_confirmation"
                    type="password"
                    :class="inputClass"
                    required
                />
            </label>
            <label :class="labelClass">
                Role
                <select v-model="form.role" :class="inputClass">
                    <option
                        v-for="role in roles"
                        :key="role.value"
                        :value="role.value"
                    >
                        {{ role.label }}
                    </option>
                </select>
            </label>
            <FormError :message="form.errors.name" />
            <FormError :message="form.errors.email" />
            <FormError :message="form.errors.password" />
            <FormError :message="form.errors.role" />
            <button
                type="submit"
                :class="buttonClass"
                class="self-start"
                :disabled="form.processing"
            >
                Add user
            </button>
        </form>

        <div :class="panelClass" class="overflow-x-auto p-0">
            <table class="w-full text-left text-sm">
                <thead :class="mutedClass">
                    <tr>
                        <th class="px-4 py-2 font-medium">Name</th>
                        <th class="px-4 py-2 font-medium">Email</th>
                        <th class="px-4 py-2 font-medium">Role</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="person in staff"
                        :key="person.id"
                        class="border-t border-[#e3e3e0] dark:border-[#3E3E3A]"
                    >
                        <td class="px-4 py-3">{{ person.name }}</td>
                        <td class="px-4 py-3">{{ person.email }}</td>
                        <td class="px-4 py-3 capitalize">{{ person.role }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
