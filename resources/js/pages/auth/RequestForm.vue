<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const API_URL = '/api/upload';
const form = ref({ name: '', phone: '', consent: false });
const loading = ref(false);
const result = ref<null | { success: boolean; message?: string; url?: string }> (null);

function goBack() {
  router.visit('/');
}

function goToConsent() {
  router.visit('/consent-form');
}

async function submitForm() {
  if (!form.value.consent) {
    result.value = { success: false, message: 'Необходимо согласие на обработку данных.' };
    return;
  }
  loading.value = true;
  result.value = null;
  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        forms: [
          {
            name: form.value.name,
            phone: form.value.phone,
            consent: form.value.consent ? 'yes' : 'no',
          },
        ],
      }),
    });
    const data = await response.json();
    result.value = data;
    if (data.success) {
      form.value = { name: '', phone: '', consent: false };
    }
  } catch (e: any) {
    result.value = { success: false, message: e.message };
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="min-h-screen flex flex-col items-center justify-center relative bg-gray-50" style="background: #fafbfc url('data:image/svg+xml;utf8,<svg width=\'20\' height=\'20\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'><circle cx=\'2\' cy=\'2\' r=\'1\' fill=\'%23d1d5db\'/></svg>') repeat; font-family: 'Montserrat', 'Arial', sans-serif;">
    <button
      class="absolute top-8 left-8 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-2xl px-8 py-3 text-lg font-medium transition-colors"
      @click="goBack"
    >
      Вернуться
    </button>
    <div class="text-4xl font-bold mb-6 select-none text-black">
      <img src="/svg/icon/Group 26.svg" alt="этобаза" class="h-12 md:h-16 w-auto inline-block align-middle" />
    </div>
    <h1 class="text-5xl font-light mb-8 text-center text-black">Оставить заявку</h1>
    <form class="flex flex-col gap-4 w-full max-w-xs text-black" @submit.prevent="submitForm">
      <label class="flex flex-col gap-1 text-black">
        <span class="text-lg text-black">имя</span>
        <input v-model="form.name" type="text" placeholder="имя" class="rounded-xl border border-blue-300 px-4 py-3 text-lg focus:outline-none focus:border-blue-500 text-black placeholder-gray-400" required />
      </label>
      <label class="flex flex-col gap-1 text-black">
        <span class="text-lg text-black">номер телефона</span>
        <input v-model="form.phone" type="tel" placeholder="+7 (999) 123-45-67" class="rounded-xl border border-blue-300 px-4 py-3 text-lg focus:outline-none focus:border-blue-500 text-black placeholder-gray-400" required />
      </label>
      <label class="flex items-center gap-2 text-black">
        <input v-model="form.consent" type="checkbox" />
        <span class="text-sm">
          я даю свое согласие на
          <span class="text-blue-600 underline cursor-pointer" @click="goToConsent">обработку персональных данных</span>
        </span>
      </label>
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white rounded-2xl px-8 py-3 text-lg font-medium transition-colors mt-2" :disabled="loading">
        {{ loading ? 'Отправка...' : 'перезвоните мне' }}
      </button>
    </form>
    <div v-if="result" class="mt-4 w-full max-w-xs">
      <div v-if="result.success" class="text-green-600 text-center">
        Заявка отправлена!
      </div>
      <div v-else class="text-red-600 text-center">
        Ошибка: {{ result.message }}
      </div>
    </div>
  </div>
</template> 