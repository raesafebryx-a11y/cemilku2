<script setup>
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import Navbar from '@/components/Navbar.vue'
import Footer from '@/components/Footer.vue'

const route = useRoute()
const showSiteShell = computed(() => {
  const hiddenRoutes = ['/login', '/register', '/admin']
  return !hiddenRoutes.some((path) => route.path === path || route.path.startsWith(`${path}/`))
})
</script>

<template>
  <div class="app-shell">
    <Navbar v-if="showSiteShell" />
    <RouterView />
    <Footer v-if="showSiteShell" />
  </div>
</template>