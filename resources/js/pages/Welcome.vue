<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';

function goToRequestForm() {
  router.visit('/request-form');
}

const icons = [
  '/svg/icon/Group 26.svg',
  '/svg/icon/Group 27.svg',
  '/svg/icon/Group 28.svg',
  '/svg/icon/Group 29.svg',
  '/svg/icon/Group 30.svg',
];
const currentIndex = ref(0);
let intervalId: number | undefined;

function startInterval() {
  intervalId = window.setInterval(() => {
    currentIndex.value = (currentIndex.value + 1) % icons.length;
  }, 2000);
}

function stopInterval() {
  if (intervalId) clearInterval(intervalId);
}

function handleVisibilityChange() {
  if (document.visibilityState === 'visible') {
    startInterval();
  } else {
    stopInterval();
  }
}

onMounted(() => {
  document.addEventListener('visibilitychange', handleVisibilityChange);
  startInterval();
});

onBeforeUnmount(() => {
  document.removeEventListener('visibilitychange', handleVisibilityChange);
  stopInterval();
});
</script>

<template>
  <div class="min-h-screen flex flex-col items-center justify-center relative bg-gray-50" style="background: #fafbfc url('data:image/svg+xml;utf8,<svg width=\'20\' height=\'20\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'><circle cx=\'2\' cy=\'2\' r=\'1\' fill=\'%23d1d5db\'/></svg>') repeat; font-family: 'Montserrat', 'Arial', sans-serif;"> 
    <section class="relative flex flex-col items-center justify-center min-h-screen px-4">
      
        
      <img src="svg/icon/Group 26.svg" alt="" class="absolute top-8 right-245 w-30">
      <button
        class="absolute top-8 left-200 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl px-8 py-3 text-lg font-medium transition-colors z-10 w-72"
        @click="goToRequestForm"
      >
        попробовать бесплатно
      </button>
      <div class="flex flex-col items-center justify-center w-full">
        
          <span class="inline-block align-middle relative min-w-[80px] pl-0 text-left tracking-tight w-96 mb-6" style="height: 72px;">
            <transition-group name="fade-slide" tag="span" class="w-full h-full flex items-center">
              <span
                v-for="(icon, idx) in icons"
                v-show="currentIndex === idx"
                :key="icon"
                class="absolute left-0 top-0 w-full h-full flex items-center justify-center"
                style="font-family: inherit;"
              >
                <img :src="icon" alt="icon" class="h-20 md:h-32 w-auto inline-block align-middle" />
              </span>
            </transition-group>
          </span>
        <div class="text-xl text-center mb-8 max-w-xl text-black">
          Наш сервис подключается к вашему сайту и автоматически сохраняет клиентские данные в ваш Яндекс.Диск. Надёжно, легально, без посредников.
        </div>
        <button
          class="bg-blue-600 hover:bg-blue-700 text-white rounded-2xl px-10 py-4 text-lg font-medium transition-colors"
          @click="goToRequestForm"
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
    <div class="w-full flex justify-center items-center min-h-screen">
      <section class="flex flex-col md:flex-row px-4 gap-0 items-center w-full max-w-[1600px]">
        <div class="flex-1 flex h-full min-h-[500px] items-center justify-center">
          <div class="flex flex-col justify-end items-start w-full max-w-2xl text-left">
            <div class="text-5xl md:text-5xl font-normal mb-12 text-black leading-tight w-full" style="font-family: 'Idealist Sans', sans-serif;">Вам это подходит, если вы</div>
            <ul class="space-y-10 text-3xl text-black w-full">
              <li class="flex items-center"><span class="inline-block mr-6 mt-2"><svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 10H28" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/><path d="M22 5L28 10L22 15" stroke="#2563eb" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>малый предприниматель (ИП)</li>
              <li class="flex items-center"><span class="inline-block mr-6 mt-2"><svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 10H28" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/><path d="M22 5L28 10L22 15" stroke="#2563eb" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>собираете имена, телефоны, почту и другие данные</li>
              <li class="flex items-center"><span class="inline-block mr-6 mt-2"><svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 10H28" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/><path d="M22 5L28 10L22 15" stroke="#2563eb" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>не умеете и не хотите разбираться в CRM системах</li>
              <li class="flex items-center"><span class="inline-block mr-6 mt-2"><svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 10H28" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/><path d="M22 5L28 10L22 15" stroke="#2563eb" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>желаете избежать штрафов, но не знаете с чего начать</li>
            </ul>
          </div>
        </div>
        <div class="flex-1 flex h-full min-h-[500px] items-center">
          <div class="rounded-3xl p-0 md:p-0 flex items-end justify-end w-full max-w-4xl h-full min-h-[500px] ml-12" style="min-width:320px;">
            <img src="/svg/Group 54(1).svg" class="w-full h-auto object-contain" style="max-width:800px; min-width:240px;" />
          </div>
        </div>
      </section>
    </div>
    <section class="flex flex-col items-center justify-center min-h-screen px-4">
      <div class="flex flex-col items-center w-full max-w-4xl">
        <div class="relative flex flex-col items-center w-full" style="min-height: 320px;">
          <img src="/svg/Group 57.svg" alt="100% соответствие закону" class="mb-8 mt-0 z-10 w-full md:w-[1200px]" />
        </div>
      </div>
    </section>
    <section class="w-screen flex flex-col items-center justify-center min-h-screen px-4 bg-blue-50">
      <div class="flex flex-col md:flex-row items-center w-full max-w-7xl gap-20 py-8">
        <div class="flex-1 flex justify-center">
          <img src="/svg/Group 45.svg" alt="Закон и безопасность"  class="pt-70" />
        </div>
        <div class="flex-1">
          <div class="text-4xl font-bold mb-12 text-black leading-tight">ЗАКОН СОБЛЮДЕН<br>ДАННЫЕ ЗАЩИЩЕНЫ</div>
          <img src="/svg/Group 43.svg" alt="Закон и безопасность" class="w-full max-w-xl rounded-3xl shadow-none" style="min-width:320px;" />
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
            @click="goToRequestForm"
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
      <div class="max-w-6xl mx-auto flex flex-col md:flex-row justify-between items-center px-4 text-black text-base gap-8">
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
