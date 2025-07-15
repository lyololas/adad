<script setup lang="ts">
import { router } from '@inertiajs/vue3';

function loginWithYandex() {
  router.visit('/auth/yandex');
}

function goBack() {
  router.visit('/');
}
</script>

<template>
  <div class="min-h-screen flex flex-col items-center justify-center relative" style="background: #fafbfc url('data:image/svg+xml;utf8,<svg width=\'20\' height=\'20\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'><circle cx=\'2\' cy=\'2\' r=\'1\' fill=\'%23d1d5db\'/></svg>') repeat;">
    <button
      class="absolute top-8 left-8 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-2xl px-8 py-3 text-lg font-medium transition-colors"
      @click="goBack"
    >
      Вернуться
    </button>
    <div class="text-4xl font-bold mb-6 select-none">
      <span class="text-red-600">это</span><span class="text-blue-600">база</span>
    </div>
    <h1 class="text-4xl font-light mb-8 text-center text-black">Войти через Yandex</h1>
    <button
      class="bg-blue-600 hover:bg-blue-700 text-white rounded-2xl px-12 py-4 text-lg font-medium transition-colors"
      @click="loginWithYandex"
    >
      Войти
    </button>
  </div>
</template>