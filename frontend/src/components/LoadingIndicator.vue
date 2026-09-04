<script setup lang="ts">
withDefaults(defineProps<{ label?: string }>(), { label: 'Ładowanie…' })
</script>

<template>
  <div class="loading-indicator">
    <span class="spinner" aria-hidden="true"></span>
    <span>{{ label }}</span>
  </div>
</template>

<style scoped>
.loading-indicator {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px 0;
  color: var(--text);
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid var(--border);
  border-top-color: var(--accent);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
