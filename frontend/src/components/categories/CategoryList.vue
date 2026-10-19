<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { Plus, Edit, Trash2, FolderTree, CheckCircle, AlertTriangle } from 'lucide-vue-next'

interface Category {
  id: number
  name: string
  parent_id: number | null
  status: boolean
}

const categories = ref<Category[]>([])
const loading = ref(false)
const showModal = ref(false)
const editingCategory = ref<Category | null>(null)
const form = ref({ name: '', parent_id: null as number | null, status: true })
const message = ref<{ text: string; type: 'success' | 'error' } | null>(null)

async function fetchCategories() {
  loading.value = true
  try {
    const response = await api.get('/categories', { params: { per_page: 100 } })
    categories.value = response.data.data
  } catch (err: any) {
    message.value = {
      text: err.response?.data?.message || 'Gagal mengambil data kategori',
      type: 'error',
    }
  } finally {
    loading.value = false
  }
}

function openAddForm() {
  editingCategory.value = null
  form.value = { name: '', parent_id: null, status: true }
  showModal.value = true
}

function openEditForm(category: Category) {
  editingCategory.value = category
  form.value = { name: category.name, parent_id: category.parent_id, status: category.status }
  showModal.value = true
}

async function saveCategory() {
  try {
    const payload = {
      name: form.value.name,
      parent_id: form.value.parent_id ? Number(form.value.parent_id) : null,
      status: form.value.status,
    }

    if (editingCategory.value) {
      await api.put(`/categories/${editingCategory.value.id}`, payload)
      message.value = { text: 'Kategori berhasil diperbarui', type: 'success' }
    } else {
      await api.post('/categories', payload)
      message.value = { text: 'Kategori berhasil ditambahkan', type: 'success' }
    }
    showModal.value = false
    await fetchCategories()
    setTimeout(() => { message.value = null }, 3500)
  } catch (err: any) {
    message.value = {
      text: err.response?.data?.message || 'Gagal menyimpan kategori',
      type: 'error',
    }
  }
}

async function deleteCategory(id: number, name: string) {
  if (!confirm(`Hapus kategori "${name}"?`)) return

  try {
    await api.delete(`/categories/${id}`)
    message.value = { text: 'Kategori berhasil dihapus', type: 'success' }
    await fetchCategories()
    setTimeout(() => { message.value = null }, 3500)
  } catch (err: any) {
    message.value = {
      text: err.response?.data?.message || 'Gagal menghapus kategori',
      type: 'error',
    }
  }
}

onMounted(fetchCategories)
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">Kategori Produk</h1>
        <p class="text-sm text-muted-foreground">Kelola kelompok produk toko untuk memudahkan filter dan klasifikasi penjualan.</p>
      </div>
      <button
        @click="openAddForm"
        class="inline-flex items-center gap-2 px-4 py-2.5 warm-primary rounded-lg text-white font-medium hover:warm-primary/90 transition-colors"
      >
        <Plus class="w-4 h-4" />
        Tambah Kategori
      </button>
    </div>

    <!-- Alert Message -->
    <div
      v-if="message"
      :class="[
        'flex items-center gap-2 p-4 rounded-lg text-sm transition-all',
        message.type === 'success' ? 'warm-card bg-emerald-50 text-emerald-700 border border-emerald-200' : 'warm-card bg-rose-50 text-rose-700 border border-rose-200'
      ]"
    >
      <CheckCircle v-if="message.type === 'success'" class="w-4 h-4 flex-shrink-0" />
      <AlertTriangle v-else class="w-4 h-4 flex-shrink-0" />
      <span>{{ message.text }}</span>
    </div>

    <!-- List Table -->
    <div class="warm-card rounded-xl border border-border shadow-sm overflow-hidden">
      <div v-if="loading" class="p-12 text-center text-muted-foreground">
        <FolderTree class="w-8 h-8 mx-auto animate-pulse mb-2 text-primary" />
        Memuat kategori...
      </div>

      <div v-else-if="categories.length === 0" class="p-12 text-center text-muted-foreground">
        <FolderTree class="w-10 h-10 mx-auto mb-3 text-muted-foreground" />
        <p class="font-medium">Belum ada kategori produk.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm text-muted-foreground">
          <thead class="bg-card text-xs font-semibold text-muted-foreground uppercase tracking-wider border-b border-border">
            <tr>
              <th class="px-6 py-3.5">Nama Kategori</th>
              <th class="px-6 py-3.5">Induk Kategori</th>
              <th class="px-6 py-3.5 text-center">Status</th>
              <th class="px-6 py-3.5 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-muted-foreground/20">
            <tr v-for="cat in categories" :key="cat.id" class="hover:warm-card">
              <td class="px-6 py-4 font-medium text-foreground">
                <div class="flex items-center gap-2">
                  <FolderTree class="w-4 h-4 text-primary" />
                  <span>{{ cat.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-xs text-muted-foreground">
                {{ categories.find(c => c.id === cat.parent_id)?.name || 'Root (Utama)' }}
              </td>
              <td class="px-6 py-4 text-center">
                <span
                  :class="[
                    'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                    cat.status ? 'warm-card bg-emerald-50 text-emerald-700' : 'warm-card bg-rose-50 text-rose-700'
                  ]"
                >
                  {{ cat.status ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="inline-flex items-center gap-2">
                  <button
                    @click="openEditForm(cat)"
                    class="p-1.5 text-muted-foreground hover:text-primary rounded-md hover:warm/5 transition-colors"
                    title="Edit"
                  >
                    <Edit class="w-4 h-4" />
                  </button>
                  <button
                    @click="deleteCategory(cat.id, cat.name)"
                    class="p-1.5 text-muted-foreground hover:text-destructive rounded-md hover:warm/5 transition-colors"
                    title="Hapus"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-muted/20 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="warm-card rounded-2xl max-w-md w-full p-6 shadow-lg border border-border">
        <h2 class="text-xl font-bold text-foreground mb-4">
          {{ editingCategory ? 'Edit Kategori' : 'Tambah Kategori Baru' }}
        </h2>

        <form @submit.prevent="saveCategory" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1">Nama Kategori *</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 text-sm border border-border rounded-lg focus:ring-2 focus:ring-primary focus:outline-none"
              placeholder="Contoh: Makanan Ringan"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1">Induk Kategori</label>
            <select
              v-model="form.parent_id"
              class="w-full px-3 py-2 text-sm border border-border rounded-lg focus:ring-2 focus:ring-primary focus:outline-none"
            >
              <option :value="null">-- Tidak Ada (Kategori Utama) --</option>
              <option
                v-for="cat in categories.filter(c => c.id !== editingCategory?.id)"
                :key="cat.id"
                :value="cat.id"
              >
                {{ cat.name }}
              </option>
            </select>
          </div>

          <div class="flex items-center gap-2 pt-1">
            <input
              id="cat-status"
              v-model="form.status"
              type="checkbox"
              class="w-4 h-4 text-primary rounded border-muted-300 focus:ring-primary"
            />
            <label for="cat-status" class="ml-2 block text-sm font-medium text-foreground">Status Aktif</label>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-border">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 text-sm text-muted-foreground hover:bg-muted transition-colors"
            >
              Batal
            </button>
            <button
              type="submit"
              class="px-5 py-2 text-sm warm-primary hover:warm-primary/90 text-white font-medium rounded-lg shadow-sm transition-colors"
            >
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
