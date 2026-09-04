<script setup lang="ts">
defineProps<{ message: string }>()
</script>

<template>
  <p class="empty-state">{{ message }}</p>
</template>

<style scoped>
.empty-state {
  padding: 16px;
  border: 1px dashed var(--border);
  border-radius: 8px;
  color: var(--text);
  margin-bottom: 24px;
}
</style>
