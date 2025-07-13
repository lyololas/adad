<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

interface User {
  organization: string | null;
}

interface Props {
  user: User;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Tilda exporter',
    href: '/tilda-exporter',
  },
];


const showInput = ref(false);
const inputText = ref('');
const parsedRows = ref<string[][]>([]);

function handleTildaClick() {
  showInput.value = true;
}


function parseInput() {
  parsedRows.value = inputText.value
    .split('\n')
    .filter(line => line.trim())
    .map(line => line.split(/\t|,/).map(cell => cell.trim()));
  showInput.value = false;
}
</script>

<template>
  <Head title="adad" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <div class="mb-4">
        <div v-if="parsedRows.length" class="mb-4">
          <div class="font-bold mb-2">info itself</div>
          <table class="min-w-full border border-gray-300 rounded">
            <tbody>
              <tr v-for="(row, rowIndex) in parsedRows" :key="rowIndex">
                <td
                  v-for="(cell, cellIndex) in row"
                  :key="cellIndex"
                  class="border px-2 py-1"
                >
                  {{ cell }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="showInput">
          <textarea
            v-model="inputText"
            rows="5"
            class="w-full border rounded p-2 mb-2"
            placeholder="Paste your data here (CSV or tab-separated)"
          ></textarea>
          <button
            class="bg-blue-500 text-white px-4 py-2 rounded"
            @click="parseInput"
          >
            Parse
          </button>
        </div>
      </div>
      <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <button
          class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300"
          @click="handleTildaClick"
          v-if="!showInput"
        >
          TILDA
        </button>
      </div>
    </div>
  </AppLayout>
</template>