<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    company: {
        id: string;
        name: string;
        registration_number: string;
        country: string;
        currency: string;
        timezone: string;
        status: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Companies',
        href: '/backoffice/companies',
    },
    {
        title: 'Edit',
        href: `/backoffice/companies/${props.company.id}/edit`,
    },
];

const form = useForm({
    name: props.company.name,
    registration_number: props.company.registration_number,
    country: props.company.country,
    currency: props.company.currency,
    timezone: props.company.timezone,
    status: props.company.status,
});

const submit = () => {
    form.put(`/backoffice/companies/${props.company.id}`);
};
</script>

<template>
    <Head title="Edit Company" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Edit Company</h2>
                    <p class="text-muted-foreground">
                        Update company details.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card class="col-span-2">
                    <CardHeader>
                        <CardTitle>Company Details</CardTitle>
                        <CardDescription>
                            Update the details of the company.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="name">Company Name</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Acme Corp"
                                    required
                                />
                                <div v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="registration_number">Registration Number</Label>
                                <Input
                                    id="registration_number"
                                    v-model="form.registration_number"
                                    type="text"
                                    placeholder="123456-X"
                                />
                                <div v-if="form.errors.registration_number" class="text-sm text-red-500">{{ form.errors.registration_number }}</div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="country">Country (ISO Code)</Label>
                                    <Input
                                        id="country"
                                        v-model="form.country"
                                        type="text"
                                        placeholder="MY"
                                        maxlength="2"
                                        required
                                    />
                                    <div v-if="form.errors.country" class="text-sm text-red-500">{{ form.errors.country }}</div>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="currency">Currency</Label>
                                    <Input
                                        id="currency"
                                        v-model="form.currency"
                                        type="text"
                                        placeholder="MYR"
                                        maxlength="3"
                                        required
                                    />
                                    <div v-if="form.errors.currency" class="text-sm text-red-500">{{ form.errors.currency }}</div>
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="timezone">Timezone</Label>
                                <Input
                                    id="timezone"
                                    v-model="form.timezone"
                                    type="text"
                                    placeholder="Asia/Kuala_Lumpur"
                                    required
                                />
                                <div v-if="form.errors.timezone" class="text-sm text-red-500">{{ form.errors.timezone }}</div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="status">Status</Label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <div v-if="form.errors.status" class="text-sm text-red-500">{{ form.errors.status }}</div>
                            </div>

                            <div class="flex justify-end gap-4">
                                <Button variant="outline" as-child>
                                    <Link href="/backoffice/companies">Cancel</Link>
                                </Button>
                                <Button type="submit" :disabled="form.processing">
                                    Update Company
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
