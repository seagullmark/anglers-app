<template>
  <Head title="Login" />

  <div class="min-h-screen bg-slate-100 px-4 py-12">
    <div class="mx-auto flex min-h-[calc(100vh-6rem)] max-w-md items-center justify-center">
      <div class="w-full rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="mb-8 text-center">
          <p class="text-sm font-medium tracking-wide text-sky-600">Anglers</p>
          <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Log in</h1>
          <p class="mt-2 text-sm text-slate-500">Enter your credentials to continue.</p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
          <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              autocomplete="username"
              required
              class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100"
              :class="{
                'border-rose-400 focus:border-rose-500 focus:ring-rose-100': form.errors.email
              }"
              placeholder="you@example.com"
            />
            <p v-if="form.errors.email" class="mt-2 text-sm text-rose-600">
              {{ form.errors.email }}
            </p>
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              autocomplete="current-password"
              required
              class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100"
              :class="{
                'border-rose-400 focus:border-rose-500 focus:ring-rose-100': form.errors.password
              }"
              placeholder="••••••••"
            />
            <p v-if="form.errors.password" class="mt-2 text-sm text-rose-600">
              {{ form.errors.password }}
            </p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-200 disabled:cursor-not-allowed disabled:bg-slate-400"
          >
            {{ form.processing ? 'Signing in...' : 'Log in' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import SingleLayout from '/resources/js/Layouts/SingleLayout.vue'
defineOptions({ layout: SingleLayout })
import { useZiggyRoute } from '@/composables/useZiggyRoute'

const route = useZiggyRoute()

const form = useForm({
  email: '',
  password: ''
})

const submit = () => {
  form.post(route('login.store'), {
    onFinish: () => form.reset('password')
  })
}
</script>
