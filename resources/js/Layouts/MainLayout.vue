<template>
  <div class="min-h-screen bg-slate-100">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
        <p class="text-lg font-semibold text-slate-900">Anglers</p>
        <div v-if="userLabel" class="flex items-center gap-3">
          <div
            v-if="userThumbnail"
            class="h-10 w-10 overflow-hidden rounded-full border border-slate-200 bg-slate-100"
          >
            <img :src="userThumbnail" alt="User profile photo" class="h-full w-full object-cover" />
          </div>
          <div
            v-else
            class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-slate-100 text-sm font-semibold text-slate-600"
          >
            {{ userInitial }}
          </div>

          <p class="text-sm text-slate-600">
            <span class="font-medium text-slate-900">{{ userLabel }}</span>
          </p>
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
      <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <slot />
      </section>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

const user = computed(() => page.props.auth?.user ?? null)
const userThumbnail = computed(() => user.value?.thumbnail ?? null)
const userLabel = computed(() => user.value?.name || user.value?.email || user.value?.id || null)
const userInitial = computed(() => {
  const source = user.value?.name || user.value?.email || 'U'

  return source.charAt(0).toUpperCase()
})
</script>
