<template>
  <div class="main-content">
    <div class="page-header">
      <div>
        <h2>ADL Breakdown</h2>
        <p class="sub">ADL Detail ID: <b>{{ adlDetailId || "—" }}</b></p>
      </div>

      <div class="actions">
        <button class="btn" @click="goBack">Back</button>
        <button
          class="btn btn-primary"
          :disabled="!adlDetailId || loadingDetail || adlBalance <= 0"
          @click="openAddModal"
          title="Disabled when budget is fully allocated"
        >
          + Add LGU
        </button>
      </div>
    </div>

    <div class="cards">
      <div class="card glass">
        <div class="card-title">Rows</div>
        <div class="card-value">{{ rows.length }}</div>
      </div>
      <div class="card glass">
        <div class="card-title">Total Beneficiaries</div>
        <div class="card-value">{{ formatNumber(totalBeneficiaries) }}</div>
      </div>
      <div class="card glass">
        <div class="card-title">Allocated Amount</div>
        <div class="card-value">{{ formatMoney(allocatedAmount) }}</div>
      </div>
      <div class="card glass">
        <div class="card-title">Balance</div>
        <div class="card-value">{{ formatMoney(adlBalance) }}</div>
      </div>
      <div class="card glass highlight">
        <div class="card-title">Utilization Rate</div>
        <div class="card-value">{{ utilizationRate }}%</div>
        <div class="card-sub">Spent: {{ formatMoney(spentAmount) }}</div>
      </div>
    </div>

    <div class="table-toolbar glass">
      <div class="toolbar-left">
        <input v-model="searchQuery" type="text" class="search-input" placeholder="Search LGU / Barangay..." />
        <select v-model="statusFilter" class="filter-select">
          <option value="all">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="received">Received</option>
        </select>
      </div>
      <div class="toolbar-right">
        <label>Rows per page:</label>
        <select v-model="itemsPerPage" class="filter-select mini">
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
        </select>
      </div>
    </div>

    <input ref="excelInputRef" type="file" class="hidden-file" accept=".xlsx,.xls" @change="onExcelChosen" />

    <div class="table-section glass">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th class="sticky-col">
                <div class="th-content" @click="toggleSort('lgu')">
                  LGU / Barangay <span class="sort-icon">{{ sortIcon('lgu') }}</span>
                </div>
              </th>
              <th class="numeric">
                <div class="th-content right" @click="toggleSort('beneficiaries')">
                  Beneficiaries <span class="sort-icon">{{ sortIcon('beneficiaries') }}</span>
                </div>
              </th>
              <th class="numeric">
                <div class="th-content right" @click="toggleSort('amount')">
                  Amount <span class="sort-icon">{{ sortIcon('amount') }}</span>
                </div>
              </th>
              <th>
                <div class="th-content" @click="toggleSort('status')">
                  Status <span class="sort-icon">{{ sortIcon('status') }}</span>
                </div>
              </th>
              <th class="actions-head">Actions</th>
            </tr>
          </thead>

          <tbody>
            <template v-for="row in paginatedRows" :key="row.id">
              <tr class="main-tr">
                <td class="sticky-col" data-label="LGU / Barangay">
                  <div class="lgu-cell">
                    <button class="expand-btn" @click="toggleRowExpand(row.id)">
                      {{ expandedRows.has(row.id) ? '▼' : '▶' }}
                    </button>
                    <div class="lgu-badge">{{ (row.lgu || "L").slice(0, 1).toUpperCase() }}</div>
                    <div class="lgu-meta">
                      <div class="lgu-name">{{ row.lgu || "—" }}</div>
                      <div class="lgu-sub">Breakdown ID: {{ row.id }}</div>
                    </div>
                  </div>
                </td>

                <td class="numeric" data-label="Beneficiaries">
                  <span class="num-pill">{{ formatNumber(row.beneficiaries || 0) }}</span>
                </td>

                <td class="numeric" data-label="Amount">
                  <span class="money-pill">{{ formatMoney(row.amount || 0) }}</span>
                </td>

                <td data-label="Status">
                  <span :class="['pill', normalizeStatus(row.status) === 'received' ? 'ok' : 'pending']">
                    {{ normalizeStatus(row.status) === "received" ? "Received" : "Pending" }}
                  </span>
                </td>

                <td class="actions-cell" data-label="Actions">
                  <button class="icon-btn" @click="openEditModal(row)" title="Edit">✎</button>
                  <button class="icon-btn danger" @click="removeRow(row)" title="Delete">🗑</button>
                  <button
                    class="icon-btn okbtn"
                    :disabled="normalizeStatus(row.status) === 'received'"
                    @click="markAsReceived(row)"
                    title="Mark as received"
                  >
                    ✓
                  </button>
                  <button class="icon-btn uploadbtn" @click="openBenModal(row)" title="Beneficiaries Info">
                    👩‍👩‍👧‍👧
                  </button>
                  <button class="icon-btn" :disabled="excelBusyRowId === row.id" @click="triggerExcel(row)" title="Upload Excel">
                    ⬆
                  </button>
                </td>
              </tr>

              <tr v-if="expandedRows.has(row.id)" class="expanded-tr">
                <td colspan="5" class="expanded-td">
                  <div class="expanded-content">
                    <div class="exp-group">
                      <div class="exp-label">OSH Date</div>
                      <div class="exp-val date-pill">{{ formatDate(row.osh_date) }}</div>
                    </div>
                    <div class="exp-group">
                      <div class="exp-label">Payout Date</div>
                      <div class="exp-val date-pill">{{ formatDate(row.payout_date) }}</div>
                    </div>
                    <div class="exp-divider"></div>
                    <div class="exp-group">
                      <div class="exp-label">4Ps</div>
                      <div class="exp-val mini-pill">{{ formatNumber(row.four_ps || 0) }}</div>
                    </div>
                    <div class="exp-group">
                      <div class="exp-label">Senior Citizens</div>
                      <div class="exp-val mini-pill">{{ formatNumber(row.seniors || 0) }}</div>
                    </div>
                    <div class="exp-group">
                      <div class="exp-label">PWD</div>
                      <div class="exp-val mini-pill">{{ formatNumber(row.pwd || 0) }}</div>
                    </div>
                    <div class="exp-group">
                      <div class="exp-label">Females</div>
                      <div class="exp-val mini-pill">{{ formatNumber(row.female || 0) }}</div>
                    </div>
                  </div>
                </td>
              </tr>
            </template>

            <tr v-if="processedRows.length === 0 && !loadingRows && !loadingDetail">
              <td colspan="5" class="empty text-center">No records found matching your criteria.</td>
            </tr>
            <tr v-if="loadingRows || loadingDetail">
              <td colspan="5" class="empty text-center">Loading data...</td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div class="pagination-footer">
        <div class="page-info">
          Showing <b>{{ paginationStart }}</b> to <b>{{ paginationEnd }}</b> of <b>{{ processedRows.length }}</b> entries
        </div>
        <div class="page-controls">
          <button class="page-btn" :disabled="currentPage === 1" @click="currentPage--">Prev</button>
          <span class="page-current">{{ currentPage }} / {{ totalPages || 1 }}</span>
          <button class="page-btn" :disabled="currentPage >= totalPages" @click="currentPage++">Next</button>
        </div>
      </div>
    </div>

    <div v-if="crudModal.open" class="gov-overlay" @click.self="closeCrudModal">
      <div class="gov-modal" role="dialog" aria-modal="true">
        <div class="gov-topbar">
          <div class="gov-seal" aria-hidden="true">PH</div>
          <div class="gov-head">
            <div class="gov-title">{{ crudModal.mode === "add" ? "Add LGU Breakdown" : "Edit LGU Breakdown" }}</div>
            <div class="gov-sub">
              ADL Detail ID: <b>{{ adlDetailId || "—" }}</b>
              <span class="gov-chip" v-if="crudModal.mode === 'edit'">Breakdown ID: {{ crudModal.rowId }}</span>
            </div>
          </div>
          <button class="gov-x" @click="closeCrudModal" aria-label="Close">✕</button>
        </div>

        <div class="gov-body">
          <div class="gov-grid">
            <div class="field">
              <label>LGU / Barangay <span class="req">*</span></label>
              <input v-model="crudForm.lgu" class="gov-input" placeholder="e.g. Brgy. San Roque" />
            </div>
            <div class="field">
              <label>Beneficiaries</label>
              <input v-model="crudForm.beneficiaries" type="number" min="0" class="gov-input" />
            </div>
            <div class="field">
              <label>Amount (PHP)</label>
              <input v-model="crudForm.amount" inputmode="decimal" class="gov-input" placeholder="0.00" />
              <div class="hint">Balance will decrease based on this amount.</div>
            </div>
            <div class="field">
              <label>Status</label>
              <select v-model="crudForm.status" class="gov-select">
                <option value="pending">Pending</option>
                <option value="received">Received</option>
              </select>
            </div>
            <div class="field">
              <label>OSH Date</label>
              <input v-model="crudForm.osh_date" type="date" class="gov-input" />
            </div>
            <div class="field">
              <label>Payout Date</label>
              <input v-model="crudForm.payout_date" type="date" class="gov-input" />
            </div>
          </div>

          <div v-if="crudError" class="gov-alert" role="alert">
            <b>Validation:</b> {{ crudError }}
          </div>

          <div class="gov-summary">
            <div class="gov-summary-card">
              <div class="k">Available Balance</div>
              <div class="v">{{ formatMoney(modalAvailableBalance) }}</div>
            </div>
            <div class="gov-summary-card">
              <div class="k">Balance After Save</div>
              <div class="v">{{ formatMoney(balanceAfterSavePreview) }}</div>
            </div>
          </div>
        </div>

        <div class="gov-footer">
          <button class="btn" @click="closeCrudModal" :disabled="crudModal.saving">Cancel</button>
          <button class="btn btn-primary" @click="saveCrud" :disabled="crudModal.saving || !adlDetailId">
            <span v-if="!crudModal.saving">{{ crudModal.mode === "add" ? "Save Record" : "Update Record" }}</span>
            <span v-else>Saving…</span>
          </button>
        </div>
      </div>
    </div>

    <div v-if="benModal.open" class="ben-overlay" @click.self="closeBenModal">
      <div class="ben-modal" role="dialog" aria-modal="true">
        <div class="ben-header">
          <div class="ben-title-wrap">
            <div class="ben-title">Female Beneficiaries</div>
            <div class="ben-sub">
              LGU: <b>{{ benModal.lgu || "—" }}</b>
              <span class="ben-chip">Breakdown ID: {{ benModal.breakdownId }}</span>
              <span class="ben-chip primary">Female: {{ benModal.totalFemale }}</span>
              <span class="ben-chip">All: {{ benModal.totalAll }}</span>
            </div>
          </div>
          <button class="ben-close" @click="closeBenModal" aria-label="Close">✕</button>
        </div>

        <div v-if="benModal.uploading" class="ben-progress-wrap" aria-live="polite">
          <div class="ben-progress-top">
            <div class="ben-progress-label">{{ benModal.progressText }}</div>
            <div class="ben-progress-pct">{{ benModal.progress }}%</div>
          </div>
          <div class="ben-progress">
            <div class="ben-progress-fill" :style="{ width: benModal.progress + '%' }"></div>
          </div>
        </div>

        <div class="ben-toolbar">
          <div class="ben-left">
            <input v-model="benModal.search" class="ben-search" placeholder="Search female name…" />
            <label class="toggle">
              <input type="checkbox" v-model="benModal.showAll" @change="reloadBenList" />
              <span>Show All (not only female)</span>
            </label>
          </div>
          <div class="ben-right">
            <input ref="benExcelRef" type="file" class="hidden-file" accept=".xlsx,.xls" @change="onBenExcelChosen" />
            <button class="btn btn-primary" :disabled="benModal.uploading" @click="triggerBenExcel">⬆ Upload</button>
            <button class="btn" :disabled="benModal.loading || benModal.uploading" @click="reloadBenList">⟳ Refresh</button>
          </div>
        </div>

        <div class="ben-body">
          <div v-if="benModal.loading" class="ben-empty">Loading…</div>
          <div v-else class="ben-table-wrap">
            <table class="ben-table">
              <thead>
                <tr>
                  <th style="width: 56px;">#</th>
                  <th>Full Name</th>
                  <th style="width: 320px;">Tags</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="filteredBeneficiaries.length === 0">
                  <td colspan="3" class="ben-empty">No records found. Upload Excel to populate.</td>
                </tr>
                <tr v-for="(b, i) in filteredBeneficiaries" :key="b.id || `${b.full_name}-${i}`">
                  <td class="mono">{{ i + 1 }}</td>
                  <td class="name-cell">
                    <div class="avatar">{{ (b.full_name || "B").slice(0, 1).toUpperCase() }}</div>
                    <div class="name-meta">
                      <div class="name-main">{{ b.full_name }}</div>
                      <div class="name-sub">ID: {{ b.id }}</div>
                    </div>
                  </td>
                  <td>
                    <div class="tag-wrap">
                      <span v-for="(tag, tIndex) in tagsFromFlags(b)" :key="tIndex" class="tag" :class="tagClass(tag)">
                        {{ tag }}
                      </span>
                      <span v-if="tagsFromFlags(b).length === 0" class="tag muted">—</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="ben-footer">
          <div class="gov-note">
            <b>Note:</b> Upload replaces current list. Tags come from Excel flags (Female / 4Ps / PWD / Senior).
          </div>
          <button class="btn danger" @click="closeBenModal">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, watch } from "vue";
import axios from "axios";
import { useRoute, useRouter } from "vue-router";
import Swal from "sweetalert2";

import { inject } from "vue";

const API_BASE = inject("API_BASE");

axios.defaults.withCredentials = true;
axios.defaults.headers.common["Accept"] = "application/json";

const route = useRoute();
const router = useRouter();

const adlDetailId = computed(() => Number(route.query.adl_detail_id || route.query.adl_id || 0) || null);

const rows = ref([]);
const adlDetail = ref(null);
const loadingRows = ref(false);
const loadingDetail = ref(false);

const excelInputRef = ref(null);
const selectedRowForExcel = ref(null);
const excelBusyRowId = ref(null);

// UI States
const searchQuery = ref("");
const statusFilter = ref("all");
const sortKey = ref("lgu");
const sortAsc = ref(true);
const currentPage = ref(1);
const itemsPerPage = ref(10);
const expandedRows = ref(new Set());

// Formatters
const formatMoney = (value) => new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(Number(value || 0));
const formatNumber = (n) => (Number(n || 0)).toLocaleString();
const normalizeStatus = (s) => String(s || "").trim().toLowerCase() || "pending";
const formatDate = (v) => {
  if (!v) return "—";
  const d = new Date(String(v).slice(0, 10));
  return isNaN(d.getTime()) ? "—" : d.toLocaleDateString("en-PH", { year: "numeric", month: "short", day: "2-digit" });
};

// Computeds for Cards
const allocatedAmount = computed(() => Number(adlDetail.value?.amount || 0));
const spentAmount = computed(() => rows.value.filter(r => normalizeStatus(r.status) === "received").reduce((sum, r) => sum + Number(r.amount || 0), 0));
const adlBalance = computed(() => Math.max(0, allocatedAmount.value - spentAmount.value));
const utilizationRate = computed(() => {
  if (allocatedAmount.value <= 0) return 0;
  if (adlBalance.value <= 0) return 100;
  return Math.max(0, Math.min(100, Math.round((spentAmount.value / allocatedAmount.value) * 100)));
});
const totalBeneficiaries = computed(() => rows.value.reduce((sum, r) => sum + Number(r.beneficiaries || 0), 0));

// Processed Data (Search, Filter, Sort)
const processedRows = computed(() => {
  let result = [...rows.value];

  // Filter Search
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(r => (r.lgu || "").toLowerCase().includes(q));
  }
  // Filter Status
  if (statusFilter.value !== "all") {
    result = result.filter(r => normalizeStatus(r.status) === statusFilter.value);
  }

  // Sort
  result.sort((a, b) => {
    let valA = a[sortKey.value] || "";
    let valB = b[sortKey.value] || "";

    if (sortKey.value === 'amount' || sortKey.value === 'beneficiaries') {
      valA = Number(valA);
      valB = Number(valB);
    } else {
      valA = String(valA).toLowerCase();
      valB = String(valB).toLowerCase();
    }

    if (valA < valB) return sortAsc.value ? -1 : 1;
    if (valA > valB) return sortAsc.value ? 1 : -1;
    return 0;
  });

  return result;
});

// Pagination Computeds
const totalPages = computed(() => Math.ceil(processedRows.value.length / itemsPerPage.value));
const paginatedRows = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  return processedRows.value.slice(start, start + itemsPerPage.value);
});
const paginationStart = computed(() => processedRows.value.length === 0 ? 0 : (currentPage.value - 1) * itemsPerPage.value + 1);
const paginationEnd = computed(() => Math.min(currentPage.value * itemsPerPage.value, processedRows.value.length));

watch([searchQuery, statusFilter, itemsPerPage], () => { currentPage.value = 1; });

// Toggles
const toggleSort = (key) => {
  if (sortKey.value === key) {
    sortAsc.value = !sortAsc.value;
  } else {
    sortKey.value = key;
    sortAsc.value = true;
  }
};
const sortIcon = (key) => sortKey.value === key ? (sortAsc.value ? "▲" : "▼") : "↕";

const toggleRowExpand = (id) => {
  const newSet = new Set(expandedRows.value);
  newSet.has(id) ? newSet.delete(id) : newSet.add(id);
  expandedRows.value = newSet;
};

// API Fetching
const fetchDetail = async () => {
  if (!adlDetailId.value) return;
  loadingDetail.value = true;
  try {
    const resp = await axios.get(`${API_BASE}/tupad_adl_details/${adlDetailId.value}`);
    adlDetail.value = resp.data || null;
  } catch (err) {
    Swal.fire("Error", "Failed to load ADL Detail.", "error");
  } finally { loadingDetail.value = false; }
};

const fetchRows = async () => {
  if (!adlDetailId.value) return;
  loadingRows.value = true;
  try {
    const resp = await axios.get(`${API_BASE}/tupad_adl_breakdown`, { params: { adl_detail_id: adlDetailId.value } });
    rows.value = Array.isArray(resp.data) ? resp.data : [];
  } catch (err) {
    Swal.fire("Error", "Failed to load breakdown.", "error");
  } finally { loadingRows.value = false; }
};

const refreshAll = async () => { await fetchDetail(); await fetchRows(); };
const goBack = () => router.back();

// Helpers
const toISODateInput = (v) => (!v ? "" : String(v).slice(0, 10));
const parseMoneySafe = (v) => Number.isFinite(Number(String(v).replace(/,/g, ""))) ? Number(String(v).replace(/,/g, "")) : 0;
const parseIntSafe = (v) => Number.isFinite(parseInt(String(v).replace(/,/g, ""), 10)) ? parseInt(String(v).replace(/,/g, ""), 10) : 0;
const validateRow = (f, max) => {
  if (!String(f.lgu || "").trim()) return "LGU / Barangay is required.";
  if (Number(f.beneficiaries) < 0 || Number(f.amount) < 0) return "Values cannot be negative.";
  if (Number(f.amount) > Number(max)) return `Exceeds balance (${formatMoney(max)}).`;
  return "";
};

// Excel Operations
const triggerExcel = (row) => {
  selectedRowForExcel.value = row;
  if (excelInputRef.value) { excelInputRef.value.value = ""; excelInputRef.value.click(); }
};
const onExcelChosen = async (e) => {
  const file = e.target.files?.[0];
  const row = selectedRowForExcel.value;
  if (!file || !row?.id) return;
  excelBusyRowId.value = row.id;
  try {
    const form = new FormData(); form.append("file", file);
    const resp = await axios.post(`${API_BASE}/tupad_adl_breakdown/${row.id}/import-demographics`, form, { headers: { "Content-Type": "multipart/form-data" } });
    const idx = rows.value.findIndex((r) => r.id === row.id);
    if (idx >= 0) rows.value[idx] = { ...rows.value[idx], ...resp.data?.counts };
    Swal.fire("Imported!", "Demographics uploaded.", "success");
  } catch (err) {
    Swal.fire("Error", "Import failed.", "error");
  } finally {
    excelBusyRowId.value = null; selectedRowForExcel.value = null;
    if (excelInputRef.value) excelInputRef.value.value = "";
  }
};

// CRUD Modal Logic
const crudModal = ref({ open: false, mode: "add", rowId: null, saving: false, originalAmount: 0 });
const crudError = ref("");
const crudForm = ref({ lgu: "", beneficiaries: 0, amount: "", status: "pending", osh_date: "", payout_date: "" });

const openAddModal = () => {
  if (adlBalance.value <= 0) return Swal.fire("Not Allowed", "Budget is fully allocated.", "warning");
  crudModal.value = { open: true, mode: "add", rowId: null, saving: false, originalAmount: 0 };
  crudForm.value = { lgu: "", beneficiaries: 0, amount: "", status: "pending", osh_date: "", payout_date: "" };
  crudError.value = "";
};
const openEditModal = (row) => {
  crudModal.value = { open: true, mode: "edit", rowId: row.id, saving: false, originalAmount: Number(row.amount || 0) };
  crudForm.value = { lgu: row.lgu, beneficiaries: Number(row.beneficiaries), amount: String(row.amount), status: normalizeStatus(row.status), osh_date: toISODateInput(row.osh_date), payout_date: toISODateInput(row.payout_date) };
  crudError.value = "";
};
const closeCrudModal = () => { if (!crudModal.value.saving) crudModal.value.open = false; };
const modalAvailableBalance = computed(() => crudModal.value.mode === "edit" ? adlBalance.value + crudModal.value.originalAmount : adlBalance.value);
const balanceAfterSavePreview = computed(() => Math.max(0, modalAvailableBalance.value - parseMoneySafe(crudForm.value.amount)));

const saveCrud = async () => {
  crudError.value = "";
  const payload = { lgu: String(crudForm.value.lgu).trim(), beneficiaries: parseIntSafe(crudForm.value.beneficiaries), amount: parseMoneySafe(crudForm.value.amount), status: crudForm.value.status, osh_date: crudForm.value.osh_date || null, payout_date: crudForm.value.payout_date || null };
  const err = validateRow(payload, modalAvailableBalance.value);
  if (err) return crudError.value = err;
  
  crudModal.value.saving = true;
  try {
    if (crudModal.value.mode === "add") {
      await axios.post(`${API_BASE}/tupad_adl_breakdown`, { adl_detail_id: adlDetailId.value, ...payload });
      Swal.fire("Saved", "Row created.", "success");
    } else {
      await axios.put(`${API_BASE}/tupad_adl_breakdown/${crudModal.value.rowId}`, payload);
      Swal.fire("Updated", "Row updated.", "success");
    }
    closeCrudModal(); await refreshAll();
  } catch (e) {
    Swal.fire("Error", "Save failed.", "error");
  } finally { crudModal.value.saving = false; }
};

const removeRow = async (row) => {
  if (!row?.id) return;
  const res = await Swal.fire({ icon: "warning", title: "Delete this breakdown?", showCancelButton: true, confirmButtonText: "Yes, delete", confirmButtonColor: "#ef4444" });
  if (res.isConfirmed) {
    try { await axios.delete(`${API_BASE}/tupad_adl_breakdown/${row.id}`); await refreshAll(); Swal.fire("Deleted", "Row removed.", "success"); } 
    catch (e) { Swal.fire("Error", "Failed to delete.", "error"); }
  }
};

const markAsReceived = async (row) => {
  if (!row?.id || normalizeStatus(row.status) === "received") return;
  const res = await Swal.fire({ icon: "question", title: "Mark as received?", showCancelButton: true, confirmButtonText: "Yes" });
  if (res.isConfirmed) {
    try { await axios.put(`${API_BASE}/tupad_adl_breakdown/${row.id}/mark-as-received`); await refreshAll(); Swal.fire("Done", "Marked as received.", "success"); } 
    catch (e) { Swal.fire("Error", "Failed to update.", "error"); }
  }
};

// Beneficiaries Logic
const benExcelRef = ref(null);
const benModal = ref({ open: false, breakdownId: null, lgu: "", loading: false, uploading: false, progress: 0, progressText: "Uploading…", totalAll: 0, totalFemale: 0, rows: [], search: "", showAll: false });
const filteredBeneficiaries = computed(() => {
  const q = benModal.value.search.toLowerCase().trim();
  return q ? benModal.value.rows.filter(x => String(x.full_name).toLowerCase().includes(q)) : benModal.value.rows;
});

const openBenModal = async (row) => {
  benModal.value = { ...benModal.value, open: true, breakdownId: row.id, lgu: row.lgu, search: "", showAll: false };
  await reloadBenList();
};
const closeBenModal = () => { benModal.value.open = false; };
const reloadBenList = async () => {
  benModal.value.loading = true;
  try {
    const res = await axios.get(`${API_BASE}/tupad_adl_breakdown/${benModal.value.breakdownId}/beneficiaries`, { params: { female_only: benModal.value.showAll ? 0 : 1 } });
    benModal.value.rows = res.data?.rows || []; benModal.value.totalAll = Number(res.data?.total_all || 0); benModal.value.totalFemale = Number(res.data?.total_female || 0);
  } catch (err) { Swal.fire("Error", "Failed to load.", "error"); } 
  finally { benModal.value.loading = false; }
};

const triggerBenExcel = () => { if (benExcelRef.value) { benExcelRef.value.value = ""; benExcelRef.value.click(); } };
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const onBenExcelChosen = async (e) => {
  const file = e.target.files?.[0]; if (!file || !benModal.value.breakdownId) return;
  benModal.value.uploading = true; benModal.value.progress = 1; benModal.value.progressText = "Uploading…";
  try {
    const form = new FormData(); form.append("file", file);
    await axios.post(`${API_BASE}/tupad_adl_breakdown/${benModal.value.breakdownId}/import-demographics`, form, {
      headers: { "Content-Type": "multipart/form-data" },
      onUploadProgress: (p) => { benModal.value.progress = p.total ? Math.max(1, Math.min(60, Math.round((p.loaded / p.total) * 60))) : 60; }
    });
    benModal.value.progress = 100; benModal.value.progressText = "Done!";
    await sleep(350); await reloadBenList(); Swal.fire("Uploaded!", "Saved successfully.", "success");
  } catch (err) { Swal.fire("Error", "Upload failed.", "error"); } 
  finally { benModal.value.uploading = false; }
};

const tagsFromFlags = (b) => {
  const tags = []; const sex = String(b?.sex_raw || "").trim().toLowerCase();
  if (sex === "female" || sex === "f") tags.push("Female");
  if (b?.is_four_ps) tags.push("4Ps"); if (b?.is_pwd) tags.push("PWD"); if (b?.is_senior) tags.push("Senior Citizen");
  return tags;
};
const tagClass = (tag) => {
  const t = String(tag).toLowerCase();
  return t.includes("female") ? "female" : t.includes("pwd") ? "pwd" : t.includes("4ps") ? "fourps" : t.includes("senior") ? "senior" : "neutral";
};

onMounted(refreshAll);
watch(() => adlDetailId.value, async () => { rows.value = []; await refreshAll(); });
</script>

<style scoped>
/* Base */
.main-content { padding: 24px; max-width: 1300px; margin: 0 auto; font-family: Inter, system-ui, -apple-system, sans-serif; color: #0f172a; background: #f8fafc; min-height: 100vh;}
.page-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
h2 { margin: 0; font-size: 26px; font-weight: 900; letter-spacing: -0.02em; color: #1e293b; }
.sub { margin: 6px 0 0; color: #64748b; font-weight: 600; font-size: 14px; }
.actions { display: flex; gap: 12px; align-items: center; }

/* Buttons */
.btn { padding: 10px 16px; border-radius: 10px; border: 1px solid #cbd5e1; background: #fff; cursor: pointer; font-weight: 700; font-size: 14px; transition: all 0.2s ease; color: #334155; }
.btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08); border-color: #94a3b8; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-primary { background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none; color: #fff; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25); }
.btn-primary:hover:not(:disabled) { background: linear-gradient(135deg, #1d4ed8, #1e40af); box-shadow: 0 10px 25px rgba(37, 99, 235, 0.35); }

/* Glass Cards */
.cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin: 16px 0 24px; }
.glass { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 16px; box-shadow: 0 8px 32px rgba(15, 23, 42, 0.04); }
.card { padding: 20px; transition: transform 0.2s; }
.card:hover { transform: translateY(-3px); }
.card-title { color: #64748b; font-weight: 800; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; }
.card-value { margin-top: 10px; font-size: 24px; font-weight: 900; color: #0f172a; }
.card-sub { margin-top: 8px; font-size: 13px; font-weight: 700; color: #64748b; }
.highlight { border: 1px solid rgba(37, 99, 235, 0.2); background: linear-gradient(180deg, rgba(239, 246, 255, 0.8), rgba(255, 255, 255, 0.9)); }

/* Toolbar */
.table-toolbar { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; margin-bottom: 16px; gap: 16px; flex-wrap: wrap; }
.toolbar-left, .toolbar-right { display: flex; gap: 12px; align-items: center; }
.search-input { padding: 10px 16px; border-radius: 10px; border: 1px solid #cbd5e1; width: 280px; font-size: 14px; outline: none; transition: border 0.2s; }
.search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.filter-select { padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; background: #fff; font-size: 14px; font-weight: 600; color: #334155; cursor: pointer; outline: none; }
.filter-select.mini { padding: 6px 12px; }

/* Table */
.table-section { overflow: hidden; padding-bottom: 10px; }
.table-wrapper { overflow-x: auto; max-width: 100%; }
table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 900px; text-align: left; }
thead th { background: rgba(248, 250, 252, 0.9); padding: 16px; font-weight: 800; font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; color: #475569; border-bottom: 2px solid #e2e8f0; cursor: pointer; user-select: none; white-space: nowrap; }
.th-content { display: flex; align-items: center; gap: 6px; }
.th-content.right { justify-content: flex-end; }
.sort-icon { color: #94a3b8; font-size: 10px; }

tbody td { padding: 16px; border-bottom: 1px solid #f1f5f9; background: transparent; vertical-align: middle; transition: background 0.2s; }
.main-tr:hover td { background: rgba(248, 250, 252, 0.6); }
.numeric { text-align: right; font-weight: 700; }
.actions-head { text-align: center; cursor: default; }
.actions-cell { display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; }

/* Sticky Column */
.sticky-col { position: sticky; left: 0; z-index: 1; background: rgba(255, 255, 255, 0.95) !important; backdrop-filter: blur(10px); box-shadow: 2px 0 10px rgba(0,0,0,0.03); }
thead .sticky-col { z-index: 2; background: #f8fafc !important; }

/* Expandable Row Styling */
.expand-btn { background: none; border: none; font-size: 12px; cursor: pointer; color: #64748b; padding: 4px; display: flex; align-items: center; justify-content: center; width: 24px; border-radius: 4px; transition: 0.2s; }
.expand-btn:hover { background: #e2e8f0; color: #0f172a; }
.expanded-tr td { background: #f8fafc; padding: 0; border-bottom: 2px solid #e2e8f0; }
.expanded-content { padding: 16px 24px; display: flex; gap: 32px; flex-wrap: wrap; align-items: center; animation: slideDown 0.3s ease-out forwards; }
@keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.exp-group { display: flex; flex-direction: column; gap: 6px; }
.exp-label { font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
.exp-divider { width: 1px; height: 30px; background: #cbd5e1; }

/* LGU Cell */
.lgu-cell { display: flex; align-items: center; gap: 12px; }
.lgu-badge { width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center; font-weight: 900; color: #2563eb; background: rgba(37, 99, 235, 0.1); border: 1px solid rgba(37, 99, 235, 0.15); font-size: 16px; }
.lgu-meta { display: flex; flex-direction: column; gap: 2px; }
.lgu-name { font-weight: 800; color: #0f172a; font-size: 15px; }
.lgu-sub { font-size: 12px; color: #64748b; font-weight: 600; }

/* Pills */
.num-pill { padding: 6px 12px; border-radius: 8px; background: #f1f5f9; font-weight: 700; color: #334155; border: 1px solid #e2e8f0; }
.money-pill { padding: 6px 14px; border-radius: 8px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #047857; font-weight: 800; }
.pill { padding: 6px 12px; border-radius: 20px; font-weight: 800; font-size: 12px; text-transform: uppercase; display: inline-block; }
.pill.ok { background: #dcfce7; color: #166534; }
.pill.pending { background: #fef3c7; color: #92400e; }
.date-pill { font-size: 13px; font-weight: 600; color: #475569; }
.mini-pill { padding: 4px 10px; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; font-weight: 700; color: #0f172a; font-size: 13px; }

/* Action Buttons */
.icon-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; cursor: pointer; display: grid; place-items: center; transition: all 0.2s; font-size: 14px; color: #475569; }
.icon-btn:hover:not(:disabled) { background: #f1f5f9; transform: translateY(-1px); }
.icon-btn.danger { color: #ef4444; border-color: #fecaca; background: #fef2f2; }
.icon-btn.danger:hover { background: #fee2e2; }
.icon-btn.okbtn { color: #10b981; border-color: #a7f3d0; background: #ecfdf5; }
.icon-btn.uploadbtn { font-size: 16px; border-color: #ddd6fe; background: #f5f3ff; }

/* Pagination Footer */
.pagination-footer { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-top: 1px solid #e2e8f0; background: rgba(255,255,255,0.5); flex-wrap: wrap; gap: 16px; }
.page-info { font-size: 14px; color: #64748b; }
.page-controls { display: flex; align-items: center; gap: 12px; }
.page-btn { padding: 6px 14px; border: 1px solid #cbd5e1; background: #fff; border-radius: 8px; font-weight: 600; cursor: pointer; color: #334155; transition: 0.2s; }
.page-btn:hover:not(:disabled) { background: #f1f5f9; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-current { font-weight: 700; color: #0f172a; font-size: 14px; }

/* Modals & Utilities */
.hidden-file { display: none; }
.empty { padding: 40px !important; color: #64748b; font-weight: 600; font-size: 15px; text-align: center; }

/* Modal overlays (Gov/Ben) */
.gov-overlay, .ben-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1000; display: grid; place-items: center; padding: 20px; }
.gov-modal, .ben-modal { background: #fff; border-radius: 16px; width: 100%; max-width: 600px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; display: flex; flex-direction: column; max-height: 90vh; }
.ben-modal { max-width: 800px; }

.gov-topbar, .ben-header { padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-start; background: #f8fafc; }
.gov-title, .ben-title { font-size: 18px; font-weight: 800; color: #0f172a; }
.gov-sub, .ben-sub { margin-top: 4px; font-size: 13px; color: #64748b; }
.gov-x, .ben-close { background: transparent; border: none; font-size: 20px; cursor: pointer; color: #94a3b8; }

.gov-body, .ben-body { padding: 24px; overflow-y: auto; }
.gov-grid { display: grid; gap: 16px; }
.field label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: #334155; }
.gov-input, .gov-select { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; }
.gov-input:focus, .gov-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.hint { font-size: 12px; color: #64748b; margin-top: 4px; }
.req { color: #ef4444; }

.gov-alert { padding: 12px; background: #fef2f2; color: #b91c1c; border-left: 4px solid #ef4444; border-radius: 6px; margin: 16px 0; font-size: 14px; }
.gov-summary { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 20px; padding-top: 20px; border-top: 1px dashed #cbd5e1; }
.gov-summary-card { background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; }
.k { font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase; }
.v { font-size: 18px; font-weight: 900; color: #0f172a; margin-top: 4px; }

.gov-footer, .ben-footer { padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px; background: #f8fafc; }

/* Tags inside Modals */
.tag-wrap { display: flex; gap: 6px; flex-wrap: wrap; }
.tag { padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; border: 1px solid transparent; }
.tag.female { background: #fdf2f8; color: #be185d; border-color: #fbcfe8; }
.tag.pwd { background: #fffbeb; color: #b45309; border-color: #fde68a; }
.tag.fourps { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
.tag.senior { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
.tag.muted { background: transparent; color: #94a3b8; border-color: #e2e8f0; }

.ben-toolbar { padding: 16px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; background: #fff; }
.ben-left, .ben-right { display: flex; gap: 12px; align-items: center; }
.ben-search { padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; width: 220px; }
.ben-table { width: 100%; border-collapse: collapse; }
.ben-table th { background: #f8fafc; padding: 10px; text-align: left; font-size: 12px; font-weight: 800; color: #475569; border-bottom: 2px solid #e2e8f0; }
.ben-table td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
.name-cell { display: flex; align-items: center; gap: 10px; }
.avatar { width: 32px; height: 32px; border-radius: 50%; background: #e2e8f0; color: #334155; display: grid; place-items: center; font-weight: 800; font-size: 14px; }
.name-main { font-weight: 700; color: #0f172a; }
.name-sub { font-size: 11px; color: #64748b; }
.mono { font-family: monospace; color: #64748b; }

/* Progress Bar */
.ben-progress-wrap { padding: 16px 24px; background: #eff6ff; border-bottom: 1px solid #bfdbfe; }
.ben-progress-top { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; font-weight: 700; color: #1d4ed8; }
.ben-progress { height: 6px; background: #dbeafe; border-radius: 4px; overflow: hidden; }
.ben-progress-fill { height: 100%; background: #2563eb; transition: width 0.3s ease; }
</style>