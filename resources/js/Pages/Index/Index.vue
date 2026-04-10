<template>
  <Head title="Home" />

  <div class="space-y-8">
    <div>
      <p class="text-sm font-medium text-sky-600">Home</p>
      <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">User Settings</h1>
      <p class="mt-3 max-w-2xl text-sm text-slate-600">
        Choose a page to manage your account settings.
      </p>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
      <Link
        :href="route('fishing-trips.index')"
        class="block rounded-2xl border border-slate-200 bg-slate-50 p-6 transition hover:border-sky-300 hover:bg-sky-50"
      >
        <p class="text-sm font-medium text-sky-600">Fishing Trips</p>
        <h2 class="mt-2 text-xl font-semibold text-slate-900">Trip Log</h2>
        <p class="mt-3 text-sm text-slate-600">
          Create, edit, and review your uploaded fishing trips.
        </p>
      </Link>

      <Link
        :href="route('user.profile-photo')"
        class="block rounded-2xl border border-slate-200 bg-slate-50 p-6 transition hover:border-sky-300 hover:bg-sky-50"
      >
        <p class="text-sm font-medium text-sky-600">User</p>
        <h2 class="mt-2 text-xl font-semibold text-slate-900">Profile Photo</h2>
        <p class="mt-3 text-sm text-slate-600">
          Upload and replace the image used for your profile.
        </p>
      </Link>
    </div>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { useZiggyRoute } from '@/composables/useZiggyRoute'

const route = useZiggyRoute()
</script>
