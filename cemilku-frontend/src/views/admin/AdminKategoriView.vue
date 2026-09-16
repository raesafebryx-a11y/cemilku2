<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'

import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const isNavbarDark = ref(false)
const logoCemilku = ref('/images/cemilku-logo.png')
const loading = ref(true)
const search = ref('')
const showModal = ref(false)
const isEditing = ref(false)
const saving = ref(false)

const categories = ref([])

const form = ref({
  id: null,
  name: '',
  slug: ''
})

const loadTheme = () => {
  const savedTheme = localStorage.getItem('admin-theme-mode')
  if (savedTheme) {
    isNavbarDark.value = savedTheme === 'dark'
  }
}

const handleThemeChange = (event) => {
  if (event.detail?.dark !== undefined) {
    isNavbarDark.value = event.detail.dark
  }
}

const toggleNavbarTheme = () => {
  isNavbarDark.value = !isNavbarDark.value
  localStorage.setItem('admin-theme-mode', isNavbarDark.value ? 'dark' : 'light')

  window.dispatchEvent(
    new CustomEvent('admin-theme-change', {
      detail: { dark: isNavbarDark.value }
    })
  )
}

const handleLogoError = (event) => {
  event.target.src = 'https://cdn-icons-png.flaticon.com/512/2553/2553691.png'
}

const fetchCategories = async () => {
  loading.value = true

  try {
    const response = await api.get('/categories')
    const payload = response.data

    if (Array.isArray(payload)) {
      categories.value = payload
    } else if (Array.isArray(payload.data)) {
      categories.value = payload.data
    } else if (payload.data && Array.isArray(payload.data.data)) {
      categories.value = payload.data.data
    } else {
      categories.value = []
    }
  } catch (error) {
    console.error('Gagal mengambil kategori:', error)
    await Swal.fire({
      icon: 'error',
      title: 'Gagal Memuat Kategori',
      text: error.response?.data?.message || 'Data kategori tidak dapat diambil.',
      confirmButtonColor: '#2563eb'
    })
  } finally {
    loading.value = false
  }
}

const filteredCategories = computed(() => {
  const keyword = search.value.trim().toLowerCase()
  if (!keyword) return categories.value

  return categories.value.filter((category) => {
    const name = String(category.name || '').toLowerCase()
    const slug = String(category.slug || '').toLowerCase()
    return name.includes(keyword) || slug.includes(keyword)
  })
})

const totalCategories = computed(() => categories.value.length)

const totalProducts = computed(() => {
  return categories.value.reduce((total, category) => {
    return total + Number(category.products_count || category.productsCount || 0)
  }, 0)
})

const activeCategories = computed(() => {
  return categories.value.filter((category) => {
    return Number(category.products_count || category.productsCount || 0) > 0
  }).length
})

const openAddModal = () => {
  isEditing.value = false
  form.value = { id: null, name: '', slug: '' }
  showModal.value = true
}

const openEditModal = (category) => {
  isEditing.value = true
  form.value = {
    id: category.id,
    name: category.name || '',
    slug: category.slug || ''
  }
  showModal.value = true
}

const closeModal = () => {
  if (saving.value) return
  showModal.value = false
  form.value = { id: null, name: '', slug: '' }
  isEditing.value = false
}

const generateSlug = () => {
  form.value.slug = form.value.name
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
}

const saveCategory = async () => {
  if (!form.value.name.trim()) {
    await Swal.fire({
      icon: 'warning',
      title: 'Nama kategori kosong',
      text: 'Silakan masukkan nama kategori.',
      confirmButtonColor: '#2563eb'
    })
    return
  }

  saving.value = true

  try {
    const payload = {
      name: form.value.name.trim(),
      slug: form.value.slug.trim() || null
    }

    if (isEditing.value) {
      await api.put(`/categories/${form.value.id}`, payload)
      await Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Kategori berhasil diperbarui.',
        timer: 1500,
        showConfirmButton: false
      })
    } else {
      await api.post('/categories', payload)
      await Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Kategori berhasil ditambahkan.',
        timer: 1500,
        showConfirmButton: false
      })
    }

    closeModal()
    await fetchCategories()
  } catch (error) {
    console.error('Gagal menyimpan kategori:', error)

    let message = error.response?.data?.message || 'Kategori gagal disimpan.'

    if (error.response?.data?.errors) {
      const firstError = Object.values(error.response.data.errors)[0]
      if (Array.isArray(firstError) && firstError[0]) {
        message = firstError[0]
      }
    }

    await Swal.fire({
      icon: 'error',
      title: 'Gagal!',
      text: message,
      confirmButtonColor: '#2563eb'
    })
  } finally {
    saving.value = false
  }
}

const deleteCategory = async (category) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: 'Hapus kategori?',
    html: `Kategori <strong>${category.name}</strong> akan dihapus.<br><small>Kategori yang sedang digunakan produk tidak dapat dihapus.</small>`,
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#64748b'
  })

  if (!result.isConfirmed) return

  try {
    await api.delete(`/categories/${category.id}`)
    await Swal.fire({
      icon: 'success',
      title: 'Terhapus!',
      text: 'Kategori berhasil dihapus.',
      timer: 1500,
      showConfirmButton: false
    })
    await fetchCategories()
  } catch (error) {
    console.error('Gagal menghapus kategori:', error)
    await Swal.fire({
      icon: 'error',
      title: 'Tidak dapat dihapus',
      text: error.response?.data?.message || 'Kategori tidak dapat dihapus.',
      confirmButtonColor: '#2563eb'
    })
  }
}

const handleLogout = async () => {
  const result = await Swal.fire({
    icon: 'question',
    title: 'Keluar dari Admin?',
    text: 'Kamu akan keluar dari dashboard.',
    showCancelButton: true,
    confirmButtonText: 'Ya, Keluar',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#dc2626'
  })

  if (!result.isConfirmed) return

  await auth.logout()
  router.push('/login')
}

const goTo = (path) => {
  router.push(path)
}

const checkAdmin = () => {
  if (!auth.isLoggedIn) {
    router.replace('/login')
    return false
  }

  if (auth.user && auth.userRole !== 'admin') {
    router.replace('/')
    return false
  }

  return true
}

onMounted(async () => {
  loadTheme()
  window.addEventListener('admin-theme-change', handleThemeChange)

  if (!auth.isLoggedIn) {
    router.replace('/login')
    return
  }

  if (!auth.user) {
    try {
      await auth.fetchUser()
    } catch (error) {
      console.error('Gagal mengambil data user:', error)
      router.replace('/login')
      return
    }
  }

  if (!checkAdmin()) {
    return
  }

  await fetchCategories()
})

onUnmounted(() => {
  window.removeEventListener('admin-theme-change', handleThemeChange)
})
</script>

<template>
  <div class="dashboard-layout" :class="{ 'navbar-dark': isNavbarDark }">
    <aside class="sidebar">
      <div class="sidebar-brand" @click="router.push('/')">
        <div class="brand-mark">
          <img :src="logoCemilku" alt="Logo Cemilku" class="cemilku-logo-img" draggable="false" @error="handleLogoError" />
        </div>

        <div class="brand-info">
          <span class="brand-text">Cemilku</span>
          <small>SNACK STORE</small>
        </div>
      </div>

      <nav class="sidebar-menu">
        <div class="menu-category">MAIN</div>

        <button class="menu-item" @click="goTo('/admin')">
          <span class="menu-icon">📊</span>
          <span class="menu-text">Dashboard</span>
        </button>

        <div class="menu-category">KELOLA TOKO</div>

        <button class="menu-item" @click="goTo('/admin/produk')">
          <span class="menu-icon">🍿</span>
          <span class="menu-text">Produk</span>
        </button>

        <button class="menu-item router-link-exact-active">
          <span class="menu-icon">🏷️</span>
          <span class="menu-text">Kategori</span>
        </button>

        <button class="menu-item" @click="goTo('/admin/order')">
          <span class="menu-icon">📑</span>
          <span class="menu-text">Order</span>
        </button>

        <button class="menu-item" @click="goTo('/admin/order-item')">
          <span class="menu-icon">📋</span>
          <span class="menu-text">Order Item</span>
        </button>

        <button class="menu-item" @click="goTo('/admin/kontak')">
          <span class="menu-icon">💬</span>
          <span class="menu-text">Pesan Kontak</span>
        </button>

        <div class="menu-category">SISTEM</div>

        <button class="menu-item" @click="goTo('/admin/pengaturan')">
          <span class="menu-icon">⚙️</span>
          <span class="menu-text">Pengaturan</span>
        </button>

        <a href="#" class="menu-item logout" @click.prevent="handleLogout">
          <span class="menu-icon">🚪</span>
          <span class="menu-text">Keluar</span>
        </a>
      </nav>
    </aside>

    <div class="main-wrapper">
      <header class="topbar">
        <div class="search-box">
          <span class="search-icon">🔍</span>
          <input v-model="search" type="text" placeholder="Cari kategori..." />
        </div>

        <div class="topbar-right">
          <button class="theme-toggle" aria-label="Toggle navbar theme" @click="toggleNavbarTheme">
            {{ isNavbarDark ? '☀️' : '🌙' }}
          </button>

          <button class="icon-btn" aria-label="Notifikasi">🔔</button>

          <div class="user-info">
            <div class="user-name">{{ auth.username || 'Admin' }}</div>
            <div class="user-role">Administrator</div>
          </div>

          <div class="user-avatar">
            {{ (auth.username || 'A').charAt(0).toUpperCase() }}
          </div>
        </div>
      </header>

      <main class="content-body">
        <div class="page-header">
          <div>
            <span class="section-kicker">Kelola Toko</span>
            <h1>Kategori</h1>
            <p>Kelola semua kategori produk Cemilku</p>
          </div>

          <button class="btn-primary-action" @click="openAddModal">
            <span class="btn-icon">＋</span>
            Tambah Kategori
          </button>
        </div>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Total Kategori</span>
              <div class="stat-icon-wrapper">🏷️</div>
            </div>
            <div class="stat-value">{{ totalCategories }}</div>
            <div class="stat-sub">Semua kategori</div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Produk Terhubung</span>
              <div class="stat-icon-wrapper">📦</div>
            </div>
            <div class="stat-value">{{ totalProducts }}</div>
            <div class="stat-sub">Total produk</div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Kategori Terpakai</span>
              <div class="stat-icon-wrapper">✓</div>
            </div>
            <div class="stat-value">{{ activeCategories }}</div>
            <div class="stat-sub">Memiliki produk</div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Hasil Pencarian</span>
              <div class="stat-icon-wrapper">🔎</div>
            </div>
            <div class="stat-value">{{ filteredCategories.length }}</div>
            <div class="stat-sub">Kategori ditemukan</div>
          </div>
        </div>

        <div class="table-card">
          <div class="table-header">
            <div>
              <h3>Daftar Kategori</h3>
              <p>{{ filteredCategories.length }} kategori tersedia</p>
            </div>

            <button v-if="search" class="view-all-btn" @click="search = ''">✕ Bersihkan</button>
          </div>

          <div v-if="loading" class="loading-card">
            <div class="spinner"></div>
            <p>Memuat kategori...</p>
          </div>

          <div v-else-if="filteredCategories.length === 0" class="empty-card">
            <div class="empty-icon">🗂️</div>
            <h3>{{ search ? 'Kategori Tidak Ditemukan' : 'Belum Ada Kategori' }}</h3>
            <p>{{ search ? 'Coba kata kunci lain.' : 'Tambahkan kategori pertama untuk produk Cemilku.' }}</p>
            <button v-if="!search" class="btn-primary-action" @click="openAddModal">
              ＋ Tambah Kategori
            </button>
          </div>

          <div v-else class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>KATEGORI</th>
                  <th>SLUG</th>
                  <th>PRODUK</th>
                  <th>STATUS</th>
                  <th>AKSI</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(category, index) in filteredCategories" :key="category.id">
                  <td><span class="row-number">{{ index + 1 }}</span></td>

                  <td>
                    <div class="category-cell">
                      <div class="category-icon">{{ (category.name || 'C').charAt(0).toUpperCase() }}</div>
                      <div>
                        <strong>{{ category.name }}</strong>
                        <small>ID #{{ category.id }}</small>
                      </div>
                    </div>
                  </td>

                  <td><span class="slug-badge">{{ category.slug || '-' }}</span></td>

                  <td>
                    <div class="product-meta">
                      <span class="product-count-icon">📦</span>
                      <strong>{{ category.products_count || category.productsCount || 0 }}</strong>
                      <span>produk</span>
                    </div>
                  </td>

                  <td>
                    <span class="status-badge" :class="Number(category.products_count || category.productsCount || 0) > 0 ? 'status-active' : 'status-inactive'">
                      {{ Number(category.products_count || category.productsCount || 0) > 0 ? 'Terpakai' : 'Kosong' }}
                    </span>
                  </td>

                  <td>
                    <div class="actions">
                      <button class="edit-button" title="Edit" @click="openEditModal(category)">✏️</button>
                      <button class="delete-button" title="Hapus" @click="deleteCategory(category)">🗑️</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>

    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <div>
            <h2>{{ isEditing ? 'Edit Kategori' : 'Tambah Kategori' }}</h2>
            <p>{{ isEditing ? 'Perbarui kategori produk' : 'Masukkan kategori produk baru' }}</p>
          </div>

          <button class="close-button" @click="closeModal">×</button>
        </div>

        <form class="category-form" @submit.prevent="saveCategory">
          <div class="form-group">
            <label>Nama Kategori <span>*</span></label>
            <input v-model="form.name" type="text" placeholder="Contoh: Keripik" maxlength="255" required @input="!isEditing && generateSlug()" />
          </div>

          <div class="form-group">
            <label>Slug</label>
            <div class="slug-input">
              <input v-model="form.slug" type="text" placeholder="keripik" maxlength="255" />
              <button type="button" @click="generateSlug">Generate</button>
            </div>
          </div>

          <div class="category-preview">
            <span>Preview</span>
            <div class="preview-content">
              <div class="preview-icon">{{ (form.name || 'C').charAt(0).toUpperCase() }}</div>
              <div>
                <strong>{{ form.name || 'Nama Kategori' }}</strong>
                <small>{{ form.slug || 'nama-kategori' }}</small>
              </div>
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" class="cancel-button" @click="closeModal" :disabled="saving">Batal</button>
            <button type="submit" class="save-button" :disabled="saving">
              <span v-if="saving">Menyimpan...</span>
              <span v-else>{{ isEditing ? 'Simpan Perubahan' : 'Tambah Kategori' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dashboard-layout {
  --page-bg: linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
  --sidebar-bg: rgba(255, 255, 255, 0.8);
  --sidebar-border: rgba(226, 232, 240, 0.9);
  --topbar-bg: rgba(255, 255, 255, 0.75);
  --topbar-border: rgba(226, 232, 240, 0.9);
  --surface: rgba(255, 255, 255, 0.9);
  --text: #0f172a;
  --muted: #64748b;
  --nav-text: #475569;
  --nav-hover: #eff6ff;
  --nav-active: linear-gradient(135deg, #2563eb, #3b82f6);
  --panel-border: rgba(226, 232, 240, 0.9);

  min-height: 100vh;
  display: flex;
  background: var(--page-bg);
  color: var(--text);
  font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
}

.dashboard-layout.navbar-dark {
  --page-bg: linear-gradient(180deg, #0f172a 0%, #111827 100%);
  --sidebar-bg: rgba(15, 23, 42, 0.85);
  --sidebar-border: rgba(51, 65, 85, 0.9);
  --topbar-bg: rgba(15, 23, 42, 0.8);
  --topbar-border: rgba(51, 65, 85, 0.9);
  --surface: rgba(15, 23, 42, 0.8);
  --text: #f8fafc;
  --muted: #cbd5e1;
  --nav-text: #cbd5e1;
  --nav-hover: rgba(59, 130, 246, 0.12);
  --nav-active: linear-gradient(135deg, #1d4ed8, #3b82f6);
  --panel-border: rgba(51, 65, 85, 0.9);
}

.sidebar {
  width: 250px;
  min-width: 250px;
  height: 100vh;
  position: fixed;
  left: 0;
  top: 0;
  background: var(--sidebar-bg);
  border-right: 1px solid var(--sidebar-border);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  z-index: 1000;
}

.sidebar-brand {
  height: 64px;
  padding: 12px 18px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid var(--sidebar-border);
  cursor: pointer;
  transition: opacity 0.2s ease;
  flex-shrink: 0;
}

.sidebar-brand:hover { opacity: 0.9; }

.brand-mark {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding: 3px;
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.18);
}

.cemilku-logo-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

.brand-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.brand-text {
  font-weight: 800;
  font-size: 1.3rem;
  color: var(--text);
  letter-spacing: -0.05em;
  line-height: 1.1;
}

.brand-info small {
  font-size: 0.58rem;
  letter-spacing: 0.16em;
  color: #2563eb;
  font-weight: 700;
  text-transform: uppercase;
  margin-top: 2px;
}

.sidebar-menu {
  padding: 16px 12px 20px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  overflow-y: auto;
}

.menu-category {
  font-size: 10px;
  font-weight: 700;
  color: var(--muted);
  padding: 12px 12px 5px;
  letter-spacing: 0.6px;
  text-transform: uppercase;
}

.menu-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  color: var(--nav-text);
  text-decoration: none;
  font-size: 14px;
  font-weight: 600;
  border-radius: 10px;
  border: none;
  background: transparent;
  cursor: pointer;
  transition: background 0.2s ease, color 0.2s ease;
  box-sizing: border-box;
}

.menu-item:hover {
  background: var(--nav-hover);
  color: var(--text);
}

.menu-item.router-link-active,
.menu-item.router-link-exact-active {
  background: var(--nav-active);
  color: #ffffff;
  box-shadow: 0 8px 16px rgba(37, 99, 235, 0.18);
}

.menu-item.logout {
  color: #dc2626;
  margin-top: 4px;
}

.menu-item.logout:hover {
  background: rgba(239, 68, 68, 0.08);
  color: #b91c1c;
}

.menu-icon {
  width: 22px;
  min-width: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
  line-height: 1;
}

.menu-text {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.main-wrapper {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  overflow-x: hidden;
  margin-left: 250px;
}

.topbar {
  height: 64px;
  background: var(--topbar-bg);
  border-bottom: 1px solid var(--topbar-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 28px;
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  flex-shrink: 0;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(148, 163, 184, 0.06);
  border: 1px solid var(--panel-border);
  padding: 8px 14px;
  border-radius: 10px;
  width: 300px;
}

.search-icon {
  font-size: 14px;
}

.search-box input {
  background: transparent;
  border: none;
  outline: none;
  color: var(--text);
  font-size: 13px;
  width: 100%;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.theme-toggle,
.icon-btn {
  background: rgba(148, 163, 184, 0.08);
  border: 1px solid var(--panel-border);
  border-radius: 10px;
  cursor: pointer;
  font-size: 16px;
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  color: var(--text);
}

.user-info {
  text-align: right;
  line-height: 1.2;
}

.user-name {
  font-size: 13px;
  font-weight: 800;
  color: var(--text);
}

.user-role {
  font-size: 10px;
  color: var(--muted);
  margin-top: 3px;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: #ffffff;
  display: grid;
  place-items: center;
  font-weight: 700;
}

.content-body {
  padding: 0 28px 28px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
  margin-bottom: 18px;
}

.section-kicker {
  display: block;
  font-size: 12px;
  color: #2563eb;
  font-weight: 700;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.page-header h1 {
  margin: 0;
  font-size: 28px;
  font-weight: 800;
  color: var(--text);
}

.page-header p {
  margin: 8px 0 0;
  color: var(--muted);
  font-size: 13px;
}

.btn-primary-action {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: #ffffff;
  border: none;
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.22);
  transition: all 0.3s ease;
  white-space: nowrap;
}

.btn-primary-action:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
}

.btn-icon { font-size: 18px; }

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 18px;
}

.stat-card {
  background: var(--surface);
  border: 1px solid var(--panel-border);
  border-radius: 16px;
  padding: 18px 20px;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
}

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.stat-label {
  color: var(--muted);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.stat-icon-wrapper {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  background: rgba(37, 99, 235, 0.08);
  font-size: 18px;
}

.stat-value {
  font-size: 30px;
  font-weight: 800;
  color: var(--text);
  line-height: 1.1;
}

.stat-sub {
  margin-top: 6px;
  font-size: 11px;
  color: var(--muted);
}

.table-card {
  background: var(--surface);
  border: 1px solid var(--panel-border);
  border-radius: 14px;
  overflow: hidden;
}

.table-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px;
  border-bottom: 1px solid var(--panel-border);
}

.table-header h3 {
  margin: 0;
  color: var(--text);
  font-size: 16px;
  font-weight: 800;
}

.table-header p {
  margin: 4px 0 0;
  color: var(--muted);
  font-size: 11px;
}

.view-all-btn {
  background: rgba(37, 99, 235, 0.08);
  color: #2563eb;
  border: none;
  border-radius: 8px;
  padding: 7px 10px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 700;
}

.loading-card,
.empty-card {
  background: var(--surface);
  border: 1px solid var(--panel-border);
  border-radius: 14px;
  min-height: 320px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  text-align: center;
}

.spinner {
  width: 35px;
  height: 35px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.empty-icon {
  font-size: 46px;
}

.empty-card h3 {
  margin: 13px 0 5px;
  color: var(--text);
}

.empty-card p,
.loading-card p {
  margin: 0;
  color: var(--muted);
  font-size: 13px;
}

.table-wrapper {
  width: 100%;
  overflow-x: auto;
}

.table-card table {
  width: 100%;
  min-width: 900px;
  border-collapse: collapse;
}

thead { background: rgba(148, 163, 184, 0.08); }

th {
  padding: 15px 18px;
  color: var(--muted);
  font-size: 10px;
  letter-spacing: 0.6px;
  font-weight: 800;
  text-align: left;
}

td {
  padding: 16px 18px;
  border-top: 1px solid var(--panel-border);
  font-size: 13px;
  color: var(--text);
}

.row-number {
  color: var(--muted);
  font-weight: 700;
}

.category-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.category-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  background: rgba(37, 99, 235, 0.08);
  color: #2563eb;
  font-weight: 800;
}

.category-cell strong {
  display: block;
  color: var(--text);
  font-size: 13px;
}

.category-cell small {
  display: block;
  color: var(--muted);
  margin-top: 3px;
  font-size: 10px;
}

.slug-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 8px;
  background: rgba(100, 116, 139, 0.08);
  color: var(--muted);
  font-size: 11px;
  font-weight: 700;
}

.product-meta {
  display: flex;
  align-items: center;
  gap: 6px;
}

.product-count-icon {
  font-size: 13px;
}

.product-meta strong {
  font-size: 13px;
}

.product-meta span:last-child {
  color: var(--muted);
  font-size: 10px;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 5px 8px;
  border-radius: 7px;
  font-size: 10px;
  font-weight: 750;
}

.status-active {
  background: #ecfdf5;
  color: #059669;
}

.status-inactive {
  background: #f8fafc;
  color: var(--muted);
}

.actions {
  display: flex;
  gap: 7px;
}

.edit-button,
.delete-button {
  width: 34px;
  height: 34px;
  border: none;
  border-radius: 9px;
  cursor: pointer;
  transition: 0.2s;
}

.edit-button { background: #eff6ff; }
.delete-button { background: #fef2f2; }
.edit-button:hover,
.delete-button:hover { transform: translateY(-1px); }

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 100;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(5px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal {
  width: 100%;
  max-width: 560px;
  max-height: 92vh;
  overflow-y: auto;
  background: var(--surface);
  border: 1px solid var(--panel-border);
  border-radius: 20px;
  box-shadow: 0 30px 80px rgba(15, 23, 42, 0.25);
}

.modal-header {
  padding: 22px 25px;
  border-bottom: 1px solid var(--panel-border);
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.modal-header h2 {
  margin: 0;
  font-size: 20px;
  color: var(--text);
}

.modal-header p {
  margin: 5px 0 0;
  color: var(--muted);
  font-size: 12px;
}

.close-button {
  width: 34px;
  height: 34px;
  border: none;
  border-radius: 9px;
  background: rgba(148, 163, 184, 0.08);
  color: var(--text);
  font-size: 22px;
  cursor: pointer;
}

.category-form {
  padding: 24px 25px;
}

.form-group {
  margin-bottom: 17px;
}

.form-group label {
  display: block;
  margin-bottom: 7px;
  color: var(--text);
  font-size: 12px;
  font-weight: 750;
}

.form-group label span {
  color: #ef4444;
}

.form-group input {
  width: 100%;
  border: 1px solid var(--panel-border);
  border-radius: 10px;
  padding: 11px 12px;
  background: rgba(148, 163, 184, 0.04);
  color: var(--text);
  outline: none;
  font-size: 13px;
  font-family: inherit;
  transition: 0.2s;
}

.form-group input:focus {
  border-color: #60a5fa;
  background: transparent;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
}

.slug-input {
  display: flex;
  gap: 8px;
}

.slug-input input {
  flex: 1;
}

.slug-input button {
  border: 1px solid var(--panel-border);
  background: rgba(37, 99, 235, 0.08);
  color: #2563eb;
  border-radius: 10px;
  padding: 0 12px;
  font-weight: 700;
  cursor: pointer;
}

.category-preview {
  padding: 14px;
  margin-bottom: 22px;
  border: 1px dashed var(--panel-border);
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.03);
}

.category-preview > span {
  display: block;
  margin-bottom: 10px;
  color: var(--muted);
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.preview-content {
  display: flex;
  align-items: center;
  gap: 10px;
}

.preview-icon {
  width: 40px;
  height: 40px;
  border-radius: 11px;
  display: grid;
  place-items: center;
  background: rgba(37, 99, 235, 0.08);
  color: #2563eb;
  font-weight: 800;
}

.preview-content strong {
  display: block;
  color: var(--text);
  font-size: 12px;
}

.preview-content small {
  display: block;
  margin-top: 3px;
  color: var(--muted);
  font-size: 10px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.cancel-button,
.save-button {
  border: none;
  padding: 11px 17px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 750;
  cursor: pointer;
}

.cancel-button {
  background: rgba(148, 163, 184, 0.08);
  color: var(--text);
}

.save-button {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: white;
}

.save-button:disabled,
.cancel-button:disabled { opacity: 0.6; cursor: not-allowed; }

@media (max-width: 900px) {
  .sidebar { width: 220px; }
  .main-wrapper { margin-left: 220px; }
  .content-body { padding: 0 20px 24px; }
  .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 700px) {
  .sidebar {
    position: relative;
    width: 100%;
    min-height: auto;
    height: auto;
  }

  .main-wrapper {
    width: 100%;
    margin-left: 0;
  }

  .dashboard-layout { display: block; }

  .topbar {
    height: auto;
    padding: 18px 20px;
    gap: 15px;
    flex-wrap: wrap;
  }

  .content-body { padding: 0 16px 20px; }

  .page-header {
    align-items: flex-start;
    flex-direction: column;
    gap: 16px;
  }

  .btn-primary-action {
    width: 100%;
    justify-content: center;
  }

  .stats-grid { grid-template-columns: 1fr; }

  .topbar-right { width: 100%; justify-content: flex-end; }
  .user-info { display: none; }
}
</style>
