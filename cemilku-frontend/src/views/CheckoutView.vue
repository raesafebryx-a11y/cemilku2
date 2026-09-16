<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'

import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import api from '../services/api'

const router = useRouter()

// ==========================================
// STATE
// ==========================================

const cart = ref(null)
const addresses = ref([])

const loading = ref(true)
const savingAddress = ref(false)
const placingOrder = ref(false)

const selectedAddressId = ref(null)

const showAddressForm = ref(false)

// Form alamat
const addressForm = ref({
  label: '',
  recipient_name: '',
  phone: '',
  full_address: '',
  city: '',
  postal_code: ''
})

// Pengiriman
const shippingOption = ref('regular')

const shippingOptions = [
  {
    id: 'regular',
    name: 'Pengiriman Reguler',
    description: 'Estimasi 2–4 hari',
    price: 10000
  },
  {
    id: 'express',
    name: 'Pengiriman Express',
    description: 'Estimasi 1–2 hari',
    price: 15000
  }
]

// ==========================================
// COMPUTED
// ==========================================

const cartItems = computed(() => {
  return cart.value?.items || []
})

const subtotal = computed(() => {
  return cartItems.value.reduce((total, item) => {
    const price = Number(item.product?.price || 0)
    const quantity = Number(item.quantity || 0)

    return total + price * quantity
  }, 0)
})

const shippingCost = computed(() => {
  const selected = shippingOptions.find(
    option => option.id === shippingOption.value
  )

  return selected?.price || 0
})

const total = computed(() => {
  return subtotal.value + shippingCost.value
})

const selectedAddress = computed(() => {
  return addresses.value.find(
    address => Number(address.id) === Number(selectedAddressId.value)
  )
})

// ==========================================
// FORMAT RUPIAH
// ==========================================

const formatRupiah = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(Number(value || 0))
}

// ==========================================
// FETCH CART
// ==========================================

const fetchCart = async () => {
  try {
    const response = await api.get('/cart')

    cart.value = response.data

    console.log('Cart checkout:', response.data)
  } catch (error) {
    console.error('Gagal mengambil keranjang:', error)

    await Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: 'Gagal mengambil data keranjang.'
    })
  }
}

// ==========================================
// FETCH ADDRESSES
// ==========================================

const fetchAddresses = async () => {
  try {
    const response = await api.get('/addresses')

    addresses.value = response.data || []

    console.log('Alamat:', response.data)

    // Pilih alamat pertama secara otomatis
    if (addresses.value.length > 0 && !selectedAddressId.value) {
      selectedAddressId.value = addresses.value[0].id
    }
  } catch (error) {
    console.error('Gagal mengambil alamat:', error)

    if (error.response?.status === 401) {
      await Swal.fire({
        icon: 'warning',
        title: 'Belum Login',
        text: 'Silakan login terlebih dahulu.'
      })

      router.push('/login')
      return
    }

    await Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: 'Gagal mengambil data alamat.'
    })
  }
}

// ==========================================
// BUKA FORM ALAMAT
// ==========================================

const openAddressForm = () => {
  addressForm.value = {
    label: '',
    recipient_name: '',
    phone: '',
    full_address: '',
    city: '',
    postal_code: ''
  }

  showAddressForm.value = true

  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  })
}

// ==========================================
// TUTUP FORM ALAMAT
// ==========================================

const closeAddressForm = () => {
  showAddressForm.value = false
}

// ==========================================
// SIMPAN ALAMAT
// ==========================================

const saveAddress = async () => {
  if (
    !addressForm.value.recipient_name ||
    !addressForm.value.phone ||
    !addressForm.value.full_address ||
    !addressForm.value.city ||
    !addressForm.value.postal_code
  ) {
    await Swal.fire({
      icon: 'warning',
      title: 'Data Belum Lengkap',
      text: 'Silakan lengkapi semua data alamat.'
    })

    return
  }

  savingAddress.value = true

  try {
    const response = await api.post('/addresses', {
      label: addressForm.value.label || null,
      recipient_name: addressForm.value.recipient_name,
      phone: addressForm.value.phone,
      full_address: addressForm.value.full_address,
      city: addressForm.value.city,
      postal_code: addressForm.value.postal_code
    })

    console.log('Alamat berhasil disimpan:', response.data)

    const newAddress = response.data

    // Tambahkan alamat baru ke daftar
    addresses.value.push(newAddress)

    // Langsung pilih alamat baru
    selectedAddressId.value = newAddress.id

    // Tutup form
    showAddressForm.value = false

    await Swal.fire({
      icon: 'success',
      title: 'Alamat Tersimpan',
      text: 'Alamat berhasil ditambahkan.',
      timer: 1500,
      showConfirmButton: false
    })
  } catch (error) {
    console.error('Gagal menyimpan alamat:', error)

    let message = 'Gagal menyimpan alamat.'

    if (error.response?.data?.message) {
      message = error.response.data.message
    }

    if (error.response?.data?.errors) {
      const errors = error.response.data.errors

      const firstError = Object.values(errors)[0]

      if (firstError?.[0]) {
        message = firstError[0]
      }
    }

    await Swal.fire({
      icon: 'error',
      title: 'Gagal Menyimpan',
      text: message
    })
  } finally {
    savingAddress.value = false
  }
}

// ==========================================
// BUAT PESANAN
// ==========================================

const placeOrder = async () => {
  if (!selectedAddressId.value) {
    await Swal.fire({
      icon: 'warning',
      title: 'Pilih Alamat',
      text: 'Silakan pilih alamat pengiriman terlebih dahulu.'
    })

    return
  }

  if (cartItems.value.length === 0) {
    await Swal.fire({
      icon: 'warning',
      title: 'Keranjang Kosong',
      text: 'Tidak ada produk untuk diproses.'
    })

    router.push('/cart')
    return
  }

  const result = await Swal.fire({
    icon: 'question',
    title: 'Buat Pesanan?',
    text: `Total pembayaran ${formatRupiah(total.value)}`,
    showCancelButton: true,
    confirmButtonText: 'Ya, Buat Pesanan',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#2563eb'
  })

  if (!result.isConfirmed) {
    return
  }

  placingOrder.value = true

  try {
    const response = await api.post('/orders', {
      address_id: selectedAddressId.value,
      shipping_cost: shippingCost.value
    })

    const order = response.data

    console.log('Order berhasil:', order)

    // Update jumlah cart navbar
    window.dispatchEvent(
      new CustomEvent('cemilku-cart-updated', {
        detail: {
          count: 0
        }
      })
    )

    await Swal.fire({
      icon: 'success',
      title: 'Pesanan Berhasil!',
      text: 'Pesanan kamu berhasil dibuat.',
      confirmButtonText: 'Lihat Pesanan',
      confirmButtonColor: '#2563eb'
    })

    // Backend membuat payment otomatis.
    // Untuk sementara arahkan ke detail order.
    router.push(`/orders/${order.id}`)
  } catch (error) {
    console.error('Gagal membuat pesanan:', error)

    let message = 'Gagal membuat pesanan.'

    if (error.response?.data?.message) {
      message = error.response.data.message
    }

    if (error.response?.data?.errors) {
      const errors = error.response.data.errors

      const firstError = Object.values(errors)[0]

      if (firstError?.[0]) {
        message = firstError[0]
      }
    }

    await Swal.fire({
      icon: 'error',
      title: 'Pesanan Gagal',
      text: message
    })
  } finally {
    placingOrder.value = false
  }
}

// ==========================================
// KEMBALI KE CART
// ==========================================

const goToCart = () => {
  router.push('/cart')
}

// ==========================================
// ON MOUNTED
// ==========================================

onMounted(async () => {
  loading.value = true

  try {
    await Promise.all([
      fetchCart(),
      fetchAddresses()
    ])
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="checkout-page">

    <main class="checkout-main">
      <div class="checkout-container">

        <!-- HEADER -->
        <div class="page-header">
          <button
            class="back-button"
            @click="goToCart"
          >
            ← Kembali ke Keranjang
          </button>

          <div>
            <h1>Checkout</h1>
            <p>Periksa pesanan dan lengkapi alamat pengiriman.</p>
          </div>
        </div>

        <!-- LOADING -->
        <div
          v-if="loading"
          class="loading-box"
        >
          <div class="spinner"></div>
          <p>Memuat data checkout...</p>
        </div>

        <!-- CONTENT -->
        <div
          v-else
          class="checkout-grid"
        >

          <!-- ======================================
               LEFT
          ======================================= -->

          <div class="checkout-left">

            <!-- ==================================
                 ALAMAT
            =================================== -->

            <section class="checkout-card">

              <div class="card-header">
                <div>
                  <span class="section-number">1</span>

                  <div>
                    <h2>Alamat Pengiriman</h2>
                    <p>Pilih alamat untuk pengiriman pesanan.</p>
                  </div>
                </div>

                <button
                  class="add-address-button"
                  type="button"
                  @click="openAddressForm"
                >
                  + Tambah Alamat
                </button>
              </div>

              <!-- FORM TAMBAH ALAMAT -->
              <div
                v-if="showAddressForm"
                class="address-form-wrapper"
              >

                <div class="form-title">
                  <div class="form-icon">
                    📍
                  </div>

                  <div>
                    <h3>Tambah Alamat Baru</h3>
                    <p>
                      Masukkan alamat pengiriman kamu.
                    </p>
                  </div>
                </div>

                <form
                  class="address-form"
                  @submit.prevent="saveAddress"
                >

                  <div class="form-row">

                    <div class="form-group">
                      <label>
                        Label Alamat
                        <span class="optional">(opsional)</span>
                      </label>

                      <input
                        v-model="addressForm.label"
                        type="text"
                        placeholder="Contoh: Rumah"
                      />
                    </div>

                    <div class="form-group">
                      <label>
                        Nama Penerima
                      </label>

                      <input
                        v-model="addressForm.recipient_name"
                        type="text"
                        placeholder="Nama penerima"
                        required
                      />
                    </div>

                  </div>

                  <div class="form-row">

                    <div class="form-group">
                      <label>
                        Nomor HP
                      </label>

                      <input
                        v-model="addressForm.phone"
                        type="tel"
                        placeholder="08xxxxxxxxxx"
                        required
                      />
                    </div>

                    <div class="form-group">
                      <label>
                        Kota
                      </label>

                      <input
                        v-model="addressForm.city"
                        type="text"
                        placeholder="Contoh: Jakarta"
                        required
                      />
                    </div>

                  </div>

                  <div class="form-group">
                    <label>
                      Alamat Lengkap
                    </label>

                    <textarea
                      v-model="addressForm.full_address"
                      rows="4"
                      placeholder="Nama jalan, nomor rumah, RT/RW, kecamatan, dan detail lainnya..."
                      required
                    ></textarea>
                  </div>

                  <div class="form-group">
                    <label>
                      Kode Pos
                    </label>

                    <input
                      v-model="addressForm.postal_code"
                      type="text"
                      placeholder="Contoh: 12345"
                      required
                    />
                  </div>

                  <div class="address-form-actions">

                    <button
                      type="button"
                      class="cancel-button"
                      @click="closeAddressForm"
                    >
                      Batal
                    </button>

                    <button
                      type="submit"
                      class="save-address-button"
                      :disabled="savingAddress"
                    >
                      <span v-if="savingAddress">
                        Menyimpan...
                      </span>

                      <span v-else>
                        Simpan Alamat
                      </span>
                    </button>

                  </div>

                </form>

              </div>

              <!-- LIST ALAMAT -->
              <div
                v-if="addresses.length > 0"
                class="address-list"
              >

                <label
                  v-for="address in addresses"
                  :key="address.id"
                  class="address-item"
                  :class="{
                    selected:
                      Number(selectedAddressId) === Number(address.id)
                  }"
                >

                  <input
                    v-model="selectedAddressId"
                    type="radio"
                    name="address"
                    :value="address.id"
                  />

                  <div class="radio-custom"></div>

                  <div class="address-content">

                    <div class="address-top">

                      <div>
                        <strong>
                          {{ address.recipient_name }}
                        </strong>

                        <span
                          v-if="address.label"
                          class="address-label"
                        >
                          {{ address.label }}
                        </span>
                      </div>

                      <span class="selected-badge">
                        {{
                          Number(selectedAddressId) === Number(address.id)
                            ? 'Dipilih'
                            : ''
                        }}
                      </span>

                    </div>

                    <p class="phone">
                      📱 {{ address.phone }}
                    </p>

                    <p class="full-address">
                      {{ address.full_address }}
                    </p>

                    <p class="city-postal">
                      {{ address.city }}
                      ·
                      {{ address.postal_code }}
                    </p>

                  </div>

                </label>

              </div>

              <!-- BELUM ADA ALAMAT -->
              <div
                v-else
                class="empty-address"
              >
                <div class="empty-icon">
                  📍
                </div>

                <h3>Belum Ada Alamat</h3>

                <p>
                  Tambahkan alamat pengiriman terlebih dahulu
                  sebelum membuat pesanan.
                </p>

                <button
                  class="empty-add-button"
                  type="button"
                  @click="openAddressForm"
                >
                  + Tambah Alamat
                </button>
              </div>

            </section>

            <!-- ==================================
                 PENGIRIMAN
            =================================== -->

            <section class="checkout-card">

              <div class="section-heading">

                <span class="section-number">
                  2
                </span>

                <div>
                  <h2>Metode Pengiriman</h2>
                  <p>
                    Pilih metode pengiriman yang kamu inginkan.
                  </p>
                </div>

              </div>

              <div class="shipping-list">

                <label
                  v-for="option in shippingOptions"
                  :key="option.id"
                  class="shipping-item"
                  :class="{
                    selected:
                      shippingOption === option.id
                  }"
                >

                  <input
                    v-model="shippingOption"
                    type="radio"
                    name="shipping"
                    :value="option.id"
                  />

                  <div class="shipping-radio"></div>

                  <div class="shipping-info">

                    <strong>
                      {{ option.name }}
                    </strong>

                    <span>
                      {{ option.description }}
                    </span>

                  </div>

                  <div class="shipping-price">
                    {{ formatRupiah(option.price) }}
                  </div>

                </label>

              </div>

            </section>

            <!-- ==================================
                 PEMBAYARAN
            =================================== -->

            <section class="checkout-card">

              <div class="section-heading">

                <span class="section-number">
                  3
                </span>

                <div>
                  <h2>Metode Pembayaran</h2>
                  <p>
                    Pembayaran dilakukan setelah pesanan dibuat.
                  </p>
                </div>

              </div>

              <div class="payment-item selected">

                <div class="payment-radio">
                  <div></div>
                </div>

                <div class="payment-icon">
                  🏦
                </div>

                <div class="payment-info">

                  <strong>
                    Transfer Bank
                  </strong>

                  <span>
                    Upload bukti pembayaran setelah pesanan dibuat.
                  </span>

                </div>

                <div class="payment-check">
                  ✓
                </div>

              </div>

              <div class="payment-note">

                <div class="note-icon">
                  💡
                </div>

                <p>
                  Setelah pesanan dibuat, kamu akan mendapatkan
                  detail pembayaran dan dapat mengunggah bukti
                  transfer pada halaman detail pesanan.
                </p>

              </div>

            </section>

          </div>

          <!-- ======================================
               RIGHT / RINGKASAN
          ======================================= -->

          <aside class="checkout-right">

            <section class="summary-card">

              <div class="summary-header">
                <h2>Ringkasan Pesanan</h2>

                <span>
                  {{ cartItems.length }} item
                </span>
              </div>

              <!-- PRODUK -->
              <div class="product-list">

                <div
                  v-for="item in cartItems"
                  :key="item.id"
                  class="product-item"
                >

                  <div class="product-image">

                    <img
                      v-if="item.product?.image_url"
                      :src="item.product.image_url"
                      :alt="item.product?.name"
                    />

                    <div
                      v-else
                      class="image-placeholder"
                    >
                      🍪
                    </div>

                  </div>

                  <div class="product-info">

                    <h3>
                      {{ item.product?.name }}
                    </h3>

                    <span>
                      {{ item.quantity }} ×
                      {{ formatRupiah(item.product?.price) }}
                    </span>

                  </div>

                  <strong>
                    {{
                      formatRupiah(
                        Number(item.product?.price || 0) *
                        Number(item.quantity || 0)
                      )
                    }}
                  </strong>

                </div>

              </div>

              <div class="summary-divider"></div>

              <!-- SUBTOTAL -->
              <div class="summary-row">
                <span>Subtotal</span>

                <strong>
                  {{ formatRupiah(subtotal) }}
                </strong>
              </div>

              <!-- SHIPPING -->
              <div class="summary-row">
                <span>Pengiriman</span>

                <strong>
                  {{ formatRupiah(shippingCost) }}
                </strong>
              </div>

              <div class="summary-divider"></div>

              <!-- TOTAL -->
              <div class="total-row">
                <span>Total</span>

                <strong>
                  {{ formatRupiah(total) }}
                </strong>
              </div>

              <!-- ALAMAT TERPILIH -->
              <div
                v-if="selectedAddress"
                class="selected-address-box"
              >

                <div class="selected-address-header">
                  <span>📍</span>

                  <strong>
                    Dikirim ke
                  </strong>
                </div>

                <strong>
                  {{ selectedAddress.recipient_name }}
                </strong>

                <p>
                  {{ selectedAddress.full_address }}
                </p>

                <span>
                  {{ selectedAddress.city }},
                  {{ selectedAddress.postal_code }}
                </span>

              </div>

              <!-- BUTTON -->
              <button
                class="order-button"
                type="button"
                :disabled="
                  placingOrder ||
                  !selectedAddressId ||
                  cartItems.length === 0
                "
                @click="placeOrder"
              >

                <span v-if="placingOrder">
                  Memproses Pesanan...
                </span>

                <span v-else>
                  Buat Pesanan
                  <span>→</span>
                </span>

              </button>

              <p class="secure-text">
                🔒 Transaksi aman dan data kamu terlindungi.
              </p>

            </section>

          </aside>

        </div>

      </div>
    </main>

  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.checkout-page {
  min-height: 100vh;
  background: #f6f8fb;
  color: #0f172a;
}

.checkout-main {
  min-height: calc(100vh - 150px);
  padding: 40px 20px 70px;
}

.checkout-container {
  max-width: 1200px;
  margin: 0 auto;
}

/* ==========================================
   HEADER
========================================== */

.page-header {
  margin-bottom: 30px;
}

.back-button {
  border: none;
  background: transparent;
  color: #2563eb;
  font-size: 14px;
  font-weight: 700;
  padding: 0;
  margin-bottom: 16px;
  cursor: pointer;
}

.back-button:hover {
  color: #1d4ed8;
}

.page-header h1 {
  margin: 0 0 7px;
  font-size: 32px;
  font-weight: 800;
}

.page-header p {
  margin: 0;
  color: #64748b;
  font-size: 15px;
}

/* ==========================================
   GRID
========================================== */

.checkout-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 390px;
  gap: 24px;
  align-items: start;
}

.checkout-left {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.checkout-right {
  position: sticky;
  top: 90px;
}

/* ==========================================
   CARD
========================================== */

.checkout-card,
.summary-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  box-shadow: 0 8px 30px rgba(15, 23, 42, 0.05);
}

.checkout-card {
  padding: 24px;
}

.summary-card {
  padding: 24px;
}

/* ==========================================
   SECTION HEADER
========================================== */

.card-header,
.section-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 15px;
}

.section-heading {
  justify-content: flex-start;
  margin-bottom: 22px;
}

.card-header > div:first-child {
  display: flex;
  align-items: flex-start;
  gap: 13px;
}

.section-number {
  width: 34px;
  height: 34px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eff6ff;
  color: #2563eb;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 800;
}

.card-header h2,
.section-heading h2 {
  margin: 0 0 4px;
  font-size: 18px;
  font-weight: 800;
}

.card-header p,
.section-heading p {
  margin: 0;
  color: #64748b;
  font-size: 13px;
}

/* ==========================================
   ADD ADDRESS BUTTON
========================================== */

.add-address-button {
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  color: #2563eb;
  padding: 9px 13px;
  border-radius: 9px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: 0.2s;
}

.add-address-button:hover {
  background: #dbeafe;
}

/* ==========================================
   ADDRESS FORM
========================================== */

.address-form-wrapper {
  margin-top: 22px;
  padding: 20px;
  background: #f8fbff;
  border: 1px solid #bfdbfe;
  border-radius: 15px;
}

.form-title {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.form-icon {
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #dbeafe;
  border-radius: 12px;
  font-size: 20px;
}

.form-title h3 {
  margin: 0 0 3px;
  font-size: 16px;
}

.form-title p {
  margin: 0;
  color: #64748b;
  font-size: 13px;
}

.address-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.form-group label {
  font-size: 13px;
  font-weight: 700;
  color: #334155;
}

.optional {
  color: #94a3b8;
  font-weight: 500;
}

.form-group input,
.form-group textarea {
  width: 100%;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  border-radius: 10px;
  padding: 11px 13px;
  outline: none;
  color: #0f172a;
  font-size: 14px;
  font-family: inherit;
  transition: 0.2s;
}

.form-group input:focus,
.form-group textarea:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
}

.form-group textarea {
  resize: vertical;
  min-height: 100px;
}

.address-form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding-top: 5px;
}

.cancel-button,
.save-address-button {
  border: none;
  border-radius: 10px;
  padding: 11px 17px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.cancel-button {
  background: #f1f5f9;
  color: #475569;
}

.cancel-button:hover {
  background: #e2e8f0;
}

.save-address-button {
  background: #2563eb;
  color: #ffffff;
}

.save-address-button:hover {
  background: #1d4ed8;
}

.save-address-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ==========================================
   ADDRESS LIST
========================================== */

.address-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 22px;
}

.address-item {
  position: relative;
  display: flex;
  gap: 12px;
  padding: 17px;
  border: 1px solid #e2e8f0;
  border-radius: 13px;
  cursor: pointer;
  transition: 0.2s;
}

.address-item:hover {
  border-color: #93c5fd;
  background: #f8fbff;
}

.address-item.selected {
  border-color: #2563eb;
  background: #eff6ff;
}

.address-item input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.radio-custom {
  width: 19px;
  height: 19px;
  flex-shrink: 0;
  margin-top: 2px;
  border: 2px solid #cbd5e1;
  border-radius: 50%;
  position: relative;
}

.address-item.selected .radio-custom {
  border-color: #2563eb;
}

.address-item.selected .radio-custom::after {
  content: '';
  position: absolute;
  width: 9px;
  height: 9px;
  top: 3px;
  left: 3px;
  border-radius: 50%;
  background: #2563eb;
}

.address-content {
  min-width: 0;
  flex: 1;
}

.address-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.address-top strong {
  font-size: 14px;
}

.address-label {
  display: inline-block;
  margin-left: 8px;
  padding: 3px 7px;
  background: #dbeafe;
  color: #2563eb;
  border-radius: 5px;
  font-size: 10px;
  font-weight: 700;
}

.selected-badge {
  color: #2563eb;
  font-size: 11px;
  font-weight: 800;
}

.address-content p {
  margin: 5px 0 0;
}

.phone {
  color: #475569;
  font-size: 12px;
}

.full-address {
  color: #475569;
  font-size: 13px;
  line-height: 1.5;
}

.city-postal {
  color: #64748b;
  font-size: 12px;
}

/* ==========================================
   EMPTY ADDRESS
========================================== */

.empty-address {
  text-align: center;
  padding: 35px 20px 15px;
}

.empty-icon {
  width: 55px;
  height: 55px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 12px;
  background: #eff6ff;
  border-radius: 15px;
  font-size: 25px;
}

.empty-address h3 {
  margin: 0 0 7px;
  font-size: 16px;
}

.empty-address p {
  max-width: 420px;
  margin: 0 auto 17px;
  color: #64748b;
  font-size: 13px;
  line-height: 1.6;
}

.empty-add-button {
  border: none;
  background: #2563eb;
  color: #ffffff;
  padding: 10px 16px;
  border-radius: 9px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

/* ==========================================
   SHIPPING
========================================== */

.shipping-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.shipping-item {
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 15px;
  border: 1px solid #e2e8f0;
  border-radius: 13px;
  cursor: pointer;
  transition: 0.2s;
}

.shipping-item:hover {
  border-color: #93c5fd;
}

.shipping-item.selected {
  border-color: #2563eb;
  background: #eff6ff;
}

.shipping-item input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.shipping-radio {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  border: 2px solid #cbd5e1;
  border-radius: 50%;
  position: relative;
}

.shipping-item.selected .shipping-radio {
  border-color: #2563eb;
}

.shipping-item.selected .shipping-radio::after {
  content: '';
  position: absolute;
  width: 8px;
  height: 8px;
  top: 3px;
  left: 3px;
  border-radius: 50%;
  background: #2563eb;
}

.shipping-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
  flex: 1;
}

.shipping-info strong {
  font-size: 13px;
}

.shipping-info span {
  color: #64748b;
  font-size: 12px;
}

.shipping-price {
  color: #2563eb;
  font-size: 13px;
  font-weight: 800;
}

/* ==========================================
   PAYMENT
========================================== */

.payment-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border: 1px solid #2563eb;
  background: #eff6ff;
  border-radius: 13px;
}

.payment-radio {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #2563eb;
  border-radius: 50%;
}

.payment-radio div {
  width: 8px;
  height: 8px;
  background: #2563eb;
  border-radius: 50%;
}

.payment-icon {
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  border-radius: 10px;
  font-size: 19px;
}

.payment-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
  flex: 1;
}

.payment-info strong {
  font-size: 14px;
}

.payment-info span {
  color: #64748b;
  font-size: 12px;
  line-height: 1.4;
}

.payment-check {
  color: #2563eb;
  font-weight: 900;
}

.payment-note {
  display: flex;
  gap: 10px;
  margin-top: 13px;
  padding: 12px;
  background: #f8fafc;
  border-radius: 10px;
}

.note-icon {
  flex-shrink: 0;
}

.payment-note p {
  margin: 0;
  color: #64748b;
  font-size: 12px;
  line-height: 1.5;
}

/* ==========================================
   SUMMARY
========================================== */

.summary-card {
  overflow: hidden;
}

.summary-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 18px;
}

.summary-header h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 800;
}

.summary-header span {
  padding: 5px 9px;
  background: #eff6ff;
  color: #2563eb;
  border-radius: 7px;
  font-size: 11px;
  font-weight: 700;
}

.product-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
  max-height: 310px;
  overflow-y: auto;
}

.product-item {
  display: flex;
  align-items: center;
  gap: 10px;
}

.product-image {
  width: 55px;
  height: 55px;
  flex-shrink: 0;
  overflow: hidden;
  background: #eff6ff;
  border-radius: 10px;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.product-info {
  min-width: 0;
  flex: 1;
}

.product-info h3 {
  margin: 0 0 4px;
  overflow: hidden;
  color: #0f172a;
  font-size: 12px;
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.product-info span {
  color: #64748b;
  font-size: 11px;
}

.product-item > strong {
  font-size: 12px;
  white-space: nowrap;
}

.summary-divider {
  height: 1px;
  margin: 18px 0;
  background: #e2e8f0;
}

.summary-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 11px;
  color: #64748b;
  font-size: 13px;
}

.summary-row strong {
  color: #334155;
}

.total-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.total-row span {
  font-size: 15px;
  font-weight: 800;
}

.total-row strong {
  color: #2563eb;
  font-size: 20px;
  font-weight: 900;
}

/* ==========================================
   SELECTED ADDRESS
========================================== */

.selected-address-box {
  margin-top: 20px;
  padding: 13px;
  background: #f8fbff;
  border: 1px solid #dbeafe;
  border-radius: 11px;
}

.selected-address-header {
  display: flex;
  align-items: center;
  gap: 7px;
  margin-bottom: 8px;
  color: #2563eb;
  font-size: 12px;
}

.selected-address-box > strong {
  display: block;
  margin-bottom: 4px;
  font-size: 12px;
}

.selected-address-box p {
  margin: 0 0 4px;
  color: #64748b;
  font-size: 11px;
  line-height: 1.5;
}

.selected-address-box > span {
  color: #64748b;
  font-size: 11px;
}

/* ==========================================
   ORDER BUTTON
========================================== */

.order-button {
  width: 100%;
  margin-top: 20px;
  border: none;
  background: #2563eb;
  color: #ffffff;
  border-radius: 11px;
  padding: 14px;
  font-size: 14px;
  font-weight: 800;
  cursor: pointer;
  transition: 0.2s;
}

.order-button:hover:not(:disabled) {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.order-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.order-button span span {
  margin-left: 5px;
}

.secure-text {
  margin: 12px 0 0;
  text-align: center;
  color: #94a3b8;
  font-size: 10px;
}

/* ==========================================
   LOADING
========================================== */

.loading-box {
  min-height: 400px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #64748b;
}

.spinner {
  width: 38px;
  height: 38px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.loading-box p {
  margin: 0;
  font-size: 13px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ==========================================
   RESPONSIVE
========================================== */

@media (max-width: 900px) {
  .checkout-grid {
    grid-template-columns: 1fr;
  }

  .checkout-right {
    position: static;
  }

  .summary-card {
    max-width: none;
  }
}

@media (max-width: 600px) {
  .checkout-main {
    padding: 25px 14px 50px;
  }

  .page-header h1 {
    font-size: 27px;
  }

  .checkout-card,
  .summary-card {
    padding: 18px;
    border-radius: 14px;
  }

  .card-header {
    flex-direction: column;
  }

  .add-address-button {
    width: 100%;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .address-form-wrapper {
    padding: 15px;
  }

  .address-form-actions {
    flex-direction: column-reverse;
  }

  .cancel-button,
  .save-address-button {
    width: 100%;
  }

  .shipping-item {
    align-items: flex-start;
  }

  .shipping-price {
    font-size: 12px;
  }
}
</style>