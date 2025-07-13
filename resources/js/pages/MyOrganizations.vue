<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

interface User {
  organization: string | null;
}

interface Props {
  user: User;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Мои организации',
    href: '/my-organization',
  },
];
</script>

<template>
  <Head title="my-organization" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div v-if="props.user.organization">
          Your organization: {{ props.user.organization }}
        </div>
        <div v-else>
          У вас нет орги
        </div>
      </div>
    </div>
  </AppLayout>
</template>