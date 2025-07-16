<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 relative" style="background: #fafbfc url('data:image/svg+xml;utf8,<svg width=\'20\' height=\'20\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'><circle cx=\'2\' cy=\'2\' r=\'1\' fill=\'%23d1d5db\'/></svg>') repeat;">
    <button
      class="absolute top-8 left-8 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-2xl px-8 py-3 text-lg font-medium transition-colors"
      @click="goBack"
    >
      Вернуться
    </button>
    <div class="text-4xl font-bold mb-6 select-none">
      <img src="/svg/icon/Group 26.svg" alt="этобаза" class="h-12 md:h-16 w-auto inline-block align-middle" />
    </div>
    <h1 class="text-4xl font-light mb-4 text-center text-black">Получить ключ API</h1>
    <div v-if="!result || (result && result.success && !result.api_key)" class="mb-6 text-center text-base text-red-600 max-w-md">
      Этот ключ будет показан вам только 1 раз, сохраните его где-нибудь
    </div>
    <button
      class="bg-blue-600 hover:bg-blue-700 text-white rounded-2xl px-12 py-4 text-lg font-medium mb-8 transition-colors disabled:bg-blue-300 disabled:cursor-not-allowed"
      :disabled="loading"
      @click="generateApiKey"
    >
      {{ loading ? '...' : 'сгенерировать' }}
    </button>
    <div v-if="result" class="mt-4 w-full max-w-md flex flex-col items-center">
      <div v-if="result.success" class="w-full">
        <div class="text-base mb-2 text-center">Ваш API ключ:</div>
        <div class="font-mono text-base bg-gray-100 rounded px-3 py-2 mb-2 break-all text-center">{{ result.api_key }}</div>
        <div class="text-sm text-gray-500 text-center">Действует до: {{ result.expires_at }}</div>
      </div>
      <div v-else class="text-red-600 font-medium text-center mb-2">{{ result.message }}</div>
      <div v-if="result.next_allowed" class="text-xs text-gray-400 text-center">Следующая генерация: {{ result.next_allowed }}</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const loading = ref(false);
const result = ref<any>(null);

function goBack() {
  router.visit('/');
}

async function generateApiKey() {
  loading.value = true;
  result.value = null;
  try {
    const { data } = await axios.post('/generate-api-key');
    result.value = data;
  } catch (e: any) {
    result.value = e.response?.data || { success: false, message: 'Ошибка запроса' };
  } finally {
    loading.value = false;
  }
}
</script> 