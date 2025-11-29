<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    staff: Array<{
        id: string;
        employee_id: string;
        first_name: string;
        last_name: string;
        email: string;
        type: string;
        status: string;
    }>;
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
];

const deleteStaff = (id: string) => {
    if (confirm('Are you sure you want to delete this staff member?')) {
        router.delete(`/backoffice/staff/${id}`);
    }
};
</script>

<template>
    <Head title="Staff" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Staff</h2>
                    <p class="text-muted-foreground">
                        Manage your organization's staff members.
                    </p>
                </div>
                <Button as-child>
                    <Link href="/backoffice/staff/create">Add Staff</Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Staff List</CardTitle>
                    <CardDescription>
                        A list of all staff members in the system.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="relative w-full overflow-auto">
                        <table class="w-full caption-bottom text-sm">
                            <thead class="[&_tr]:border-b">
                                <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Employee ID</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Name</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Email</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Type</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Status</th>
                                    <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="[&_tr:last-child]:border-0">
                                <tr v-for="person in staff" :key="person.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <td class="p-4 align-middle font-medium">{{ person.employee_id }}</td>
                                    <td class="p-4 align-middle">{{ person.first_name }} {{ person.last_name }}</td>
                                    <td class="p-4 align-middle">{{ person.email }}</td>
                                    <td class="p-4 align-middle">{{ person.type }}</td>
                                    <td class="p-4 align-middle">
                                        <Badge v-if="person.status === 'active'" variant="default">Active</Badge>
                                        <Badge v-else variant="secondary">{{ person.status }}</Badge>
                                    </td>
                                    <td class="p-4 align-middle text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button variant="ghost" size="sm" as-child>
                                                <Link :href="`/backoffice/staff/${person.id}/edit`">Edit</Link>
                                            </Button>
                                            <Button variant="destructive" size="sm" @click="deleteStaff(person.id)">
                                                Delete
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="staff.length === 0">
                                    <td colspan="6" class="p-4 text-center text-muted-foreground">
                                        No staff found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
