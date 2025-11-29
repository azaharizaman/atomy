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
    companies: Array<{ id: string; name: string }>;
    departments: Array<{ id: string; name: string }>;
    offices: Array<{ id: string; name: string }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Staff',
        href: '/backoffice/staff',
    },
    {
        title: 'Create',
        href: '/backoffice/staff/create',
    },
];

const form = useForm({
    company_id: '',
    employee_id: '',
    staff_code: '',
    first_name: '',
    last_name: '',
    middle_name: '',
    email: '',
    phone: '',
    mobile: '',
    type: 'permanent',
    status: 'active',
    position: '',
    hire_date: '',
    department_id: '',
    office_id: '',
});

const submit = () => {
    form.post('/backoffice/staff');
};
</script>

<template>
    <Head title="Create Staff" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Create Staff</h2>
                    <p class="text-muted-foreground">
                        Add a new staff member to your organization.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card class="col-span-2">
                    <CardHeader>
                        <CardTitle>Staff Details</CardTitle>
                        <CardDescription>
                            Enter the details of the new staff member.
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
                                    <Label for="employee_id">Employee ID</Label>
                                    <Input
                                        id="employee_id"
                                        v-model="form.employee_id"
                                        type="text"
                                        placeholder="EMP-001"
                                        required
                                    />
                                    <div v-if="form.errors.employee_id" class="text-sm text-red-500">{{ form.errors.employee_id }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div class="grid gap-2">
                                    <Label for="first_name">First Name</Label>
                                    <Input
                                        id="first_name"
                                        v-model="form.first_name"
                                        type="text"
                                        placeholder="John"
                                        required
                                    />
                                    <div v-if="form.errors.first_name" class="text-sm text-red-500">{{ form.errors.first_name }}</div>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="middle_name">Middle Name</Label>
                                    <Input
                                        id="middle_name"
                                        v-model="form.middle_name"
                                        type="text"
                                        placeholder="Quincy"
                                    />
                                    <div v-if="form.errors.middle_name" class="text-sm text-red-500">{{ form.errors.middle_name }}</div>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="last_name">Last Name</Label>
                                    <Input
                                        id="last_name"
                                        v-model="form.last_name"
                                        type="text"
                                        placeholder="Doe"
                                        required
                                    />
                                    <div v-if="form.errors.last_name" class="text-sm text-red-500">{{ form.errors.last_name }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="email">Email</Label>
                                    <Input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        placeholder="john.doe@example.com"
                                        required
                                    />
                                    <div v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</div>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="staff_code">Staff Code (Optional)</Label>
                                    <Input
                                        id="staff_code"
                                        v-model="form.staff_code"
                                        type="text"
                                        placeholder="SC-001"
                                    />
                                    <div v-if="form.errors.staff_code" class="text-sm text-red-500">{{ form.errors.staff_code }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="phone">Phone</Label>
                                    <Input
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        placeholder="+1234567890"
                                    />
                                    <div v-if="form.errors.phone" class="text-sm text-red-500">{{ form.errors.phone }}</div>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="mobile">Mobile</Label>
                                    <Input
                                        id="mobile"
                                        v-model="form.mobile"
                                        type="tel"
                                        placeholder="+1987654321"
                                    />
                                    <div v-if="form.errors.mobile" class="text-sm text-red-500">{{ form.errors.mobile }}</div>
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
                                        <option value="permanent">Permanent</option>
                                        <option value="contract">Contract</option>
                                        <option value="intern">Intern</option>
                                        <option value="part_time">Part Time</option>
                                        <option value="temporary">Temporary</option>
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
                                        <option value="terminated">Terminated</option>
                                        <option value="resigned">Resigned</option>
                                        <option value="on_leave">On Leave</option>
                                    </select>
                                    <div v-if="form.errors.status" class="text-sm text-red-500">{{ form.errors.status }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="position">Position</Label>
                                    <Input
                                        id="position"
                                        v-model="form.position"
                                        type="text"
                                        placeholder="Software Engineer"
                                    />
                                    <div v-if="form.errors.position" class="text-sm text-red-500">{{ form.errors.position }}</div>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="hire_date">Hire Date</Label>
                                    <Input
                                        id="hire_date"
                                        v-model="form.hire_date"
                                        type="date"
                                        required
                                    />
                                    <div v-if="form.errors.hire_date" class="text-sm text-red-500">{{ form.errors.hire_date }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="department_id">Department</Label>
                                    <select
                                        id="department_id"
                                        v-model="form.department_id"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="">None</option>
                                        <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                            {{ dept.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.department_id" class="text-sm text-red-500">{{ form.errors.department_id }}</div>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="office_id">Office</Label>
                                    <select
                                        id="office_id"
                                        v-model="form.office_id"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="">None</option>
                                        <option v-for="office in offices" :key="office.id" :value="office.id">
                                            {{ office.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.office_id" class="text-sm text-red-500">{{ form.errors.office_id }}</div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-4">
                                <Button variant="outline" as-child>
                                    <Link href="/backoffice/staff">Cancel</Link>
                                </Button>
                                <Button type="submit" :disabled="form.processing">
                                    Create Staff
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
