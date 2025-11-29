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
    offices: Array<{
        id: string;
        code: string;
        name: string;
        type: string;
        country: string;
        is_head_office: boolean;
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
        title: 'Offices',
        href: '/backoffice/offices',
    },
];

const deleteOffice = (id: string) => {
    if (confirm('Are you sure you want to delete this office?')) {
        router.delete(`/backoffice/offices/${id}`);
    }
};
</script>

<template>
    <Head title="Offices" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Offices</h2>
                    <p class="text-muted-foreground">
                        Manage your organization's offices.
                    </p>
                </div>
                <Button as-child>
                    <Link href="/backoffice/offices/create">Add Office</Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Office List</CardTitle>
                    <CardDescription>
                        A list of all offices in the system.
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
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Country</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Head Office</th>
                                    <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="[&_tr:last-child]:border-0">
                                <tr v-for="office in offices" :key="office.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <td class="p-4 align-middle font-medium">{{ office.code }}</td>
                                    <td class="p-4 align-middle">{{ office.name }}</td>
                                    <td class="p-4 align-middle">{{ office.company?.name }}</td>
                                    <td class="p-4 align-middle">{{ office.type }}</td>
                                    <td class="p-4 align-middle">{{ office.country }}</td>
                                    <td class="p-4 align-middle">
                                        <Badge v-if="office.is_head_office" variant="default">Yes</Badge>
                                        <Badge v-else variant="outline">No</Badge>
                                    </td>
                                    <td class="p-4 align-middle text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button variant="ghost" size="sm" as-child>
                                                <Link :href="`/backoffice/offices/${office.id}/edit`">Edit</Link>
                                            </Button>
                                            <Button variant="destructive" size="sm" @click="deleteOffice(office.id)">
                                                Delete
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="offices.length === 0">
                                    <td colspan="7" class="p-4 text-center text-muted-foreground">
                                        No offices found.
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
