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
    companies: Array<{
        id: string;
        name: string;
        registration_number: string;
        country: string;
        status: string;
    }>;
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
];

const deleteCompany = (id: string) => {
    if (confirm('Are you sure you want to delete this company?')) {
        router.delete(`/backoffice/companies/${id}`);
    }
};
</script>

<template>
    <Head title="Companies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Companies</h2>
                    <p class="text-muted-foreground">
                        Manage your organization's companies.
                    </p>
                </div>
                <Button as-child>
                    <Link href="/backoffice/companies/create">Add Company</Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Company List</CardTitle>
                    <CardDescription>
                        A list of all companies in the system.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="relative w-full overflow-auto">
                        <table class="w-full caption-bottom text-sm">
                            <thead class="[&_tr]:border-b">
                                <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Name</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Reg. Number</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Country</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Status</th>
                                    <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="[&_tr:last-child]:border-0">
                                <tr v-for="company in companies" :key="company.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <td class="p-4 align-middle font-medium">{{ company.name }}</td>
                                    <td class="p-4 align-middle">{{ company.registration_number }}</td>
                                    <td class="p-4 align-middle">{{ company.country }}</td>
                                    <td class="p-4 align-middle">
                                        <Badge variant="outline">{{ company.status }}</Badge>
                                    </td>
                                    <td class="p-4 align-middle text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button variant="ghost" size="sm" as-child>
                                                <Link :href="`/backoffice/companies/${company.id}/edit`">Edit</Link>
                                            </Button>
                                            <Button variant="destructive" size="sm" @click="deleteCompany(company.id)">
                                                Delete
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="companies.length === 0">
                                    <td colspan="5" class="p-4 text-center text-muted-foreground">
                                        No companies found.
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
