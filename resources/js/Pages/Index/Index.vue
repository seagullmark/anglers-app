<template>
  <div>
    <h1>Index</h1>
    <p>You are logged in.</p>
    <p>User ID: {{ userId }}</p>
    <div v-if="userThumbnail">
      <p>Thumbnail</p>
      <img :src="userThumbnail" alt="User thumbnail" class="user-thumbnail" />
    </div>
    <p v-else>No thumbnail</p>
    <Link :href="route('logout')" method="post" as="button">Log out</Link>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const userId = computed(() => page.props.auth?.user?.id ?? 'unknown')
const userThumbnail = computed(() => page.props.auth?.user?.thumbnail ?? null)
</script>
