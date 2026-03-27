<template>
  <Head title="Profile Photo" />

  <div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p class="text-sm font-medium text-sky-600">User Settings</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Profile Photo</h1>
        <p class="mt-3 max-w-2xl text-sm text-slate-600">
          Upload the image used for your account. The server saves it as a square thumbnail.
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
          :href="route('logout')"
          method="post"
          as="button"
          class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        >
          Log out
        </Link>
      </div>
    </div>

    <div
      v-if="successMessage"
      class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ successMessage }}
    </div>

    <div class="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
      <section class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
        <p class="text-sm font-medium text-slate-500">Current User</p>

        <div class="mt-5 flex flex-col items-center text-center">
          <div
            v-if="userThumbnail"
            class="h-32 w-32 overflow-hidden rounded-full border border-slate-200 bg-white"
          >
            <img
              :src="userThumbnail"
              alt="Current profile photo"
              class="h-full w-full object-cover"
            />
          </div>

          <div
            v-else
            class="flex h-32 w-32 items-center justify-center rounded-full border border-dashed border-slate-300 bg-white text-3xl font-semibold text-slate-400"
          >
            {{ userInitial }}
          </div>

          <p class="mt-4 text-lg font-semibold text-slate-900">{{ userName }}</p>
          <p class="mt-1 text-sm text-slate-500">{{ userEmail }}</p>
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-6">
        <h2 class="text-lg font-semibold text-slate-900">Upload New Image</h2>
        <p class="mt-2 text-sm text-slate-600">
          Choose a PNG or JPEG file. The uploaded image is cropped to 128 x 128.
        </p>

        <form class="mt-6 space-y-5" @submit.prevent="submit" enctype="multipart/form-data">
          <div>
            <label for="photo" class="block text-sm font-medium text-slate-700">Image file</label>
            <input
              id="photo"
              ref="fileInput"
              type="file"
              accept="image/png,image/jpeg"
              required
              class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
              @change="onFileChange"
            />
            <p v-if="form.errors.file" class="mt-2 text-sm text-rose-600">
              {{ form.errors.file }}
            </p>
          </div>

          <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <button
              type="submit"
              :disabled="form.processing"
              class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
            >
              {{ form.processing ? 'Uploading...' : 'Save Profile Photo' }}
            </button>
            <p class="text-sm text-slate-500">The preview updates after a successful upload.</p>
          </div>
        </form>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { useZiggyRoute } from '@/composables/useZiggyRoute'

const page = usePage()
const route = useZiggyRoute()

const user = computed(() => page.props.auth?.user ?? null)
const successMessage = computed(() => page.props.flash?.success ?? null)
const userName = computed(() => user.value?.name || 'User')
const userEmail = computed(() => user.value?.email || 'No email available')
const userThumbnail = computed(() => user.value?.thumbnail ?? null)
const userInitial = computed(() => {
  const source = user.value?.name || user.value?.email || 'U'

  return source.charAt(0).toUpperCase()
})

const form = useForm({
  file: null
})

const fileInput = ref(null)

const onFileChange = (event) => {
  form.file = event.target.files?.[0] ?? null
}

const submit = () => {
  form.post(route('user-photos.store'), {
    forceFormData: true,
    onFinish: () => {
      form.reset('file')

      if (fileInput.value) {
        fileInput.value.value = ''
      }
    }
  })
}
</script>
