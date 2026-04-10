<template>
  <Head :title="trip.river_name" />

  <div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p class="text-sm font-medium text-sky-600">Fishing Trips</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">
          {{ trip.river_name }}
        </h1>
        <p class="mt-3 max-w-2xl text-sm text-slate-600">
          Review the full trip details and all uploaded photos.
        </p>
      </div>

      <div class="flex flex-col gap-3 sm:flex-row">
        <Link
          :href="route('fishing-trips.index')"
          class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        >
          Back to List
        </Link>
        <Link
          v-if="trip.can_edit"
          :href="route('fishing-trips.edit', trip.id)"
          class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
          Edit Trip
        </Link>
      </div>
    </div>

    <section v-if="trip.photos.length" class="space-y-4">
      <div class="flex items-center justify-between gap-4">
        <h2 class="text-lg font-semibold text-slate-900">Photos</h2>
        <p class="text-sm text-slate-500">
          {{ trip.photos.length }} photo<span v-if="trip.photos.length !== 1">s</span>
        </p>
      </div>

      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <figure
          v-for="photo in trip.photos"
          :key="photo.id"
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
        >
          <div class="aspect-4/3 overflow-hidden bg-slate-100">
            <img :src="photo.image_url" alt="" class="h-full w-full object-cover" />
          </div>
          <figcaption class="space-y-1 p-4">
            <p class="text-xs font-medium text-slate-500">Photo {{ photo.sort_order }}</p>
            <p v-if="photo.caption" class="text-sm text-slate-600">{{ photo.caption }}</p>
          </figcaption>
        </figure>
      </div>
    </section>

    <section
      v-else
      class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center"
    >
      <p class="text-lg font-semibold text-slate-900">No photos uploaded</p>
      <p class="mt-2 text-sm text-slate-600">
        This trip does not have any photos yet.
      </p>
    </section>

    <section class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(280px,0.8fr)]">
      <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <h2 class="text-lg font-semibold text-slate-900">Memo</h2>
        <p v-if="trip.memo" class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-600">
          {{ trip.memo }}
        </p>
        <p v-else class="mt-4 text-sm text-slate-500">
          No memo was saved for this trip.
        </p>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
        <h2 class="text-lg font-semibold text-slate-900">Trip Details</h2>
        <dl class="mt-5 space-y-5 text-sm text-slate-600">
          <div>
            <dt class="font-medium text-slate-900">Date</dt>
            <dd class="mt-1">{{ trip.trip_date }}</dd>
          </div>
          <div>
            <dt class="font-medium text-slate-900">Time</dt>
            <dd class="mt-1">{{ trip.start_time }} - {{ trip.end_time }}</dd>
          </div>
          <div>
            <dt class="font-medium text-slate-900">Point</dt>
            <dd class="mt-1">{{ trip.point_name }}</dd>
          </div>
          <div>
            <dt class="font-medium text-slate-900">Tackle</dt>
            <dd class="mt-1">{{ trip.tackle_name }}</dd>
          </div>
        </dl>
      </div>
    </section>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { useZiggyRoute } from '@/composables/useZiggyRoute'

defineProps({
  trip: {
    type: Object,
    required: true
  }
})

const route = useZiggyRoute()
</script>
