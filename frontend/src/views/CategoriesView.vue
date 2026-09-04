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

    <table v-if="categoriesStore.list.length" class="data-table">
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
          <td class="form-actions">
            <button type="button" class="btn btn-secondary btn-small" @click="startEdit(category)">Edytuj</button>
            <button type="button" class="btn btn-secondary btn-small" @click="handleDelete(category)">Usuń</button>
          </td>
        </tr>
      </tbody>
    </table>

    <form @submit.prevent="handleSubmit" class="entity-form">
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
        <button type="submit" class="btn">{{ editingId !== null ? 'Zapisz zmiany' : 'Dodaj kategorię' }}</button>
        <button v-if="editingId !== null" type="button" class="btn btn-secondary" @click="resetForm">Anuluj</button>
      </div>
    </form>
  </div>
</template>
