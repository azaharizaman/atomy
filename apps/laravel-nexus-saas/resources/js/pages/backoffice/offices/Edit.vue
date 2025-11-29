<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    office: {
        id: string;
        company_id: string;
        code: string;
        name: string;
        type: string;
        country: string;
        postal_code: string;
        address_line_1: string;
        address_line_2: string;
        city: string;
        state: string;
        phone: string;
        email: string;
        is_head_office: boolean;
    };
    companies: Array<{
        id: string;
        name: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Offices',
        href: '/backoffice/offices',
    },
    {
        title: 'Edit',
        href: `/backoffice/offices/${props.office.id}/edit`,
    },
];

const form = useForm({
    company_id: props.office.company_id,
    code: props.office.code,
    name: props.office.name,
    type: props.office.type,
    country: props.office.country,
    postal_code: props.office.postal_code,
    address_line_1: props.office.address_line_1,
    address_line_2: props.office.address_line_2,
    city: props.office.city,
    state: props.office.state,
    phone: props.office.phone,
    email: props.office.email,
    is_head_office: Boolean(props.office.is_head_office),
});

const submit = () => {
    form.put(`/backoffice/offices/${props.office.id}`);
};
</script>

<template>
    <Head title="Edit Office" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Edit Office</h2>
                    <p class="text-muted-foreground">
                        Update the details of the office.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card class="col-span-2">
                    <CardHeader>
                        <CardTitle>Office Details</CardTitle>
                        <CardDescription>
                            Update the details of the office.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="company_id">Company</Label>
                                <select
                                    id="company_id"
                                    v-model="form.company_id"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    required
                                >
                                    <option value="" disabled>Select a company</option>
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.company_id" class="text-sm text-red-500">{{ form.errors.company_id }}</div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="code">Office Code</Label>
                                    <Input
                                        id="code"
                                        v-model="form.code"
                                        type="text"
                                        placeholder="HQ-01"
                                        required
                                    />
                                    <div v-if="form.errors.code" class="text-sm text-red-500">{{ form.errors.code }}</div>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="name">Office Name</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        placeholder="Headquarters"
                                        required
                                    />
                                    <div v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="type">Type</Label>
                                    <select
                                        id="type"
                                        v-model="form.type"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="hq">Headquarters</option>
                                        <option value="branch">Branch</option>
                                        <option value="warehouse">Warehouse</option>
                                        <option value="sales_office">Sales Office</option>
                                    </select>
                                    <div v-if="form.errors.type" class="text-sm text-red-500">{{ form.errors.type }}</div>
                                </div>

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
                            </div>

                            <div class="grid gap-2">
                                <Label for="address_line_1">Address Line 1</Label>
                                <Input
                                    id="address_line_1"
                                    v-model="form.address_line_1"
                                    type="text"
                                    placeholder="123 Main St"
                                />
                                <div v-if="form.errors.address_line_1" class="text-sm text-red-500">{{ form.errors.address_line_1 }}</div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="address_line_2">Address Line 2</Label>
                                <Input
                                    id="address_line_2"
                                    v-model="form.address_line_2"
                                    type="text"
                                    placeholder="Suite 100"
                                />
                                <div v-if="form.errors.address_line_2" class="text-sm text-red-500">{{ form.errors.address_line_2 }}</div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div class="grid gap-2">
                                    <Label for="postal_code">Postal Code</Label>
                                    <Input
                                        id="postal_code"
                                        v-model="form.postal_code"
                                        type="text"
                                        placeholder="50000"
                                        required
                                    />
                                    <div v-if="form.errors.postal_code" class="text-sm text-red-500">{{ form.errors.postal_code }}</div>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="city">City</Label>
                                    <Input
                                        id="city"
                                        v-model="form.city"
                                        type="text"
                                        placeholder="Kuala Lumpur"
                                    />
                                    <div v-if="form.errors.city" class="text-sm text-red-500">{{ form.errors.city }}</div>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="state">State</Label>
                                    <Input
                                        id="state"
                                        v-model="form.state"
                                        type="text"
                                        placeholder="Wilayah Persekutuan"
                                    />
                                    <div v-if="form.errors.state" class="text-sm text-red-500">{{ form.errors.state }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="phone">Phone</Label>
                                    <Input
                                        id="phone"
                                        v-model="form.phone"
                                        type="text"
                                        placeholder="+60312345678"
                                    />
                                    <div v-if="form.errors.phone" class="text-sm text-red-500">{{ form.errors.phone }}</div>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="email">Email</Label>
                                    <Input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        placeholder="office@example.com"
                                    />
                                    <div v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                <Checkbox id="is_head_office" :checked="form.is_head_office" @update:checked="form.is_head_office = $event" />
                                <Label for="is_head_office">Is Head Office?</Label>
                            </div>
                            <div v-if="form.errors.is_head_office" class="text-sm text-red-500">{{ form.errors.is_head_office }}</div>

                            <div class="flex justify-end gap-4">
                                <Button variant="outline" as-child>
                                    <Link href="/backoffice/offices">Cancel</Link>
                                </Button>
                                <Button type="submit" :disabled="form.processing">
                                    Update Office
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
