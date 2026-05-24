<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
defineProps({ totalUsuarios: Number, usuariosPorRol: Object, ultimosAccesos: Array })
</script>

<template>
  <AppLayout>
    <div style="padding: 2rem">
      <h1>Dashboard</h1>
      <p>Total usuarios: {{ totalUsuarios }}</p>
    </div>
  </AppLayout>
</template>