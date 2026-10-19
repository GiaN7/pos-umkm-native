<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/lib/api'

interface Cashier {
  id: number
  name: string
  email: string
  status: boolean
}

interface CredentialForm {
  name: string
  email: string
  password: string
  password_confirmation: string
  current_password: string
}

const auth = useAuthStore()
const ownerForm = ref<CredentialForm>({
  name: auth.user?.name ?? '',
  email: auth.user?.email ?? '',
  password: '',
  password_confirmation: '',
  current_password: '',
})
const cashiers = ref<Cashier[]>([])
const cashierForms = ref<Record<number, CredentialForm>>({})
const loading = ref(false)
const savingOwner = ref(false)
const savingCashierId = ref<number | null>(null)
const message = ref('')
const isError = ref(false)

function emptyForm(name = '', email = ''): CredentialForm {
  return {
    name,
    email,
    password: '',
    password_confirmation: '',
    current_password: '',
  }
}

function errorMessage(error: any): string {
  const validationMessages = error.response?.data?.errors
  if (validationMessages) {
    return Object.values(validationMessages).flat()[0] as string
  }
  return error.response?.data?.message || 'Perubahan kredensial gagal disimpan.'
}

async function fetchCashiers() {
  loading.value = true
  try {
    const response = await api.get('/cashiers')
    cashiers.value = response.data
    cashierForms.value = Object.fromEntries(
      cashiers.value.map((cashier) => [cashier.id, emptyForm(cashier.name, cashier.email)])
    )
  } catch (error: any) {
    isError.value = true
    message.value = errorMessage(error)
  } finally {
    loading.value = false
  }
}

async function saveOwnerCredentials() {
  savingOwner.value = true
  isError.value = false
  message.value = ''

  const payload: Record<string, string> = {
    current_password: ownerForm.value.current_password,
  }
  if (ownerForm.value.name !== auth.user?.name) payload.name = ownerForm.value.name
  if (ownerForm.value.email !== auth.user?.email) payload.email = ownerForm.value.email
  if (ownerForm.value.password) {
    payload.password = ownerForm.value.password
    payload.password_confirmation = ownerForm.value.password_confirmation
  }

  try {
    const response = await api.put('/account/credentials', payload)
    auth.user = response.data.user
    ownerForm.value = emptyForm(response.data.user.name, response.data.user.email)
    message.value = response.data.message
  } catch (error: any) {
    isError.value = true
    message.value = errorMessage(error)
  } finally {
    savingOwner.value = false
  }
}

async function saveCashierCredentials(cashier: Cashier) {
  const form = cashierForms.value[cashier.id]
  savingCashierId.value = cashier.id
  isError.value = false
  message.value = ''

  const payload: Record<string, string> = {
    current_password: form.current_password,
  }
  if (form.name !== cashier.name) payload.name = form.name
  if (form.email !== cashier.email) payload.email = form.email
  if (form.password) {
    payload.password = form.password
    payload.password_confirmation = form.password_confirmation
  }

  try {
    const response = await api.put(`/cashiers/${cashier.id}`, payload)
    cashier.name = response.data.cashier.name
    cashier.email = response.data.cashier.email
    cashierForms.value[cashier.id] = emptyForm(cashier.name, cashier.email)
    message.value = response.data.message
  } catch (error: any) {
    isError.value = true
    message.value = errorMessage(error)
  } finally {
    savingCashierId.value = null
  }
}

onMounted(fetchCashiers)
</script>

<template>
  <section class="space-y-5 py-2">
    <header>
      <p class="text-xs font-bold uppercase tracking-[0.18em] text-secondary-foreground">Pengaturan</p>
      <h1 class="mt-1 text-2xl font-bold">Akun & keamanan</h1>
      <p class="mt-1 text-sm text-muted-foreground">
        Perbarui email login dan password owner maupun kasir.
      </p>
    </header>

    <div
      v-if="message"
      class="rounded-xl px-4 py-3 text-sm"
      :class="isError ? 'bg-error-container text-on-error-container' : 'bg-green-50 text-green-800'"
      role="status"
    >
      {{ message }}
    </div>

    <form class="warm-card space-y-4 rounded-2xl p-5" @submit.prevent="saveOwnerCredentials">
      <div>
        <h2 class="font-bold">Akun owner</h2>
        <p class="mt-1 text-sm text-muted-foreground">Masukkan password saat ini untuk menyimpan perubahan.</p>
      </div>

      <label class="block space-y-2 text-sm font-medium">
        <span>Nama owner</span>
        <input
          v-model="ownerForm.name"
          type="text"
          autocomplete="name"
          required
          class="warm-input h-11 w-full rounded-xl px-3 font-normal"
        />
      </label>

      <label class="block space-y-2 text-sm font-medium">
        <span>Email login</span>
        <input
          v-model="ownerForm.email"
          type="email"
          autocomplete="email"
          required
          class="warm-input h-11 w-full rounded-xl px-3 font-normal"
        />
      </label>

      <label class="block space-y-2 text-sm font-medium">
        <span>Password baru <span class="font-normal text-muted-foreground">(kosongkan jika tidak diganti)</span></span>
        <input
          v-model="ownerForm.password"
          type="password"
          autocomplete="new-password"
          minlength="8"
          class="warm-input h-11 w-full rounded-xl px-3 font-normal"
        />
      </label>

      <label v-if="ownerForm.password" class="block space-y-2 text-sm font-medium">
        <span>Konfirmasi password baru</span>
        <input
          v-model="ownerForm.password_confirmation"
          type="password"
          autocomplete="new-password"
          required
          class="warm-input h-11 w-full rounded-xl px-3 font-normal"
        />
      </label>

      <label class="block space-y-2 text-sm font-medium">
        <span>Password saat ini</span>
        <input
          v-model="ownerForm.current_password"
          type="password"
          autocomplete="current-password"
          required
          class="warm-input h-11 w-full rounded-xl px-3 font-normal"
        />
      </label>

      <button
        type="submit"
        class="warm-primary min-h-11 w-full rounded-xl px-4 text-sm font-semibold disabled:opacity-60"
        :disabled="savingOwner"
      >
        {{ savingOwner ? 'Menyimpan…' : 'Simpan akun owner' }}
      </button>
    </form>

    <div class="space-y-4">
      <div>
        <h2 class="text-lg font-bold">Akun kasir</h2>
        <p class="mt-1 text-sm text-muted-foreground">
          Konfirmasi password owner setiap kali mengubah kredensial kasir.
        </p>
      </div>

      <p v-if="loading" class="text-sm text-muted-foreground">Memuat akun kasir…</p>
      <p v-else-if="!cashiers.length" class="rounded-xl bg-muted p-4 text-sm text-muted-foreground">
        Belum ada akun kasir.
      </p>

      <form
        v-for="cashier in cashiers"
        :key="cashier.id"
        class="warm-card space-y-4 rounded-2xl p-5"
        @submit.prevent="saveCashierCredentials(cashier)"
      >
        <div>
          <h3 class="font-bold">{{ cashier.name }}</h3>
          <p class="text-sm text-muted-foreground">{{ cashier.status ? 'Aktif' : 'Nonaktif' }}</p>
        </div>

        <label class="block space-y-2 text-sm font-medium">
          <span>Nama kasir</span>
          <input
            v-model="cashierForms[cashier.id].name"
            type="text"
            autocomplete="name"
            required
            class="warm-input h-11 w-full rounded-xl px-3 font-normal"
          />
        </label>

        <label class="block space-y-2 text-sm font-medium">
          <span>Email login</span>
          <input
            v-model="cashierForms[cashier.id].email"
            type="email"
            autocomplete="email"
            required
            class="warm-input h-11 w-full rounded-xl px-3 font-normal"
          />
        </label>

        <label class="block space-y-2 text-sm font-medium">
          <span>Password baru <span class="font-normal text-muted-foreground">(kosongkan jika tidak diganti)</span></span>
          <input
            v-model="cashierForms[cashier.id].password"
            type="password"
            autocomplete="new-password"
            minlength="8"
            class="warm-input h-11 w-full rounded-xl px-3 font-normal"
          />
        </label>

        <label v-if="cashierForms[cashier.id].password" class="block space-y-2 text-sm font-medium">
          <span>Konfirmasi password baru</span>
          <input
            v-model="cashierForms[cashier.id].password_confirmation"
            type="password"
            autocomplete="new-password"
            required
            class="warm-input h-11 w-full rounded-xl px-3 font-normal"
          />
        </label>

        <label class="block space-y-2 text-sm font-medium">
          <span>Password owner saat ini</span>
          <input
            v-model="cashierForms[cashier.id].current_password"
            type="password"
            autocomplete="current-password"
            required
            class="warm-input h-11 w-full rounded-xl px-3 font-normal"
          />
        </label>

        <button
          type="submit"
          class="warm-primary min-h-11 w-full rounded-xl px-4 text-sm font-semibold disabled:opacity-60"
          :disabled="savingCashierId === cashier.id"
        >
          {{ savingCashierId === cashier.id ? 'Menyimpan…' : `Simpan akun ${cashier.name}` }}
        </button>
      </form>
    </div>
  </section>
</template>
