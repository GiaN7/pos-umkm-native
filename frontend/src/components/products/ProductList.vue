<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import api from '@/lib/api'
import ProductImage from '@/components/products/ProductImage.vue'
import { compressProductImage } from '@/lib/compressProductImage'

interface Product {
  id: number
  category_id: number | null
  category_name: string | null
  name: string
  sku: string
  barcode: string | null
  harga_beli: string
  harga_jual: string
  stok: number
  stok_minimal: number
  status: boolean
  image: string | null
}

interface Category {
  id: number
  name: string
}

const products = ref<Product[]>([])
const categories = ref<Category[]>([])
const loading = ref(false)
const showForm = ref(false)
const editingProduct = ref<Product | null>(null)
const selectedImage = ref<File | null>(null)
const imagePreview = ref<string | null>(null)
const imageError = ref('')
const saving = ref(false)
const removeImage = ref(false)
const form = ref({
  category_id: null as number | null,
  name: '',
  sku: '',
  barcode: '',
  harga_beli: 0,
  harga_jual: 0,
  stok: 0,
  stok_minimal: 0,
  status: true,
})
const message = ref('')

async function fetchProducts() {
  loading.value = true
  try {
    const response = await api.get('/products')
    products.value = response.data.data
  } catch (err: any) {
    message.value = err.response?.data?.message || 'Failed to fetch products'
  } finally {
    loading.value = false
  }
}

async function fetchCategories() {
  try {
    const response = await api.get('/categories')
    categories.value = response.data.data
  } catch (err: any) {
    console.error('Failed to fetch categories', err)
  }
}

function openAddForm() {
  clearSelectedImage()
  editingProduct.value = null
  removeImage.value = false
  imageError.value = ''
  form.value = {
    category_id: null,
    name: '',
    sku: '',
    barcode: '',
    harga_beli: 0,
    harga_jual: 0,
    stok: 0,
    stok_minimal: 0,
    status: true,
  }
  showForm.value = true
}

function openEditForm(product: Product) {
  clearSelectedImage()
  editingProduct.value = product
  removeImage.value = false
  imageError.value = ''
  form.value = {
    category_id: product.category_id,
    name: product.name,
    sku: product.sku,
    barcode: product.barcode || '',
    harga_beli: Number(product.harga_beli),
    harga_jual: Number(product.harga_jual),
    stok: product.stok,
    stok_minimal: product.stok_minimal,
    status: product.status,
  }
  showForm.value = true
}

function clearSelectedImage() {
  if (imagePreview.value) {
    URL.revokeObjectURL(imagePreview.value)
  }
  imagePreview.value = null
  selectedImage.value = null
}

async function handleImageChange(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  imageError.value = ''

  if (!file) return

  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
    imageError.value = 'Pilih foto berformat JPG, PNG, atau WebP.'
    return
  }

  if (file.size > 20 * 1024 * 1024) {
    imageError.value = 'Ukuran foto maksimal 20 MB sebelum dikompres.'
    return
  }

  try {
    const compressed = await compressProductImage(file)
    clearSelectedImage()
    selectedImage.value = compressed
    imagePreview.value = URL.createObjectURL(compressed)
    removeImage.value = false
  } catch (err) {
    imageError.value = err instanceof Error ? err.message : 'Foto tidak dapat diproses.'
  }
}

function discardImage() {
  clearSelectedImage()
  removeImage.value = Boolean(editingProduct.value?.image)
}

function buildProductFormData() {
  const payload = new FormData()

  Object.entries(form.value).forEach(([key, value]) => {
    if (key === 'category_id' && value === null) return
    if (key === 'barcode' && !value) return
    payload.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : String(value))
  })

  if (selectedImage.value) {
    payload.append('image', selectedImage.value)
  }

  if (removeImage.value) {
    payload.append('remove_image', '1')
  }

  if (editingProduct.value) {
    payload.append('_method', 'PUT')
  }

  return payload
}

async function saveProduct() {
  saving.value = true
  try {
    const payload = buildProductFormData()
    if (editingProduct.value) {
      await api.post(`/products/${editingProduct.value.id}`, payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      message.value = 'Produk berhasil diperbarui'
    } else {
      await api.post('/products', payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      message.value = 'Produk berhasil ditambahkan'
    }
    showForm.value = false
    clearSelectedImage()
    await fetchProducts()
  } catch (err: any) {
    message.value = err.response?.data?.message || 'Gagal menyimpan produk'
  } finally {
    saving.value = false
  }
}

async function deleteProduct(id: number) {
  if (!confirm('Are you sure you want to delete this product?')) return

  try {
    await api.delete(`/products/${id}`)
    message.value = 'Produk berhasil dihapus'
    fetchProducts()
  } catch (err: any) {
    message.value = err.response?.data?.message || 'Failed to delete product'
  }
}

function formatRupiah(value: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value)
}

onMounted(() => {
  fetchProducts()
  fetchCategories()
})

onBeforeUnmount(clearSelectedImage)
</script>

<template>
  <div class="space-y-4 py-2">
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-foreground">Produk</h1>
      <button
        type="button"
        @click="openAddForm"
        class="min-h-12 rounded-2xl warm-primary px-4 font-semibold text-white hover:warm-primary/90"
      >
        Tambah Produk
      </button>
    </div>

    <div v-if="message" class="mb-4 p-3 warm-card rounded-lg p-3">
      {{ message }}
    </div>

    <!-- Form -->
    <div v-if="showForm" class="mb-6 warm-card rounded-lg p-4 sm:p-5">
      <h2 class="mb-4 text-lg font-semibold">
        {{ editingProduct ? 'Edit Produk' : 'Tambah Produk' }}
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Name</label>
          <input
            v-model="form.name"
            type="text"
            class="warm-input w-full px-3 py-2 rounded-md border border-border focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="Product name"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Category</label>
          <select
            v-model="form.category_id"
            class="warm-input w-full px-3 py-2 rounded-md border border-border focus:outline-none focus:ring-2 focus:ring-primary"
          >
            <option :value="null">No Category</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">SKU</label>
          <input
            v-model="form.sku"
            type="text"
            class="warm-input w-full px-3 py-2 rounded-md border border-border focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="SKU"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Barcode</label>
          <input
            v-model="form.barcode"
            type="text"
            class="warm-input w-full px-3 py-2 rounded-md border border-border focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="Barcode"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Harga Beli</label>
          <input
            v-model.number="form.harga_beli"
            type="number"
            class="warm-input w-full px-3 py-2 rounded-md border border-border focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="0"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Harga Jual</label>
          <input
            v-model.number="form.harga_jual"
            type="number"
            class="warm-input w-full px-3 py-2 rounded-md border border-border focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="0"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Stok</label>
          <input
            v-model.number="form.stok"
            type="number"
            class="warm-input w-full px-3 py-2 rounded-md border border-border focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="0"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Stok Minimal</label>
          <input
            v-model.number="form.stok_minimal"
            type="number"
            class="warm-input w-full px-3 py-2 rounded-md border border-border focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="0"
          />
        </div>
        <div class="md:col-span-2">
          <label for="product-image" class="mb-2 block text-sm font-medium text-muted-foreground">Foto produk</label>
          <div class="flex flex-col gap-4 rounded-2xl border border-dashed border-border p-3 sm:flex-row sm:items-center">
            <div class="h-28 w-full shrink-0 overflow-hidden rounded-xl bg-white sm:w-36">
              <img
                v-if="imagePreview"
                :src="imagePreview"
                :alt="`Pratinjau foto ${form.name || 'produk'}`"
                class="h-full w-full object-cover"
              />
              <ProductImage
                v-else
                :image="removeImage ? null : editingProduct?.image || null"
                :name="form.name || 'Foto produk'"
                class="h-full w-full"
              />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-semibold text-foreground">
                {{ selectedImage ? selectedImage.name : editingProduct?.image && !removeImage ? 'Foto saat ini' : 'Pilih foto asli produk' }}
              </p>
              <p class="mt-1 text-xs leading-5 text-muted-foreground">
                JPG, PNG, atau WebP. Foto otomatis diperkecil hingga 1600 px dan disimpan sebagai WebP berkualitas tinggi.
              </p>
              <div class="mt-3 flex flex-wrap items-center gap-2">
                <label
                  for="product-image"
                  class="inline-flex min-h-11 cursor-pointer items-center rounded-xl bg-blue-100 px-4 text-sm font-semibold text-blue-800 transition hover:bg-blue-200"
                >
                  {{ selectedImage || (editingProduct?.image && !removeImage) ? 'Ganti foto' : 'Pilih foto' }}
                </label>
                <button
                  v-if="selectedImage || (editingProduct?.image && !removeImage)"
                  type="button"
                  class="min-h-11 rounded-xl px-3 text-sm font-semibold text-red-700 hover:bg-red-50"
                  @click="discardImage"
                >
                  Hapus foto
                </button>
                <span v-if="selectedImage" class="text-xs tabular-nums text-muted-foreground">
                  {{ (selectedImage.size / 1024).toFixed(0) }} KB
                </span>
              </div>
              <input
                id="product-image"
                class="sr-only"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                @change="handleImageChange"
              />
            </div>
          </div>
          <p v-if="imageError" class="mt-2 text-sm text-red-600" role="alert">{{ imageError }}</p>
        </div>
        <div class="flex items-center">
          <input
            v-model="form.status"
            type="checkbox"
            id="status"
            class="warm-primary rounded border-gray-300 focus:ring-primary"
          />
          <label for="status" class="ml-2 block text-muted-foreground">Active</label>
        </div>
      </div>
      <div class="flex gap-2 mt-4">
        <button
          type="button"
          @click="saveProduct"
          :disabled="saving"
          class="min-h-12 warm-primary rounded-2xl px-4 font-semibold text-white hover:warm-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
        >
          {{ saving ? 'Menyimpan…' : 'Simpan Produk' }}
        </button>
        <button
          type="button"
          @click="showForm = false; clearSelectedImage()"
          class="min-h-12 warm-card rounded-2xl px-4 font-semibold text-foreground hover:bg-muted"
        >
          Batal
        </button>
      </div>
    </div>

    <!-- List -->
    <div v-if="loading" class="text-center py-8">Loading...</div>

    <div v-else class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
      <p class="px-4 pt-3 text-xs text-muted-foreground sm:hidden">Geser tabel ke samping untuk melihat semua kolom.</p>
      <div
        class="max-w-full overflow-x-auto"
        role="region"
        aria-label="Daftar produk"
        tabindex="0"
      >
        <table class="min-w-[980px] divide-y divide-border">
          <thead class="bg-card">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Foto</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">SKU</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Category</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Harga Beli</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Harga Jual</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Stok</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-card">
            <tr v-for="product in products" :key="product.id">
              <td class="px-4 py-3 whitespace-nowrap">
                <ProductImage
                  :image="product.image"
                  :name="product.name"
                  class="h-12 w-12 rounded-xl border border-border bg-muted-foreground/5"
                />
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ product.name }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ product.sku }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ product.category_name || '-' }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ formatRupiah(Number(product.harga_beli)) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ formatRupiah(Number(product.harga_jual)) }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'text-sm font-medium',
                    product.stok <= product.stok_minimal ? 'text-red-600' : 'text-foreground'
                  ]"
                >
                  {{ product.stok }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded-full',
                    product.status ? 'warm-card bg-emerald-50 text-emerald-700' : 'warm-card bg-rose-50 text-rose-700'
                  ]"
                >
                  {{ product.status ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <button
                  @click="openEditForm(product)"
                  class="text-primary hover:text-primary-foreground mr-3"
                >
                  Edit
                </button>
                <button
                  @click="deleteProduct(product.id)"
                  class="text-red-600 hover:text-red-800"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
