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
const fallbackImage =
  'https://cdn-icons-png.flaticon.com/512/2553/2553691.png'

const products = ref([])
const categories = ref([])

const loading = ref(true)
const saving = ref(false)

const search = ref('')
const filterCategory = ref('')
const filterStatus = ref('')

const showModal = ref(false)
const editingProduct = ref(null)

const selectedImage = ref(null)
const imagePreview = ref(null)

const form = ref({
  category_id: '',
  name: '',
  slug: '',
  description: '',
  price: '',
  stock: 0,
  image: null,
  is_active: true
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

  localStorage.setItem(
    'admin-theme-mode',
    isNavbarDark.value ? 'dark' : 'light'
  )

  window.dispatchEvent(
    new CustomEvent('admin-theme-change', {
      detail: { dark: isNavbarDark.value }
    })
  )
}

const handleLogoError = (event) => {
  if (event.target.dataset.fallbackApplied === 'true') return
  event.target.dataset.fallbackApplied = 'true'
  event.target.src = fallbackImage
}

const handleProductImageError = (event) => {
  if (event.target.dataset.fallbackApplied === 'true') return
  event.target.dataset.fallbackApplied = 'true'
  event.target.src = fallbackImage
}

const checkAdmin = () => {
  if (!auth.token) {
    router.replace('/login')
    return false
  }

  if (auth.user && auth.user.role && auth.user.role !== 'admin') {
    router.replace('/')
    return false
  }

  return true
}

const fetchProducts = async () => {
  try {
    loading.value = true

    const response = await api.get('/products')
    const result = response.data

    if (Array.isArray(result)) {
      products.value = result
    } else if (Array.isArray(result.data)) {
      products.value = result.data
    } else if (result.data && Array.isArray(result.data.data)) {
      products.value = result.data.data
    } else {
      products.value = []
    }
  } catch (error) {
    console.error('Gagal mengambil produk:', error)

    Swal.fire({
      icon: 'error',
      title: 'Gagal Memuat Produk',
      text: error.response?.data?.message || 'Data produk tidak dapat diambil.',
      confirmButtonColor: '#2563eb'
    })
  } finally {
    loading.value = false
  }
}

const fetchCategories = async () => {
  try {
    const response = await api.get('/categories')
    const result = response.data

    if (Array.isArray(result)) {
      categories.value = result
    } else if (Array.isArray(result.data)) {
      categories.value = result.data
    } else {
      categories.value = []
    }
  } catch (error) {
    console.error('Gagal mengambil kategori:', error)

    Swal.fire({
      icon: 'error',
      title: 'Gagal Memuat Kategori',
      text: error.response?.data?.message || 'Data kategori tidak dapat diambil.',
      confirmButtonColor: '#2563eb'
    })
  }
}

const filteredProducts = computed(() => {
  let result = [...products.value]
  const keyword = search.value.trim().toLowerCase()

  if (keyword) {
    result = result.filter((product) => {
      const name = String(product.name || '').toLowerCase()
      return name.includes(keyword)
    })
  }

  if (filterCategory.value) {
    result = result.filter(
      (product) => String(product.category_id) === String(filterCategory.value)
    )
  }

  if (filterStatus.value === 'active') {
    result = result.filter(
      (product) => product.is_active === true || product.is_active === 1
    )
  }

  if (filterStatus.value === 'inactive') {
    result = result.filter(
      (product) => product.is_active === false || product.is_active === 0
    )
  }

  return result
})

const formatPrice = (price) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(Number(price || 0))
}

const stockStatus = (stock) => {
  const value = Number(stock || 0)

  if (value <= 0) {
    return { text: 'Habis', class: 'stock-empty' }
  }

  if (value <= 5) {
    return { text: 'Menipis', class: 'stock-low' }
  }

  return { text: 'Tersedia', class: 'stock-ready' }
}

const imageUrl = (image) => {
  if (!image) return fallbackImage

  const value = String(image).trim()

  if (!value) return fallbackImage

  if (/^https?:\/\//i.test(value)) return value

  const normalized = value
    .replace(/^\/+/, '')
    .replace(/^public\//i, '')
    .replace(/^storage\//i, '')

  const apiUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'
  const backendUrl = apiUrl.replace(/\/api\/?$/, '')

  return `${backendUrl}/storage/${normalized}`
}

const resetForm = () => {
  form.value = {
    category_id: '',
    name: '',
    slug: '',
    description: '',
    price: '',
    stock: 0,
    image: null,
    is_active: true
  }

  selectedImage.value = null
  imagePreview.value = null
  editingProduct.value = null
}

const openAddModal = () => {
  resetForm()
  showModal.value = true
}

const openEditModal = (product) => {
  editingProduct.value = product

  form.value = {
    category_id: product.category_id || '',
    name: product.name || '',
    slug: product.slug || '',
    description: product.description || '',
    price: product.price || '',
    stock: product.stock || 0,
    image: null,
    is_active: product.is_active === true || product.is_active === 1
  }

  selectedImage.value = null
  imagePreview.value = product.image ? imageUrl(product.image) : null
  showModal.value = true
}

const closeModal = () => {
  if (saving.value) return
  showModal.value = false
  resetForm()
}

const handleImageChange = (event) => {
  const file = event.target.files?.[0]

  if (!file) return

  const allowedTypes = ['image/jpeg', 'image/png', 'image/webp']

  if (!allowedTypes.includes(file.type)) {
    Swal.fire({
      icon: 'warning',
      title: 'Format Gambar Tidak Sesuai',
      text: 'Gunakan JPG, PNG, atau WEBP.',
      confirmButtonColor: '#2563eb'
    })

    event.target.value = ''
    return
  }

  if (file.size > 5 * 1024 * 1024) {
    Swal.fire({
      icon: 'warning',
      title: 'Ukuran Gambar Terlalu Besar',
      text: 'Ukuran gambar maksimal 5 MB.',
      confirmButtonColor: '#2563eb'
    })

    event.target.value = ''
    return
  }

  selectedImage.value = file
  form.value.image = file
  imagePreview.value = URL.createObjectURL(file)
}

const generateSlug = () => {
  if (editingProduct.value || !form.value.name) return

  form.value.slug = form.value.name
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
}

const saveProduct = async () => {
  if (!form.value.category_id) {
    Swal.fire({
      icon: 'warning',
      title: 'Kategori Belum Dipilih',
      text: 'Silakan pilih kategori produk.',
      confirmButtonColor: '#2563eb'
    })
    return
  }

  if (!form.value.name.trim()) {
    Swal.fire({
      icon: 'warning',
      title: 'Nama Produk Kosong',
      text: 'Nama produk wajib diisi.',
      confirmButtonColor: '#2563eb'
    })
    return
  }

  if (form.value.price === '' || Number(form.value.price) < 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Harga Tidak Valid',
      text: 'Masukkan harga produk yang benar.',
      confirmButtonColor: '#2563eb'
    })
    return
  }

  if (form.value.stock === '' || Number(form.value.stock) < 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Stok Tidak Valid',
      text: 'Masukkan stok produk yang benar.',
      confirmButtonColor: '#2563eb'
    })
    return
  }

  saving.value = true

  try {
    const formData = new FormData()

    formData.append('category_id', form.value.category_id)
    formData.append('name', form.value.name)
    formData.append('slug', form.value.slug || form.value.name)
    formData.append('description', form.value.description || '')
    formData.append('price', form.value.price)
    formData.append('stock', form.value.stock)
    formData.append('is_active', form.value.is_active ? '1' : '0')

    if (selectedImage.value) {
      formData.append('image', selectedImage.value)
    }

    if (editingProduct.value) {
      formData.append('_method', 'PUT')
      await api.post(`/products/${editingProduct.value.id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })

      await Swal.fire({
        icon: 'success',
        title: 'Produk Diperbarui',
        text: 'Produk berhasil diperbarui.',
        timer: 1500,
        showConfirmButton: false
      })
    } else {
      await api.post('/products', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })

      await Swal.fire({
        icon: 'success',
        title: 'Produk Ditambahkan',
        text: 'Produk baru berhasil ditambahkan.',
        timer: 1500,
        showConfirmButton: false
      })
    }

    closeModal()
    await fetchProducts()
  } catch (error) {
    console.error('Gagal menyimpan produk:', error)

    const validationErrors = error.response?.data?.errors
    let message = error.response?.data?.message || 'Produk gagal disimpan.'

    if (validationErrors) {
      message = Object.values(validationErrors).flat().join(', ')
    }

    Swal.fire({
      icon: 'error',
      title: 'Gagal Menyimpan Produk',
      text: message,
      confirmButtonColor: '#2563eb'
    })
  } finally {
    saving.value = false
  }
}

const deleteProduct = async (product) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: 'Hapus Produk?',
    text: `Produk "${product.name}" akan dihapus permanen.`,
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#64748b'
  })

  if (!result.isConfirmed) return

  try {
    await api.delete(`/products/${product.id}`)

    await Swal.fire({
      icon: 'success',
      title: 'Produk Dihapus',
      text: 'Produk berhasil dihapus.',
      timer: 1500,
      showConfirmButton: false
    })

    await fetchProducts()
  } catch (error) {
    console.error('Gagal menghapus produk:', error)

    Swal.fire({
      icon: 'error',
      title: 'Gagal Menghapus',
      text: error.response?.data?.message || 'Produk tidak dapat dihapus.',
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

onMounted(async () => {
  loadTheme()
  window.addEventListener('admin-theme-change', handleThemeChange)

  if (!checkAdmin()) return

  await Promise.all([fetchProducts(), fetchCategories()])
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
          <img
            :src="logoCemilku"
            alt="Logo Cemilku"
            class="cemilku-logo-img"
            draggable="false"
            @error="handleLogoError"
          />
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

        <button class="menu-item router-link-exact-active">
          <span class="menu-icon">🍿</span>
          <span class="menu-text">Produk</span>
        </button>

        <button class="menu-item" @click="goTo('/admin/kategori')">
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
          <input
            v-model="search"
            type="text"
            placeholder="Cari nama produk..."
          />
        </div>

        <div class="topbar-right">
          <button
            class="theme-toggle"
            aria-label="Toggle navbar theme"
            @click="toggleNavbarTheme"
          >
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
            <h1>Produk</h1>
            <p>Kelola semua produk Cemilku</p>
          </div>

          <button class="btn-primary-action" @click="openAddModal">
            <span class="btn-icon">＋</span>
            Tambah Produk
          </button>
        </div>

        <div class="filter-card">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input
              v-model="search"
              type="text"
              placeholder="Cari nama produk..."
            />
          </div>

          <select v-model="filterCategory">
            <option value="">Semua Kategori</option>
            <option
              v-for="category in categories"
              :key="category.id"
              :value="category.id"
            >
              {{ category.name || category.nama }}
            </option>
          </select>

          <select v-model="filterStatus">
            <option value="">Semua Status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
          </select>
        </div>

        <div v-if="loading" class="loading-card">
          <div class="spinner"></div>
          <p>Memuat produk...</p>
        </div>

        <div v-else-if="filteredProducts.length === 0" class="empty-card">
          <div class="empty-icon">🍿</div>
          <h3>Belum Ada Produk</h3>
          <p>Tambahkan produk pertama Cemilku kamu.</p>
          <button class="btn-primary-action" @click="openAddModal">
            ＋ Tambah Produk
          </button>
        </div>

        <div v-else class="table-card">
          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>PRODUK</th>
                  <th>KATEGORI</th>
                  <th>HARGA</th>
                  <th>STOK</th>
                  <th>STATUS</th>
                  <th>AKSI</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="product in filteredProducts" :key="product.id">
                  <td>
                    <div class="product-info">
                      <div class="product-image">
                        <img
                          v-if="product.image"
                          :src="imageUrl(product.image)"
                          :alt="product.name"
                          @error="handleProductImageError"
                        />
                        <span v-else>🍿</span>
                      </div>

                      <div>
                        <strong>{{ product.name }}</strong>
                        <small>{{ product.slug || '-' }}</small>
                      </div>
                    </div>
                  </td>

                  <td>
                    <span class="category-badge">
                      {{
                        product.category?.name ||
                        product.category?.nama ||
                        'Tanpa Kategori'
                      }}
                    </span>
                  </td>

                  <td>
                    <strong class="price">{{ formatPrice(product.price) }}</strong>
                  </td>

                  <td>
                    <span class="stock-number">{{ product.stock }}</span>
                  </td>

                  <td>
                    <span
                      class="status-badge"
                      :class="product.is_active ? 'status-active' : 'status-inactive'"
                    >
                      {{ product.is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>

                    <span
                      class="stock-status"
                      :class="stockStatus(product.stock).class"
                    >
                      {{ stockStatus(product.stock).text }}
                    </span>
                  </td>

                  <td>
                    <div class="actions">
                      <button
                        class="edit-button"
                        title="Edit"
                        @click="openEditModal(product)"
                      >
                        ✏️
                      </button>

                      <button
                        class="delete-button"
                        title="Hapus"
                        @click="deleteProduct(product)"
                      >
                        🗑️
                      </button>
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
            <h2>{{ editingProduct ? 'Edit Produk' : 'Tambah Produk' }}</h2>
            <p>
              {{
                editingProduct
                  ? 'Perbarui informasi produk'
                  : 'Masukkan informasi produk baru'
              }}
            </p>
          </div>

          <button class="close-button" @click="closeModal">×</button>
        </div>

        <form class="product-form" @submit.prevent="saveProduct">
          <div class="image-upload">
            <div class="image-preview">
              <img
                v-if="imagePreview"
                :src="imagePreview"
                alt="Preview"
                @error="handleProductImageError"
              />
              <span v-else>🍿</span>
            </div>

            <div class="upload-content">
              <label class="upload-button">
                📷 Pilih Gambar
                <input
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  @change="handleImageChange"
                />
              </label>
              <small>JPG, PNG atau WEBP. Maksimal 5 MB.</small>
            </div>
          </div>

          <div class="form-group">
            <label>Nama Produk</label>
            <input
              v-model="form.name"
              type="text"
              placeholder="Contoh: Basreng Pedas"
              @blur="generateSlug"
            />
          </div>

          <div class="form-group">
            <label>Kategori</label>
            <select v-model="form.category_id">
              <option value="">Pilih kategori</option>
              <option
                v-for="category in categories"
                :key="category.id"
                :value="category.id"
              >
                {{ category.name || category.nama }}
              </option>
            </select>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Harga</label>
              <input
                v-model="form.price"
                type="number"
                min="0"
                placeholder="15000"
              />
            </div>

            <div class="form-group">
              <label>Stok</label>
              <input
                v-model="form.stock"
                type="number"
                min="0"
                placeholder="20"
              />
            </div>
          </div>

          <div class="form-group">
            <label>
              Slug
              <span>Opsional</span>
            </label>
            <input
              v-model="form.slug"
              type="text"
              placeholder="basreng-pedas"
            />
          </div>

          <div class="form-group">
            <label>Deskripsi</label>
            <textarea
              v-model="form.description"
              rows="4"
              placeholder="Deskripsi produk..."
            ></textarea>
          </div>

          <label class="switch-row">
            <div>
              <strong>Produk Aktif</strong>
              <small>Produk akan ditampilkan di toko.</small>
            </div>

            <input v-model="form.is_active" type="checkbox" />
            <span class="switch"></span>
          </label>

          <div class="modal-actions">
            <button
              type="button"
              class="cancel-button"
              @click="closeModal"
              :disabled="saving"
            >
              Batal
            </button>

            <button type="submit" class="save-button" :disabled="saving">
              <span v-if="saving">Menyimpan...</span>
              <span v-else>
                {{
                  editingProduct ? 'Simpan Perubahan' : 'Simpan Produk'
                }}
              </span>
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

.sidebar-brand:hover {
  opacity: 0.9;
}

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
  flex-shrink: 0;
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

.sidebar-menu::-webkit-scrollbar {
  width: 4px;
}

.sidebar-menu::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.4);
  border-radius: 10px;
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

.btn-icon {
  font-size: 18px;
}

.filter-card {
  background: var(--surface);
  border: 1px solid var(--panel-border);
  border-radius: 14px;
  padding: 16px;
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.filter-card .search-box {
  flex: 1;
  min-width: 220px;
  height: 44px;
}

.filter-card select {
  min-width: 170px;
  border: 1px solid var(--panel-border);
  border-radius: 10px;
  padding: 0 12px;
  background: var(--surface);
  color: var(--text);
  outline: none;
}

.table-card {
  background: var(--surface);
  border: 1px solid var(--panel-border);
  border-radius: 14px;
  overflow: hidden;
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

thead {
  background: rgba(148, 163, 184, 0.08);
}

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

.product-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.product-image {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  overflow: hidden;
  background: #eff6ff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  flex-shrink: 0;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-info strong {
  display: block;
  color: var(--text);
  font-size: 13px;
}

.product-info small {
  display: block;
  color: var(--muted);
  margin-top: 3px;
  font-size: 10px;
}

.category-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 8px;
  background: rgba(37, 99, 235, 0.08);
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
}

.status-badge,
.stock-status {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  border-radius: 7px;
  padding: 5px 8px;
  font-size: 10px;
  font-weight: 750;
}

.status-active {
  background: #ecfdf5;
  color: #059669;
}

.status-inactive {
  background: #fef2f2;
  color: #dc2626;
}

.stock-ready {
  background: #eff6ff;
  color: #2563eb;
}

.stock-low {
  background: #fff7ed;
  color: #ea580c;
}

.stock-empty {
  background: #fef2f2;
  color: #dc2626;
}

.stock-status {
  margin-top: 5px;
}

.price {
  color: var(--text);
  font-weight: 800;
}

.stock-number {
  font-weight: 750;
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

.edit-button {
  background: #eff6ff;
}

.delete-button {
  background: #fef2f2;
}

.edit-button:hover,
.delete-button:hover {
  transform: translateY(-1px);
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
  margin-top: 20px;
}

.spinner {
  width: 35px;
  height: 35px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

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
  max-width: 650px;
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

.product-form {
  padding: 24px 25px;
}

.image-upload {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}

.image-preview {
  width: 90px;
  height: 90px;
  flex-shrink: 0;
  border-radius: 15px;
  background: #eff6ff;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 35px;
  border: 1px dashed #bfdbfe;
}

.image-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.upload-content {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.upload-button {
  display: inline-flex;
  width: fit-content;
  padding: 9px 13px;
  border-radius: 9px;
  background: rgba(37, 99, 235, 0.08);
  color: #2563eb;
  font-size: 12px;
  font-weight: 750;
  cursor: pointer;
}

.upload-button input {
  display: none;
}

.upload-content small {
  color: var(--muted);
  font-size: 10px;
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
  color: var(--muted);
  font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
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

.form-group textarea {
  resize: vertical;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  border-color: #60a5fa;
  background: transparent;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.switch-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 13px;
  margin-bottom: 20px;
  border-radius: 12px;
  background: rgba(148, 163, 184, 0.04);
  border: 1px solid var(--panel-border);
  cursor: pointer;
}

.switch-row strong {
  display: block;
  font-size: 12px;
  color: var(--text);
}

.switch-row small {
  display: block;
  color: var(--muted);
  margin-top: 3px;
  font-size: 10px;
}

.switch-row input {
  display: none;
}

.switch {
  width: 43px;
  height: 24px;
  background: #cbd5e1;
  border-radius: 20px;
  position: relative;
  transition: 0.2s;
}

.switch::after {
  content: '';
  position: absolute;
  width: 18px;
  height: 18px;
  top: 3px;
  left: 3px;
  border-radius: 50%;
  background: white;
  transition: 0.2s;
}

.switch-row input:checked + .switch {
  background: #2563eb;
}

.switch-row input:checked + .switch::after {
  transform: translateX(19px);
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding-top: 5px;
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
.cancel-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 900px) {
  .sidebar {
    width: 220px;
  }

  .main-wrapper {
    margin-left: 220px;
  }

  .content-body {
    padding: 0 20px 24px;
  }

  .filter-card {
    flex-wrap: wrap;
  }

  .search-box {
    width: 100%;
    flex-basis: 100%;
  }
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

  .dashboard-layout {
    display: block;
  }

  .topbar {
    height: auto;
    padding: 18px 20px;
    gap: 15px;
    flex-wrap: wrap;
  }

  .content-body {
    padding: 0 16px 20px;
  }

  .page-header {
    align-items: flex-start;
    flex-direction: column;
    gap: 16px;
  }

  .btn-primary-action {
    width: 100%;
    justify-content: center;
  }

  .filter-card {
    display: block;
  }

  .filter-card .search-box,
  .filter-card select {
    width: 100%;
    min-width: 0;
    margin-bottom: 10px;
  }

  .topbar-right {
    width: 100%;
    justify-content: flex-end;
  }

  .user-info {
    display: none;
  }

  .form-row {
    grid-template-columns: 1fr;
    gap: 0;
  }
}
</style>
