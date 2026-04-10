<template>
  <Head :title="pageTitle" />

  <div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p class="text-sm font-medium text-sky-600">Fishing Trips</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">
          {{ pageTitle }}
        </h1>
        <p class="mt-3 max-w-2xl text-sm text-slate-600">
          Save the trip itself first, then let Policy and modId protect the update flow.
        </p>
      </div>

      <div class="flex flex-col gap-3 sm:flex-row">
        <Link
          :href="route('fishing-trips.index')"
          class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        >
          Back to List
        </Link>
      </div>
    </div>

    <div
      v-if="successMessage"
      class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="form.errors.mod_id"
      class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
    >
      {{ form.errors.mod_id }}
    </div>

    <form class="space-y-8" @submit.prevent="submit">
      <input v-if="isEdit" v-model="form.mod_id" type="hidden" />

      <section class="grid gap-6 md:grid-cols-2">
        <div>
          <label for="trip_date" class="block text-sm font-medium text-slate-700">Trip date</label>
          <input
            id="trip_date"
            v-model="form.trip_date"
            type="date"
            required
            class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100"
            :class="fieldErrorClass(form.errors.trip_date)"
          />
          <p v-if="form.errors.trip_date" class="mt-2 text-sm text-rose-600">
            {{ form.errors.trip_date }}
          </p>
        </div>

        <div>
          <label for="river_name" class="block text-sm font-medium text-slate-700">River</label>
          <input
            id="river_name"
            v-model="form.river_name"
            type="text"
            required
            class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100"
            :class="fieldErrorClass(form.errors.river_name)"
          />
          <p v-if="form.errors.river_name" class="mt-2 text-sm text-rose-600">
            {{ form.errors.river_name }}
          </p>
        </div>

        <div>
          <label for="start_at" class="block text-sm font-medium text-slate-700">Start</label>
          <input
            id="start_at"
            v-model="form.start_at"
            type="datetime-local"
            required
            class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100"
            :class="fieldErrorClass(form.errors.start_at)"
          />
          <p v-if="form.errors.start_at" class="mt-2 text-sm text-rose-600">
            {{ form.errors.start_at }}
          </p>
        </div>

        <div>
          <label for="end_at" class="block text-sm font-medium text-slate-700">End</label>
          <input
            id="end_at"
            v-model="form.end_at"
            type="datetime-local"
            required
            class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100"
            :class="fieldErrorClass(form.errors.end_at)"
          />
          <p v-if="form.errors.end_at" class="mt-2 text-sm text-rose-600">
            {{ form.errors.end_at }}
          </p>
        </div>

        <div>
          <label for="point_name" class="block text-sm font-medium text-slate-700">Point</label>
          <input
            id="point_name"
            v-model="form.point_name"
            type="text"
            required
            class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100"
            :class="fieldErrorClass(form.errors.point_name)"
          />
          <p v-if="form.errors.point_name" class="mt-2 text-sm text-rose-600">
            {{ form.errors.point_name }}
          </p>
        </div>

        <div>
          <label for="tackle_name" class="block text-sm font-medium text-slate-700">Tackle</label>
          <input
            id="tackle_name"
            v-model="form.tackle_name"
            type="text"
            required
            class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100"
            :class="fieldErrorClass(form.errors.tackle_name)"
          />
          <p v-if="form.errors.tackle_name" class="mt-2 text-sm text-rose-600">
            {{ form.errors.tackle_name }}
          </p>
        </div>
      </section>

      <section>
        <label for="memo" class="block text-sm font-medium text-slate-700">Memo</label>
        <textarea
          id="memo"
          v-model="form.memo"
          rows="4"
          class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100"
          :class="fieldErrorClass(form.errors.memo)"
        />
        <p v-if="form.errors.memo" class="mt-2 text-sm text-rose-600">
          {{ form.errors.memo }}
        </p>
      </section>

      <section class="space-y-4">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">Photos</h2>
          <p class="mt-2 text-sm text-slate-600">
            Upload one or more PNG or JPEG images. New files are appended to the trip.
          </p>
        </div>

        <div
          v-if="isEdit && props.trip.photos.length"
          class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
          <label
            v-for="photo in props.trip.photos"
            :key="photo.id"
            class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50"
          >
            <div class="aspect-4/3 overflow-hidden bg-slate-100">
              <img :src="photo.image_url" alt="" class="h-full w-full object-cover" />
            </div>
            <div class="space-y-2 p-4">
              <div class="flex items-center gap-3">
                <input
                  :id="`remove-photo-${photo.id}`"
                  v-model="form.remove_photo_ids"
                  :value="photo.id"
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                />
                <span class="text-sm font-medium text-slate-700">Remove this photo</span>
              </div>
              <p class="text-xs text-slate-500">Sort order: {{ photo.sort_order }}</p>
            </div>
          </label>
        </div>

        <div
          v-else-if="isEdit"
          class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-500"
        >
          No photos uploaded yet.
        </div>

        <div>
          <label for="photos" class="block text-sm font-medium text-slate-700">Add photos</label>
          <input
            id="photos"
            ref="fileInput"
            type="file"
            accept="image/png,image/jpeg"
            multiple
            class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
            @change="onFileChange"
          />
          <p v-if="form.errors.photos" class="mt-2 text-sm text-rose-600">
            {{ form.errors.photos }}
          </p>
          <p v-if="form.errors['photos.0']" class="mt-2 text-sm text-rose-600">
            {{ form.errors['photos.0'] }}
          </p>
        </div>
      </section>

      <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center">
        <button
          type="submit"
          :disabled="form.processing || deleteForm.processing"
          class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
        >
          {{ submitLabel }}
        </button>

        <button
          v-if="isEdit"
          type="button"
          :disabled="form.processing || deleteForm.processing"
          class="inline-flex items-center justify-center rounded-xl border border-rose-300 bg-white px-4 py-3 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-60"
          @click="destroy"
        >
          Delete Trip
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { useZiggyRoute } from '@/composables/useZiggyRoute'

const props = defineProps({
  mode: {
    type: String,
    required: true
  },
  trip: {
    type: Object,
    required: true
  }
})

const page = usePage()
const route = useZiggyRoute()
const fileInput = ref(null)

const isEdit = computed(() => props.mode === 'edit')
const pageTitle = computed(() => (isEdit.value ? 'Edit Fishing Trip' : 'New Fishing Trip'))
const submitLabel = computed(() => {
  if (form.processing) {
    return isEdit.value ? 'Saving...' : 'Creating...'
  }

  return isEdit.value ? 'Save Changes' : 'Create Trip'
})
const successMessage = computed(() => page.props.flash?.success ?? null)

const form = useForm({
  mod_id: props.trip.mod_id,
  trip_date: props.trip.trip_date ?? '',
  start_at: props.trip.start_at ?? '',
  end_at: props.trip.end_at ?? '',
  river_name: props.trip.river_name ?? '',
  point_name: props.trip.point_name ?? '',
  tackle_name: props.trip.tackle_name ?? '',
  memo: props.trip.memo ?? '',
  photos: [],
  remove_photo_ids: []
})

const deleteForm = useForm({
  mod_id: props.trip.mod_id
})

const fieldErrorClass = (error) =>
  error ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-100' : ''

const onFileChange = (event) => {
  form.photos = Array.from(event.target.files ?? [])
}

const clearSelectedFiles = () => {
  form.reset('photos')

  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const submit = () => {
  const options = {
    forceFormData: true,
    onSuccess: clearSelectedFiles
  }

  if (isEdit.value) {
    form.submit('put', route('fishing-trips.update', props.trip.id), options)

    return
  }

  form.post(route('fishing-trips.store'), options)
}

const destroy = () => {
  if (!window.confirm('Delete this fishing trip?')) {
    return
  }

  deleteForm.submit('delete', route('fishing-trips.destroy', props.trip.id))
}
</script>
