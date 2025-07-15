<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';

function goToApiKey() {
  router.visit('/settings/api-key');
}

const words = ['база', 'просто', 'быстро', 'безопасно', 'доступно'];
const currentIndex = ref(0);
let intervalId: number | undefined;

onMounted(() => {
  intervalId = window.setInterval(() => {
    currentIndex.value = (currentIndex.value + 1) % words.length;
  }, 2000);
});

onBeforeUnmount(() => {
  if (intervalId) clearInterval(intervalId);
});
</script>

<template>
  <div class="w-full min-h-screen bg-gray-50 overflow-x-hidden" style="background: #fafbfc url('data:image/svg+xml;utf8,<svg width=\'20\' height=\'20\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'><circle cx=\'2\' cy=\'2\' r=\'1\' fill=\'%23d1d5db\'/></svg>') repeat; font-family: 'Montserrat', 'Arial', sans-serif;">
    <section class="relative flex flex-col items-center justify-center min-h-screen px-4">
      <button
        class="absolute top-8 right-8 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl px-8 py-3 text-lg font-medium transition-colors z-10"
        @click="goToApiKey"
      >
        попробовать бесплатно
      </button>
      <div class="flex flex-col items-center justify-center w-full">
        <div class="text-6xl font-bold mb-8 select-none flex items-center justify-center text-black" style="height: 72px;">
          <span class="text-red-600">это&nbsp;</span>
          <span class="inline-block align-middle relative min-w-[80px] pl-0 text-left" style="height: 72px;">
            <transition-group name="fade-slide" tag="span" class="w-full h-full flex items-center">
                                <span
                v-for="(word, idx) in words"
                v-show="currentIndex === idx"
                :key="word"
                class="absolute left-0 top-0 w-full h-full flex items-center text-blue-600 text-left"
                style="font-family: inherit;"
              >
                {{ word }}
                                </span>
            </transition-group>
                            </span>
        </div>
        <div class="text-xl text-center mb-8 max-w-xl text-black">
          Наш сервис подключается к вашему сайту и автоматически сохраняет клиентские данные в ваш Яндекс.Диск. Надёжно, легально, без посредников.
        </div>
        <button
          class="bg-blue-600 hover:bg-blue-700 text-white rounded-2xl px-10 py-4 text-lg font-medium transition-colors"
          @click="goToApiKey"
        >
          попробовать бесплатно
        </button>
      </div>
    </section>
    <section class="flex flex-col items-center justify-center min-h-screen px-4 py-0">
      <div class="flex flex-col items-center w-full">
        <img src="/svg/Group 23.svg" alt="Как это работает" class="w-full max-w-[110rem] min-w-[340px] md:min-w-[700px] xl:min-w-[900px]" />
      </div>
    </section>
    <section class="flex flex-col md:flex-row min-h-screen px-4 gap-8">
      <div class="flex-1 max-w-2xl flex items-center justify-center h-full">
        <div class="flex flex-col justify-center items-start w-full max-w-2xl">
          <div class="text-5xl md:text-6xl font-normal mb-12 text-black text-left leading-tight w-full">Вам это подходит, если вы</div>
          <ul class="space-y-10 text-3xl text-black text-left w-full">
            <li class="flex items-start"><span class="inline-block mr-6 mt-2"><svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 10H28" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/><path d="M22 5L28 10L22 15" stroke="#2563eb" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>малый предприниматель (ИП)</li>
            <li class="flex items-start"><span class="inline-block mr-6 mt-2"><svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 10H28" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/><path d="M22 5L28 10L22 15" stroke="#2563eb" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>собираете имена, телефоны, почту и другие данные</li>
            <li class="flex items-start"><span class="inline-block mr-6 mt-2"><svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 10H28" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/><path d="M22 5L28 10L22 15" stroke="#2563eb" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>не умеете и не хотите разбираться в CRM системах</li>
            <li class="flex items-start"><span class="inline-block mr-6 mt-2"><svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 10H28" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/><path d="M22 5L28 10L22 15" stroke="#2563eb" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>желаете избежать штрафов, но не знаете с чего начать</li>
                    </ul>
        </div>
      </div>
      <div class="flex-1 flex items-end justify-end h-full">
        <div class="bg-blue-50 rounded-3xl p-8 md:p-12 flex items-end justify-end w-full max-w-xl h-full min-h-[400px]" style="min-width:320px;">
          <img src="/svg/Group 44.svg" alt="Иллюстрация подходит" class="w-full h-auto object-contain" style="max-width:400px; min-width:240px;" />
        </div>
      </div>
    </section>
    <section class="flex flex-col items-center justify-center min-h-screen px-4">
      <div class="flex flex-col items-center w-full max-w-4xl">
        <div class="text-5xl font-semibold mb-4 mt-8 text-red-600">это<span class="text-blue-600">база</span></div>
        <div class="text-6xl font-light mb-8 text-black text-center text-left mt-12">
          Хранение данных на личном Яндекс.Диске — это 100% соответствие закону<br>
          №152-ФЗ «О персональных данных» от 2025 года
        </div>
      </div>
    </section>
    <section class="flex flex-col items-center justify-center min-h-screen px-4 bg-blue-50">
      <div class="flex flex-col md:flex-row items-center w-full max-w-7xl gap-20 py-8">
        <div class="flex-1 flex justify-center">
          <img src="/svg/Group 45.svg" alt="Закон и безопасность" class="w-full max-w-xl rounded-3xl shadow-none" style="min-width:320px;" />
        </div>
        <div class="flex-1">
          <div class="text-5xl md:text-6xl font-bold mb-12 text-black leading-tight">ЗАКОН СОБЛЮДЕН<br>ДАННЫЕ ЗАЩИЩЕНЫ</div>
          <ul class="space-y-10 text-2xl text-black">
            <li class="flex items-center"><span class="inline-block w-12 h-12 mr-6"><svg viewBox="0 0 32 32" fill="none"><rect x="4" y="8" width="24" height="16" rx="4" stroke="#DD4F62" stroke-width="2"/><text x="16" y="22" text-anchor="middle" font-size="16" fill="#DD4F62">@</text></svg></span>нет хранения на иностранных серверах</li>
            <li class="flex items-center"><span class="inline-block w-12 h-12 mr-6"><svg viewBox="0 0 32 32" fill="none"><rect x="4" y="4" width="24" height="24" rx="8" stroke="#83B5FA" stroke-width="2"/><polyline points="10,18 15,23 22,11" fill="none" stroke="#83B5FA" stroke-width="2"/></svg></span>у нас нет доступа к вашим данным</li>
            <li class="flex items-center"><span class="inline-block w-12 h-12 mr-6"><svg viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="12" stroke="#83B5FA" stroke-width="2"/><polyline points="10,16 14,20 22,12" fill="none" stroke="#83B5FA" stroke-width="2"/></svg></span>все хранится на вашем Яндекс Диске</li>
          </ul>
        </div>
      </div>
    </section>
    <section class="flex flex-col items-center justify-center min-h-screen px-4 py-16">
      <div class="flex flex-col md:flex-row items-center w-full max-w-[90rem] gap-32 py-12">
        <div class="flex-1 flex flex-col justify-center">
          <div class="text-6xl md:text-7xl font-bold mb-16 text-black leading-tight">ПРОЗРАЧНЫЕ ЦЕНЫ</div>
          <div class="text-5xl mb-10 text-black">Подписка: <span class="font-bold">200 руб в месяц</span></div>
          <div class="mb-16 text-3xl text-blue-600">Всем новым клиентам первый месяц - в подарок!</div>
          <button
            class="bg-blue-600 hover:bg-blue-700 text-white rounded-2xl px-16 py-7 text-3xl font-medium transition-colors shadow-lg"
            @click="goToApiKey"
          >
            попробовать бесплатно
          </button>
        </div>
        <div class="flex-1 flex justify-center items-center">
          <img src="/svg/Group 46.svg" alt="Подарок" class="w-full max-w-2xl rounded-3xl shadow-none" style="min-width:400px; max-width:600px;" />
        </div>
      </div>
    </section>
    <footer class="w-full bg-blue-50 py-8 mt-12">
      <div class="max-w-6xl mx-auto flex flex-col md:flex-row justify-between items-center px-4 text-black text-base">
        <div class="mb-4 md:mb-0">
          Петров Леонид Николаевич<br>
          проект для ITMO Stars<br>
          тел: +79148232567
                </div>
        <div class="text-center md:text-right text-xs text-gray-500">
          ALL RIGHTS RESERVED © 2025
        </div>
      </div>
    </footer>
    </div>
</template>

<style scoped>
.fade-slide-enter-active, .fade-slide-leave-active {
  transition: opacity 0.5s, transform 0.5s;
}
.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(20px);
}
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
</style>
