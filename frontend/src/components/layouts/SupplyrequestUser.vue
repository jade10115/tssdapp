<template>
  <div class="page">
    <div class="container">
      <!-- Header -->
      <header class="topbar">
        <div class="brand">
          <div class="logos">
            <img src="/img/dole.png" alt="DOLE Logo" />
            <img src="/img/bagongphlogo.png" alt="Bagong Pilipinas Logo" />
          </div>

          <div class="title">
            <div class="title-top">
              <h2>Supply Requests</h2>
              <span class="gov-seal" aria-hidden="true">RIS</span>
            </div>
            <p class="subtitle">Manage requests, approvals, and issuance in one place.</p>
          </div>
        </div>

        <div class="header-actions">
          <div class="pill">
            <span class="dot" :class="requests.length ? 'ok' : 'muted'"></span>
            <span>{{ requests.length }} request{{ requests.length === 1 ? "" : "s" }}</span>
          </div>
        </div>
      </header>

      <!-- Controls -->
      <section class="controls">
        <!-- Search -->
        <div class="search">
          <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
            <path
              d="M10 18a8 8 0 1 1 6.32-3.1l4.39 4.39a1 1 0 0 1-1.42 1.42l-4.39-4.39A7.96 7.96 0 0 1 10 18Zm0-2a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z"
            />
          </svg>

          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by name or division…"
            @keydown.enter="searchData"
          />

          <button class="btn primary" @click="searchData">Search</button>
        </div>

        <!-- ✅ QUICK FILTER: All / Pending / Approved (backend stays Pending / Accepted) -->
        <div class="filter">
          <div class="filter-head">
            <span class="filter-title">Status Filter</span>
            <span class="filter-hint">Quick filter or tick multiple</span>
          </div>

          <div class="segmented" role="tablist" aria-label="Quick status filter">
            <button
              class="seg"
              :class="{ active: quickStatus === 'ALL' }"
              type="button"
              role="tab"
              :aria-selected="quickStatus === 'ALL'"
              @click="setQuickStatus('ALL')"
            >
              All <span class="seg-count">{{ filteredBaseCount }}</span>
            </button>

            <button
              class="seg"
              :class="{ active: quickStatus === 'PENDING' }"
              type="button"
              role="tab"
              :aria-selected="quickStatus === 'PENDING'"
              @click="setQuickStatus('PENDING')"
            >
              Pending <span class="seg-count">{{ pendingCount }}</span>
            </button>

            <button
              class="seg"
              :class="{ active: quickStatus === 'APPROVED' }"
              type="button"
              role="tab"
              :aria-selected="quickStatus === 'APPROVED'"
              @click="setQuickStatus('APPROVED')"
            >
              Approved <span class="seg-count">{{ approvedCount }}</span>
            </button>
          </div>

          <!-- ✅ MULTI FILTER (optional) -->
          <div class="filter-body" role="group" aria-label="Status filters (multi-select)">
            <label class="chk">
              <input type="checkbox" value="Pending" v-model="statusFilters" @change="syncQuickFromCheckbox()" />
              <span class="badge pending">Pending</span>
            </label>

            <label class="chk">
              <!-- backend uses "Accepted" for approved requests -->
              <input type="checkbox" value="Accepted" v-model="statusFilters" @change="syncQuickFromCheckbox()" />
              <span class="badge approved">Approved</span>
            </label>

            <button class="btn tiny ghost soft" type="button" @click="clearStatusOnly" :disabled="statusFilters.length === 0">
              Clear
            </button>
          </div>
        </div>

        <!-- Right controls -->
        <div class="right-controls">
          <div class="selectlike">
            <span class="label">Rows</span>
            <select v-model.number="itemsPerPage" class="native-select" aria-label="Rows per page">
              <option :value="5">5</option>
              <option :value="10">10</option>
              <option :value="15">15</option>
            </select>
          </div>

          <button class="btn ghost" @click="resetFilters" title="Reset search, filter & sorting">
            Reset
          </button>
        </div>
      </section>

      <!-- Active chips -->
      <section v-if="hasActiveFilters" class="chips" aria-label="Active filters">
        <span v-if="searchQuery.trim()" class="chipline">
          Keyword: <b>{{ searchQuery.trim() }}</b>
          <button class="x" type="button" @click="searchQuery = ''; searchData()">×</button>
        </span>

        <span v-for="st in statusFilters" :key="st" class="chipline">
          Status: <b>{{ st === 'Accepted' ? 'Approved' : st }}</b>
          <button class="x" type="button" @click="removeStatus(st)">×</button>
        </span>

        <button class="btn tiny ghost" type="button" @click="resetFilters">Clear all</button>
      </section>

      <!-- Table -->
      <section class="card">
        <div class="card-head">
          <div class="card-title">
            <strong>Requests List</strong>
            <span class="muted"
              >Showing {{ filteredRequests.length }} result{{ filteredRequests.length === 1 ? "" : "s" }}</span
            >
          </div>

          <div class="mini-legend" aria-label="Status legend">
            <span class="legend-item"><span class="dot-sm pending"></span> Pending</span>
            <span class="legend-item"><span class="dot-sm accepted"></span> Approved</span>
            <span class="legend-item"><span class="dot-sm rejected"></span> Rejected</span>
          </div>
        </div>

        <div class="table-wrapper" role="region" aria-label="Supply requests table">
          <table class="table">
            <thead>
              <tr>
                <th @click="sortBy('name')" :class="{ active: sortKey === 'name' }">
                  Requested By
                  <span v-if="sortKey === 'name'" class="sort-indicator">
                    {{ sortOrder === "asc" ? "↑" : "↓" }}
                  </span>
                </th>

                <th @click="sortBy('division')" :class="{ active: sortKey === 'division' }">
                  Division
                  <span v-if="sortKey === 'division'" class="sort-indicator">
                    {{ sortOrder === "asc" ? "↑" : "↓" }}
                  </span>
                </th>

                <th @click="sortBy('date_requested')" :class="{ active: sortKey === 'date_requested' }">
                  Date Requested
                  <span v-if="sortKey === 'date_requested'" class="sort-indicator">
                    {{ sortOrder === "asc" ? "↑" : "↓" }}
                  </span>
                </th>

                <th @click="sortBy('status')" :class="{ active: sortKey === 'status' }">
                  Status
                  <span v-if="sortKey === 'status'" class="sort-indicator">
                    {{ sortOrder === "asc" ? "↑" : "↓" }}
                  </span>
                </th>

                <th class="nowrap">RIS Control No.</th>
                <!-- <th class="actions-col">Action</th> -->
              </tr>
            </thead>

            <tbody>
              <tr v-if="loading">
                <td colspan="6" class="empty">
                  <div class="spinner" aria-hidden="true"></div>
                  Loading requests…
                </td>
              </tr>

              <tr v-else-if="paginatedRequests.length === 0">
                <td colspan="6" class="empty">
                  No results found. Try a different keyword or clear the filters.
                </td>
              </tr>

              <tr v-else v-for="(request, index) in paginatedRequests" :key="request.id">
                <td data-label="Requested By">
                  <div class="cell-main">
                    <span class="name">{{ request.name }}</span>
                    <span v-if="request.designation" class="muted">{{ request.designation }}</span>
                  </div>
                </td>

                <td data-label="Division">
                  <span class="tag">{{ request.division }}</span>
                </td>

                <td data-label="Date Requested">
                  <span class="nowrap">{{ request.date_requested }}</span>
                </td>

                <td data-label="Status">
                  <span :class="['status', String(request.status || '').toLowerCase()]">
                    {{ request.status === "Accepted" ? "Approved" : request.status }}
                  </span>
                </td>

                <td data-label="RIS Control No." class="ris-control-no">
                  {{ formatRISControlNo(request.id) }}
                </td>

                <!-- ✅ KEEP ACTION BUTTONS -->
                <!-- <td data-label="Action">
                  <div class="row-actions">
                    <button class="btn tiny info" @click="viewRequest(request.id)">View</button>
                    <button class="btn tiny success" @click="confirmAccept(index)">Accept</button>
                    <button class="btn tiny danger" @click="confirmReject(index)">Reject</button>
                  </div>
                </td> -->
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="pagination">
          <button class="btn ghost" :disabled="currentPage === 1" @click="currentPage--">‹ Prev</button>

          <div class="page-meta">
            <span class="muted">Page</span>
            <strong>{{ currentPage }}</strong>
            <span class="muted">of</span>
            <strong>{{ totalPages }}</strong>
          </div>

          <button class="btn ghost" :disabled="currentPage === totalPages" @click="currentPage++">Next ›</button>
        </div>
      </section>

      <!-- MODERN MODAL (unchanged behavior / backend calls kept) -->
      <div v-if="showModal" class="m-overlay" @click.self="closeModal" role="dialog" aria-modal="true">
        <div class="m-sheet" ref="modalRef">
          <div class="m-head">
            <div class="m-title">
              <h3>Request Details</h3>
              <p class="m-sub">
                RIS Control No.: <span class="mono">{{ formatRISControlNo(selectedRequest.id) }}</span>
              </p>
            </div>

            <button class="m-close" @click="closeModal" aria-label="Close modal">×</button>
          </div>

          <!-- Summary cards -->
          <div class="m-summary">
            <div class="sum-card">
              <div class="sum-row">
                <span class="k">Requested By</span>
                <span class="v">{{ selectedRequest.name }}</span>
              </div>
              <div class="sum-row">
                <span class="k">Division</span>
                <span class="v">{{ selectedRequest.division }}</span>
              </div>
              <div class="sum-row">
                <span class="k">Status</span>
                <span class="v">
                  <span class="chip" :class="`chip-${String(selectedRequest.status || '').toLowerCase()}`">
                    {{ selectedRequest.status === "Accepted" ? "Approved" : selectedRequest.status }}
                  </span>
                </span>
              </div>
              <div class="sum-row">
                <span class="k">Date Requested</span>
                <span class="v">{{ selectedRequest.date_requested }}</span>
              </div>
            </div>

            <div class="sum-card">
              <div class="sum-row">
                <span class="k">Items</span>
                <span class="v">{{ selectedRequest.items?.length || 0 }}</span>
              </div>
              <div class="sum-row">
                <span class="k">Total</span>
                <span class="v strong">₱{{ formatMoney(displayTotal) }}</span>
              </div>
              <div class="sum-row">
                <span class="k">RIS</span>
                <span class="v mono">{{ formatRISControlNo(selectedRequest.id) }}</span>
              </div>
              <div class="sum-row">
                <span class="k">Note</span>
                <span class="v muted">Final qty is editable below.</span>
              </div>
            </div>
          </div>

          <!-- Items -->
          <div class="m-section">
            <div class="m-sec-head">
              <h4>Requested Items</h4>
              <p class="muted">Approve/Reject per item and adjust final quantity.</p>
            </div>

            <div class="m-table">
              <div class="m-thead">
                <div>Item</div>
                <div class="center">Requested</div>
                <div class="center">Final Qty</div>
                <div class="center">Unit</div>
                <div class="center">Status</div>
                <div class="right">Action</div>
              </div>

              <div class="m-tbody">
                <div v-for="item in selectedRequest.items" :key="item.id" class="m-row">
                  <div class="itemcell">
                    <img
                      :src="item.image || '/img/no-image.png'"
                      class="thumb"
                      @click="openZoom(item.image || '/img/no-image.png')"
                      alt="Product image"
                    />
                    <div class="itemmeta">
                      <div class="itemname">{{ item.product_name }}</div>
                      <div v-if="item.price != null" class="muted">₱{{ formatMoney(item.price) }}</div>
                    </div>
                  </div>

                  <div class="center">
                    <span class="pill-lite">{{ item.quantity }}</span>
                  </div>

                  <div class="center">
                    <input
                      class="qty-input-modern"
                      type="number"
                      v-model.number="item.approved_qty"
                      min="0"
                      inputmode="numeric"
                    />
                  </div>

                  <div class="center">
                    <span class="pill-lite">{{ item.unit }}</span>
                  </div>

                  <div class="center">
                    <span class="chip chip-lite">{{ item.status }}</span>
                  </div>

                  <div class="right actions">
                    <button class="btn tiny success" @click="saveItemStatus({ ...item, status: 'Approved' })">
                      Accept
                    </button>
                    <button class="btn tiny danger" @click="saveItemStatus({ ...item, status: 'Rejected' })">
                      Reject
                    </button>
                  </div>
                </div>

                <div v-if="!selectedRequest.items || selectedRequest.items.length === 0" class="m-empty">
                  No items found.
                </div>
              </div>
            </div>
          </div>

          <!-- Signatories (kept as-is) -->
          <div class="m-section">
            <div class="m-sec-head">
              <h4>Signatories</h4>
              <p class="muted">
                Requested/Received show full name + position. Approved/Issued dropdown shows all employees as
                <b>Full Name — Position</b>.
              </p>
            </div>

            <div class="sig-grid-modern">
              <div class="sig-card">
                <div class="sig-k">Requested by</div>
                <div class="sig-v">
                  <div class="sig-name">{{ requesterFullName }}</div>
                  <div class="sig-pos">{{ requesterPosition }}</div>
                </div>
              </div>

              <!-- Approved by -->
              <div class="sig-card sig-rel">
                <div class="sig-k">Approved by</div>

                <button class="pick" type="button" @click="openDropdown('approved')">
                  <div class="pick-main">
                    <div class="pick-name">
                      {{
                        selectedApprovedObj
                          ? `${selectedApprovedObj.full_name} — ${selectedApprovedObj.position || '—'}`
                          : "Select employee…"
                      }}
                    </div>
                    <div class="pick-pos">Click to choose</div>
                  </div>
                  <div class="pick-chev">▾</div>
                </button>

                <div v-if="dropdownOpen && activeDropdown === 'approved'" class="dd">
                  <div class="dd-search">
                    <input v-model="employeeQuery" placeholder="Search name or position…" />
                  </div>

                  <div class="dd-list">
                    <div v-if="usersLoading" class="dd-empty">Loading employees…</div>

                    <template v-else>
                      <button
                        v-for="u in filteredEmployees"
                        :key="u.user_id"
                        type="button"
                        class="dd-item"
                        @click="pickEmployee(u)"
                      >
                        <div class="dd-name">{{ u.full_name }} — {{ u.position || "—" }}</div>
                        <div class="dd-pos">{{ u.position || "—" }}</div>
                      </button>

                      <div v-if="filteredEmployees.length === 0" class="dd-empty">No employees found.</div>
                    </template>
                  </div>
                </div>
              </div>

              <div class="sig-card">
                <div class="sig-k">RIS No.</div>
                <div class="sig-v">
                  <div class="sig-name mono">{{ formatRISControlNo(selectedRequest.id) }}</div>
                  <div class="sig-pos muted">Auto-generated</div>
                </div>
              </div>

              <!-- Issued by -->
              <div class="sig-card sig-rel">
                <div class="sig-k">Issued by</div>

                <button class="pick" type="button" @click="openDropdown('issued')">
                  <div class="pick-main">
                    <div class="pick-name">
                      {{
                        selectedIssuedObj
                          ? `${selectedIssuedObj.full_name} — ${selectedIssuedObj.position || '—'}`
                          : "Select employee…"
                      }}
                    </div>
                    <div class="pick-pos">Click to choose</div>
                  </div>
                  <div class="pick-chev">▾</div>
                </button>

                <div v-if="dropdownOpen && activeDropdown === 'issued'" class="dd">
                  <div class="dd-search">
                    <input v-model="employeeQuery" placeholder="Search name or position…" />
                  </div>

                  <div class="dd-list">
                    <div v-if="usersLoading" class="dd-empty">Loading employees…</div>

                    <template v-else>
                      <button
                        v-for="u in filteredEmployees"
                        :key="u.user_id"
                        type="button"
                        class="dd-item"
                        @click="pickEmployee(u)"
                      >
                        <div class="dd-name">{{ u.full_name }} — {{ u.position || "—" }}</div>
                        <div class="dd-pos">{{ u.position || "—" }}</div>
                      </button>

                      <div v-if="filteredEmployees.length === 0" class="dd-empty">No employees found.</div>
                    </template>
                  </div>
                </div>
              </div>

              <div class="sig-card">
                <div class="sig-k">Received by</div>
                <div class="sig-v">
                  <div class="sig-name">{{ requesterFullName }}</div>
                  <div class="sig-pos">{{ requesterPosition }}</div>
                </div>
              </div>
            </div>
          </div>

          <div class="m-foot">
            <button class="btn ghost" @click="closeModal">Close</button>
            <button class="btn primary" @click="printRIS(selectedRequest.id)">🖨️ Print RIS</button>
            <button class="btn primary outline" @click="exportRIS(selectedRequest.id)">Export RIS</button>
          </div>
        </div>
      </div>

      <!-- ZOOM MODAL -->
      <div v-if="zoomImage" class="zoom-overlay" @click.self="closeZoom">
        <div class="zoom-inner">
          <img :src="zoomImage" class="zoom-img" alt="Zoomed image" />
          <button class="zoom-close" @click="closeZoom" aria-label="Close zoom">×</button>
        </div>
      </div>

      <!-- backdrop to close dropdown -->
      <div v-if="dropdownOpen" class="dropdown-backdrop" @click="closeDropdown" aria-hidden="true"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onBeforeUnmount } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

import { inject } from "vue";

const API_BASE = inject("API_BASE");


const requests = ref([]);
const loading = ref(false);

/**
 * ✅ Status filter stays backend-ready:
 * - Pending
 * - Accepted   (shown as "Approved" in UI labels only)
 */
const statusFilters = ref([]); // array of exact backend statuses

const searchQuery = ref("");
const currentPage = ref(1);
const itemsPerPage = ref(5);

// Sorting
const sortKey = ref("name");
const sortOrder = ref("asc");

// Modal
const showModal = ref(false);
const selectedRequest = ref({ items: [] });
const zoomImage = ref(null);

// Users (signatories)
const users = ref([]);
const usersLoading = ref(false);
const selectedApproved = ref("");
const selectedIssued = ref("");

// Requested/Received
const requesterFullName = ref("");
const requesterPosition = ref("");

// refs
const modalRef = ref(null);

// ✅ quick status tab state
const quickStatus = ref("ALL"); // 'ALL' | 'PENDING' | 'APPROVED'

onMounted(async () => {
  await fetchRequests();
  await loadUsers();
});

/**
 * Normalize any employee row into:
 * { user_id, full_name, position }
 */
const normalizeEmployee = (u) => {
  const user_id = u.user_id ?? u.id ?? u.userId ?? u.user?.id ?? "";
  const full_name =
    (u.full_name ??
      u.name ??
      [u.first_name, u.middle_name, u.last_name, u.suffix]
        .filter(Boolean)
        .join(" ")
        .replace(/\s+/g, " ")
        .trim()) || "";

  const position =
    u.position?.position ??
    u.position ??
    u.designation ??
    u.position_name ??
    u.tbl_position?.position ??
    u.position_title ??
    "";

  return { user_id, full_name, position };
};

// LOAD ALL EMPLOYEES
const loadUsers = async () => {
  usersLoading.value = true;
  try {
    const res = await axios.get(`${API_BASE}/users/signatories`);
    const data = res.data;
    const list = Array.isArray(data) ? data : Array.isArray(data?.data) ? data.data : [];

    users.value = (list || [])
      .map(normalizeEmployee)
      .filter((x) => String(x.user_id).trim() !== "" && String(x.full_name).trim() !== "");
  } catch (e) {
    users.value = [];
  } finally {
    usersLoading.value = false;
  }
};

// LOAD REQUESTS LIST
const fetchRequests = async () => {
  loading.value = true;
  try {
    const res = await axios.get(`${API_BASE}/supply-requests`);
    requests.value = Array.isArray(res.data) ? res.data : [];
  } finally {
    loading.value = false;
  }
};

// RIS Control No.
const formatRISControlNo = (id) => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  return `${year}-${month}-${id}`;
};

// SORTING
const sortBy = (key) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
  } else {
    sortKey.value = key;
    sortOrder.value = "asc";
  }
};

// ✅ base filtered only for counts (search only)
const baseSearchFiltered = computed(() => {
  const q = searchQuery.value.toLowerCase().trim();
  return requests.value.filter((r) => {
    const name = String(r.name || "").toLowerCase();
    const division = String(r.division || "").toLowerCase();
    return !q || name.includes(q) || division.includes(q);
  });
});

const filteredBaseCount = computed(() => baseSearchFiltered.value.length);

const pendingCount = computed(
  () => baseSearchFiltered.value.filter((r) => String(r.status || "") === "Pending").length
);

const approvedCount = computed(
  () => baseSearchFiltered.value.filter((r) => String(r.status || "") === "Accepted").length
);

// ✅ SEARCH + STATUS FILTER + SORT
const filteredRequests = computed(() => {
  const q = searchQuery.value.toLowerCase().trim();

  let filtered = requests.value.filter((r) => {
    const name = String(r.name || "").toLowerCase();
    const division = String(r.division || "").toLowerCase();
    const matchesSearch = !q || name.includes(q) || division.includes(q);

    const st = String(r.status || "");
    const matchesStatus = statusFilters.value.length === 0 || statusFilters.value.includes(st);

    return matchesSearch && matchesStatus;
  });

  return filtered.sort((a, b) => {
    let valA = a[sortKey.value];
    let valB = b[sortKey.value];

    if (sortKey.value === "date_requested") {
      valA = new Date(valA);
      valB = new Date(valB);
    } else {
      valA = String(valA ?? "").toLowerCase();
      valB = String(valB ?? "").toLowerCase();
    }

    if (sortOrder.value === "asc") return valA > valB ? 1 : -1;
    return valA < valB ? 1 : -1;
  });
});

// PAGINATION
const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredRequests.value.length / itemsPerPage.value))
);

watch([itemsPerPage, filteredRequests], () => {
  currentPage.value = 1;
});

const paginatedRequests = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  return filteredRequests.value.slice(start, start + itemsPerPage.value);
});

const searchData = () => {
  currentPage.value = 1;
};

// ✅ quick status handlers (maps to backend exact values)
const setQuickStatus = (mode) => {
  quickStatus.value = mode;

  if (mode === "ALL") statusFilters.value = [];
  if (mode === "PENDING") statusFilters.value = ["Pending"];
  if (mode === "APPROVED") statusFilters.value = ["Accepted"];

  currentPage.value = 1;
};

const syncQuickFromCheckbox = () => {
  // if user multi-selects, quick tab should go "ALL" (neutral) unless exact single-match
  const s = [...statusFilters.value].sort().join(",");
  if (!s) quickStatus.value = "ALL";
  else if (s === "Pending") quickStatus.value = "PENDING";
  else if (s === "Accepted") quickStatus.value = "APPROVED";
  else quickStatus.value = "ALL";
};

const clearStatusOnly = () => {
  statusFilters.value = [];
  quickStatus.value = "ALL";
  currentPage.value = 1;
};

const removeStatus = (st) => {
  statusFilters.value = statusFilters.value.filter((x) => x !== st);
  syncQuickFromCheckbox();
  currentPage.value = 1;
};

const resetFilters = () => {
  searchQuery.value = "";
  statusFilters.value = [];
  quickStatus.value = "ALL";
  sortKey.value = "name";
  sortOrder.value = "asc";
  currentPage.value = 1;
};

const hasActiveFilters = computed(() => {
  return Boolean(searchQuery.value.trim()) || statusFilters.value.length > 0;
});

// VIEW REQUEST (LOAD ITEMS/PRODUCTS)
const viewRequest = async (id) => {
  const res = await axios.get(`${API_BASE}/supply-requests/${id}/items`);
  selectedRequest.value = res.data;

  requesterFullName.value = res.data.name;
  requesterPosition.value = res.data.designation;

  selectedApproved.value = res.data.approved_by_id ?? "";
  selectedIssued.value = res.data.issued_by_id ?? "";

  showModal.value = true;
  closeDropdown();
};

// SIGNATORY SAVE
const saveApprovedBy = async () => {
  await axios.put(`${API_BASE}/checkout/${selectedRequest.value.id}/approve-by`, {
    approved_by_id: selectedApproved.value,
  });

  Swal.fire({
    toast: true,
    position: "top-end",
    icon: "success",
    title: "Approved By updated successfully.",
    showConfirmButton: false,
    timer: 1400,
  });
};

const saveIssuedBy = async () => {
  await axios.put(`${API_BASE}/checkout/${selectedRequest.value.id}/issue-by`, {
    issued_by_id: selectedIssued.value,
  });

  Swal.fire({
    toast: true,
    position: "top-end",
    icon: "success",
    title: "Issued By updated successfully.",
    showConfirmButton: false,
    timer: 1400,
  });
};

// CLOSE MODAL
const closeModal = () => (showModal.value = false);

// IMAGE ZOOM
const openZoom = (img) => (zoomImage.value = img);
const closeZoom = () => (zoomImage.value = null);

// SAVE ITEM STATUS (Accept/Reject + final qty)
const saveItemStatus = async (item) => {
  try {
    await axios.put(`${API_BASE}/checkout-item/${item.id}/update`, {
      status: item.status,
      approved_qty: item.approved_qty,
    });

    const target = selectedRequest.value.items.find((i) => i.id === item.id);
    if (target) {
      target.status = item.status;
      target.approved_qty = item.approved_qty;
    }

    Swal.fire({
      toast: true,
      position: "top-end",
      icon: "success",
      title: `Item marked as ${item.status}`,
      showConfirmButton: false,
      timer: 1200,
    });
  } catch (error) {
    Swal.fire({
      toast: true,
      position: "top-end",
      icon: "error",
      title: "Something went wrong while updating the status.",
      showConfirmButton: false,
      timer: 2000,
    });
  }
};

// UPDATE MAIN REQUEST STATUS (Accept/Reject request)
const updateStatus = async (id, status) => {
  await axios.put(`${API_BASE}/supply-requests/${id}/status`, { status });

  const rq = requests.value.find((r) => r.id === id);
  if (rq) rq.status = status;

  Swal.fire({
    toast: true,
    position: "top-end",
    icon: status === "Accepted" ? "success" : "error",
    title: `Request ${status}`,
    showConfirmButton: false,
    timer: 1200,
  });
};

// CONFIRM ACCEPT/REJECT
const confirmAccept = (index) => {
  Swal.fire({
    title: "Accept request?",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "OK",
    cancelButtonText: "Cancel",
    zIndex: 99999,
  }).then((r) => {
    if (r.isConfirmed) {
      const id = paginatedRequests.value[index].id;
      updateStatus(id, "Accepted");
    }
  });
};

const confirmReject = (index) => {
  Swal.fire({
    title: "Reject request?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "OK",
    cancelButtonText: "Cancel",
    zIndex: 99999,
  }).then((r) => {
    if (r.isConfirmed) {
      const id = paginatedRequests.value[index].id;
      updateStatus(id, "Rejected");
    }
  });
};

// EXPORT XLSX
const exportRIS = async (id) => {
  const res = await axios({
    url: `${API_BASE}/export-ris/${id}`,
    method: "GET",
    responseType: "blob",
  });
  const link = document.createElement("a");
  link.href = URL.createObjectURL(new Blob([res.data]));
  link.download = `RIS_${formatRISControlNo(id)}.xlsx`;
  link.click();
};

// PRINT RIS
const printRIS = async (id) => {
  const res = await axios.get(`${API_BASE}/generate-ris/${id}`, { responseType: "blob" });
  const link = document.createElement("a");
  link.href = URL.createObjectURL(new Blob([res.data]));
  link.download = `RIS-${formatRISControlNo(id)}.xlsx`;
  link.click();
};

// TOTAL
const formatMoney = (v) => Number(v || 0).toFixed(2);
const displayTotal = computed(() =>
  (selectedRequest.value.items || []).reduce(
    (s, it) => s + (Number(it.quantity) || 0) * (Number(it.price) || 0),
    0
  )
);

/** Searchable Dropdown logic */
const dropdownOpen = ref(false);
const activeDropdown = ref(null); // 'approved' | 'issued' | null
const employeeQuery = ref("");

const openDropdown = (type) => {
  activeDropdown.value = type;
  dropdownOpen.value = true;
  employeeQuery.value = "";
};

const closeDropdown = () => {
  dropdownOpen.value = false;
  activeDropdown.value = null;
  employeeQuery.value = "";
};

const filteredEmployees = computed(() => {
  const q = employeeQuery.value.toLowerCase().trim();
  const list = Array.isArray(users.value) ? users.value : [];
  if (!q) return list;

  return list.filter((u) => {
    const name = String(u.full_name || "").toLowerCase();
    const pos = String(u.position || "").toLowerCase();
    return name.includes(q) || pos.includes(q);
  });
});

const selectedApprovedObj = computed(
  () => users.value.find((u) => String(u.user_id) === String(selectedApproved.value)) || null
);
const selectedIssuedObj = computed(
  () => users.value.find((u) => String(u.user_id) === String(selectedIssued.value)) || null
);

const pickEmployee = async (u) => {
  if (activeDropdown.value === "approved") {
    selectedApproved.value = u.user_id;
    await saveApprovedBy();
  } else if (activeDropdown.value === "issued") {
    selectedIssued.value = u.user_id;
    await saveIssuedBy();
  }
  closeDropdown();
};

// keyboard close
const onEsc = (e) => {
  if (e.key === "Escape") {
    if (dropdownOpen.value) closeDropdown();
    if (showModal.value) closeModal();
  }
};
onMounted(() => window.addEventListener("keydown", onEsc));
onBeforeUnmount(() => window.removeEventListener("keydown", onEsc));
</script>

<style scoped>
/* ===== Government-friendly look: calmer colors, strong readability ===== */
:root {
  --gov-blue: #0b4f8a;
  --gov-blue-2: #0a3f70;
  --paper: rgba(255, 255, 255, 0.92);
  --line: rgba(15, 23, 42, 0.10);
}

.page {
  min-height: 100vh;
  background:
    radial-gradient(1200px 600px at 20% 10%, rgba(11, 79, 138, 0.12), transparent 60%),
    radial-gradient(1000px 600px at 80% 0%, rgba(34, 197, 94, 0.08), transparent 55%),
    linear-gradient(180deg, #f7f9fc, #f1f5f9);
  padding: 18px;
  font-family: "Poppins", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
  color: #0f172a;
}

.container {
  max-width: 1120px;
  margin: 0 auto;
}

/* Header */
.topbar {
  display: flex;
  gap: 14px;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-radius: 16px;
  background: var(--paper);
  border: 1px solid var(--line);
  box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
}
.brand {
  display: flex;
  gap: 14px;
  align-items: center;
  min-width: 0;
}
.logos {
  display: flex;
  gap: 12px;
  align-items: center;
}
.logos img {
  width: 52px;
  height: 52px;
  object-fit: contain;
  border-radius: 12px;
  background: #fff;
  border: 1px solid var(--line);
  padding: 6px;
}
.title-top {
  display: flex;
  align-items: center;
  gap: 10px;
}
.gov-seal {
  font-size: 11px;
  font-weight: 900;
  color: var(--gov-blue);
  background: rgba(11, 79, 138, 0.10);
  border: 1px solid rgba(11, 79, 138, 0.20);
  padding: 4px 8px;
  border-radius: 999px;
  letter-spacing: 0.08em;
}
.title h2 {
  margin: 0;
  font-size: 20px;
  letter-spacing: -0.2px;
}
.subtitle {
  margin: 4px 0 0;
  color: #475569;
  font-size: 12.5px;
}

.header-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}
.pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.04);
  border: 1px solid rgba(15, 23, 42, 0.06);
  font-size: 12px;
  color: #334155;
}
.dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: #94a3b8;
}
.dot.ok {
  background: #22c55e;
}
.dot.muted {
  background: #cbd5e1;
}

/* Controls */
.controls {
  margin-top: 14px;
  display: grid;
  grid-template-columns: 1.2fr 0.9fr auto;
  gap: 12px;
  align-items: stretch;
}

/* Search */
.search {
  display: flex;
  gap: 10px;
  align-items: center;
  padding: 10px;
  border-radius: 14px;
  background: var(--paper);
  border: 1px solid var(--line);
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
}
.search .icon {
  width: 18px;
  height: 18px;
  fill: #64748b;
  flex: 0 0 auto;
}
.search input {
  width: 100%;
  border: none;
  outline: none;
  font-size: 14px;
  background: transparent;
  color: #0f172a;
}

/* Filter */
.filter {
  border-radius: 14px;
  background: var(--paper);
  border: 1px solid var(--line);
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
  padding: 10px 12px;
  display: grid;
  gap: 10px;
}
.filter-head {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 10px;
}
.filter-title {
  font-size: 12px;
  font-weight: 900;
  color: #0f172a;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}
.filter-hint {
  font-size: 12px;
  color: #64748b;
}

/* ✅ Quick segmented buttons */
.segmented {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
}
.seg {
  border: 1px solid rgba(15, 23, 42, 0.10);
  background: rgba(255, 255, 255, 0.92);
  border-radius: 12px;
  padding: 10px 10px;
  cursor: pointer;
  font-weight: 950;
  font-size: 13px;
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  transition: background 0.2s ease, border 0.2s ease, transform 0.05s ease;
}
.seg:active {
  transform: translateY(1px);
}
.seg.active {
  border-color: rgba(11, 79, 138, 0.30);
  box-shadow: 0 10px 22px rgba(11, 79, 138, 0.10);
  background: linear-gradient(180deg, rgba(11, 132, 198, 0.12), rgba(11, 79, 138, 0.06));
}
.seg-count {
  font-weight: 950;
  font-size: 12px;
  padding: 4px 8px;
  border-radius: 999px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(15, 23, 42, 0.04);
  color: #334155;
}

.filter-body {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
}
.chk {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  user-select: none;
}
.chk input {
  width: 16px;
  height: 16px;
  accent-color: var(--gov-blue);
}

.badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 900;
  border: 1px solid transparent;
}
.badge.pending {
  background: rgba(245, 158, 11, 0.18);
  color: #92400e;
  border-color: rgba(245, 158, 11, 0.25);
}
.badge.approved {
  background: rgba(34, 197, 94, 0.18);
  color: #166534;
  border-color: rgba(34, 197, 94, 0.25);
}

/* Right controls */
.right-controls {
  display: flex;
  gap: 10px;
  align-items: center;
  justify-content: flex-end;
}
.selectlike {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  border-radius: 14px;
  background: var(--paper);
  border: 1px solid var(--line);
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
}
.selectlike .label {
  font-size: 12px;
  color: #64748b;
}
.native-select {
  border: none;
  outline: none;
  background: transparent;
  font-weight: 700;
  color: #0f172a;
}

/* Active chips */
.chips {
  margin-top: 10px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  align-items: center;
}
.chipline {
  display: inline-flex;
  gap: 8px;
  align-items: center;
  padding: 8px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.92);
  border: 1px solid rgba(15, 23, 42, 0.10);
  box-shadow: 0 8px 16px rgba(15, 23, 42, 0.04);
  font-size: 12px;
  color: #334155;
}
.chipline b {
  color: #0f172a;
  font-weight: 950;
}
.chipline .x {
  width: 26px;
  height: 26px;
  border-radius: 999px;
  border: 1px solid rgba(15, 23, 42, 0.10);
  background: rgba(15, 23, 42, 0.04);
  cursor: pointer;
  font-weight: 950;
  line-height: 0;
}

/* Card + table */
.card {
  margin-top: 14px;
  border-radius: 16px;
  background: var(--paper);
  border: 1px solid var(--line);
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
  overflow: hidden;
}
.card-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 14px;
  border-bottom: 1px solid var(--line);
  background: rgba(255, 255, 255, 0.6);
}
.card-title {
  display: flex;
  gap: 10px;
  align-items: baseline;
  flex-wrap: wrap;
}
.mini-legend {
  display: flex;
  gap: 12px;
  align-items: center;
  flex-wrap: wrap;
}
.legend-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #475569;
  font-weight: 700;
}
.dot-sm {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  display: inline-block;
}
.dot-sm.pending {
  background: #f59e0b;
}
.dot-sm.accepted {
  background: #22c55e;
}
.dot-sm.rejected {
  background: #ef4444;
}

.table-wrapper {
  overflow-x: auto;
}
.table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}
.table thead th {
  position: sticky;
  top: 0;
  z-index: 1;
  background: linear-gradient(180deg, var(--gov-blue), var(--gov-blue-2));
  color: #fff;
  padding: 12px 14px;
  text-align: left;
  font-size: 12.5px;
  user-select: none;
  cursor: pointer;
  white-space: nowrap;
}
.table thead th.active {
  filter: brightness(1.03);
}
.table tbody td {
  padding: 12px 14px;
  border-top: 1px solid var(--line);
  vertical-align: middle;
  background: rgba(255, 255, 255, 0.65);
}
.table tbody tr:hover td {
  background: rgba(2, 6, 23, 0.03);
}
.actions-col {
  width: 220px;
}
.sort-indicator {
  margin-left: 6px;
  font-size: 12px;
  opacity: 0.9;
}

.cell-main {
  display: grid;
  gap: 2px;
}
.name {
  font-weight: 900;
  color: #0f172a;
  line-height: 1.2;
}
.muted {
  color: #64748b;
  font-size: 12px;
}
.nowrap {
  white-space: nowrap;
}

.tag {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 12px;
  color: #0f172a;
  background: rgba(15, 23, 42, 0.05);
  border: 1px solid rgba(15, 23, 42, 0.07);
}

.status {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  font-weight: 900;
  font-size: 12px;
  color: #fff;
}
.status.pending {
  background: #f59e0b;
}
.status.accepted {
  background: #22c55e;
}
.status.rejected {
  background: #ef4444;
}

.ris-control-no {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Courier New", monospace;
  font-weight: 900;
  color: #0b4f8a;
}

.row-actions {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

/* Buttons */
.btn {
  border: 1px solid transparent;
  border-radius: 12px;
  padding: 10px 12px;
  font-weight: 900;
  font-size: 13px;
  cursor: pointer;
  transition: transform 0.05s ease, box-shadow 0.2s ease, background 0.2s ease, border 0.2s ease;
  user-select: none;
}
.btn:active {
  transform: translateY(1px);
}
.btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
}

.btn.primary {
  background: linear-gradient(180deg, #0b84c6, #075f90);
  color: #fff;
  box-shadow: 0 8px 18px rgba(11, 132, 198, 0.22);
}
.btn.primary:hover {
  filter: brightness(1.03);
}

.btn.primary.outline {
  background: rgba(11, 132, 198, 0.08);
  border-color: rgba(11, 132, 198, 0.25);
  color: #075f90;
  box-shadow: none;
}

.btn.ghost {
  background: rgba(15, 23, 42, 0.04);
  border-color: rgba(15, 23, 42, 0.07);
  color: #0f172a;
}
.btn.ghost:hover {
  background: rgba(15, 23, 42, 0.06);
}
.btn.ghost.soft {
  padding: 7px 10px;
  border-radius: 999px;
}

.btn.tiny {
  padding: 7px 10px;
  border-radius: 10px;
  font-size: 12px;
}

.btn.info {
  background: rgba(23, 162, 184, 0.16);
  color: #0b7285;
  border-color: rgba(23, 162, 184, 0.25);
}
.btn.success {
  background: rgba(34, 197, 94, 0.16);
  color: #15803d;
  border-color: rgba(34, 197, 94, 0.25);
}
.btn.danger {
  background: rgba(239, 68, 68, 0.14);
  color: #b91c1c;
  border-color: rgba(239, 68, 68, 0.25);
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 14px;
  border-top: 1px solid var(--line);
  background: rgba(255, 255, 255, 0.7);
}
.page-meta {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Empty/loading */
.empty {
  text-align: center;
  padding: 18px;
  color: #64748b;
}
.spinner {
  width: 18px;
  height: 18px;
  border-radius: 999px;
  border: 2px solid rgba(15, 23, 42, 0.18);
  border-top-color: rgba(11, 132, 198, 0.9);
  display: inline-block;
  margin-right: 10px;
  animation: spin 0.7s linear infinite;
  vertical-align: -3px;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ===== Modal + rest (kept from your original) ===== */
.m-overlay {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.65);
  display: grid;
  place-items: center;
  z-index: 9999;
  padding: 16px;
}
.m-sheet {
  width: min(1040px, 100%);
  max-height: 90vh;
  overflow: auto;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid rgba(15, 23, 42, 0.12);
  box-shadow: 0 18px 60px rgba(2, 6, 23, 0.38);
  padding: 16px;
}
.m-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
}
.m-title h3 {
  margin: 0;
  font-size: 18px;
  letter-spacing: -0.2px;
}
.m-sub {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 12.5px;
}
.m-close {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  background: rgba(15, 23, 42, 0.04);
  cursor: pointer;
  font-size: 20px;
  line-height: 0;
}

/* summary */
.m-summary {
  margin-top: 14px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.sum-card {
  border-radius: 16px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.78);
  padding: 12px;
}
.sum-row {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 10px;
  padding: 8px 0;
  border-bottom: 1px dashed rgba(15, 23, 42, 0.1);
}
.sum-row:last-child {
  border-bottom: none;
}
.k {
  color: #64748b;
  font-size: 12px;
}
.v {
  color: #0f172a;
  font-weight: 650;
}
.strong {
  font-weight: 900;
}
.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Courier New", monospace;
  font-weight: 900;
  color: #075f90;
}

/* section */
.m-section {
  margin-top: 14px;
}
.m-sec-head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 10px;
}
.m-sec-head h4 {
  margin: 0;
  font-size: 14px;
}
.m-sec-head p {
  margin: 0;
}

/* modern table */
.m-table {
  border-radius: 16px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.82);
  overflow: hidden;
}
.m-thead {
  display: grid;
  grid-template-columns: 2.2fr 0.7fr 0.9fr 0.7fr 0.8fr 1.2fr;
  gap: 10px;
  padding: 12px;
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.86), rgba(15, 23, 42, 0.72));
  color: #fff;
  font-size: 12.5px;
  font-weight: 800;
}
.m-tbody {
  display: grid;
}
.m-row {
  display: grid;
  grid-template-columns: 2.2fr 0.7fr 0.9fr 0.7fr 0.8fr 1.2fr;
  gap: 10px;
  padding: 12px;
  border-top: 1px solid rgba(15, 23, 42, 0.08);
  align-items: center;
}
.m-row:hover {
  background: rgba(15, 23, 42, 0.02);
}
.center {
  display: flex;
  justify-content: center;
}
.right {
  display: flex;
  justify-content: flex-end;
}
.actions {
  gap: 8px;
  flex-wrap: wrap;
}

.itemcell {
  display: flex;
  gap: 10px;
  align-items: center;
  min-width: 0;
}
.thumb {
  width: 46px;
  height: 46px;
  border-radius: 14px;
  border: 1px solid rgba(15, 23, 42, 0.1);
  object-fit: cover;
  cursor: zoom-in;
  background: rgba(15, 23, 42, 0.02);
}
.itemmeta {
  display: grid;
  gap: 2px;
  min-width: 0;
}
.itemname {
  font-weight: 900;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.pill-lite {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 12px;
  background: rgba(15, 23, 42, 0.05);
  border: 1px solid rgba(15, 23, 42, 0.07);
  font-weight: 800;
}
.qty-input-modern {
  width: 96px;
  padding: 9px 10px;
  border-radius: 12px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  outline: none;
  text-align: center;
  background: rgba(255, 255, 255, 0.95);
}
.qty-input-modern:focus {
  border-color: rgba(11, 132, 198, 0.55);
  box-shadow: 0 0 0 4px rgba(11, 132, 198, 0.12);
}
.m-empty {
  padding: 14px;
  text-align: center;
  color: #64748b;
}

/* chips */
.chip {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  font-weight: 900;
  font-size: 12px;
  color: #fff;
}
.chip-pending {
  background: #f59e0b;
}
.chip-accepted,
.chip-approved {
  background: #22c55e;
}
.chip-rejected {
  background: #ef4444;
}
.chip-lite {
  background: #64748b;
}

/* signatories modern */
.sig-grid-modern {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 10px;
}
.sig-card {
  border-radius: 16px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.82);
  padding: 12px;
  min-width: 0;
}
.sig-k {
  font-size: 12px;
  color: #64748b;
  margin-bottom: 8px;
}
.sig-v {
  display: grid;
  gap: 3px;
}
.sig-name {
  font-weight: 950;
  line-height: 1.15;
  overflow: hidden;
  text-overflow: ellipsis;
}
.sig-pos {
  font-size: 12px;
  color: #64748b;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* employee picker */
.sig-rel {
  position: relative;
}
.pick {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  border-radius: 14px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  background: rgba(255, 255, 255, 0.96);
  padding: 10px 12px;
  cursor: pointer;
  text-align: left;
}
.pick-main {
  display: grid;
  gap: 2px;
  min-width: 0;
}
.pick-name {
  font-weight: 900;
  font-size: 13px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.pick-pos {
  font-size: 12px;
  color: #64748b;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.pick-chev {
  margin-left: auto;
  font-weight: 900;
  color: #64748b;
}

/* dropdown */
.dd {
  position: absolute;
  z-index: 100;
  width: min(520px, calc(100vw - 48px));
  margin-top: 10px;
  border-radius: 14px;
  border: 1px solid rgba(15, 23, 42, 0.1);
  background: rgba(255, 255, 255, 0.99);
  box-shadow: 0 18px 45px rgba(2, 6, 23, 0.18);
  overflow: hidden;
}
.dd-search {
  padding: 10px;
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
}
.dd-search input {
  width: 100%;
  border: 1px solid rgba(15, 23, 42, 0.12);
  border-radius: 12px;
  padding: 10px 12px;
  outline: none;
}
.dd-search input:focus {
  border-color: rgba(11, 132, 198, 0.55);
  box-shadow: 0 0 0 4px rgba(11, 132, 198, 0.12);
}
.dd-list {
  max-height: 260px;
  overflow: auto;
  padding: 6px;
}
.dd-item {
  width: 100%;
  text-align: left;
  border: 1px solid transparent;
  background: transparent;
  border-radius: 12px;
  padding: 10px;
  cursor: pointer;
}
.dd-item:hover {
  background: rgba(11, 132, 198, 0.08);
  border-color: rgba(11, 132, 198, 0.18);
}
.dd-name {
  font-weight: 900;
  font-size: 13px;
}
.dd-pos {
  font-size: 12px;
  color: #64748b;
  margin-top: 2px;
}
.dd-empty {
  padding: 12px;
  text-align: center;
  color: #64748b;
}

/* footer */
.m-foot {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding-top: 14px;
}

/* Backdrop for dropdown */
.dropdown-backdrop {
  position: fixed;
  inset: 0;
  z-index: 9998;
  background: transparent;
}

/* Zoom */
.zoom-overlay {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 20000;
  padding: 16px;
}
.zoom-inner {
  position: relative;
}
.zoom-img {
  max-width: min(920px, 90vw);
  max-height: 80vh;
  border-radius: 16px;
  box-shadow: 0 22px 60px rgba(0, 0, 0, 0.55);
}
.zoom-close {
  position: absolute;
  top: -12px;
  right: -12px;
  width: 40px;
  height: 40px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.35);
  background: rgba(255, 255, 255, 0.95);
  cursor: pointer;
  font-size: 22px;
}

/* SweetAlert layering */
.swal2-container {
  z-index: 40000 !important;
}

/* Responsive */
@media (max-width: 980px) {
  .controls {
    grid-template-columns: 1fr;
  }
  .right-controls {
    justify-content: space-between;
  }
  .m-summary {
    grid-template-columns: 1fr;
  }
  .sig-grid-modern {
    grid-template-columns: 1fr 1fr;
  }
  .m-thead,
  .m-row {
    grid-template-columns: 2fr 0.8fr 1fr 0.8fr 1fr;
  }
  .m-thead > div:nth-child(6),
  .m-row > div:nth-child(6) {
    display: none;
  }
}

@media (max-width: 860px) {
  .table thead {
    display: none;
  }
  .table tbody tr {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
    padding: 10px;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
  }
  .table tbody td {
    border-top: none;
    padding: 8px 10px;
    display: flex;
    justify-content: space-between;
    gap: 10px;
    background: rgba(255, 255, 255, 0.75);
    border-radius: 12px;
    border: 1px solid rgba(15, 23, 42, 0.07);
  }
  .table tbody td::before {
    content: attr(data-label);
    color: #64748b;
    font-size: 12px;
    font-weight: 800;
  }
  .row-actions {
    justify-content: flex-end;
  }

  .m-thead {
    display: none;
  }
  .m-row {
    grid-template-columns: 1fr;
    gap: 10px;
    align-items: stretch;
  }
  .center,
  .right {
    justify-content: space-between;
  }
}

@media (max-width: 520px) {
  .logos img {
    width: 44px;
    height: 44px;
  }
  .sig-grid-modern {
    grid-template-columns: 1fr;
  }
  .sum-row {
    grid-template-columns: 110px 1fr;
  }
  .segmented {
    grid-template-columns: 1fr;
  }
}
</style>
