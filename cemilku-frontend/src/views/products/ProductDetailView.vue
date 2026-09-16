<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Swal from 'sweetalert2'

import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const product = ref(null)
const loading = ref(true)
const error = ref('')
const quantity = ref(1)
const addingToCart = ref(false)

// ==========================================
// FOTO PRODUK
// ==========================================

const productImage = computed(() => {
  if (!product.value?.image) {
    return 'https://via.placeholder.com/600x500?text=Cemilku'
  }

  if (product.value.image.startsWith('http')) {
    return product.value.image
  }

  return `http://127.0.0.1:8000/storage/${product.value.image}`
})

// ==========================================
// FORMAT HARGA
// ==========================================

const formatRupiah = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(Number(value || 0))
}

// ==========================================
// TOTAL HARGA
// ==========================================

const totalPrice = computed(() => {
  if (!product.value) {
    return 0
  }

  return Number(product.value.price || 0) * quantity.value
})

// ==========================================
// CEK STOK
// ==========================================

const stockAvailable = computed(() => {
  return Number(product.value?.stock || 0)
})

const isOutOfStock = computed(() => {
  return stockAvailable.value <= 0
})

// ==========================================
// TAMBAH QUANTITY
// ==========================================

const increaseQuantity = () => {
  if (!product.value) {
    return
  }

  if (quantity.value < stockAvailable.value) {
    quantity.value++
  }
}

// ==========================================
// KURANGI QUANTITY
// ==========================================

const decreaseQuantity = () => {
  if (quantity.value > 1) {
    quantity.value--
  }
}

// ==========================================
// VALIDASI QUANTITY
// ==========================================

const validateQuantity = () => {
  if (quantity.value < 1) {
    quantity.value = 1
  }

  if (
    product.value &&
    quantity.value > stockAvailable.value
  ) {
    quantity.value = stockAvailable.value
  }
}

// ==========================================
// AMBIL DETAIL PRODUK
// ==========================================

const fetchProduct = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get(
      `/products/${route.params.id}`
    )

    product.value = response.data

    if (product.value?.stock <= 0) {
      quantity.value = 1
    }
  } catch (err) {
    console.error(
      'Gagal mengambil detail produk:',
      err
    )

    error.value =
      err.response?.data?.message ||
      'Produk tidak ditemukan.'

  } finally {
    loading.value = false
  }
}

// ==========================================
// TAMBAH KE KERANJANG
// ==========================================

const addToCart = async () => {
  if (!auth.isLoggedIn) {
    const result = await Swal.fire({
      icon: 'info',
      title: 'Login Diperlukan',
      text: 'Silakan login terlebih dahulu untuk menambahkan produk ke keranjang.',
      showCancelButton: true,
      confirmButtonText: 'Login',
      cancelButtonText: 'Nanti',
      confirmButtonColor: '#2563eb'
    })

    if (result.isConfirmed) {
      router.push('/login')
    }

    return
  }

  if (!product.value) {
    return
  }

  if (isOutOfStock.value) {
    await Swal.fire({
      icon: 'warning',
      title: 'Stok Habis',
      text: 'Produk ini sedang tidak tersedia.',
      confirmButtonColor: '#2563eb'
    })

    return
  }

  if (quantity.value > stockAvailable.value) {
    await Swal.fire({
      icon: 'warning',
      title: 'Stok Tidak Mencukupi',
      text: `Stok yang tersedia hanya ${stockAvailable.value} pcs.`,
      confirmButtonColor: '#2563eb'
    })

    quantity.value = stockAvailable.value

    return
  }

  addingToCart.value = true

  try {
    await api.post('/cart/items', {
      product_id: product.value.id,
      quantity: quantity.value
    })

    // Kurangi stok yang tampil di halaman
    product.value.stock =
      stockAvailable.value - quantity.value

    // Reset jumlah
    quantity.value = 1

    await Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: `${product.value.name} berhasil ditambahkan ke keranjang.`,
      confirmButtonColor: '#2563eb'
    })
  } catch (err) {
    console.error(
      'Gagal menambahkan ke keranjang:',
      err
    )

    await Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text:
        err.response?.data?.message ||
        'Gagal menambahkan produk ke keranjang.',
      confirmButtonColor: '#2563eb'
    })
  } finally {
    addingToCart.value = false
  }
}

// ==========================================
// KEMBALI
// ==========================================

const goBack = () => {
  router.push('/products')
}

// ==========================================
// THEME
// ==========================================

const isDarkMode = ref(false)

const handleThemeChange = (event) => {
  if (typeof event.detail?.dark === 'boolean') {
    isDarkMode.value = event.detail.dark
  }
}

// ==========================================
// MOUNT
// ==========================================

onMounted(() => {
  const savedTheme = localStorage.getItem('theme')

  if (savedTheme === 'dark') {
    isDarkMode.value = true
  }

  window.addEventListener('cemilku-theme-change', handleThemeChange)
  fetchProduct()
})

onUnmounted(() => {
  window.removeEventListener('cemilku-theme-change', handleThemeChange)
})
</script>

<template>
  <div class="product-detail-page" :class="{ 'dark-mode': isDarkMode }">

    <!-- LOADING -->
    <div
      v-if="loading"
      class="loading-container"
    >
      <div class="loading-spinner"></div>

      <p>
        Memuat detail produk...
      </p>
    </div>

    <!-- ERROR -->
    <div
      v-else-if="error"
      class="error-container"
    >
      <div class="error-icon">
        ⚠️
      </div>

      <h2>
        Produk Tidak Ditemukan
      </h2>

      <p>
        {{ error }}
      </p>

      <button
        class="back-button"
        @click="goBack"
      >
        ← Kembali ke Produk
      </button>
    </div>

    <!-- DETAIL PRODUK -->
    <main
      v-else-if="product"
      class="detail-container"
    >

      <!-- BREADCRUMB -->
      <div class="breadcrumb">
        <button @click="goBack">
          ← Kembali ke Produk
        </button>

        <span>/</span>

        <span>
          {{ product.name }}
        </span>
      </div>

      <!-- PRODUCT CARD -->
      <section class="product-detail-card">

        <!-- IMAGE -->
        <div class="product-image-section">

          <div class="product-image-wrapper">

            <img
              :src="productImage"
              :alt="product.name"
              class="product-image"
              @error="
                $event.target.src =
                  'https://via.placeholder.com/600x500?text=Cemilku'
              "
            />

            <span
              v-if="isOutOfStock"
              class="stock-overlay"
            >
              STOK HABIS
            </span>

          </div>

        </div>

        <!-- INFORMATION -->
        <div class="product-info">

          <!-- CATEGORY -->
          <div
            v-if="product.category"
            class="category-badge"
          >
            🏷️
            {{
              product.category.name ||
              product.category.nama
            }}
          </div>

          <!-- NAME -->
          <h1>
            {{ product.name }}
          </h1>

          <!-- PRICE -->
          <div class="price">
            {{ formatRupiah(product.price) }}
          </div>

          <!-- DESCRIPTION -->
          <div class="description-section">

            <h3>
              Deskripsi Produk
            </h3>

            <p>
              {{
                product.description ||
                'Belum ada deskripsi produk.'
              }}
            </p>

          </div>

          <!-- STOCK -->
          <div class="stock-info">

            <span class="stock-label">
              Ketersediaan
            </span>

            <span
              class="stock-value"
              :class="{
                'stock-empty': isOutOfStock,
                'stock-low':
                  !isOutOfStock &&
                  stockAvailable <= 5
              }"
            >
              {{
                isOutOfStock
                  ? 'Stok Habis'
                  : `${stockAvailable} pcs tersedia`
              }}
            </span>

          </div>

          <div class="divider"></div>

          <!-- QUANTITY -->
          <div
            v-if="!isOutOfStock"
            class="purchase-section"
          >

            <label>
              Jumlah
            </label>

            <div class="quantity-control">

              <button
                type="button"
                :disabled="quantity <= 1"
                @click="decreaseQuantity"
              >
                −
              </button>

              <input
                v-model.number="quantity"
                type="number"
                min="1"
                :max="stockAvailable"
                @change="validateQuantity"
              />

              <button
                type="button"
                :disabled="
                  quantity >= stockAvailable
                "
                @click="increaseQuantity"
              >
                +
              </button>

            </div>

          </div>

          <!-- TOTAL -->
          <div
            v-if="!isOutOfStock"
            class="total-section"
          >
            <span>
              Total
            </span>

            <strong>
              {{ formatRupiah(totalPrice) }}
            </strong>
          </div>

          <!-- BUTTON -->
          <button
            class="cart-button"
            :class="{
              disabled: isOutOfStock
            }"
            :disabled="
              isOutOfStock ||
              addingToCart
            "
            @click="addToCart"
          >

            <span v-if="addingToCart">
              ⏳ Menambahkan...
            </span>

            <span v-else-if="isOutOfStock">
              ❌ Stok Habis
            </span>

            <span v-else>
              🛒 Tambah ke Keranjang
            </span>

          </button>

        </div>
      </section>

    </main>

  </div>
</template>

<style scoped>
.product-detail-page {
  min-height: 100vh;
  background: linear-gradient(
    180deg,
    #f8fbff 0%,
    #eef5ff 100%
  );
  padding: 30px 20px 60px;
  color: #0f172a;
  transition: background 0.3s ease, color 0.3s ease;
}

.dark-mode.product-detail-page {
  background: linear-gradient(
    180deg,
    #020817 0%,
    #0f172a 100%
  );
  color: #e2e8f0;
}

.dark-mode .product-detail-card,
.dark-mode .total-section,
.dark-mode .quantity-control,
.dark-mode .error-container,
.dark-mode .loading-container {
  background: rgba(15, 23, 42, 0.82);
  border-color: #334155;
}

.dark-mode .product-info h1,
.dark-mode .description-section h3,
.dark-mode .total-section strong,
.dark-mode .error-container h2,
.dark-mode .loading-container p {
  color: #f8fafc;
}

.dark-mode .description-section p,
.dark-mode .stock-label,
.dark-mode .breadcrumb,
.dark-mode .breadcrumb span,
.dark-mode .error-container p {
  color: #cbd5e1;
}

.dark-mode .category-badge {
  background: rgba(30, 41, 59, 0.9);
  border-color: #334155;
  color: #bfdbfe;
}

.dark-mode .price,
.dark-mode .stock-value,
.dark-mode .breadcrumb button {
  color: #60a5fa;
}

.dark-mode .divider,
.dark-mode .quantity-control input,
.dark-mode .quantity-control button,
.dark-mode .total-section {
  border-color: #334155;
}

.dark-mode .quantity-control {
  background: #0f172a;
}

.dark-mode .quantity-control button,
.dark-mode .quantity-control input {
  background: #111827;
  color: #f8fafc;
}

.dark-mode .quantity-control button:disabled {
  color: #64748b;
}

.dark-mode .cart-button {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
}

.dark-mode .loading-spinner {
  border-color: #1e293b;
  border-top-color: #60a5fa;
}

/* ==========================================
   CONTAINER
========================================== */

.detail-container {
  width: 100%;
  max-width: 1100px;
  margin: 0 auto;
}

/* ==========================================
   BREADCRUMB
========================================== */

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
  font-size: 13px;
  color: #64748b;
}

.breadcrumb button {
  border: none;
  background: transparent;
  color: #2563eb;
  font-weight: 700;
  cursor: pointer;
  padding: 0;
}

.breadcrumb button:hover {
  text-decoration: underline;
}

/* ==========================================
   PRODUCT CARD
========================================== */

.product-detail-card {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 45px;
  background: rgba(255, 255, 255, 0.92);
  border: 1px solid #e2e8f0;
  border-radius: 24px;
  padding: 30px;
  box-shadow:
    0 20px 45px rgba(37, 99, 235, 0.08);
  backdrop-filter: blur(15px);
}

/* ==========================================
   IMAGE
========================================== */

.product-image-section {
  display: flex;
  align-items: flex-start;
  justify-content: center;
}

.product-image-wrapper {
  position: relative;
  width: 100%;
  border-radius: 20px;
  overflow: hidden;
  background: #f1f5f9;
  aspect-ratio: 1 / 1;
}

.product-image {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.product-image-wrapper:hover .product-image {
  transform: scale(1.03);
}

.stock-overlay {
  position: absolute;
  top: 18px;
  right: 18px;
  background: rgba(220, 38, 38, 0.94);
  color: #ffffff;
  padding: 8px 13px;
  border-radius: 30px;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.05em;
}

/* ==========================================
   PRODUCT INFO
========================================== */

.product-info {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.category-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  width: fit-content;
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
  border-radius: 30px;
  padding: 7px 12px;
  font-size: 12px;
  font-weight: 700;
  margin-bottom: 14px;
}

.product-info h1 {
  margin: 0;
  color: #0f172a;
  font-size: 34px;
  line-height: 1.15;
  font-weight: 800;
  letter-spacing: -0.03em;
}

.price {
  margin-top: 14px;
  color: #2563eb;
  font-size: 27px;
  font-weight: 800;
}

/* ==========================================
   DESCRIPTION
========================================== */

.description-section {
  margin-top: 25px;
}

.description-section h3 {
  margin: 0 0 8px;
  font-size: 14px;
  font-weight: 800;
  color: #0f172a;
}

.description-section p {
  margin: 0;
  color: #64748b;
  font-size: 14px;
  line-height: 1.7;
}

/* ==========================================
   STOCK
========================================== */

.stock-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 24px;
  gap: 15px;
}

.stock-label {
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
}

.stock-value {
  color: #15803d;
  font-size: 13px;
  font-weight: 800;
}

.stock-value.stock-low {
  color: #a16207;
}

.stock-value.stock-empty {
  color: #dc2626;
}

.divider {
  height: 1px;
  background: #e2e8f0;
  margin: 20px 0;
}

/* ==========================================
   PURCHASE
========================================== */

.purchase-section {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.purchase-section label {
  color: #334155;
  font-size: 13px;
  font-weight: 700;
}

.quantity-control {
  display: flex;
  align-items: center;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  overflow: hidden;
  background: #ffffff;
}

.quantity-control button {
  width: 38px;
  height: 38px;
  border: none;
  background: #f8fafc;
  color: #2563eb;
  font-size: 20px;
  font-weight: 700;
  cursor: pointer;
}

.quantity-control button:hover:not(:disabled) {
  background: #eff6ff;
}

.quantity-control button:disabled {
  color: #cbd5e1;
  cursor: not-allowed;
}

.quantity-control input {
  width: 48px;
  height: 38px;
  border: none;
  border-left: 1px solid #e2e8f0;
  border-right: 1px solid #e2e8f0;
  text-align: center;
  outline: none;
  font-weight: 700;
  color: #0f172a;
}

/* Hilangkan spinner input number */

.quantity-control input::-webkit-inner-spin-button,
.quantity-control input::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.quantity-control input[type='number'] {
  appearance: textfield;
  -moz-appearance: textfield;
}

/* ==========================================
   TOTAL
========================================== */

.total-section {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 22px;
  padding: 15px 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}

.total-section span {
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
}

.total-section strong {
  color: #0f172a;
  font-size: 18px;
  font-weight: 800;
}

/* ==========================================
   CART BUTTON
========================================== */

.cart-button {
  width: 100%;
  margin-top: 18px;
  border: none;
  border-radius: 12px;
  padding: 14px 20px;
  background: linear-gradient(
    135deg,
    #2563eb,
    #3b82f6
  );
  color: #ffffff;
  font-size: 14px;
  font-weight: 800;
  cursor: pointer;
  box-shadow:
    0 8px 20px rgba(37, 99, 235, 0.2);
  transition: all 0.25s ease;
}

.cart-button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow:
    0 12px 25px rgba(37, 99, 235, 0.3);
}

.cart-button:disabled,
.cart-button.disabled {
  background: #cbd5e1;
  box-shadow: none;
  cursor: not-allowed;
}

/* ==========================================
   LOADING
========================================== */

.loading-container {
  min-height: 70vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  color: #64748b;
}

.loading-spinner {
  width: 42px;
  height: 42px;
  border: 4px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 15px;
}

.loading-container p {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ==========================================
   ERROR
========================================== */

.error-container {
  min-height: 70vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  color: #64748b;
}

.error-icon {
  font-size: 50px;
  margin-bottom: 10px;
}

.error-container h2 {
  margin: 0 0 8px;
  color: #0f172a;
}

.error-container p {
  margin: 0 0 20px;
  font-size: 14px;
}

.back-button {
  border: none;
  border-radius: 10px;
  padding: 11px 18px;
  background: #2563eb;
  color: #ffffff;
  font-weight: 700;
  cursor: pointer;
}

/* ==========================================
   RESPONSIVE
========================================== */

@media (max-width: 850px) {
  .product-detail-card {
    grid-template-columns: 1fr;
    gap: 30px;
  }

  .product-image-wrapper {
    max-width: 600px;
    margin: 0 auto;
  }

  .product-info h1 {
    font-size: 29px;
  }
}

@media (max-width: 600px) {
  .product-detail-page {
    padding: 20px 14px 40px;
  }

  .product-detail-card {
    padding: 18px;
    border-radius: 18px;
  }

  .product-info h1 {
    font-size: 25px;
  }

  .price {
    font-size: 23px;
  }

  .purchase-section {
    align-items: flex-start;
    flex-direction: column;
  }

  .quantity-control {
    width: 100%;
  }

  .quantity-control button {
    flex: 1;
  }

  .quantity-control input {
    width: 70px;
  }
}
</style>