<template>
  <Head title="Fishing Trips" />

  <div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p class="text-sm font-medium text-sky-600">Fishing Trips</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Trip Log</h1>
        <p class="mt-3 max-w-2xl text-sm text-slate-600">
          Review trips shared by everyone, then open a card to view the full details.
        </p>
      </div>

      <div class="flex flex-col gap-3 sm:flex-row">
        <Link
          :href="route('index')"
          class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        >
          Back to Home
        </Link>
        <Link
          :href="route('fishing-trips.create')"
          class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
          New Trip
        </Link>
      </div>
    </div>

    <div
      v-if="successMessage"
      class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ successMessage }}
    </div>

    <InfiniteScroll data="trips" :buffer="320" as="section">
      <template #default="{ loadingNext, hasNext }">
        <div v-if="props.trips.data.length" class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
          <article
            v-for="trip in props.trips.data"
            :key="trip.id"
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
          >
            <Link :href="route('fishing-trips.show', trip.id)" class="block">
              <div
                v-if="trip.cover_image_url"
                class="aspect-4/3 overflow-hidden border-b border-slate-200 bg-slate-100"
              >
                <img :src="trip.cover_image_url" alt="" class="h-full w-full object-cover" />
              </div>

              <div
                v-else
                class="flex aspect-4/3 items-center justify-center border-b border-slate-200 bg-slate-100 text-sm font-medium text-slate-400"
              >
                No photo
              </div>

              <div class="space-y-4 p-5">
                <div class="space-y-2">
                  <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-semibold text-slate-900">
                      {{ trip.river_name }}
                    </h2>
                    <div class="flex items-center gap-2">
                      <span v-if="trip.can_edit" class="rounded-full bg-slate-900 px-2.5 py-1 text-[11px] font-semibold text-white">
                        Yours
                      </span>
                      <span class="text-xs font-medium text-slate-500">
                        {{ trip.photo_count }} photo<span v-if="trip.photo_count !== 1">s</span>
                      </span>
                    </div>
                  </div>
                  <p class="text-sm text-slate-600">{{ trip.point_name }}</p>
                  <p class="text-xs font-medium text-slate-500">
                    Posted by {{ trip.owner.label }}
                  </p>
                </div>

                <dl class="grid gap-3 text-sm text-slate-600">
                  <div>
                    <dt class="font-medium text-slate-900">Date</dt>
                    <dd class="mt-1">{{ trip.trip_date }}</dd>
                  </div>
                  <div>
                    <dt class="font-medium text-slate-900">Time</dt>
                    <dd class="mt-1">{{ trip.start_time }} - {{ trip.end_time }}</dd>
                  </div>
                  <div>
                    <dt class="font-medium text-slate-900">Tackle</dt>
                    <dd class="mt-1">{{ trip.tackle_name }}</dd>
                  </div>
                </dl>

                <p v-if="trip.memo" class="line-clamp-3 text-sm text-slate-500">
                  {{ trip.memo }}
                </p>
              </div>
            </Link>
          </article>
        </div>

        <div
          v-else
          class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center"
        >
          <p class="text-lg font-semibold text-slate-900">No trips yet</p>
          <p class="mt-2 text-sm text-slate-600">
            Create your first fishing trip to start building the list.
          </p>
        </div>

        <div v-if="loadingNext" class="pt-6 text-center text-sm text-slate-500">
          Loading more trips...
        </div>

        <div v-else-if="hasNext" class="pt-6 text-center text-sm text-slate-400">
          Scroll to load more.
        </div>
      </template>
    </InfiniteScroll>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Head, InfiniteScroll, Link, usePage } from '@inertiajs/vue3'
import { useZiggyRoute } from '@/composables/useZiggyRoute'

const props = defineProps({
  trips: {
    type: Object,
    required: true
  }
})

const page = usePage()
const route = useZiggyRoute()
const successMessage = computed(() => page.props.flash?.success ?? null)
</script>
