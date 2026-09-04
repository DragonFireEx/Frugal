<script setup lang="ts">
import axios from 'axios'
import { onMounted, reactive, ref } from 'vue'
import { useCategoriesStore, type CategoryPayload } from '../stores/categories'
import type { Category } from '../types'

const categoriesStore = useCategoriesStore()

const errorMessage = ref('')
const editingId = ref<number | null>(null)

const form = reactive<CategoryPayload>({
  name: '',
  type: 'expense',
  color: '#6366f1',
  icon: null,
})

function resetForm(): void {
  editingId.value = null
  form.name = ''
  form.type = 'expense'
  form.color = '#6366f1'
  form.icon = null
}

function startEdit(category: Category): void {
  editingId.value = category.id
  form.name = category.name
  form.type = category.type
  form.color = category.color
  form.icon = category.icon
}

async function handleSubmit(): Promise<void> {
  errorMessage.value = ''

  const payload: CategoryPayload = {
    name: form.name,
    type: form.type,
    color: form.color,
    icon: form.icon?.trim() ? form.icon.trim() : null,
  }

  try {
    if (editingId.value !== null) {
      await categoriesStore.update(editingId.value, payload)
    } else {
      await categoriesStore.create(payload)
    }
    resetForm()
  } catch (error) {
    errorMessage.value = axios.isAxiosError(error)
      ? (error.response?.data?.error ?? 'Nie udało się zapisać kategorii.')
      : 'Nie udało się zapisać kategorii.'
  }
}

async function handleDelete(category: Category): Promise<void> {
  if (!confirm(`Usunąć kategorię „${category.name}”?`)) {
    return
  }

  errorMessage.value = ''

  try {
    await categoriesStore.remove(category.id)
  } catch (error) {
    errorMessage.value = axios.isAxiosError(error)
      ? (error.response?.data?.error ?? 'Nie udało się usunąć kategorii.')
      : 'Nie udało się usunąć kategorii.'
  }
}

onMounted(() => {
  categoriesStore.fetchAll()
})
</script>

<template>
  <div class="categories-view">
    <h1>Kategorie</h1>

    <table v-if="categoriesStore.list.length" class="categories-table">
      <thead>
        <tr>
          <th>Kolor</th>
          <th>Nazwa</th>
          <th>Typ</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="category in categoriesStore.list" :key="category.id">
          <td><span class="color-dot" :style="{ backgroundColor: category.color }"></span></td>
          <td>{{ category.name }}</td>
          <td>{{ category.type === 'income' ? 'Przychód' : 'Wydatek' }}</td>
          <td class="actions">
            <button type="button" @click="startEdit(category)">Edytuj</button>
            <button type="button" @click="handleDelete(category)">Usuń</button>
          </td>
        </tr>
      </tbody>
    </table>

    <form @submit.prevent="handleSubmit" class="category-form">
      <h2>{{ editingId !== null ? 'Edytuj kategorię' : 'Nowa kategoria' }}</h2>

      <label>
        Nazwa
        <input v-model="form.name" type="text" required maxlength="100" />
      </label>

      <label>
        Typ
        <select v-model="form.type">
          <option value="expense">Wydatek</option>
          <option value="income">Przychód</option>
        </select>
      </label>

      <label>
        Kolor
        <input v-model="form.color" type="color" />
      </label>

      <label>
        Ikona (opcjonalnie)
        <input v-model="form.icon" type="text" maxlength="50" />
      </label>

      <p v-if="errorMessage" class="form-error" role="alert">{{ errorMessage }}</p>

      <div class="form-actions">
        <button type="submit">{{ editingId !== null ? 'Zapisz zmiany' : 'Dodaj kategorię' }}</button>
        <button v-if="editingId !== null" type="button" @click="resetForm">Anuluj</button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.categories-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 24px;
}

.categories-table th,
.categories-table td {
  text-align: left;
  padding: 8px;
  border-bottom: 1px solid var(--border);
}

.color-dot {
  display: inline-block;
  width: 14px;
  height: 14px;
  border-radius: 50%;
}

.actions {
  display: flex;
  gap: 8px;
}

.actions button {
  padding: 4px 10px;
  border: 1px solid var(--border);
  border-radius: 6px;
  background: transparent;
  color: var(--text);
  font: inherit;
  cursor: pointer;
}

.category-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-width: 360px;
}

.category-form label {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 14px;
}

.category-form input,
.category-form select {
  padding: 8px 10px;
  border: 1px solid var(--border);
  border-radius: 6px;
  font: inherit;
}

.form-actions {
  display: flex;
  gap: 8px;
}

.form-actions button {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  background: var(--accent);
  color: white;
  font: inherit;
  cursor: pointer;
}

.form-actions button[type='button'] {
  background: transparent;
  color: var(--text);
  border: 1px solid var(--border);
}

.form-error {
  color: #dc2626;
  font-size: 14px;
  margin: 0;
}
</style>
