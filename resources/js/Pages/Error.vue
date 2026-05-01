<template>
  <Head :title="title" />

  <main class="flex min-h-screen items-center justify-center bg-slate-50 px-6 py-16">
    <div class="w-full max-w-md text-center">
      <p class="text-sm font-semibold text-sky-600">{{ status }}</p>
      <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ title }}</h1>
      <p class="mt-4 text-sm leading-6 text-slate-600">{{ description }}</p>

      <div class="mt-8">
        <Link
          href="/"
          class="inline-flex items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700"
        >
          ホームへ戻る
        </Link>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import SingleLayout from '@/Layouts/SingleLayout.vue'

defineOptions({ layout: SingleLayout })

const props = defineProps({
  status: {
    type: Number,
    required: true
  }
})

const title = computed(() => {
  return {
    503: 'Service Unavailable',
    500: 'Server Error',
    404: 'Not Found',
    403: 'Forbidden'
  }[props.status] || 'Error'
})

const description = computed(() => {
  return {
    503: '現在メンテナンス中です。しばらくしてから再度お試しください。',
    500: 'サーバーでエラーが発生しました。',
    404: 'お探しのページは見つかりませんでした。',
    403: 'このページへのアクセス権限がありません。'
  }[props.status] || 'エラーが発生しました。'
})
</script>
