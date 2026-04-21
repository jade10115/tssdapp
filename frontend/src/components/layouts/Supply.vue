<template>
  <div class="inv-page">
    <!-- Header -->
    <div class="gov-header">
      <div class="gov-title">
        <div class="seal" aria-hidden="true">🏛️</div>
        <div>
          <div class="h-title">Supply & Inventory</div>
          <div class="h-sub">Modern government-friendly UI • responsive • accessible</div>
        </div>
      </div>

      <div class="gov-actions">
        <button @click="openAddProduct" class="gbtn gbtn-primary">
          <i class="bi bi-plus-circle"></i>
          <span>Add Product</span>
        </button>

        <div class="search">
          <i class="bi bi-search"></i>
          <input v-model="searchQuery" type="text" placeholder="Search products…" />
          <button v-if="searchQuery" class="search-clear" @click="searchQuery = ''" aria-label="Clear search">
            <i class="bi bi-x-circle"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="seg" role="tablist" aria-label="Status filter">
        <button @click="statusFilter = 'All'" :class="segClass('All')" role="tab">
          <span class="dot all"></span> All
          <span class="seg-count">{{ products.length }}</span>
        </button>
        <button @click="statusFilter = 'Available'" :class="segClass('Available')" role="tab">
          <span class="dot ok"></span> Available
          <span class="seg-count">{{ countsByStatus.Available }}</span>
        </button>
        <button @click="statusFilter = 'Low Qty'" :class="segClass('Low Qty')" role="tab">
          <span class="dot warn"></span> Low Qty
          <span class="seg-count">{{ countsByStatus["Low Qty"] }}</span>
        </button>
        <button @click="statusFilter = 'Not Available'" :class="segClass('Not Available')" role="tab">
          <span class="dot bad"></span> Not Available
          <span class="seg-count">{{ countsByStatus["Not Available"] }}</span>
        </button>
      </div>

      <div class="quick">
        <div class="qcard">
          <div class="qk">Showing</div>
          <div class="qv">{{ filteredProducts.length }}</div>
        </div>
        <div class="qcard">
          <div class="qk">In Cart</div>
          <div class="qv">{{ cartCount }}</div>
        </div>
        <div class="qcard">
          <div class="qk">Total</div>
          <div class="qv money">₱{{ formatMoneyNoSymbol(cartTotal) }}</div>
        </div>
      </div>
    </div>

    <!-- Low Stock Notice -->
    <div class="notice warn" v-if="lowStock.length">
      <div class="nicon"><i class="bi bi-exclamation-triangle"></i></div>
      <div class="nbody">
        <div class="ntitle">Items Running Low</div>
        <div class="ntext">
          <span v-for="l in lowStock" :key="l.id" class="pill-warn">
            {{ l.product_name }} • {{ l.current_stock }} {{ pluralizeUnit(l.unit, l.current_stock) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Main Grid -->
    <div class="grid">
      <!-- Cart -->
      <aside class="cart" aria-label="Cart panel">
        <div class="cart-head">
          <div class="cart-title">
            <i class="bi bi-cart3"></i>
            <span>Cart</span>
          </div>

          <div class="cart-actions">
            <span class="cart-badge" :title="`${cartCount} items`">{{ cartCount }}</span>
            <button class="mini-btn" @click="clearCart" :disabled="!cart.length" title="Clear cart">
              <i class="bi bi-trash3"></i>
            </button>
          </div>
        </div>

        <div class="cart-body">
          <div v-if="cart.length" class="cart-list">
            <div v-for="item in cart" :key="item.id" class="cart-item">
              <div class="ci-top">
                <div class="ci-left">
                  <div class="ci-name">{{ item.product_name }}</div>
                  <div class="ci-sub">
                    ₱{{ item.price }} • {{ item.unit }}
                    <span class="sep">•</span>
                    Stock: {{ item.current_stock }}
                  </div>
                </div>

                <button class="mini-btn danger" @click="removeFromCart(item.id)" title="Remove item">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>

              <div class="ci-bottom">
                <div class="qty qty-sm">
                  <button class="qty-btn" type="button" @click="decCart(item.id)" :disabled="item.quantity <= 1">−</button>
                  <input
                    type="number"
                    min="1"
                    :max="item.current_stock"
                    :value="item.quantity"
                    @input="setCartQty(item.id, $event.target.value)"
                  />
                  <button
                    class="qty-btn"
                    type="button"
                    @click="incCart(item.id)"
                    :disabled="item.quantity >= item.current_stock"
                  >
                    +
                  </button>
                </div>

                <div class="ci-sum">
                  <div class="ci-line">Line Total</div>
                  <div class="ci-total">₱{{ formatMoneyNoSymbol(item.price * item.quantity) }}</div>
                </div>
              </div>
            </div>

            <div class="cart-summary">
              <div class="sum-row">
                <span>Items</span>
                <b>{{ cartCount }}</b>
              </div>
              <div class="sum-row">
                <span>Total Amount</span>
                <b class="sum-money">₱{{ formatMoneyNoSymbol(cartTotal) }}</b>
              </div>
            </div>
          </div>

          <div v-else class="empty-cart">
            <i class="bi bi-inbox"></i>
            <div>
              <div class="ec-title">No items in cart</div>
              <div class="ec-sub">Add products from the list to request supplies.</div>
            </div>
          </div>

          <div class="reqby">
            <label class="lbl">Requested By</label>
            <select v-model="selectedRequestedBy" class="sel">
              <option value="">-- Select Profile --</option>
              <option v-for="p in profiles" :key="p.id" :value="p.user_id">
                {{ p.first_name }} {{ p.last_name }} ({{ p.position }})
              </option>
            </select>
            <div class="hint" v-if="!selectedRequestedBy">
              Required to checkout.
            </div>
          </div>

          <button class="gbtn gbtn-primary full" @click="checkout" :disabled="checkoutDisabled">
            <span v-if="checkoutLoading" class="spinner"></span>
            <i v-else class="bi bi-check-circle"></i>
            <span>{{ checkoutLoading ? "Processing…" : "Checkout" }}</span>
          </button>
        </div>
      </aside>

      <!-- Products -->
      <main class="products" aria-label="Products list">
        <div class="phead">
          <div class="pmeta">
            <div class="ptitle">Products</div>
            <div class="psub">
              Sorted by status • Tap “Add to Cart” to include items
            </div>
          </div>

          <div class="phead-actions">
            <button class="gbtn gbtn-ghost" @click="loadProducts" :disabled="loadingProducts">
              <span v-if="loadingProducts" class="spinner"></span>
              <i v-else class="bi bi-arrow-clockwise"></i>
              Refresh
            </button>
          </div>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loadingProducts" class="cards">
          <div v-for="n in 8" :key="n" class="pcard skel">
            <div class="skel-img"></div>
            <div class="skel-body">
              <div class="skel-line w80"></div>
              <div class="skel-line w60"></div>
              <div class="skel-box"></div>
              <div class="skel-line w70"></div>
              <div class="skel-btn"></div>
            </div>
          </div>
        </div>

        <div v-else>
          <div v-if="!filteredProducts.length" class="empty-products">
            <div class="empty-ico"><i class="bi bi-search"></i></div>
            <div class="empty-title">No products found</div>
            <div class="empty-sub">
              Try changing the filter or search terms.
            </div>
            <button class="gbtn gbtn-ghost" @click="resetFilters">
              <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
            </button>
          </div>

          <div v-else class="cards">
            <div v-for="product in paginatedProducts" :key="product.id" class="pcard" :class="statusClass(product.status)">
              <div class="pimg">
                <img :src="getImageUrl(product.image)" alt="Product image" />
                <div class="tag" :class="tagClass(product.status)">
                  <span class="tag-dot"></span>{{ product.status }}
                </div>
              </div>

              <div class="pbody">
                <div class="pname-row">
                  <div class="pname" :title="product.product_name">{{ product.product_name }}</div>
                  <div class="price">₱{{ product.price }}</div>
                </div>

                <div class="pinfo">
                  <div class="info-row">
                    <span class="k">Stock</span>
                    <span class="v">{{ product.current_stock }} {{ pluralizeUnit(product.unit, product.current_stock) }}</span>
                  </div>
                  <div class="info-row">
                    <span class="k">Unit</span>
                    <span class="v unit">{{ product.unit }}</span>
                  </div>
                </div>

                <div class="qty-row">
                  <label class="qty-lbl">Qty</label>
                  <div class="qty">
                    <button class="qty-btn" type="button"
                            :disabled="product.current_stock <= 0 || getQuantity(product.id) <= 1"
                            @click="updateQuantity(product.id, getQuantity(product.id) - 1)">−</button>

                    <input
                      type="number"
                      min="1"
                      :max="product.current_stock"
                      :disabled="product.current_stock <= 0"
                      :value="getQuantity(product.id)"
                      @input="updateQuantity(product.id, $event.target.value)"
                    />

                    <button class="qty-btn" type="button"
                            :disabled="product.current_stock <= 0 || getQuantity(product.id) >= product.current_stock"
                            @click="updateQuantity(product.id, getQuantity(product.id) + 1)">+</button>
                  </div>
                </div>

                <button
                  @click="addToCart(product)"
                  class="gbtn gbtn-warn full"
                  :disabled="product.current_stock <= 0 || product.status === 'Not Available'"
                >
                  <i class="bi bi-cart-plus"></i>
                  Add to Cart
                </button>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div class="pager" v-if="filteredProducts.length">
            <button class="gbtn gbtn-ghost" @click="prevPage" :disabled="currentPage === 1">
              <i class="bi bi-chevron-left"></i> Prev
            </button>

            <div class="page-ind">
              Page <b>{{ currentPage }}</b> of <b>{{ totalPages || 1 }}</b>
            </div>

            <button class="gbtn gbtn-ghost" @click="nextPage" :disabled="currentPage === totalPages || totalPages === 0">
              Next <i class="bi bi-chevron-right"></i>
            </button>
          </div>
        </div>
      </main>
    </div>

    <!-- Mobile sticky checkout bar -->
    <div class="mobile-bar" v-if="isMobile">
      <div class="mb-left">
        <div class="mb-items">
          <i class="bi bi-cart3"></i>
          <span><b>{{ cartCount }}</b> item(s)</span>
        </div>
        <div class="mb-total">₱{{ formatMoneyNoSymbol(cartTotal) }}</div>
      </div>
      <button class="gbtn gbtn-primary" @click="scrollToCart" :disabled="!cart.length">
        Checkout
      </button>
    </div>

    <!-- ➕ ADD PRODUCT MODAL -->
    <div v-if="showAddModal" class="modal-overlay" @click.self="closeAddModal">
      <div class="modal-shell" role="dialog" aria-modal="true">
        <div class="mhead">
          <div class="mtitle">
            <div class="micon"><i class="bi bi-plus-circle-fill"></i></div>
            <div>
              <div class="mtt">Add New Product</div>
              <div class="mts">Upload an image or take a photo using your camera.</div>
            </div>
          </div>

          <button class="mclose" type="button" @click="closeAddModal" aria-label="Close">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <div class="mbody">
          <div class="mgrid">
            <div class="field full">
              <label class="flbl">Product Name <span class="req">*</span></label>
              <input class="fin" type="text" v-model="newProduct.product_name" placeholder="Enter product name" />
            </div>

            <div class="field">
              <label class="flbl">Unit <span class="req">*</span></label>
              <select class="fin" v-model="newProduct.unit">
                <option value="">Select unit</option>
                <option value="pc">pc (piece)</option>
                <option value="rem">rem (ream)</option>
                <option value="box">box</option>
                <option value="qty">qty (quantity)</option>
              </select>
            </div>

            <div class="field">
              <label class="flbl">Price <span class="req">*</span></label>
              <div class="money">
                <span class="peso">₱</span>
                <input class="fin" type="number" v-model="newProduct.price" placeholder="0.00" />
              </div>
            </div>

            <div class="field full">
              <label class="flbl">Starting Stock <span class="req">*</span></label>
              <input class="fin" type="number" v-model="newProduct.current_stock" placeholder="Enter quantity" />
            </div>

            <div class="field full">
              <label class="flbl">Image (optional)</label>

              <div class="img-actions">
                <label class="filebtn">
                  <i class="bi bi-upload"></i>
                  <span>Upload File</span>
                  <input type="file" accept="image/*" @change="handleAddImage" />
                </label>

                <button class="cambtn" type="button" @click="openCamera">
                  <i class="bi bi-camera"></i>
                  <span>Use Camera</span>
                </button>

                <button class="ghostbtn" type="button" @click="clearImage" :disabled="!newProduct.image">
                  <i class="bi bi-trash3"></i>
                  <span>Remove</span>
                </button>
              </div>

              <div class="preview">
                <img :src="addPreviewImage" alt="Preview" />
              </div>

              <div v-if="camera.open" class="camera">
                <div class="camera-top">
                  <div class="cam-title"><i class="bi bi-record-circle"></i> Camera Preview</div>
                  <button class="mmini" type="button" @click="closeCamera">
                    <i class="bi bi-x"></i> Close
                  </button>
                </div>

                <video ref="videoRef" class="vid" playsinline autoplay muted></video>

                <div class="camera-actions">
                  <button class="gbtn gbtn-primary" type="button" @click="capturePhoto" :disabled="camera.loading">
                    <i class="bi bi-camera-fill"></i> Capture
                  </button>
                  <button class="gbtn gbtn-ghost" type="button" @click="switchCamera" :disabled="camera.loading">
                    <i class="bi bi-arrow-repeat"></i> Switch
                  </button>
                </div>

                <canvas ref="canvasRef" class="hidden-canvas"></canvas>

                <div v-if="camera.error" class="cam-error">
                  <i class="bi bi-exclamation-triangle"></i> {{ camera.error }}
                </div>

                <div class="cam-note">Allow camera permission when prompted (best on mobile).</div>
              </div>
            </div>
          </div>
        </div>

        <div class="mfoot">
          <button class="gbtn gbtn-ghost" type="button" @click="closeAddModal">
            <i class="bi bi-x-circle"></i> Close
          </button>

          <button class="gbtn gbtn-primary" type="button" @click="saveNewProduct" :disabled="savingProduct">
            <span v-if="savingProduct" class="spinner"></span>
            <i v-else class="bi bi-check-circle"></i>
            <span>{{ savingProduct ? "Saving…" : "Save Product" }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onBeforeUnmount, nextTick, inject } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

// ========== INJECT GLOBAL API BASE ==========
const API_BASE = inject("API_BASE");
// Derive base URL for images (strip trailing "/api")
const BASE_URL = API_BASE.replace(/\/api$/, "");

/* ===== State ===== */
const products = ref([]);
const lowStock = ref([]);
const cart = ref([]);
const searchQuery = ref("");
const statusFilter = ref("All");
const quantities = ref({});
const profiles = ref([]);
const selectedRequestedBy = ref("");

const loadingProducts = ref(false);
const checkoutLoading = ref(false);

const currentPage = ref(1);
const itemsPerPage = 12;

/* ===== Helpers ===== */
const getImageUrl = (image) =>
  image ? `${BASE_URL}/product-images/${image}` : "/img/no-image.png";

const pluralizeUnit = (unit, qty) => {
  if (qty > 1) {
    if (unit === "pc") return "pcs";
    if (unit === "rem") return "rems";
    if (unit === "box") return "boxes";
    if (unit === "qty") return "qtys";
  }
  return unit;
};

const formatMoneyNoSymbol = (n) => {
  const x = Number(n || 0);
  return new Intl.NumberFormat("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(x);
};

const segClass = (val) => ["segbtn", statusFilter.value === val ? "active" : ""];
const statusClass = (s) => ({ "is-low": s === "Low Qty", "is-none": s === "Not Available" });
const tagClass = (s) => ({ ok: s === "Available", warn: s === "Low Qty", bad: s === "Not Available" });

/* ===== Counts ===== */
const countsByStatus = computed(() => {
  const c = { Available: 0, "Low Qty": 0, "Not Available": 0 };
  for (const p of products.value) {
    if (c[p.status] !== undefined) c[p.status] += 1;
  }
  return c;
});

/* ===== Filtering + Sorting ===== */
const filteredProducts = computed(() =>
  products.value
    .filter((p) => p.product_name.toLowerCase().includes(searchQuery.value.toLowerCase()))
    .filter((p) => (statusFilter.value === "All" ? true : p.status === statusFilter.value))
    .sort((a, b) => {
      const rank = { Available: 1, "Low Qty": 2, "Not Available": 3 };
      return rank[a.status] - rank[b.status];
    })
);

/* ===== Pagination ===== */
const totalPages = computed(() => Math.ceil(filteredProducts.value.length / itemsPerPage));
const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredProducts.value.slice(start, start + itemsPerPage);
});
const prevPage = () => (currentPage.value = Math.max(1, currentPage.value - 1));
const nextPage = () => (currentPage.value = Math.min(totalPages.value || 1, currentPage.value + 1));

watch([searchQuery, statusFilter], () => (currentPage.value = 1));

/* ===== Quantity (Products list) ===== */
const getQuantity = (productId) => quantities.value[productId] || 1;
const updateQuantity = (productId, value) => {
  const num = typeof value === "number" ? value : parseInt(value) || 1;
  quantities.value = { ...quantities.value, [productId]: Math.max(1, num) };
};

/* ===== Cart UX improvements ===== */
const cartCount = computed(() => cart.value.reduce((s, i) => s + Number(i.quantity || 0), 0));
const cartTotal = computed(() => cart.value.reduce((s, i) => s + Number(i.price || 0) * Number(i.quantity || 0), 0));
const checkoutDisabled = computed(() => !cart.value.length || !selectedRequestedBy.value || checkoutLoading.value);

const normalizeQty = (q) => {
  const n = parseInt(q);
  return Number.isFinite(n) ? n : 1;
};

const addToCart = (product) => {
  const qtyToAdd = getQuantity(product.id);
  const existing = cart.value.find((i) => i.id === product.id);
  const already = existing ? existing.quantity : 0;

  if (qtyToAdd + already > product.current_stock) {
    return Swal.fire("Not enough stock!", "Requested quantity exceeds available stock.", "error");
  }

  if (existing) existing.quantity += qtyToAdd;
  else cart.value.push({ ...product, quantity: qtyToAdd });

  updateQuantity(product.id, 1);
  Swal.fire({ icon: "success", title: "Added to cart", timer: 900, showConfirmButton: false });
};

const removeFromCart = (id) => {
  cart.value = cart.value.filter((i) => i.id !== id);
};

const clearCart = async () => {
  if (!cart.value.length) return;
  const res = await Swal.fire({
    icon: "warning",
    title: "Clear cart?",
    text: "This will remove all items from the cart.",
    showCancelButton: true,
    confirmButtonText: "Yes, clear",
    cancelButtonText: "Cancel",
  });
  if (!res.isConfirmed) return;
  cart.value = [];
};

const setCartQty = (id, value) => {
  const item = cart.value.find((i) => i.id === id);
  if (!item) return;
  const qty = Math.max(1, normalizeQty(value));
  item.quantity = Math.min(qty, item.current_stock);
};

const incCart = (id) => {
  const item = cart.value.find((i) => i.id === id);
  if (!item) return;
  if (item.quantity + 1 > item.current_stock) return;
  item.quantity += 1;
};

const decCart = (id) => {
  const item = cart.value.find((i) => i.id === id);
  if (!item) return;
  item.quantity = Math.max(1, item.quantity - 1);
};

/* ===== Checkout ===== */
const checkout = async () => {
  if (checkoutDisabled.value) return;

  try {
    checkoutLoading.value = true;
    const token = localStorage.getItem("auth_token");
    await axios.post(
      `${API_BASE}/store`,
      {
        user_id: selectedRequestedBy.value,
        items: cart.value.map((i) => ({
          product_id: i.id,
          quantity: i.quantity,
          price: i.price,
          unit: i.unit,
        })),
      },
      { headers: { Authorization: `Bearer ${token}` } }
    );

    Swal.fire("Success!", "Request submitted.", "success");
    cart.value = [];
    await loadProducts();
    await loadLowStock();
  } catch (e) {
    console.error(e);
    Swal.fire("Error", e?.response?.data?.message || "Checkout failed.", "error");
  } finally {
    checkoutLoading.value = false;
  }
};

/* ===== Loaders ===== */
const loadLowStock = async () => {
  lowStock.value = (await axios.get(`${API_BASE}/products/low`)).data;
};
const loadProfiles = async (division) => {
  profiles.value = (await axios.get(`${API_BASE}/profiles/by-division/${division}`)).data;
};
const loadProducts = async () => {
  try {
    loadingProducts.value = true;
    const res = await axios.get(`${API_BASE}/products`);
    products.value = res.data;

    const newQuantities = {};
    res.data.forEach((p) => (newQuantities[p.id] = quantities.value[p.id] || 1));
    quantities.value = newQuantities;
  } finally {
    loadingProducts.value = false;
  }
};

onMounted(async () => {
  await loadProducts();
  const session = await axios.get(`${API_BASE}/session`);
  if (session.data.logged_in) loadProfiles(session.data.user.division);
  loadLowStock();
});

/* ===== Add Product Modal + Camera (backend unchanged) ===== */
const showAddModal = ref(false);
const savingProduct = ref(false);
const addPreviewImage = ref("/img/no-image.png");
const newProduct = ref({
  product_name: "",
  unit: "",
  price: "",
  current_stock: "",
  image: null,
});

const openAddProduct = () => (showAddModal.value = true);

const closeAddModal = async () => {
  showAddModal.value = false;
  await closeCamera();
};

const handleAddImage = (e) => {
  const file = e.target.files?.[0];
  if (file) {
    newProduct.value.image = file;
    addPreviewImage.value = URL.createObjectURL(file);
  }
};

const clearImage = () => {
  newProduct.value.image = null;
  addPreviewImage.value = "/img/no-image.png";
};

const validateNewProduct = () => {
  const p = newProduct.value;
  if (!String(p.product_name || "").trim()) return "Product name is required.";
  if (!String(p.unit || "").trim()) return "Unit is required.";
  const price = Number(p.price);
  if (!Number.isFinite(price) || price <= 0) return "Price must be greater than 0.";
  const stock = Number(p.current_stock);
  if (!Number.isFinite(stock) || stock < 0) return "Starting stock must be 0 or greater.";
  return null;
};

const saveNewProduct = async () => {
  const err = validateNewProduct();
  if (err) return Swal.fire("Validation", err, "warning");

  try {
    savingProduct.value = true;

    const fd = new FormData();
    Object.keys(newProduct.value).forEach((key) => fd.append(key, newProduct.value[key]));

    await axios.post(`${API_BASE}/products`, fd, {
      headers: { "Content-Type": "multipart/form-data" },
    });

    Swal.fire("Product Added!", "", "success");
    showAddModal.value = false;
    await closeCamera();

    await loadProducts();
    await loadLowStock();

    newProduct.value = { product_name: "", unit: "", price: "", current_stock: "", image: null };
    addPreviewImage.value = "/img/no-image.png";
  } catch (e) {
    console.error(e);
    Swal.fire("Error", e?.response?.data?.message || "Failed to add product.", "error");
  } finally {
    savingProduct.value = false;
  }
};

/* Camera support */
const videoRef = ref(null);
const canvasRef = ref(null);

const camera = ref({
  open: false,
  loading: false,
  error: "",
  facingMode: "environment",
  stream: null,
});

const stopStream = () => {
  if (camera.value.stream) {
    camera.value.stream.getTracks().forEach((t) => t.stop());
    camera.value.stream = null;
  }
};

const openCamera = async () => {
  camera.value.error = "";
  camera.value.loading = true;

  try {
    camera.value.open = true;
    await nextTick();
    stopStream();

    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: camera.value.facingMode },
      audio: false,
    });

    camera.value.stream = stream;
    if (videoRef.value) {
      videoRef.value.srcObject = stream;
      await videoRef.value.play();
    }
  } catch (err) {
    console.error(err);
    camera.value.error =
      err?.name === "NotAllowedError"
        ? "Camera permission denied."
        : "Unable to access camera on this device/browser.";
    camera.value.open = false;
  } finally {
    camera.value.loading = false;
  }
};

const closeCamera = async () => {
  stopStream();
  camera.value.open = false;
  camera.value.error = "";
};

const switchCamera = async () => {
  camera.value.facingMode = camera.value.facingMode === "environment" ? "user" : "environment";
  await openCamera();
};

const capturePhoto = async () => {
  camera.value.error = "";
  if (!videoRef.value || !canvasRef.value) return;

  try {
    const video = videoRef.value;
    const canvas = canvasRef.value;

    const w = video.videoWidth || 1280;
    const h = video.videoHeight || 720;

    canvas.width = w;
    canvas.height = h;

    const ctx = canvas.getContext("2d");
    ctx.drawImage(video, 0, 0, w, h);

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, "image/jpeg", 0.92));
    if (!blob) throw new Error("Failed to capture photo.");

    const file = new File([blob], `camera_${Date.now()}.jpg`, { type: "image/jpeg" });
    newProduct.value.image = file;
    addPreviewImage.value = URL.createObjectURL(file);

    await closeCamera();
  } catch (err) {
    console.error(err);
    camera.value.error = "Failed to capture. Try again.";
  }
};

onBeforeUnmount(() => stopStream());

/* Keep quantities updated when products change */
watch(
  products,
  (newProducts) => {
    const newQuantities = { ...quantities.value };
    let needsUpdate = false;
    newProducts.forEach((p) => {
      if (!newQuantities[p.id]) {
        newQuantities[p.id] = 1;
        needsUpdate = true;
      }
    });
    if (needsUpdate) quantities.value = newQuantities;
  },
  { deep: true }
);

/* UX helpers */
const resetFilters = () => {
  statusFilter.value = "All";
  searchQuery.value = "";
  currentPage.value = 1;
};

/* Mobile sticky bar */
const isMobile = ref(false);
const updateIsMobile = () => (isMobile.value = window.matchMedia("(max-width: 900px)").matches);
const scrollToCart = () => {
  const el = document.querySelector(".cart");
  if (el) el.scrollIntoView({ behavior: "smooth", block: "start" });
};
onMounted(() => {
  updateIsMobile();
  window.addEventListener("resize", updateIsMobile);
});
onBeforeUnmount(() => window.removeEventListener("resize", updateIsMobile));
</script>

<style scoped>
/* All styles remain exactly as in the original component – unchanged */
.inv-page {
  padding: 18px;
  max-width: 1400px;
  margin: 0 auto;
  color: #0f172a;
}

.gov-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 14px;
  flex-wrap: wrap;
  padding: 16px;
  border-radius: 18px;
  background: linear-gradient(180deg, #ffffff, #f7fbff);
  border: 1px solid #e6eef8;
  box-shadow: 0 10px 30px rgba(2, 6, 23, 0.05);
}
.gov-title {
  display: flex;
  align-items: center;
  gap: 12px;
}
.seal {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  background: rgba(2, 132, 199, 0.1);
  border: 1px solid rgba(2, 132, 199, 0.2);
  font-size: 20px;
}
.h-title {
  font-weight: 1000;
  letter-spacing: -0.02em;
  font-size: 18px;
}
.h-sub {
  margin-top: 4px;
  color: #64748b;
  font-weight: 800;
  font-size: 12px;
}

.gov-actions {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

.search {
  position: relative;
  min-width: 260px;
  flex: 1 1 260px;
}
.search i.bi-search {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
  font-size: 14px;
}
.search input {
  width: 100%;
  height: 42px;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  padding: 10px 40px 10px 38px;
  font-weight: 900;
  outline: none;
  background: #fff;
}
.search input:focus {
  border-color: rgba(2, 132, 199, 0.45);
  box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.12);
}
.search-clear {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  padding: 4px;
}

.gbtn {
  border: 1px solid transparent;
  border-radius: 14px;
  padding: 10px 14px;
  font-weight: 1000;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.12s ease;
  user-select: none;
}
.gbtn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}
.gbtn-primary {
  background: linear-gradient(90deg, #0284c7, #0369a1);
  color: #fff;
  box-shadow: 0 10px 22px rgba(2, 132, 199, 0.18);
}
.gbtn-warn {
  background: linear-gradient(90deg, #f59e0b, #d97706);
  color: #0b1220;
  box-shadow: 0 10px 22px rgba(245, 158, 11, 0.18);
}
.gbtn-ghost {
  background: #fff;
  border-color: #e2e8f0;
  color: #0f172a;
}
.gbtn:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 26px rgba(2, 6, 23, 0.08);
}
.full {
  width: 100%;
  justify-content: center;
}

.spinner {
  width: 16px;
  height: 16px;
  border-radius: 999px;
  border: 2px solid rgba(255, 255, 255, 0.45);
  border-top: 2px solid #fff;
  animation: spin 0.85s linear infinite;
}
.gbtn-ghost .spinner {
  border-color: rgba(15, 23, 42, 0.18);
  border-top-color: rgba(15, 23, 42, 0.7);
}
@keyframes spin {
  to { transform: rotate(360deg); }
}

.toolbar {
  margin-top: 14px;
  display: flex;
  gap: 12px;
  justify-content: space-between;
  align-items: stretch;
  flex-wrap: wrap;
}
.seg {
  display: inline-flex;
  gap: 8px;
  padding: 8px;
  border-radius: 16px;
  border: 1px solid #e6eef8;
  background: #fff;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.04);
  flex-wrap: wrap;
}
.segbtn {
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #0f172a;
  border-radius: 12px;
  padding: 8px 12px;
  font-weight: 1000;
  cursor: pointer;
  display: inline-flex;
  gap: 8px;
  align-items: center;
}
.segbtn.active {
  border-color: rgba(2, 132, 199, 0.35);
  background: rgba(2, 132, 199, 0.08);
  color: #075985;
}
.seg-count {
  margin-left: 2px;
  font-weight: 1100;
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #334155;
}
.dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  display: inline-block;
}
.dot.all { background: #94a3b8; }
.dot.ok { background: #10b981; }
.dot.warn { background: #f59e0b; }
.dot.bad { background: #ef4444; }

.quick {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: minmax(140px, 1fr);
  gap: 10px;
  align-items: stretch;
  flex: 1 1 420px;
}
.qcard {
  border-radius: 16px;
  border: 1px solid #e6eef8;
  background: #fff;
  padding: 12px 14px;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.04);
}
.qk {
  font-weight: 900;
  font-size: 12px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
.qv {
  margin-top: 6px;
  font-weight: 1100;
  font-size: 22px;
}
.qv.money {
  color: #047857;
}

.notice {
  margin-top: 14px;
  border-radius: 18px;
  border: 1px solid #e2e8f0;
  background: #fff;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.04);
  padding: 12px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
}
.notice.warn {
  border-color: rgba(245, 158, 11, 0.26);
  background: linear-gradient(180deg, #fff7ed, #fff);
}
.nicon {
  width: 40px;
  height: 40px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  background: rgba(245, 158, 11, 0.12);
  border: 1px solid rgba(245, 158, 11, 0.2);
  color: #b45309;
}
.ntitle { font-weight: 1100; }
.ntext {
  margin-top: 6px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.pill-warn {
  font-weight: 900;
  font-size: 12px;
  padding: 6px 10px;
  border-radius: 999px;
  border: 1px solid rgba(245, 158, 11, 0.25);
  background: rgba(245, 158, 11, 0.08);
  color: #92400e;
}

.grid {
  margin-top: 14px;
  display: grid;
  grid-template-columns: 360px 1fr;
  gap: 14px;
  align-items: start;
}

.cart {
  position: sticky;
  top: 14px;
  border-radius: 18px;
  border: 1px solid #e6eef8;
  background: #fff;
  box-shadow: 0 10px 30px rgba(2, 6, 23, 0.05);
  overflow: hidden;
}
.cart-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 16px;
  background: linear-gradient(180deg, #ffffff, #f7fbff);
  border-bottom: 1px solid #eef2f7;
}
.cart-title {
  display: inline-flex;
  gap: 10px;
  align-items: center;
  font-weight: 1100;
}
.cart-actions {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.cart-badge {
  min-width: 28px;
  height: 28px;
  border-radius: 999px;
  display: grid;
  place-items: center;
  font-weight: 1100;
  background: rgba(2, 132, 199, 0.12);
  border: 1px solid rgba(2, 132, 199, 0.2);
  color: #075985;
}
.cart-body { padding: 14px 16px 16px; }

.cart-list {
  display: grid;
  gap: 10px;
  margin-bottom: 14px;
}
.cart-item {
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  background: #fff;
  padding: 10px 12px;
}
.ci-top {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: flex-start;
}
.ci-name {
  font-weight: 1100;
  line-height: 1.1;
}
.ci-sub {
  margin-top: 4px;
  font-size: 12px;
  color: #64748b;
  font-weight: 800;
}
.sep { margin: 0 6px; color: #cbd5e1; }

.ci-bottom {
  margin-top: 10px;
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: center;
}
.ci-sum { text-align: right; }
.ci-line { font-size: 12px; color: #64748b; font-weight: 900; }
.ci-total { font-weight: 1100; color: #047857; }

.cart-summary {
  border-top: 1px solid #eef2f7;
  padding-top: 10px;
}
.sum-row {
  display: flex;
  justify-content: space-between;
  font-weight: 1000;
  color: #334155;
  margin-top: 6px;
}
.sum-money { color: #047857; }

.empty-cart {
  display: flex;
  gap: 10px;
  align-items: center;
  color: #64748b;
  padding: 12px;
  border-radius: 14px;
  border: 1px dashed #cbd5e1;
  margin-bottom: 14px;
}
.ec-title { font-weight: 1100; color: #0f172a; }
.ec-sub { margin-top: 2px; font-weight: 800; font-size: 12px; }

.reqby {
  margin-top: 8px;
  display: grid;
  gap: 8px;
  margin-bottom: 12px;
}
.lbl {
  font-size: 12px;
  font-weight: 1000;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
.sel {
  height: 44px;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  padding: 10px 12px;
  font-weight: 900;
  outline: none;
}
.sel:focus {
  border-color: rgba(2, 132, 199, 0.45);
  box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.12);
}
.hint {
  font-size: 12px;
  font-weight: 900;
  color: #b45309;
}

.mini-btn {
  width: 34px;
  height: 34px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  display: grid;
  place-items: center;
}
.mini-btn.danger {
  border-color: rgba(239, 68, 68, 0.25);
  color: #b91c1c;
}
.mini-btn:disabled { opacity: 0.6; cursor: not-allowed; }

.products {
  border-radius: 18px;
  border: 1px solid #e6eef8;
  background: #fff;
  box-shadow: 0 10px 30px rgba(2, 6, 23, 0.05);
  overflow: hidden;
}
.phead {
  padding: 14px 16px;
  background: linear-gradient(180deg, #ffffff, #f7fbff);
  border-bottom: 1px solid #eef2f7;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}
.ptitle { font-weight: 1100; }
.psub {
  margin-top: 4px;
  font-size: 12px;
  font-weight: 800;
  color: #64748b;
}

.cards {
  padding: 14px;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.pcard {
  border-radius: 18px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  background: #fff;
  transition: transform 0.12s ease, box-shadow 0.12s ease;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.04);
}
.pcard:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 34px rgba(2, 6, 23, 0.08);
}
.pcard.is-low { border-color: rgba(245, 158, 11, 0.35); }
.pcard.is-none { opacity: 0.85; }

.pimg {
  position: relative;
  background: #fff;
  border-bottom: 1px solid #eef2f7;
}
.pimg img {
  width: 100%;
  height: 190px;
  object-fit: contain;
  background: #fff;
}
.tag {
  position: absolute;
  right: 10px;
  top: 10px;
  padding: 6px 10px;
  border-radius: 999px;
  font-weight: 1000;
  font-size: 12px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: 1px solid transparent;
}
.tag .tag-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: currentColor;
}
.tag.ok {
  background: rgba(16, 185, 129, 0.12);
  color: #047857;
  border-color: rgba(16, 185, 129, 0.22);
}
.tag.warn {
  background: rgba(245, 158, 11, 0.12);
  color: #92400e;
  border-color: rgba(245, 158, 11, 0.22);
}
.tag.bad {
  background: rgba(239, 68, 68, 0.12);
  color: #b91c1c;
  border-color: rgba(239, 68, 68, 0.22);
}

.pbody {
  padding: 12px;
  display: grid;
  gap: 10px;
}
.pname-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}
.pname {
  font-weight: 1100;
  line-height: 1.1;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.price {
  white-space: nowrap;
  font-weight: 1100;
  color: #047857;
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.18);
  padding: 6px 10px;
  border-radius: 999px;
}

.pinfo {
  border: 1px solid #eef2f7;
  border-radius: 14px;
  padding: 10px;
  background: #fbfdff;
}
.info-row {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  font-size: 12px;
  font-weight: 900;
  color: #334155;
}
.info-row + .info-row { margin-top: 6px; }
.k { color: #64748b; font-weight: 900; }
.v.unit { color: #075985; }

.qty-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}
.qty-lbl {
  font-weight: 1000;
  font-size: 12px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
.qty {
  display: inline-flex;
  align-items: center;
  border: 1px solid #e2e8f0;
  background: #fff;
  border-radius: 14px;
  overflow: hidden;
}
.qty input {
  width: 64px;
  height: 38px;
  border: none;
  text-align: center;
  font-weight: 1000;
  outline: none;
}
.qty-btn {
  width: 40px;
  height: 38px;
  border: none;
  background: #f8fafc;
  cursor: pointer;
  font-weight: 1100;
}
.qty-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.qty-sm input { width: 56px; height: 36px; }
.qty-sm .qty-btn { width: 36px; height: 36px; }

.pager {
  padding: 14px 16px 16px;
  border-top: 1px solid #eef2f7;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.page-ind { font-weight: 1000; color: #334155; }

.empty-products {
  padding: 40px 14px;
  display: grid;
  place-items: center;
  gap: 10px;
  text-align: center;
}
.empty-ico {
  width: 54px; height: 54px;
  border-radius: 18px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  display: grid;
  place-items: center;
  color: #64748b;
  font-size: 20px;
}
.empty-title { font-weight: 1100; }
.empty-sub { color: #64748b; font-weight: 900; font-size: 12px; }

.pcard.skel { overflow: hidden; }
.skel-img {
  height: 190px;
  background: linear-gradient(90deg, #f1f5f9, #ffffff, #f1f5f9);
  background-size: 200% 100%;
  animation: shimmer 1.2s linear infinite;
}
.skel-body { padding: 12px; display: grid; gap: 10px; }
.skel-line, .skel-box, .skel-btn {
  border-radius: 12px;
  background: linear-gradient(90deg, #f1f5f9, #ffffff, #f1f5f9);
  background-size: 200% 100%;
  animation: shimmer 1.2s linear infinite;
}
.skel-line { height: 12px; }
.skel-line.w80 { width: 80%; }
.skel-line.w70 { width: 70%; }
.skel-line.w60 { width: 60%; }
.skel-box { height: 46px; }
.skel-btn { height: 42px; border-radius: 14px; }
@keyframes shimmer { to { background-position: -200% 0; } }

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.6);
  backdrop-filter: blur(6px);
  display: grid;
  place-items: center;
  z-index: 2000;
  padding: 16px;
}
.modal-shell {
  width: min(860px, 98vw);
  background: #fff;
  border-radius: 18px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 30px 90px rgba(2, 6, 23, 0.25);
  overflow: hidden;
}
.mhead {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 16px;
  background: linear-gradient(180deg, #ffffff, #f7fbff);
  border-bottom: 1px solid #eef2f7;
}
.mtitle { display: flex; gap: 12px; align-items: center; }
.micon {
  width: 42px;
  height: 42px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  background: rgba(2, 132, 199, 0.12);
  border: 1px solid rgba(2, 132, 199, 0.2);
  color: #075985;
}
.mtt { font-weight: 1100; }
.mts { margin-top: 4px; font-weight: 800; font-size: 12px; color: #64748b; }
.mclose {
  width: 40px;
  height: 40px;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
}
.mbody { padding: 14px 16px; }
.mgrid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.field.full { grid-column: 1 / -1; }
.flbl {
  font-size: 12px;
  font-weight: 1000;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 6px;
  display: block;
}
.req { color: #ef4444; }
.fin {
  width: 100%;
  height: 44px;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  padding: 10px 12px;
  font-weight: 900;
  outline: none;
}
.fin:focus {
  border-color: rgba(2, 132, 199, 0.45);
  box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.12);
}
.money { display: grid; grid-template-columns: 44px 1fr; align-items: center; }
.peso {
  height: 44px;
  display: grid;
  place-items: center;
  border-radius: 14px 0 0 14px;
  border: 1px solid #e2e8f0;
  border-right: none;
  background: #f8fafc;
  font-weight: 1100;
}
.money .fin { border-radius: 0 14px 14px 0; }

.img-actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.filebtn {
  position: relative;
  overflow: hidden;
  border-radius: 14px;
  padding: 10px 14px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  font-weight: 1100;
  display: inline-flex;
  gap: 8px;
  align-items: center;
}
.filebtn input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.cambtn {
  border-radius: 14px;
  padding: 10px 14px;
  border: 1px solid rgba(2, 132, 199, 0.25);
  background: rgba(2, 132, 199, 0.08);
  color: #075985;
  cursor: pointer;
  font-weight: 1100;
  display: inline-flex;
  gap: 8px;
  align-items: center;
}
.ghostbtn {
  border-radius: 14px;
  padding: 10px 14px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  font-weight: 1100;
  display: inline-flex;
  gap: 8px;
  align-items: center;
}

.preview {
  margin-top: 12px;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  background: #fbfdff;
  padding: 12px;
  display: grid;
  place-items: center;
}
.preview img {
  max-width: 260px;
  max-height: 220px;
  width: auto;
  height: auto;
  object-fit: contain;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  background: #fff;
}

.camera {
  margin-top: 12px;
  border-radius: 16px;
  border: 1px solid rgba(2, 132, 199, 0.22);
  background: linear-gradient(180deg, #f7fbff, #fff);
  padding: 12px;
}
.camera-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}
.cam-title {
  font-weight: 1100;
  color: #075985;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.mmini {
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  background: #fff;
  padding: 8px 10px;
  font-weight: 1000;
  cursor: pointer;
}
.vid {
  width: 100%;
  max-height: 360px;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  background: #0b1220;
  object-fit: cover;
}
.camera-actions { margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap; }
.cam-error { margin-top: 10px; color: #b91c1c; font-weight: 1000; display: inline-flex; gap: 8px; align-items: center; }
.cam-note { margin-top: 8px; color: #64748b; font-weight: 900; font-size: 12px; }
.hidden-canvas { display: none; }

.mfoot {
  padding: 14px 16px 16px;
  border-top: 1px solid #eef2f7;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  flex-wrap: wrap;
}

.mobile-bar {
  position: fixed;
  left: 12px;
  right: 12px;
  bottom: 12px;
  z-index: 1500;
  background: #fff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 20px 60px rgba(2, 6, 23, 0.18);
  border-radius: 18px;
  padding: 10px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}
.mb-left { display: grid; gap: 2px; }
.mb-items { display: inline-flex; gap: 8px; align-items: center; font-weight: 1000; }
.mb-total { font-weight: 1100; color: #047857; }

@media (max-width: 1100px) {
  .cards { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .grid { grid-template-columns: 340px 1fr; }
}
@media (max-width: 900px) {
  .grid { grid-template-columns: 1fr; }
  .cart { position: relative; top: auto; }
  .cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 520px) {
  .inv-page { padding: 12px; }
  .cards { grid-template-columns: 1fr; }
  .search { min-width: 100%; }
  .mgrid { grid-template-columns: 1fr; }
  .preview img { max-width: 100%; }
}
</style>