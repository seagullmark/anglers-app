<template>
  <div>
    <h1>Login</h1>

    <form @submit.prevent="submit">
      <div>
        <label for="email">Email</label>
        <input id="email" v-model="form.email" type="email" autocomplete="username" required />
        <div v-if="form.errors.email">{{ form.errors.email }}</div>
      </div>

      <div>
        <label for="password">Password</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          autocomplete="current-password"
          required
        />
        <div v-if="form.errors.password">{{ form.errors.password }}</div>
      </div>

      <button type="submit" :disabled="form.processing">Log in</button>
    </form>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
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
