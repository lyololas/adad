<script setup lang="ts">
import { ref } from 'vue'
import * as XLSX from 'xlsx'
import axios from 'axios'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'

const showInput = ref(false)
const inputText = ref('')
const table = ref({
  headers: [] as string[],
  rows: [] as string[][]
})
const editingCell = ref<{rowIndex: number | null, colIndex: number | null, isHeader: boolean}>({ 
  rowIndex: null, 
  colIndex: null,
  isHeader: false 
})
const editValue = ref('')

const toggleInput = () => showInput.value = !showInput.value

const parseInput = () => {
  const blocks = inputText.value
    .split(/\n\s*\n/)
    .map(block => block
      .split('\n')
      .filter(Boolean)
      .map(line => {
        const [key, ...vals] = line.split(/\t|:|-|,/).map(cell => cell.trim()).filter(Boolean)
        const normalizedKey = key.toLowerCase()
        return key && vals.length ? [normalizedKey, vals.join(' ')] : null
      })
      .filter(Boolean) as [string, string][]
    )
    .filter(block => block.length)

  const uniqueKeys = new Map<string, string>()
  blocks.flat().forEach(([key, _]) => {
    const lowerKey = key.toLowerCase()
    if (!uniqueKeys.has(lowerKey)) {
      uniqueKeys.set(lowerKey, key)
    }
  })
  const headers = Array.from(uniqueKeys.values())

  const rows = blocks.map(block => {
    const map = new Map<string, string>()
    block.forEach(([key, value]) => {
      map.set(key.toLowerCase(), value)
    })
    return headers.map(header => map.get(header.toLowerCase()) || '')
  })

  return { headers, rows }
}

const saveData = () => {
  const newData = parseInput()
  
  if (table.value.headers.length) {
    const combinedHeaders = [...new Set([...table.value.headers, ...newData.headers])]
    
    const mergedRows = newData.rows.map(newRow => {
      return combinedHeaders.map(header => {
        const newIndex = newData.headers.indexOf(header)
        return newIndex >= 0 ? newRow[newIndex] : ''
      })
    })
    
    const existingRows = table.value.rows.map(row => {
      return combinedHeaders.map(header => {
        const existingIndex = table.value.headers.indexOf(header)
        return existingIndex >= 0 ? row[existingIndex] : ''
      })
    })

    table.value = {
      headers: combinedHeaders,
      rows: [...existingRows, ...mergedRows]
    }
  } else {
    table.value = newData
  }
  
  inputText.value = ''
  showInput.value = false
}

const clearTable = () => {
  table.value = { headers: [], rows: [] }
}

const exportToExcel = async () => {
  if (!table.value.headers.length || !table.value.rows.length) return

  // Create Excel file
  const excelData = [
    table.value.headers, 
    ...table.value.rows  
  ]

  const wb = XLSX.utils.book_new()
  const ws = XLSX.utils.aoa_to_sheet(excelData)
  XLSX.utils.book_append_sheet(wb, ws, 'Data')
  const excelBuffer = XLSX.write(wb, { bookType: 'xlsx', type: 'array' })
  const blob = new Blob([excelBuffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })

  // Retry mechanism
  const maxAttempts = 3
  let attempts = 0
  
  const attemptUpload = async () => {
    try {
      const response = await uploadToYandexDisk(blob)
      alert(`File successfully ${response.data.action} at: ${response.data.url}`)
      window.open(response.data.url, '_blank')
      return true // Success
    } catch (error) {
      attempts++
      console.error(`Upload attempt ${attempts} failed:`, error)
      
      if (attempts < maxAttempts) {
        // Wait 5 seconds before retrying
        await new Promise(resolve => setTimeout(resolve, 5000))
        return attemptUpload() // Recursive retry
      } else {
        alert('Failed to upload after 3 attempts. Please try again later.')
        return false // Failure
      }
    }
  }

  await attemptUpload()
}

const uploadToYandexDisk = async (fileBlob: Blob) => {
  const formData = new FormData()
  formData.append('file', fileBlob, 'data.xlsx') // Fixed filename

  try {
    return await axios.post('/upload-to-yandex', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  } catch (error) {
    console.error('Upload failed:', error)
    throw error
  }
}

const startEditing = (rowIndex: number | null, colIndex: number, isHeader: boolean = false) => {
  editingCell.value = { rowIndex, colIndex, isHeader }
  editValue.value = isHeader 
    ? table.value.headers[colIndex] 
    : table.value.rows[rowIndex!][colIndex]
}

const saveEdit = () => {
  if (editingCell.value.colIndex !== null) {
    if (editingCell.value.isHeader) {
      // Update header
      const newHeaders = [...table.value.headers]
      newHeaders[editingCell.value.colIndex] = editValue.value
      table.value.headers = newHeaders
    } else if (editingCell.value.rowIndex !== null) {
      // Update cell
      const newRows = [...table.value.rows]
      newRows[editingCell.value.rowIndex][editingCell.value.colIndex] = editValue.value
      table.value.rows = newRows
    }
  }
  editingCell.value = { rowIndex: null, colIndex: null, isHeader: false }
}

const cancelEdit = () => {
  editingCell.value = { rowIndex: null, colIndex: null, isHeader: false }
}
</script>

<template>
  <AppLayout>
    <Head title="Tilda exporter" />
    
    <div class="p-5 bg-black min-h-screen text-white">
      <div class="mb-5 flex gap-2 flex-wrap">
        <button 
          @click="toggleInput"
          class="px-4 py-2 bg-gray-800 border border-gray-700 rounded hover:bg-gray-700"
        >
          {{ showInput ? 'отмена' : 'добавить' }}
        </button>
        <button 
          v-if="table.headers.length" 
          @click="clearTable"
          class="px-4 py-2 bg-red-900 text-red-200 border border-red-800 rounded hover:bg-red-700"
        >
          очистить таблицу
        </button>
        <button 
          v-if="table.headers.length && table.rows.length"
          @click="exportToExcel"
          class="px-4 py-2 bg-green-700 text-white rounded hover:bg-green-600"
        >
          эксель файл
        </button>
      </div>

      <div v-if="showInput" class="mb-5">
        <textarea 
          v-model="inputText" 
          placeholder="Paste your data here"
          class="w-full mb-2 p-2 border border-gray-700 rounded bg-gray-900 text-white"
          rows="10"
        ></textarea>
        <button 
          @click="saveData"
          class="px-4 py-2 bg-blue-600 rounded hover:bg-blue-700"
        >
          сохранить
        </button>
      </div>

      <div v-if="table.headers.length" class="overflow-x-auto">
        <table class="w-full border-collapse">
          <thead>
            <tr>
              <th 
                v-for="(header, colIndex) in table.headers" 
                :key="colIndex"
                class="bg-gray-900 border border-gray-700 p-2 text-left"
                @dblclick="startEditing(null, colIndex, true)"
              >
                <template v-if="editingCell.colIndex === colIndex && editingCell.isHeader">
                  <input
                    v-model="editValue"
                    @keyup.enter="saveEdit"
                    @keyup.esc="cancelEdit"
                    @blur="saveEdit"
                    class="w-full p-1 text-black bg-white"
                    autofocus
                  />
                </template>
                <template v-else>
                  {{ header }}
                </template>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="(row, rowIndex) in table.rows" 
              :key="rowIndex"
              class="even:bg-gray-800 hover:bg-gray-700"
            >
              <td 
                v-for="(cell, colIndex) in row" 
                :key="colIndex"
                class="border border-gray-700 p-2 relative"
                @dblclick="startEditing(rowIndex, colIndex)"
              >
                <template v-if="editingCell.rowIndex === rowIndex && editingCell.colIndex === colIndex && !editingCell.isHeader">
                  <input
                    v-model="editValue"
                    @keyup.enter="saveEdit"
                    @keyup.esc="cancelEdit"
                    @blur="saveEdit"
                    class="w-full p-1 text-black bg-white"
                    autofocus
                  />
                </template>
                <template v-else>
                  {{ cell }}
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>