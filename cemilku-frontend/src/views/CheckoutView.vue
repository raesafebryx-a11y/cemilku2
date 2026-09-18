<script setup>
import {
  ref,
  computed,
  onMounted,
  onUnmounted,
  watch
} from 'vue'
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
const paymentProcessing = ref(false)

const selectedAddressId = ref(null)

const showAddressForm = ref(false)

// ==========================================
// RAJAONGKIR
// ==========================================

const shippingOptions = ref([])
const selectedShipping = ref(null)
const shippingLoading = ref(false)

const ORIGIN_CITY_ID = 501

const provinces = ref([])
const cities = ref([])

const provinceLoading = ref(false)
const cityLoading = ref(false)

const SHIPPING_COURIERS = [
  'jne',
  'jnt',
  'sicepat'
]

const shippingWeight = computed(() => {
  /*
   * Sementara 1 KG.
   * Nanti bisa dihitung berdasarkan berat produk.
   */
  return 1000
})

// ==========================================
// ALAMAT FORM
// ==========================================

const addressForm = ref({
  label: '',
  recipient_name: '',
  phone: '',
  full_address: '',
  province_id: '',
  province_name: '',
  city_id: '',
  city: '',
  postal_code: ''
})

// ==========================================
// COMPUTED ALAMAT
// ==========================================

const selectedAddress = computed(() => {
  return addresses.value.find(
    address =>
      Number(address.id) ===
      Number(selectedAddressId.value)
  )
})

// ==========================================
// COMPUTED CART
// ==========================================

const cartItems = computed(() => {
  return cart.value?.items || []
})

const subtotal = computed(() => {
  return cartItems.value.reduce(
    (total, item) => {
      const price =
        Number(item.product?.price || 0)

      const quantity =
        Number(item.quantity || 0)

      return total + price * quantity
    },
    0
  )
})

const shippingCost = computed(() => {
  return Number(
    selectedShipping.value?.cost || 0
  )
})

const total = computed(() => {
  return (
    subtotal.value +
    shippingCost.value
  )
})

// ==========================================
// FORMAT RUPIAH
// ==========================================

const formatRupiah = value => {
  return new Intl.NumberFormat(
    'id-ID',
    {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }
  ).format(
    Number(value || 0)
  )
}

// ==========================================
// DELAY
// ==========================================

const sleep = ms => {
  return new Promise(resolve => {
    setTimeout(resolve, ms)
  })
}

// ==========================================
// FETCH PROVINSI
// ==========================================

const fetchProvinces = async () => {
  try {
    provinceLoading.value = true

    const response =
      await api.get('/provinces')

    provinces.value =
      response.data?.data || []

    console.log(
      'Provinsi RajaOngkir:',
      provinces.value
    )
  } catch (error) {
    console.error(
      'Gagal mengambil provinsi:',
      error
    )

    await Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text:
        'Gagal mengambil data provinsi.'
    })
  } finally {
    provinceLoading.value = false
  }
}

// ==========================================
// FETCH KOTA
// ==========================================

const fetchCities = async provinceId => {
  if (!provinceId) {
    cities.value = []

    addressForm.value.city_id = ''
    addressForm.value.city = ''

    return
  }

  try {
    cityLoading.value = true

    const response =
      await api.get(
        `/cities/${provinceId}`
      )

    cities.value =
      response.data?.data || []

    addressForm.value.city_id = ''
    addressForm.value.city = ''

    console.log(
      'Kota RajaOngkir:',
      cities.value
    )
  } catch (error) {
    console.error(
      'Gagal mengambil kota:',
      error
    )

    cities.value = []

    await Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text:
        'Gagal mengambil data kota.'
    })
  } finally {
    cityLoading.value = false
  }
}

// ==========================================
// WATCH PROVINSI
// ==========================================

watch(
  () =>
    addressForm.value.province_id,
  async provinceId => {
    const province =
      provinces.value.find(
        item =>
          Number(item.id) ===
          Number(provinceId)
      )

    addressForm.value.province_name =
      province?.name || ''

    await fetchCities(
      provinceId
    )
  }
)

// ==========================================
// WATCH KOTA
// ==========================================

watch(
  () =>
    addressForm.value.city_id,
  cityId => {
    const city =
      cities.value.find(
        item =>
          Number(item.id) ===
          Number(cityId)
      )

    addressForm.value.city =
      city?.name || ''
  }
)

// ==========================================
// FETCH ONGKIR
// ==========================================

const fetchShippingCost = async () => {
  if (
    !selectedAddress.value?.city_id
  ) {
    shippingOptions.value = []
    selectedShipping.value = null
    return
  }

  try {
    shippingLoading.value = true

    shippingOptions.value = []
    selectedShipping.value = null

    const results = []

    for (
      const courier
      of SHIPPING_COURIERS
    ) {
      try {
        const response =
          await api.post(
            '/shipping-cost',
            {
              origin:
                ORIGIN_CITY_ID,

              destination:
                Number(
                  selectedAddress.value
                    .city_id
                ),

              weight:
                shippingWeight.value,

              courier
            }
          )

        console.log(
          `Response ongkir ${courier}:`,
          response.data
        )

        const data =
          response.data?.data || []

        data.forEach(item => {
          const service =
            String(
              item.service || ''
            ).trim()

          const serviceUpper =
            service.toUpperCase()

          /*
           * Jangan tampilkan layanan trucking/JTR.
           */
          if (
            serviceUpper.includes('JTR')
          ) {
            return
          }

          const originalCost =
            Number(
              item.cost || 0
            )

          if (
            originalCost <= 0
          ) {
            return
          }

          /*
           * Diskon 12.5%
           */
          const discount =
            Math.round(
              originalCost * 0.125
            )

          const finalCost =
            Math.max(
              0,
              originalCost - discount
            )

          results.push({
            courier:
              item.code ||
              courier,

            courierName:
              item.name ||
              courier.toUpperCase(),

            service:
              service || '-',

            description:
              item.description || '',

            etd:
              item.etd || '-',

            originalCost,

            discount,

            cost:
              finalCost
          })
        })
      } catch (error) {
        console.error(
          `Gagal mengambil ongkir ${courier}:`,
          error.response?.data ||
          error
        )
      }
    }

    /*
     * Urutkan dari termurah.
     */
    results.sort(
      (a, b) =>
        Number(a.cost) -
        Number(b.cost)
    )

    shippingOptions.value =
      results

    /*
     * Otomatis pilih ongkir
     * termurah.
     */
    if (
      results.length > 0
    ) {
      selectedShipping.value =
        results[0]
    } else {
      selectedShipping.value =
        null
    }

    console.log(
      'Semua pilihan ongkir:',
      results
    )
  } catch (error) {
    console.error(
      'Gagal mengambil ongkir:',
      error
    )

    shippingOptions.value = []
    selectedShipping.value = null
  } finally {
    shippingLoading.value = false
  }
}

// ==========================================
// WATCH ALAMAT TERPILIH
// ==========================================

watch(
  selectedAddressId,
  async () => {
    await fetchShippingCost()
  }
)

// ==========================================
// FETCH CART
// ==========================================

const fetchCart = async () => {
  try {
    const response =
      await api.get('/cart')

    cart.value =
      response.data

    console.log(
      'Cart checkout:',
      response.data
    )
  } catch (error) {
    console.error(
      'Gagal mengambil keranjang:',
      error
    )

    await Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text:
        'Gagal mengambil data keranjang.'
    })
  }
}

// ==========================================
// FETCH ADDRESSES
// ==========================================

const fetchAddresses = async () => {
  try {
    const response =
      await api.get('/addresses')

    addresses.value =
      response.data || []

    console.log(
      'Alamat:',
      response.data
    )

    /*
     * Pilih alamat pertama otomatis.
     */
    if (
      addresses.value.length > 0
      && !selectedAddressId.value
    ) {
      selectedAddressId.value =
        addresses.value[0].id
    }
  } catch (error) {
    console.error(
      'Gagal mengambil alamat:',
      error
    )

    if (
      error.response?.status === 401
    ) {
      await Swal.fire({
        icon: 'warning',
        title: 'Belum Login',
        text:
          'Silakan login terlebih dahulu.'
      })

      router.push('/login')
      return
    }

    await Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text:
        'Gagal mengambil data alamat.'
    })
  }
}

// ==========================================
// OPEN FORM ALAMAT
// ==========================================

const openAddressForm = () => {
  addressForm.value = {
    label: '',
    recipient_name: '',
    phone: '',
    full_address: '',
    province_id: '',
    province_name: '',
    city_id: '',
    city: '',
    postal_code: ''
  }

  cities.value = []

  showAddressForm.value =
    true

  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  })
}

// ==========================================
// CLOSE FORM
// ==========================================

const closeAddressForm = () => {
  showAddressForm.value =
    false
}

// ==========================================
// SAVE ADDRESS
// ==========================================

const saveAddress = async () => {
  if (
    !addressForm.value.recipient_name ||
    !addressForm.value.phone ||
    !addressForm.value.full_address ||
    !addressForm.value.province_id ||
    !addressForm.value.city_id ||
    !addressForm.value.postal_code
  ) {
    await Swal.fire({
      icon: 'warning',
      title: 'Data Belum Lengkap',
      text:
        'Silakan lengkapi semua data alamat.'
    })

    return
  }

  savingAddress.value =
    true

  try {
    const response =
      await api.post(
        '/addresses',
        {
          label:
            addressForm.value
              .label || null,

          recipient_name:
            addressForm.value
              .recipient_name,

          phone:
            addressForm.value.phone,

          full_address:
            addressForm.value
              .full_address,

          province_id:
            Number(
              addressForm.value
                .province_id
            ),

          province_name:
            addressForm.value
              .province_name,

          city_id:
            Number(
              addressForm.value
                .city_id
            ),

          city:
            addressForm.value.city,

          postal_code:
            addressForm.value
              .postal_code
        }
      )

    console.log(
      'Alamat berhasil disimpan:',
      response.data
    )

    const newAddress =
      response.data

    addresses.value.push(
      newAddress
    )

    selectedAddressId.value =
      newAddress.id

    showAddressForm.value =
      false

    await Swal.fire({
      icon: 'success',
      title: 'Alamat Tersimpan',
      text:
        'Alamat berhasil ditambahkan.',
      timer: 1500,
      showConfirmButton: false
    })
  } catch (error) {
    console.error(
      'Gagal menyimpan alamat:',
      error
    )

    let message =
      'Gagal menyimpan alamat.'

    if (
      error.response?.data?.message
    ) {
      message =
        error.response.data.message
    }

    if (
      error.response?.data?.errors
    ) {
      const errors =
        error.response.data.errors

      const firstError =
        Object.values(errors)[0]

      if (
        firstError?.[0]
      ) {
        message =
          firstError[0]
      }
    }

    await Swal.fire({
      icon: 'error',
      title: 'Gagal Menyimpan',
      text: message
    })
  } finally {
    savingAddress.value =
      false
  }
}

// ==========================================
// LOAD MIDTRANS SNAP.JS
// ==========================================

const loadMidtransSnap = (
  clientKey,
  isProduction = false
) => {
  return new Promise(
    (resolve, reject) => {
      /*
       * Kalau snap sudah tersedia,
       * langsung gunakan.
       */
      if (
        window.snap
        && typeof window.snap.pay ===
          'function'
      ) {
        resolve(window.snap)
        return
      }

      /*
       * Cek script yang sudah ada.
       */
      const existingScript =
        document.getElementById(
          'midtrans-snap-script'
        )

      if (existingScript) {
        existingScript.addEventListener(
          'load',
          () => {
            if (
              window.snap
              && typeof window.snap.pay ===
                'function'
            ) {
              resolve(window.snap)
            } else {
              reject(
                new Error(
                  'Snap.js berhasil dimuat tetapi window.snap tidak tersedia.'
                )
              )
            }
          },
          {
            once: true
          }
        )

        existingScript.addEventListener(
          'error',
          () => {
            reject(
              new Error(
                'Gagal memuat Snap.js Midtrans.'
              )
            )
          },
          {
            once: true
          }
        )

        return
      }

      /*
       * Sandbox / Production.
       */
      const script =
        document.createElement(
          'script'
        )

      script.id =
        'midtrans-snap-script'

      script.src = isProduction
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js'

      script.setAttribute(
        'data-client-key',
        clientKey
      )

      script.async = true

      script.onload = () => {
        if (
          window.snap
          && typeof window.snap.pay ===
            'function'
        ) {
          resolve(window.snap)
        } else {
          reject(
            new Error(
              'Snap.js berhasil dimuat tetapi window.snap tidak tersedia.'
            )
          )
        }
      }

      script.onerror = () => {
        reject(
          new Error(
            'Gagal memuat Snap.js Midtrans.'
          )
        )
      }

      document.head.appendChild(
        script
      )
    }
  )
}

// ==========================================
// WAIT STATUS PAYMENT WEBHOOK
// ==========================================

const waitForPaymentConfirmation =
  async orderId => {
    const maxAttempts = 12
    const interval = 1500

    for (
      let attempt = 0;
      attempt < maxAttempts;
      attempt++
    ) {
      try {
        const response =
          await api.get(
            `/orders/${orderId}`
          )

        const latestOrder =
          response.data

        const paymentStatus =
          latestOrder?.payment?.status

        const orderStatus =
          latestOrder?.status

        console.log(
          'Status pembayaran:',
          {
            paymentStatus,
            orderStatus,
            attempt:
              attempt + 1
          }
        )

        if (
          paymentStatus === 'paid'
          || orderStatus ===
            'processing'
        ) {
          return latestOrder
        }

        if (
          paymentStatus === 'failed'
          || orderStatus ===
            'cancelled'
        ) {
          return latestOrder
        }
      } catch (error) {
        console.error(
          'Gagal mengecek status pembayaran:',
          error
        )
      }

      await sleep(interval)
    }

    return null
  }

// ==========================================
// BUKA MIDTRANS
// ==========================================

const openMidtransPayment =
  async order => {
    /*
     * Ambil Snap Token dari backend.
     */
    const response =
      await api.post(
        `/orders/${order.id}/snap-token`
      )

    const snapToken =
      response.data?.snap_token

    const clientKey =
      response.data?.client_key

    const isProduction =
      Boolean(
        response.data?.is_production
      )

    if (!snapToken) {
      throw new Error(
        'Snap Token tidak ditemukan dari server.'
      )
    }

    if (!clientKey) {
      throw new Error(
        'Client Key Midtrans tidak ditemukan dari server.'
      )
    }

    /*
     * Load Snap.js.
     */
    const snap =
      await loadMidtransSnap(
        clientKey,
        isProduction
      )

    /*
     * Buka Snap popup.
     */
    return new Promise(
      resolve => {
        try {
          snap.pay(
            snapToken,
            {
              language: 'id',

              onSuccess:
                async result => {
                  console.log(
                    'Midtrans success:',
                    result
                  )

                  resolve({
                    type: 'success',
                    result
                  })
                },

              onPending:
                result => {
                  console.log(
                    'Midtrans pending:',
                    result
                  )

                  resolve({
                    type: 'pending',
                    result
                  })
                },

              onError:
                result => {
                  console.error(
                    'Midtrans error:',
                    result
                  )

                  resolve({
                    type: 'error',
                    result
                  })
                },

              onClose:
                () => {
                  console.log(
                    'Midtrans popup ditutup.'
                  )

                  resolve({
                    type: 'close'
                  })
                }
            }
          )
        } catch (error) {
          console.error(
            'Gagal membuka Snap:',
            error
          )

          resolve({
            type: 'error',
            error
          })
        }
      }
    )
  }

// ==========================================
// BUAT PESANAN + BAYAR MIDTRANS
// ==========================================

const placeOrder = async () => {
  /*
   * Cek alamat.
   */
  if (
    !selectedAddressId.value
  ) {
    await Swal.fire({
      icon: 'warning',
      title: 'Pilih Alamat',
      text:
        'Silakan pilih alamat pengiriman terlebih dahulu.'
    })

    return
  }

  /*
   * Cek cart.
   */
  if (
    cartItems.value.length === 0
  ) {
    await Swal.fire({
      icon: 'warning',
      title: 'Keranjang Kosong',
      text:
        'Tidak ada produk untuk diproses.'
    })

    router.push('/cart')
    return
  }

  /*
   * Alamat harus punya city_id
   * agar RajaOngkir bisa digunakan.
   */
  if (
    !selectedAddress.value?.city_id
  ) {
    await Swal.fire({
      icon: 'warning',
      title: 'Alamat Belum Lengkap',
      text:
        'Alamat ini belum memiliki data kota RajaOngkir. Silakan tambah alamat baru menggunakan provinsi dan kota.'
    })

    return
  }

  /*
   * Ongkir wajib tersedia.
   */
  if (
    shippingOptions.value.length === 0
    || !selectedShipping.value
  ) {
    await Swal.fire({
      icon: 'warning',
      title: 'Ongkos Kirim Belum Tersedia',
      text:
        'Silakan tunggu sampai ongkos kirim berhasil dihitung.'
    })

    return
  }

  /*
   * Konfirmasi.
   */
  const result =
    await Swal.fire({
      icon: 'question',

      title:
        'Lanjut ke Pembayaran?',

      html: `
        <div style="text-align:left">
          <p style="margin:0 0 8px">
            Pesanan akan dibuat dan
            pembayaran Midtrans akan dibuka.
          </p>

          <p style="margin:0">
            <strong>Total:</strong>
            ${formatRupiah(total.value)}
          </p>
        </div>
      `,

      showCancelButton:
        true,

      confirmButtonText:
        'Ya, Bayar Sekarang',

      cancelButtonText:
        'Batal',

      confirmButtonColor:
        '#2563eb'
    })

  if (
    !result.isConfirmed
  ) {
    return
  }

  placingOrder.value =
    true

  try {
    /*
     * =====================================================
     * STEP 1
     * BUAT ORDER
     * =====================================================
     */
    const orderResponse =
      await api.post(
        '/orders',
        {
          address_id:
            selectedAddressId.value,

          shipping_cost:
            shippingCost.value
        }
      )

    const order =
      orderResponse.data

    console.log(
      'Order berhasil dibuat:',
      order
    )

    /*
     * Cart backend sudah dikosongkan.
     */
    window.dispatchEvent(
      new CustomEvent(
        'cemilku-cart-updated',
        {
          detail: {
            count: 0
          }
        }
      )
    )

    /*
     * =====================================================
     * STEP 2
     * BUKA MIDTRANS
     * =====================================================
     */
    paymentProcessing.value =
      true

    await Swal.fire({
      icon: 'info',
      title:
        'Membuka Pembayaran',
      text:
        'Sebentar, halaman pembayaran Midtrans sedang disiapkan.',
      timer: 1200,
      showConfirmButton: false,
      allowOutsideClick: false
    })

    const paymentResult =
      await openMidtransPayment(
        order
      )

    console.log(
      'Hasil pembayaran Midtrans:',
      paymentResult
    )

    /*
     * =====================================================
     * PEMBAYARAN SUKSES
     * =====================================================
     */
    if (
      paymentResult.type ===
      'success'
    ) {
      /*
       * Tunggu webhook Midtrans
       * memperbarui database.
       */
      const latestOrder =
        await waitForPaymentConfirmation(
          order.id
        )

      if (
        latestOrder?.payment?.status ===
          'paid'
        || latestOrder?.status ===
          'processing'
      ) {
        await Swal.fire({
          icon: 'success',
          title:
            'Pembayaran Berhasil!',
          text:
            'Pembayaran berhasil dikonfirmasi. Pesanan sedang diproses.',
          confirmButtonText:
            'Lihat Pesanan',
          confirmButtonColor:
            '#2563eb'
        })
      } else {
        /*
         * Callback Midtrans berhasil,
         * tetapi webhook belum masuk.
         */
        await Swal.fire({
          icon: 'success',
          title:
            'Pembayaran Berhasil!',
          text:
            'Pembayaran sudah diterima Midtrans. Sistem sedang memperbarui status pesanan.',
          confirmButtonText:
            'Lihat Pesanan',
          confirmButtonColor:
            '#2563eb'
        })
      }

      router.push(
        `/orders/${order.id}`
      )

      return
    }

    /*
     * =====================================================
     * PEMBAYARAN PENDING
     * =====================================================
     */
    if (
      paymentResult.type ===
      'pending'
    ) {
      await Swal.fire({
        icon: 'info',
        title:
          'Pembayaran Menunggu',
        text:
          'Pembayaran belum selesai. Kamu dapat melanjutkannya dari halaman detail pesanan.',
        confirmButtonText:
          'Lihat Pesanan',
        confirmButtonColor:
          '#2563eb'
      })

      router.push(
        `/orders/${order.id}`
      )

      return
    }

    /*
     * =====================================================
     * POPUP DITUTUP
     * =====================================================
     */
    if (
      paymentResult.type ===
      'close'
    ) {
      await Swal.fire({
        icon: 'info',
        title:
          'Pembayaran Belum Selesai',
        text:
          'Pesanan sudah dibuat. Kamu masih dapat melanjutkan pembayaran dari detail pesanan.',
        confirmButtonText:
          'Lihat Pesanan',
        confirmButtonColor:
          '#2563eb'
      })

      router.push(
        `/orders/${order.id}`
      )

      return
    }

    /*
     * =====================================================
     * PEMBAYARAN ERROR
     * =====================================================
     */
    await Swal.fire({
      icon: 'error',
      title:
        'Pembayaran Gagal',
      text:
        'Pembayaran belum berhasil. Kamu dapat mencoba kembali dari halaman detail pesanan.',
      confirmButtonText:
        'Lihat Pesanan',
      confirmButtonColor:
        '#2563eb'
    })

    router.push(
      `/orders/${order.id}`
    )
  } catch (error) {
    console.error(
      'Gagal membuat atau membayar pesanan:',
      error
    )

    let message =
      'Gagal memproses pembayaran.'

    if (
      error.response?.data?.message
    ) {
      message =
        error.response.data.message
    } else if (
      error.message
    ) {
      message =
        error.message
    }

    if (
      error.response?.data?.errors
    ) {
      const errors =
        error.response.data.errors

      const firstError =
        Object.values(errors)[0]

      if (
        firstError?.[0]
      ) {
        message =
          firstError[0]
      }
    }

    await Swal.fire({
      icon: 'error',
      title:
        'Proses Gagal',
      text: message
    })
  } finally {
    placingOrder.value =
      false

    paymentProcessing.value =
      false
  }
}

// ==========================================
// KEMBALI KE CART
// ==========================================

const goToCart = () => {
  router.push('/cart')
}

// ==========================================
// DARK MODE
// ==========================================

const isDarkMode = ref(false)

const handleThemeChange = event => {
  if (
    typeof event.detail?.dark ===
    'boolean'
  ) {
    isDarkMode.value =
      event.detail.dark
  }
}

// ==========================================
// MOUNTED
// ==========================================

onMounted(async () => {
  const savedTheme =
    localStorage.getItem(
      'theme'
    )

  if (
    savedTheme === 'dark'
  ) {
    isDarkMode.value =
      true
  }

  window.addEventListener(
    'cemilku-theme-change',
    handleThemeChange
  )

  loading.value =
    true

  try {
    await Promise.all([
      fetchCart(),
      fetchAddresses(),
      fetchProvinces()
    ])
  } finally {
    loading.value =
      false
  }
})

// ==========================================
// UNMOUNTED
// ==========================================

onUnmounted(() => {
  window.removeEventListener(
    'cemilku-theme-change',
    handleThemeChange
  )
})
</script>

<template>
  <div
    class="checkout-page"
    :class="{ dark: isDarkMode }"
  >

    <Navbar />

    <main class="checkout-main">
      <div class="checkout-container">

        <!-- HEADER -->
        <div class="page-header">
          <button
            class="back-button"
            type="button"
            @click="goToCart"
          >
            ← Kembali ke Keranjang
          </button>

          <div>
            <h1>Checkout</h1>

            <p>
              Periksa pesanan dan lengkapi
              alamat pengiriman.
            </p>
          </div>
        </div>

        <!-- LOADING -->
        <div
          v-if="loading"
          class="loading-box"
        >
          <div class="spinner"></div>

          <p>
            Memuat data checkout...
          </p>
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

                <div class="section-heading-left">

                  <span
                    class="section-number"
                  >
                    1
                  </span>

                  <div>
                    <h2>
                      Alamat Pengiriman
                    </h2>

                    <p>
                      Pilih alamat untuk
                      pengiriman pesanan.
                    </p>
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

              <!-- FORM -->
              <div
                v-if="showAddressForm"
                class="address-form-wrapper"
              >

                <div class="form-title">

                  <div class="form-icon">
                    📍
                  </div>

                  <div>
                    <h3>
                      Tambah Alamat Baru
                    </h3>

                    <p>
                      Masukkan alamat
                      pengiriman kamu.
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
                        <span class="optional">
                          (opsional)
                        </span>
                      </label>

                      <input
                        v-model="
                          addressForm.label
                        "
                        type="text"
                        placeholder="Contoh: Rumah"
                      />

                    </div>

                    <div class="form-group">

                      <label>
                        Nama Penerima
                      </label>

                      <input
                        v-model="
                          addressForm
                            .recipient_name
                        "
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
                        v-model="
                          addressForm.phone
                        "
                        type="tel"
                        placeholder="08xxxxxxxxxx"
                        required
                      />

                    </div>

                    <div class="form-group">

                      <label>
                        Provinsi
                      </label>

                      <select
                        v-model="
                          addressForm
                            .province_id
                        "
                        required
                      >

                        <option value="">
                          {{
                            provinceLoading
                              ? 'Memuat provinsi...'
                              : 'Pilih Provinsi'
                          }}
                        </option>

                        <option
                          v-for="
                            province in provinces
                          "
                          :key="
                            province.id
                          "
                          :value="
                            province.id
                          "
                        >
                          {{
                            province.name
                          }}
                        </option>

                      </select>

                    </div>

                  </div>

                  <div class="form-row">

                    <div class="form-group">

                      <label>
                        Kota / Kabupaten
                      </label>

                      <select
                        v-model="
                          addressForm.city_id
                        "
                        :disabled="
                          !addressForm
                            .province_id ||
                          cityLoading
                        "
                        required
                      >

                        <option value="">
                          {{
                            cityLoading
                              ? 'Memuat kota...'
                              : 'Pilih Kota / Kabupaten'
                          }}
                        </option>

                        <option
                          v-for="
                            city in cities
                          "
                          :key="
                            city.id
                          "
                          :value="
                            city.id
                          "
                        >
                          {{
                            city.name
                          }}
                        </option>

                      </select>

                    </div>

                    <div class="form-group">

                      <label>
                        Kode Pos
                      </label>

                      <input
                        v-model="
                          addressForm
                            .postal_code
                        "
                        type="text"
                        placeholder="Contoh: 12345"
                        required
                      />

                    </div>

                  </div>

                  <div class="form-group">

                    <label>
                      Alamat Lengkap
                    </label>

                    <textarea
                      v-model="
                        addressForm
                          .full_address
                      "
                      rows="4"
                      placeholder="Nama jalan, nomor rumah, RT/RW, kecamatan, dan detail lainnya..."
                      required
                    ></textarea>

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
                      :disabled="
                        savingAddress
                      "
                    >

                      <span
                        v-if="
                          savingAddress
                        "
                      >
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
                v-if="
                  addresses.length > 0
                "
                class="address-list"
              >

                <label
                  v-for="
                    address in addresses
                  "
                  :key="address.id"
                  class="address-item"
                  :class="{
                    selected:
                      Number(
                        selectedAddressId
                      ) ===
                      Number(
                        address.id
                      )
                  }"
                >

                  <input
                    v-model="
                      selectedAddressId
                    "
                    type="radio"
                    name="address"
                    :value="
                      address.id
                    "
                  />

                  <div
                    class="radio-custom"
                  ></div>

                  <div
                    class="address-content"
                  >

                    <div
                      class="address-top"
                    >

                      <div>

                        <strong>
                          {{
                            address
                              .recipient_name
                          }}
                        </strong>

                        <span
                          v-if="
                            address.label
                          "
                          class="address-label"
                        >
                          {{
                            address.label
                          }}
                        </span>

                      </div>

                      <span
                        v-if="
                          Number(
                            selectedAddressId
                          ) ===
                          Number(
                            address.id
                          )
                        "
                        class="selected-badge"
                      >
                        Dipilih
                      </span>

                    </div>

                    <p class="phone">
                      📱
                      {{
                        address.phone
                      }}
                    </p>

                    <p
                      class="full-address"
                    >
                      {{
                        address.full_address
                      }}
                    </p>

                    <p
                      class="city-postal"
                    >
                      {{
                        address.city ||
                        '-'
                      }}
                      ·
                      {{
                        address.postal_code
                      }}
                    </p>

                  </div>

                </label>

              </div>

              <!-- EMPTY -->
              <div
                v-else
                class="empty-address"
              >

                <div
                  class="empty-icon"
                >
                  📍
                </div>

                <h3>
                  Belum Ada Alamat
                </h3>

                <p>
                  Tambahkan alamat
                  pengiriman terlebih
                  dahulu sebelum membuat
                  pesanan.
                </p>

                <button
                  class="empty-add-button"
                  type="button"
                  @click="
                    openAddressForm
                  "
                >
                  + Tambah Alamat
                </button>

              </div>

            </section>

            <!-- ==================================
                 PENGIRIMAN
            =================================== -->

            <section
              class="checkout-card"
            >

              <div
                class="section-heading"
              >

                <span
                  class="section-number"
                >
                  2
                </span>

                <div>

                  <h2>
                    Metode Pengiriman
                  </h2>

                  <p>
                    Biaya pengiriman
                    dihitung otomatis
                    menggunakan RajaOngkir.
                  </p>

                  <div
                    v-if="
                      selectedAddress
                    "
                    class="shipping-route-badge"
                  >
                    📍 Tujuan:
                    <strong>
                      {{
                        selectedAddress.city ||
                        '-'
                      }}
                    </strong>
                  </div>

                </div>

              </div>

              <!-- LOADING -->
              <div
                v-if="
                  shippingLoading
                "
                class="shipping-loading"
              >

                <div
                  class="spinner"
                ></div>

                <p>
                  Menghitung ongkos
                  kirim...
                </p>

              </div>

              <!-- SHIPPING -->
              <div
                v-else-if="
                  shippingOptions.length > 0
                "
                class="shipping-list"
              >

                <label
                  v-for="
                    shipping in
                      shippingOptions
                  "
                  :key="
                    `${shipping.courier}-${shipping.service}`
                  "
                  class="shipping-item"
                  :class="{
                    selected:
                      selectedShipping
                        ?.courier ===
                        shipping.courier &&
                      selectedShipping
                        ?.service ===
                        shipping.service
                  }"
                >

                  <input
                    v-model="
                      selectedShipping
                    "
                    type="radio"
                    name="shipping"
                    :value="
                      shipping
                    "
                  />

                  <div
                    class="shipping-radio"
                  ></div>

                  <div
                    class="shipping-info"
                  >

                    <strong>
                      {{
                        shipping
                          .courierName
                      }}
                      -
                      {{
                        shipping.service
                      }}
                    </strong>

                    <span>
                      {{
                        shipping.description ||
                        'Layanan pengiriman'
                      }}
                      · Estimasi
                      {{
                        shipping.etd
                      }}
                    </span>

                  </div>

                  <div
                    class="shipping-price"
                  >

                    <span
                      v-if="
                        shipping.discount >
                        0
                      "
                      class="shipping-original"
                    >
                      {{
                        formatRupiah(
                          shipping
                            .originalCost
                        )
                      }}
                    </span>

                    <strong>
                      {{
                        formatRupiah(
                          shipping.cost
                        )
                      }}
                    </strong>

                    <small
                      v-if="
                        shipping.discount >
                        0
                      "
                    >
                      Hemat
                      {{
                        formatRupiah(
                          shipping.discount
                        )
                      }}
                    </small>

                  </div>

                </label>

              </div>

              <!-- NO ADDRESS -->
              <div
                v-else-if="
                  !selectedAddress
                "
                class="empty-shipping"
              >
                Pilih alamat terlebih dahulu
                untuk melihat ongkos kirim.
              </div>

              <!-- NO SHIPPING -->
              <div
                v-else
                class="empty-shipping"
              >
                Ongkos kirim tidak tersedia
                untuk alamat ini.
              </div>

            </section>

            <!-- ==================================
                 PEMBAYARAN
            =================================== -->

            <section
              class="checkout-card"
            >

              <div
                class="section-heading"
              >

                <span
                  class="section-number"
                >
                  3
                </span>

                <div>
                  <h2>
                    Metode Pembayaran
                  </h2>

                  <p>
                    Pembayaran diproses
                    melalui Midtrans.
                  </p>
                </div>

              </div>

              <div
                class="payment-item selected"
              >

                <div
                  class="payment-radio"
                >
                  <div></div>
                </div>

                <div
                  class="payment-icon"
                >
                  💳
                </div>

                <div
                  class="payment-info"
                >

                  <strong>
                    Midtrans
                  </strong>

                  <span>
                    Pilih metode pembayaran
                    yang tersedia langsung
                    di halaman Midtrans.
                  </span>

                </div>

                <div
                  class="payment-check"
                >
                  ✓
                </div>

              </div>

              <div
                class="payment-note"
              >

                <div
                  class="note-icon"
                >
                  🔒
                </div>

                <p>
                  Setelah menekan tombol
                  pembayaran, halaman
                  pembayaran Midtrans akan
                  terbuka secara otomatis.
                </p>

              </div>

            </section>

          </div>

          <!-- ======================================
               RIGHT
          ======================================= -->

          <aside
            class="checkout-right"
          >

            <section
              class="summary-card"
            >

              <div
                class="summary-header"
              >

                <h2>
                  Ringkasan Pesanan
                </h2>

                <span>
                  {{
                    cartItems.length
                  }}
                  item
                </span>

              </div>

              <!-- PRODUK -->
              <div
                class="product-list"
              >

                <div
                  v-for="
                    item in cartItems
                  "
                  :key="item.id"
                  class="product-item"
                >

                  <div
                    class="product-image"
                  >

                    <img
                      v-if="
                        item.product
                          ?.image_url
                      "
                      :src="
                        item.product
                          .image_url
                      "
                      :alt="
                        item.product?.name
                      "
                    />

                    <div
                      v-else
                      class="image-placeholder"
                    >
                      🍪
                    </div>

                  </div>

                  <div
                    class="product-info"
                  >

                    <h3>
                      {{
                        item.product?.name
                      }}
                    </h3>

                    <span>
                      {{
                        item.quantity
                      }}
                      ×
                      {{
                        formatRupiah(
                          item.product
                            ?.price
                        )
                      }}
                    </span>

                  </div>

                  <strong>
                    {{
                      formatRupiah(
                        Number(
                          item.product
                            ?.price || 0
                        ) *
                        Number(
                          item.quantity ||
                            0
                        )
                      )
                    }}
                  </strong>

                </div>

              </div>

              <div
                class="summary-divider"
              ></div>

              <!-- SUBTOTAL -->
              <div
                class="summary-row"
              >

                <span>
                  Subtotal
                </span>

                <strong>
                  {{
                    formatRupiah(
                      subtotal
                    )
                  }}
                </strong>

              </div>

              <!-- SHIPPING -->
              <div
                class="summary-row"
              >

                <span>
                  Pengiriman
                </span>

                <strong>
                  {{
                    formatRupiah(
                      shippingCost
                    )
                  }}
                </strong>

              </div>

              <div
                class="summary-divider"
              ></div>

              <!-- TOTAL -->
              <div
                class="total-row"
              >

                <span>
                  Total
                </span>

                <strong>
                  {{
                    formatRupiah(
                      total
                    )
                  }}
                </strong>

              </div>

              <!-- ADDRESS -->
              <div
                v-if="
                  selectedAddress
                "
                class="selected-address-box"
              >

                <div
                  class="
                    selected-address-header
                  "
                >

                  <span>
                    📍
                  </span>

                  <strong>
                    Dikirim ke
                  </strong>

                </div>

                <strong>
                  {{
                    selectedAddress
                      .recipient_name
                  }}
                </strong>

                <p>
                  {{
                    selectedAddress
                      .full_address
                  }}
                </p>

                <span>
                  {{
                    selectedAddress.city
                  }},
                  {{
                    selectedAddress
                      .postal_code
                  }}
                </span>

              </div>

              <!-- BUTTON -->
              <button
                class="order-button"
                type="button"
                :disabled="
                  placingOrder ||
                  paymentProcessing ||
                  !selectedAddressId ||
                  !selectedShipping ||
                  cartItems.length === 0
                "
                @click="placeOrder"
              >

                <span
                  v-if="
                    placingOrder ||
                    paymentProcessing
                  "
                >
                  Memproses Pembayaran...
                </span>

                <span v-else>
                  Buat Pesanan & Bayar
                  <span>→</span>
                </span>

              </button>

              <p
                class="secure-text"
              >
                🔒 Pembayaran aman
                melalui Midtrans.
              </p>

            </section>

          </aside>

        </div>

      </div>
    </main>

    <Footer />

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

/* ==========================================
   MAIN
========================================== */

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
  grid-template-columns:
    minmax(0, 1fr)
    390px;
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
  box-shadow:
    0 8px 30px
    rgba(15, 23, 42, 0.05);
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
  gap: 15px;
}

.card-header {
  justify-content: space-between;
}

.section-heading {
  justify-content: flex-start;
  margin-bottom: 22px;
}

.section-heading-left {
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
   ADDRESS BUTTON
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
   FORM
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
  grid-template-columns:
    1fr
    1fr;
  gap: 15px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.form-group label {
  color: #334155;
  font-size: 13px;
  font-weight: 700;
}

.optional {
  color: #94a3b8;
  font-weight: 500;
}

.form-group input,
.form-group select,
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

.form-group select {
  cursor: pointer;
}

.form-group select:disabled {
  background: #f1f5f9;
  color: #94a3b8;
  cursor: not-allowed;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  border-color: #2563eb;
  box-shadow:
    0 0 0 3px
    rgba(37, 99, 235, 0.08);
}

.form-group textarea {
  resize: vertical;
  min-height: 100px;
}

.address-form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
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

.address-item.selected
  .radio-custom {
  border-color: #2563eb;
}

.address-item.selected
  .radio-custom::after {
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

.shipping-route-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-top: 8px;
  padding: 6px 12px;
  background: rgba(37, 99, 235, 0.08);
  border: 1px solid
    rgba(37, 99, 235, 0.2);
  border-radius: 8px;
  font-size: 12px;
  color: #2563eb;
}

.shipping-route-badge strong {
  font-weight: 700;
  text-transform: capitalize;
}

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

.shipping-item.selected
  .shipping-radio {
  border-color: #2563eb;
}

.shipping-item.selected
  .shipping-radio::after {
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
  min-width: 0;
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
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
  white-space: nowrap;
}

.shipping-price strong {
  color: #2563eb;
  font-size: 13px;
}

.shipping-original {
  color: #94a3b8;
  font-size: 11px;
  text-decoration: line-through;
}

.shipping-price small {
  color: #16a34a;
  font-size: 10px;
  font-weight: 700;
}

.shipping-loading {
  min-height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: #64748b;
  font-size: 13px;
}

.empty-shipping {
  padding: 20px;
  text-align: center;
  color: #64748b;
  font-size: 13px;
  background: #f8fafc;
  border-radius: 10px;
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
   ADDRESS SUMMARY
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
   BUTTON
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

.order-button > span > span {
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

.shipping-loading .spinner {
  width: 24px;
  height: 24px;
  border-width: 2px;
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

  .payment-item {
    align-items: flex-start;
  }
}

/* ==========================================
   DARK MODE
========================================== */

.checkout-page.dark {
  background: #0f172a;
  color: #f8fafc;
}

.checkout-page.dark
.checkout-card,
.checkout-page.dark
.summary-card {
  background: #1e293b;
  border-color: #334155;
  color: #f8fafc;
}

.checkout-page.dark
.page-header h1,
.checkout-page.dark
.card-header h2,
.checkout-page.dark
.section-heading h2,
.checkout-page.dark
.summary-header h2,
.checkout-page.dark
.payment-info strong,
.checkout-page.dark
.product-info h3 {
  color: #f8fafc;
}

.checkout-page.dark
.page-header p,
.checkout-page.dark
.card-header p,
.checkout-page.dark
.section-heading p,
.checkout-page.dark
.payment-info span,
.checkout-page.dark
.product-info span,
.checkout-page.dark
.summary-row,
.checkout-page.dark
.full-address,
.checkout-page.dark
.city-postal,
.checkout-page.dark
.phone,
.checkout-page.dark
.empty-shipping,
.checkout-page.dark
.shipping-loading {
  color: #cbd5e1;
}

.checkout-page.dark
.address-item,
.checkout-page.dark
.shipping-item {
  background: #0f172a;
  border-color: #334155;
}

.checkout-page.dark
.address-item.selected,
.checkout-page.dark
.shipping-item.selected {
  background: rgba(37, 99, 235, 0.15);
  border-color: #3b82f6;
}

.checkout-page.dark
.shipping-route-badge {
  background: rgba(37, 99, 235, 0.15);
  border-color: rgba(37, 99, 235, 0.3);
  color: #60a5fa;
}

.checkout-page.dark
.address-form-wrapper {
  background: #0f172a;
  border-color: #334155;
}

.checkout-page.dark
.form-group label {
  color: #e2e8f0;
}

.checkout-page.dark
input,
.checkout-page.dark
select,
.checkout-page.dark
textarea {
  background: #1e293b;
  border-color: #475569;
  color: #f8fafc;
}

.checkout-page.dark
input::placeholder,
.checkout-page.dark
textarea::placeholder {
  color: #94a3b8;
}

.checkout-page.dark
.form-group select:disabled {
  background: #0f172a;
  color: #64748b;
}

.checkout-page.dark
.payment-note,
.checkout-page.dark
.selected-address-box {
  background: #0f172a;
  border-color: #334155;
}

.checkout-page.dark
.summary-divider {
  background: #334155;
}

.checkout-page.dark
.payment-note p,
.checkout-page.dark
.selected-address-box p,
.checkout-page.dark
.selected-address-box > span {
  color: #cbd5e1;
}

.checkout-page.dark
.product-image {
  background: #0f172a;
}

.checkout-page.dark
.payment-icon {
  background: #0f172a;
}
</style>