<script setup>
import { ref } from 'vue'

const API_URL = 'http://adad.test/api/upload' // Change to your Site B URL
const API_KEY = 'XRD74WUQXhdWJARRIVCFSb5DdHelWFbS3S5kaOFatBiJgok6WU49mc4O4zCkAhRA' // Set to generated API key

const fields = [
  { key: 'name', label: 'Name', type: 'text' },
  { key: 'email', label: 'Email', type: 'email' },
  { key: 'checkbox', label: 'Checkbox (yes/no)', type: 'checkbox' },
]

const form = ref({ name: '', email: '', checkbox: false })
const forms = ref([])
const result = ref(null)
const loading = ref(false)

function addForm() {
  forms.value.push({
    name: form.value.name,
    email: form.value.email,
    checkbox: form.value.checkbox ? 'yes' : 'no',
  })
  form.value = { name: '', email: '', checkbox: false }
}

function removeForm(idx) {
  forms.value.splice(idx, 1)
}

async function submitToApi() {
  loading.value = true
  result.value = null
  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-API-KEY': API_KEY,
      },
      body: JSON.stringify({ forms: forms.value })
    })
    const data = await response.json()
    result.value = data
    if (data.success) {
      forms.value = []
    }
  } catch (e) {
    result.value = { success: false, message: e.message }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <h2>Submit Data to Yandex Disk API</h2>
    <form @submit.prevent="addForm">
      <div v-for="(field, idx) in fields" :key="field.key">
        <label :for="field.key">{{ field.label }}:</label>
        <input v-if="field.type !== 'checkbox'" :type="field.type" :id="field.key" v-model="form[field.key]" :placeholder="field.label" />
        <input v-else type="checkbox" :id="field.key" v-model="form[field.key]" />
      </div>
      <button type="submit">Add Entry</button>
    </form>

    <div v-if="forms.length">
      <h3>Entries to Submit</h3>
      <ul>
        <li v-for="(entry, idx) in forms" :key="idx">
          {{ entry }}
          <button @click="removeForm(idx)">Remove</button>
        </li>
      </ul>
      <button @click="submitToApi" :disabled="loading">Submit to API</button>
    </div>

    <div v-if="result">
      <div v-if="result.success">
        Success! File uploaded.
        <a :href="result.url" target="_blank">View on Yandex Disk</a>
      </div>
      <div v-else>
        Error: {{ result.message }}
      </div>
    </div>
  </div>
</template>
