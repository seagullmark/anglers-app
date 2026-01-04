<template>
  <div>
    <h1>Index</h1>
    <p>You are logged in.</p>
    <p>User ID: {{ userId }}</p>
    <Link :href="route('logout')" method="post" as="button">Log out</Link>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const userId = computed(() => page.props.auth?.user?.id ?? 'unknown')
</script>
