<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'

import api from '@/services/api'

const router = useRouter()

const cart = ref(null)
const loading = ref(true)
const error = ref('')
const updatingItem = ref(null)
const removingItem = ref(null)


// ==========================================
// URL FOTO PRODUK
// ==========================================

const productImage = (product) => {
  if (!product?.image) {
    return 'https://via.placeholder.com/300x300?text=Cemilku'
  }

  if (product.image.startsWith('http')) {
    return product.image
  }

  return `http://127.0.0.1:8000/storage/${product.image}`
}


// ==========================================
// FORMAT RUPIAH
// ==========================================

const formatRupiah = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(Number(value || 0))
}


// ==========================================
// DATA ITEMS
// ==========================================

const cartItems = computed(() => {
  return cart.value?.items || []
})


// ==========================================
// TOTAL ITEM
// ==========================================

const totalItems = computed(() => {
  return cartItems.value.reduce(
    (total, item) => total + Number(item.quantity || 0),
    0
  )
})


// ==========================================
// SUBTOTAL
// ==========================================

const subtotal = computed(() => {
  return cartItems.value.reduce((total, item) => {
    const price = Number(item.product?.price || 0)
    const quantity = Number(item.quantity || 0)

    return total + (price * quantity)
  }, 0)
})


// ==========================================
// TOTAL
// ==========================================

const totalPrice = computed(() => {
  return subtotal.value
})


// ==========================================
// AMBIL CART
// ==========================================

const fetchCart = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/cart')

    cart.value = response.data
  } catch (err) {
    console.error(
      'Gagal mengambil keranjang:',
      err
    )

    error.value =
      err.response?.data?.message ||
      'Gagal mengambil data keranjang.'
  } finally {
    loading.value = false
  }
}


// ==========================================
// TAMBAH QUANTITY
// ==========================================

const increaseQuantity = async (item) => {
  const stock = Number(item.product?.stock || 0)
  const newQuantity = Number(item.quantity) + 1

  if (newQuantity > stock) {
    await Swal.fire({
      icon: 'warning',
      title: 'Stok Tidak Mencukupi',
      text: `Stok produk hanya tersedia ${stock} pcs.`,
      confirmButtonColor: '#2563eb'
    })

    return
  }

  await updateQuantity(item, newQuantity)
}


// ==========================================
// KURANGI QUANTITY
// ==========================================

const decreaseQuantity = async (item) => {
  const newQuantity = Number(item.quantity) - 1

  if (newQuantity < 1) {
    return
  }

  await updateQuantity(item, newQuantity)
}


// ==========================================
// UPDATE QUANTITY
// ==========================================

const updateQuantity = async (
  item,
  quantity
) => {
  updatingItem.value = item.id

  try {
    const response = await api.put(
      `/cart/items/${item.id}`,
      {
        quantity
      }
    )

    const updatedItem = response.data

    const index = cartItems.value.findIndex(
      cartItem => cartItem.id === item.id
    )

    if (index !== -1) {
      cart.value.items[index] = updatedItem
    }
  } catch (err) {
    console.error(
      'Gagal mengubah jumlah:',
      err
    )

    await Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text:
        err.response?.data?.message ||
        'Gagal mengubah jumlah produk.',
      confirmButtonColor: '#2563eb'
    })
  } finally {
    updatingItem.value = null
  }
}


// ==========================================
// HAPUS ITEM
// ==========================================

const removeItem = async (item) => {
  const productName =
    item.product?.name || 'Produk ini'

  const result = await Swal.fire({
    icon: 'warning',
    title: 'Hapus Produk?',
    text: `${productName} akan dihapus dari keranjang.`,
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#64748b'
  })

  if (!result.isConfirmed) {
    return
  }

  removingItem.value = item.id

  try {
    await api.delete(
      `/cart/items/${item.id}`
    )

    cart.value.items =
      cart.value.items.filter(
        cartItem => cartItem.id !== item.id
      )

    await Swal.fire({
      icon: 'success',
      title: 'Dihapus',
      text: 'Produk berhasil dihapus dari keranjang.',
      confirmButtonColor: '#2563eb',
      timer: 1500,
      showConfirmButton: false
    })
  } catch (err) {
    console.error(
      'Gagal menghapus item:',
      err
    )

    await Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text:
        err.response?.data?.message ||
        'Gagal menghapus produk.',
      confirmButtonColor: '#2563eb'
    })
  } finally {
    removingItem.value = null
  }
}


// ==========================================
// LANJUT BELANJA
// ==========================================

const continueShopping = () => {
  router.push({
    name: 'products'
  })
}


// ==========================================
// CHECKOUT
// ==========================================

const goToCheckout = () => {
  router.push('/checkout')
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
  fetchCart()
})

onUnmounted(() => {
  window.removeEventListener('cemilku-theme-change', handleThemeChange)
})
</script>


<template>
  <div class="cart-page" :class="{ 'dark-mode': isDarkMode }">

    <!-- ======================================
         LOADING
    ======================================= -->

    <div
      v-if="loading"
      class="loading-container"
    >
      <div class="loading-spinner"></div>

      <p>
        Memuat keranjang...
      </p>
    </div>


    <!-- ======================================
         ERROR
    ======================================= -->

    <div
      v-else-if="error"
      class="state-container"
    >
      <div class="state-icon">
        ⚠️
      </div>

      <h2>
        Terjadi Kesalahan
      </h2>

      <p>
        {{ error }}
      </p>

      <button
        class="primary-button"
        @click="fetchCart"
      >
        Coba Lagi
      </button>
    </div>


    <!-- ======================================
         CART
    ======================================= -->

    <main
      v-else
      class="cart-container"
    >

      <!-- HEADER -->

      <div class="cart-header">

        <div>
          <span class="page-label">
            CEMILKU
          </span>

          <h1>
            🛒 Keranjang Saya
          </h1>

          <p>
            Periksa kembali produk pilihanmu
            sebelum melanjutkan ke checkout.
          </p>
        </div>

        <button
          class="continue-button"
          @click="continueShopping"
        >
          ← Lanjut Belanja
        </button>

      </div>


      <!-- ====================================
           KERANJANG KOSONG
      ===================================== -->

      <section
        v-if="cartItems.length === 0"
        class="empty-cart"
      >

        <div class="empty-icon">
          🛒
        </div>

        <h2>
          Keranjang Masih Kosong
        </h2>

        <p>
          Belum ada produk yang kamu pilih.
          Yuk cari cemilan favoritmu!
        </p>

        <button
          class="primary-button"
          @click="continueShopping"
        >
          🍿 Mulai Belanja
        </button>

      </section>


      <!-- ====================================
           CART CONTENT
      ===================================== -->

      <section
        v-else
        class="cart-content"
      >

        <!-- ITEMS -->

        <div class="cart-items">

          <div class="items-header">
            <div>
              <strong>
                Produk Pesanan
              </strong>

              <span>
                {{ totalItems }} item
              </span>
            </div>
          </div>


          <!-- ITEM -->

          <article
            v-for="item in cartItems"
            :key="item.id"
            class="cart-item"
          >

            <!-- IMAGE -->

            <div class="item-image-wrapper">

              <img
                :src="productImage(item.product)"
                :alt="item.product?.name"
                class="item-image"
                @error="
                  $event.target.src =
                    'https://via.placeholder.com/300x300?text=Cemilku'
                "
              />

            </div>


            <!-- INFO -->

            <div class="item-info">

              <div class="item-top">

                <div>

                  <span
                    v-if="item.product?.category"
                    class="category"
                  >
                    {{
                      item.product.category.name ||
                      item.product.category.nama
                    }}
                  </span>

                  <h3>
                    {{ item.product?.name }}
                  </h3>

                  <p class="item-price">
                    {{ formatRupiah(item.product?.price) }}
                    / pcs
                  </p>

                </div>

                <!-- REMOVE -->

                <button
                  class="remove-button"
                  :disabled="
                    removingItem === item.id
                  "
                  @click="removeItem(item)"
                >
                  <span
                    v-if="
                      removingItem === item.id
                    "
                  >
                    ⏳
                  </span>

                  <span v-else>
                    🗑️
                  </span>
                </button>

              </div>


              <!-- BOTTOM -->

              <div class="item-bottom">

                <!-- QUANTITY -->

                <div class="quantity-control">

                  <button
                    type="button"
                    :disabled="
                      updatingItem === item.id ||
                      item.quantity <= 1
                    "
                    @click="
                      decreaseQuantity(item)
                    "
                  >
                    −
                  </button>

                  <span>
                    {{ item.quantity }}
                  </span>

                  <button
                    type="button"
                    :disabled="
                      updatingItem === item.id ||
                      item.quantity >=
                        Number(
                          item.product?.stock || 0
                        )
                    "
                    @click="
                      increaseQuantity(item)
                    "
                  >
                    +
                  </button>

                </div>


                <!-- ITEM TOTAL -->

                <strong class="item-total">
                  {{
                    formatRupiah(
                      Number(
                        item.product?.price || 0
                      ) *
                      Number(item.quantity || 0)
                    )
                  }}
                </strong>

              </div>

            </div>

          </article>

        </div>


        <!-- ==================================
             SUMMARY
        =================================== -->

        <aside class="cart-summary">

          <div class="summary-card">

            <h2>
              Ringkasan Pesanan
            </h2>

            <div class="summary-line">
              <span>
                Total Produk
              </span>

              <strong>
                {{ totalItems }} item
              </strong>
            </div>

            <div class="summary-line">
              <span>
                Subtotal
              </span>

              <strong>
                {{ formatRupiah(subtotal) }}
              </strong>
            </div>

            <div class="summary-divider"></div>

            <div class="summary-total">
              <span>
                Total
              </span>

              <strong>
                {{ formatRupiah(totalPrice) }}
              </strong>
            </div>
            <button
                class="checkout-button"
                @click="router.push('/checkout')"
                >
                🛍️ Lanjut ke Checkout →
                </button>

            <p class="secure-info">
              🔒 Pesananmu aman bersama Cemilku
            </p>

          </div>

        </aside>

      </section>

    </main>
  </div>
</template>


<style scoped>
/* ==========================================
   PAGE
========================================== */

.cart-page {
  min-height: 100vh;
  background:
    linear-gradient(
      180deg,
      #f8fbff 0%,
      #eef5ff 100%
    );

  color: #0f172a;
  padding: 45px 20px 80px;
  transition: background 0.3s ease, color 0.3s ease;
}

.dark-mode.cart-page {
  background:
    linear-gradient(
      180deg,
      #020817 0%,
      #0f172a 100%
    );
  color: #e2e8f0;
}

.dark-mode .cart-header h1,
.dark-mode .cart-header p,
.dark-mode .items-header strong,
.dark-mode .item-info h3,
.dark-mode .item-price,
.dark-mode .item-total,
.dark-mode .summary-card h2,
.dark-mode .summary-line strong,
.dark-mode .summary-total span,
.dark-mode .summary-total strong,
.dark-mode .empty-cart h2,
.dark-mode .empty-cart p,
.dark-mode .state-container h2,
.dark-mode .state-container p,
.dark-mode .loading-container p {
  color: #f8fafc;
}

.dark-mode .cart-items,
.dark-mode .empty-cart,
.dark-mode .summary-card {
  background: rgba(15, 23, 42, 0.82);
  border-color: #334155;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.38);
}

.dark-mode .items-header,
.dark-mode .cart-item,
.dark-mode .summary-divider {
  border-color: #334155;
}

.dark-mode .items-header span,
.dark-mode .summary-line span,
.dark-mode .secure-info,
.dark-mode .page-label,
.dark-mode .category {
  color: #94a3b8;
}

.dark-mode .category {
  background: rgba(30, 41, 59, 0.9);
  border-color: #334155;
}

.dark-mode .continue-button,
.dark-mode .quantity-control,
.dark-mode .quantity-control button,
.dark-mode .primary-button,
.dark-mode .checkout-button {
  box-shadow: none;
}

.dark-mode .continue-button {
  background: #0f172a;
  border-color: #334155;
  color: #bfdbfe;
}

.dark-mode .continue-button:hover {
  background: #1e293b;
}

.dark-mode .quantity-control {
  background: #0f172a;
  border-color: #475569;
}

.dark-mode .quantity-control button {
  background: #111827;
  color: #bfdbfe;
}

.dark-mode .quantity-control button:hover:not(:disabled) {
  background: #1e293b;
}

.dark-mode .quantity-control span {
  color: #f8fafc;
}

.dark-mode .item-price,
.dark-mode .summary-line span,
.dark-mode .summary-line strong,
.dark-mode .empty-cart p,
.dark-mode .state-container p,
.dark-mode .loading-container {
  color: #cbd5e1;
}

.dark-mode .remove-button {
  background: rgba(127, 29, 29, 0.22);
  color: #fca5a5;
}

.dark-mode .summary-card {
  border-color: #3b82f6;
}

.dark-mode .summary-total strong,
.dark-mode .item-total,
.dark-mode .page-label {
  color: #60a5fa;
}

.dark-mode .checkout-button {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
}

.dark-mode .primary-button {
  background: #2563eb;
}

.dark-mode .loading-spinner {
  border-color: #1e293b;
  border-top-color: #60a5fa;
}


/* ==========================================
   CONTAINER
========================================== */

.cart-container {
  width: 100%;
  max-width: 1150px;
  margin: 0 auto;
}


/* ==========================================
   HEADER
========================================== */

.cart-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 30px;
  margin-bottom: 30px;
}

.page-label {
  display: inline-block;
  margin-bottom: 8px;

  color: #2563eb;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.12em;
}

.cart-header h1 {
  margin: 0;

  color: #0f172a;
  font-size: 34px;
  font-weight: 800;
  letter-spacing: -0.03em;
}

.cart-header p {
  margin: 10px 0 0;

  color: #64748b;
  font-size: 14px;
  line-height: 1.6;
}

.continue-button {
  flex-shrink: 0;

  border: 1px solid #bfdbfe;
  border-radius: 11px;

  padding: 11px 17px;

  background: #ffffff;
  color: #2563eb;

  font-size: 13px;
  font-weight: 700;

  cursor: pointer;
  transition: 0.2s ease;
}

.continue-button:hover {
  background: #eff6ff;
  transform: translateY(-1px);
}


/* ==========================================
   CART CONTENT
========================================== */

.cart-content {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 350px;
  gap: 25px;
  align-items: start;
}


/* ==========================================
   ITEMS
========================================== */

.cart-items {
  background: rgba(255, 255, 255, 0.94);

  border: 1px solid #e2e8f0;
  border-radius: 22px;

  box-shadow:
    0 18px 40px rgba(37, 99, 235, 0.07);

  overflow: hidden;
}

.items-header {
  padding: 20px 22px;

  border-bottom: 1px solid #e2e8f0;
}

.items-header > div {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
}

.items-header strong {
  color: #0f172a;
  font-size: 15px;
}

.items-header span {
  color: #64748b;
  font-size: 13px;
}


/* ==========================================
   CART ITEM
========================================== */

.cart-item {
  display: flex;
  gap: 18px;

  padding: 22px;

  border-bottom: 1px solid #e2e8f0;
}

.cart-item:last-child {
  border-bottom: none;
}


/* ==========================================
   IMAGE
========================================== */

.item-image-wrapper {
  width: 125px;
  height: 125px;

  flex-shrink: 0;

  overflow: hidden;

  border-radius: 16px;

  background: #f1f5f9;
}

.item-image {
  width: 100%;
  height: 100%;

  display: block;

  object-fit: cover;

  transition: transform 0.3s ease;
}

.item-image-wrapper:hover .item-image {
  transform: scale(1.05);
}


/* ==========================================
   INFO
========================================== */

.item-info {
  flex: 1;
  min-width: 0;

  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.item-top {
  display: flex;
  justify-content: space-between;
  gap: 20px;
}

.category {
  display: inline-block;

  margin-bottom: 7px;

  padding: 5px 9px;

  border-radius: 20px;

  background: #eff6ff;
  border: 1px solid #bfdbfe;

  color: #2563eb;

  font-size: 10px;
  font-weight: 800;
}

.item-info h3 {
  margin: 0;

  color: #0f172a;

  font-size: 17px;
  line-height: 1.35;
  font-weight: 800;
}

.item-price {
  margin: 7px 0 0;

  color: #64748b;

  font-size: 13px;
}

.remove-button {
  width: 36px;
  height: 36px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  border: none;
  border-radius: 10px;

  background: #fef2f2;

  color: #dc2626;

  cursor: pointer;

  transition: 0.2s ease;
}

.remove-button:hover:not(:disabled) {
  background: #fee2e2;
  transform: translateY(-1px);
}

.remove-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}


/* ==========================================
   ITEM BOTTOM
========================================== */

.item-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 20px;

  margin-top: 20px;
}


/* ==========================================
   QUANTITY
========================================== */

.quantity-control {
  display: flex;
  align-items: center;

  overflow: hidden;

  border: 1px solid #cbd5e1;
  border-radius: 10px;

  background: #ffffff;
}

.quantity-control button {
  width: 36px;
  height: 36px;

  border: none;

  background: #f8fafc;

  color: #2563eb;

  font-size: 19px;
  font-weight: 800;

  cursor: pointer;
}

.quantity-control button:hover:not(:disabled) {
  background: #eff6ff;
}

.quantity-control button:disabled {
  color: #cbd5e1;
  cursor: not-allowed;
}

.quantity-control span {
  min-width: 42px;

  text-align: center;

  color: #0f172a;

  font-size: 13px;
  font-weight: 800;
}

.item-total {
  color: #2563eb;
  font-size: 16px;
  font-weight: 800;
}


/* ==========================================
   SUMMARY
========================================== */

.cart-summary {
  position: sticky;
  top: 25px;
}

.summary-card {
  padding: 25px;

  border: 1px solid #bfdbfe;
  border-radius: 20px;

  background:
    linear-gradient(
      145deg,
      #ffffff,
      #f8fbff
    );

  box-shadow:
    0 18px 40px rgba(37, 99, 235, 0.1);
}

.summary-card h2 {
  margin: 0 0 25px;

  color: #0f172a;

  font-size: 18px;
  font-weight: 800;
}

.summary-line {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 15px;

  margin-bottom: 15px;
}

.summary-line span {
  color: #64748b;
  font-size: 13px;
}

.summary-line strong {
  color: #334155;
  font-size: 13px;
}

.summary-divider {
  height: 1px;

  margin: 20px 0;

  background: #dbeafe;
}

.summary-total {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 15px;
}

.summary-total span {
  color: #0f172a;

  font-size: 14px;
  font-weight: 700;
}

.summary-total strong {
  color: #2563eb;

  font-size: 22px;
  font-weight: 800;
}

.checkout-button {
  width: 100%;

  margin-top: 23px;

  border: none;
  border-radius: 12px;

  padding: 14px 18px;

  background:
    linear-gradient(
      135deg,
      #2563eb,
      #3b82f6
    );

  color: #ffffff;

  font-size: 14px;
  font-weight: 800;

  cursor: pointer;

  box-shadow:
    0 9px 22px rgba(37, 99, 235, 0.25);

  transition: 0.25s ease;
}

.checkout-button:hover {
  transform: translateY(-2px);

  box-shadow:
    0 13px 27px rgba(37, 99, 235, 0.32);
}

.secure-info {
  margin: 15px 0 0;

  color: #94a3b8;

  font-size: 11px;
  text-align: center;
}


/* ==========================================
   EMPTY CART
========================================== */

.empty-cart {
  min-height: 500px;

  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  padding: 50px 25px;

  border: 1px solid #e2e8f0;
  border-radius: 24px;

  background: rgba(255, 255, 255, 0.94);

  box-shadow:
    0 18px 40px rgba(37, 99, 235, 0.07);

  text-align: center;
}

.empty-icon {
  width: 90px;
  height: 90px;

  display: flex;
  align-items: center;
  justify-content: center;

  margin-bottom: 20px;

  border-radius: 50%;

  background: #eff6ff;

  font-size: 42px;
}

.empty-cart h2 {
  margin: 0 0 8px;

  color: #0f172a;

  font-size: 22px;
  font-weight: 800;
}

.empty-cart p {
  max-width: 400px;

  margin: 0 0 25px;

  color: #64748b;

  font-size: 14px;
  line-height: 1.7;
}


/* ==========================================
   BUTTON
========================================== */

.primary-button {
  border: none;
  border-radius: 11px;

  padding: 12px 20px;

  background: #2563eb;
  color: #ffffff;

  font-size: 13px;
  font-weight: 800;

  cursor: pointer;

  transition: 0.2s ease;
}

.primary-button:hover {
  background: #1d4ed8;
  transform: translateY(-1px);
}


/* ==========================================
   LOADING
========================================== */

.loading-container {
  min-height: 75vh;

  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  color: #64748b;
}

.loading-spinner {
  width: 43px;
  height: 43px;

  margin-bottom: 15px;

  border: 4px solid #dbeafe;
  border-top-color: #2563eb;

  border-radius: 50%;

  animation: spin 0.8s linear infinite;
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

.state-container {
  min-height: 75vh;

  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  text-align: center;
}

.state-icon {
  font-size: 50px;
  margin-bottom: 10px;
}

.state-container h2 {
  margin: 0 0 8px;

  color: #0f172a;
}

.state-container p {
  margin: 0 0 20px;

  color: #64748b;

  font-size: 14px;
}


/* ==========================================
   RESPONSIVE
========================================== */

@media (max-width: 900px) {
  .cart-content {
    grid-template-columns: 1fr;
  }

  .cart-summary {
    position: static;
  }

  .summary-card {
    max-width: none;
  }
}


@media (max-width: 650px) {
  .cart-page {
    padding: 25px 14px 50px;
  }

  .cart-header {
    align-items: flex-start;
    flex-direction: column;
    gap: 18px;
  }

  .cart-header h1 {
    font-size: 27px;
  }

  .continue-button {
    width: 100%;
  }

  .cart-item {
    gap: 14px;
    padding: 17px;
  }

  .item-image-wrapper {
    width: 90px;
    height: 90px;
  }

  .item-info h3 {
    font-size: 15px;
  }

  .item-bottom {
    align-items: flex-start;
    flex-direction: column;
    gap: 13px;
  }

  .item-total {
    font-size: 15px;
  }

  .summary-card {
    padding: 20px;
  }
}


@media (max-width: 450px) {
  .cart-item {
    align-items: flex-start;
  }

  .item-image-wrapper {
    width: 75px;
    height: 75px;
  }

  .item-top {
    gap: 8px;
  }

  .remove-button {
    width: 32px;
    height: 32px;
  }

  .category {
    font-size: 9px;
  }

  .item-info h3 {
    font-size: 14px;
  }

  .item-price {
    font-size: 12px;
  }
}
</style>