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
    departments: Array<{
        id: string;
        code: string;
        name: string;
        type: string;
        status: string;
        company: {
            name: string;
        };
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
];

const deleteDepartment = (id: string) => {
    if (confirm('Are you sure you want to delete this department?')) {
        router.delete(`/backoffice/departments/${id}`);
    }
};
</script>

<template>
    <Head title="Departments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Departments</h2>
                    <p class="text-muted-foreground">
                        Manage your organization's departments.
                    </p>
                </div>
                <Button as-child>
                    <Link href="/backoffice/departments/create">Add Department</Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Department List</CardTitle>
                    <CardDescription>
                        A list of all departments in the system.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="relative w-full overflow-auto">
                        <table class="w-full caption-bottom text-sm">
                            <thead class="[&_tr]:border-b">
                                <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Code</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Name</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Company</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Type</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Status</th>
                                    <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="[&_tr:last-child]:border-0">
                                <tr v-for="department in departments" :key="department.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <td class="p-4 align-middle font-medium">{{ department.code }}</td>
                                    <td class="p-4 align-middle">{{ department.name }}</td>
                                    <td class="p-4 align-middle">{{ department.company?.name }}</td>
                                    <td class="p-4 align-middle">{{ department.type }}</td>
                                    <td class="p-4 align-middle">
                                        <Badge v-if="department.status === 'active'" variant="default">Active</Badge>
                                        <Badge v-else variant="secondary">{{ department.status }}</Badge>
                                    </td>
                                    <td class="p-4 align-middle text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button variant="ghost" size="sm" as-child>
                                                <Link :href="`/backoffice/departments/${department.id}/edit`">Edit</Link>
                                            </Button>
                                            <Button variant="destructive" size="sm" @click="deleteDepartment(department.id)">
                                                Delete
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="departments.length === 0">
                                    <td colspan="6" class="p-4 text-center text-muted-foreground">
                                        No departments found.
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
