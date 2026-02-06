<template>
  <div>
    <h1>Index</h1>
    <p>You are logged in.</p>
    <p>User ID: {{ userId }}</p>
    <form @submit.prevent="submit" enctype="multipart/form-data">
      <label for="photo">Upload photo</label>
      <input
        id="photo"
        ref="fileInput"
        type="file"
        accept="image/png,image/jpeg"
        @change="onFileChange"
        required
      />
      <div v-if="form.errors.file">{{ form.errors.file }}</div>
      <button type="submit" :disabled="form.processing">Upload</button>
    </form>
    <div v-if="userThumbnail">
      <p>Thumbnail</p>
      <img :src="userThumbnail" alt="User thumbnail" class="user-thumbnail" />
    </div>
    <p v-else>No thumbnail</p>
    <Link :href="route('logout')" method="post" as="button">Log out</Link>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { useZiggyRoute } from '@/composables/useZiggyRoute'

const page = usePage()
const route = useZiggyRoute()
const userId = computed(() => page.props.auth?.user?.id ?? 'unknown')
const userThumbnail = computed(() => page.props.auth?.user?.thumbnail ?? null)

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
