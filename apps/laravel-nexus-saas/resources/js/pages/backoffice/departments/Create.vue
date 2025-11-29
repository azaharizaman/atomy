<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    companies: Array<{
        id: string;
        name: string;
    }>;
    departments: Array<{
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
        title: 'Departments',
        href: '/backoffice/departments',
    },
    {
        title: 'Create',
        href: '/backoffice/departments/create',
    },
];

const form = useForm({
    company_id: '',
    parent_id: null,
    code: '',
    name: '',
    type: 'engineering',
    status: 'active',
    manager_id: null,
    cost_center: '',
    budget_amount: 0,
    description: '',
});

const submit = () => {
    form.post('/backoffice/departments');
};
</script>

<template>
    <Head title="Create Department" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Create Department</h2>
                    <p class="text-muted-foreground">
                        Add a new department to your organization.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card class="col-span-2">
                    <CardHeader>
                        <CardTitle>Department Details</CardTitle>
                        <CardDescription>
                            Enter the details of the new department.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
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

                                <div class="grid gap-2">
                                    <Label for="parent_id">Parent Department (Optional)</Label>
                                    <select
                                        id="parent_id"
                                        v-model="form.parent_id"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option :value="null">None</option>
                                        <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                            {{ dept.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.parent_id" class="text-sm text-red-500">{{ form.errors.parent_id }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="code">Department Code</Label>
                                    <Input
                                        id="code"
                                        v-model="form.code"
                                        type="text"
                                        placeholder="ENG-01"
                                        required
                                    />
                                    <div v-if="form.errors.code" class="text-sm text-red-500">{{ form.errors.code }}</div>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="name">Department Name</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        placeholder="Engineering"
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
                                        <option value="admin">Admin</option>
                                        <option value="sales">Sales</option>
                                        <option value="marketing">Marketing</option>
                                        <option value="engineering">Engineering</option>
                                        <option value="hr">HR</option>
                                        <option value="finance">Finance</option>
                                        <option value="operations">Operations</option>
                                        <option value="it">IT</option>
                                        <option value="support">Support</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <div v-if="form.errors.type" class="text-sm text-red-500">{{ form.errors.type }}</div>
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
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="cost_center">Cost Center</Label>
                                    <Input
                                        id="cost_center"
                                        v-model="form.cost_center"
                                        type="text"
                                        placeholder="CC-100"
                                    />
                                    <div v-if="form.errors.cost_center" class="text-sm text-red-500">{{ form.errors.cost_center }}</div>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="budget_amount">Budget Amount</Label>
                                    <Input
                                        id="budget_amount"
                                        v-model="form.budget_amount"
                                        type="number"
                                        step="0.01"
                                        placeholder="0.00"
                                    />
                                    <div v-if="form.errors.budget_amount" class="text-sm text-red-500">{{ form.errors.budget_amount }}</div>
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="description">Description</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Enter department description..."
                                />
                                <div v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</div>
                            </div>

                            <div class="flex justify-end gap-4">
                                <Button variant="outline" as-child>
                                    <Link href="/backoffice/departments">Cancel</Link>
                                </Button>
                                <Button type="submit" :disabled="form.processing">
                                    Create Department
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
