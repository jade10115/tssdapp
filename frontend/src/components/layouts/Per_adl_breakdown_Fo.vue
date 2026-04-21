<!-- resources/js/pages/Per_adl_breakdown.vue
  Route: Adlbreakdown
  API: GET /api/per-adl-breakdown?adl_id=1  OR  /api/per-adl-breakdown?adl_detail_id=3

  ✅ Fix: uses division_key from API to avoid "All Divisions" vs "No Division" conflict
  ✅ Responsive: desktop table + mobile cards
-->
<template>
  <div class="main-content">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <h2>ADL Breakdown</h2>

        <div class="subline">
          <span class="chip">ADL: <b>{{ meta.adl || "—" }}</b></span>
          <span class="chip soft">Sponsor: <b>{{ meta.sponsor || "—" }}</b></span>
          <span class="chip">Mode: <b>{{ meta.mode || "—" }}</b></span>
          <span v-if="meta.adl_id" class="chip soft">ADL ID: <b>{{ meta.adl_id }}</b></span>
          <span v-if="meta.adl_detail_id" class="chip soft">ADL Detail ID: <b>{{ meta.adl_detail_id }}</b></span>
        </div>
      </div>

      <div class="actions">
        <button class="btn" @click="goBack">Back</button>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters">
      <div class="filter">
        <label>Division</label>

        <!-- "" = All divisions -->
        <select v-model="selectedDivisionKey" class="input">
          <option value="">All Divisions</option>
          <option v-for="d in divisionOptions" :key="d.division_key" :value="d.division_key">
            {{ d.division_name }}
          </option>
        </select>

        <div class="hint">
          Pick a division to filter LGU list + recompute totals. Click a division row below to toggle filter.
        </div>
      </div>

      <div class="filter">
        <label>Search LGU</label>
        <input v-model="search" class="input" placeholder="Type LGU..." />
        <div class="hint">Search applies to the currently shown list.</div>
      </div>
    </div>

    <!-- Computation Cards -->
    <div class="cards">
      <div class="card">
        <div class="card-title">Rows</div>
        <div class="card-value">{{ shownRows.length }}</div>
        <div class="card-sub">LGU rows shown</div>
      </div>

      <div class="card">
        <div class="card-title">Total Beneficiaries</div>
        <div class="card-value">{{ formatNumber(shownTotals.totalBeneficiaries) }}</div>
        <div class="card-sub">Sum of beneficiaries shown</div>
      </div>

      <div class="card">
        <div class="card-title">Total Amount</div>
        <div class="card-value">{{ formatMoney(shownTotals.totalAmount) }}</div>
        <div class="card-sub">Sum of amount shown</div>
      </div>

      <div class="card">
        <div class="card-title">Allocated (Backend)</div>
        <div class="card-value">{{ formatMoney(meta.allocated) }}</div>
        <div class="card-sub">From ADL/Detail</div>
      </div>

      <div class="card">
        <div class="card-title">Balance (Backend)</div>
        <div class="card-value">{{ formatMoney(meta.balance) }}</div>
        <div class="card-sub">From ADL/Detail</div>
      </div>
    </div>

    <!-- Totals per division -->
    <!-- <div class="section">
      <div class="section-head">
        <h3>Totals per Division</h3>
        <p v-if="selectedDivisionKey === ''">All divisions computed</p>
        <p v-else>Filtered by one division (click again to reset)</p>
      </div>

      <div class="div-table-wrap">
        <table class="div-table">
          <thead>
            <tr>
              <th>Division</th>
              <th class="numeric">Rows</th>
              <th class="numeric">Beneficiaries</th>
              <th class="numeric">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr
            
              v-for="d in totalsByDivision"
              :key="d.division_key"
              :class="{ active: selectedDivisionKey !== '' && d.division_key === selectedDivisionKey }"
              @click="toggleDivisionKey(d.division_key)"
              title="Click to filter by this division"
            >
              <td>{{ d.division_name }}</td>
              <td class="numeric">{{ formatNumber(d.rows) }}</td>
              <td class="numeric">{{ formatNumber(d.total_beneficiaries) }}</td>
              <td class="numeric">{{ formatMoney(d.total_amount) }}</td>
            </tr>

            <tr v-if="totalsByDivision.length === 0 && !loading">
              <td colspan="4" class="empty">No divisions found for this ADL.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div> -->

    <!-- LGU Table -->
    <div class="table-section">
      <div class="table-head">
        <div class="table-title">
          <h3>LGU List</h3>
          <p>
            Displays LGU under selected ADL.
            <span v-if="loading">Loading…</span>
          </p>
        </div>
      </div>

      <div class="table-wrapper">
        <!-- Desktop -->
        <table class="desktop-table">
          <thead>
            <tr>
              <th>LGU</th>
              <th class="numeric">Beneficiaries</th>
              <th class="numeric">Amount</th>
              <th>Division</th>
              <th>OSH Date</th>
              <th>Payout Date</th>
              
            </tr>
          </thead>

          <tbody>
            <tr v-for="row in shownRows" :key="row.id">
              <td>
                <div class="lgu-cell">
                  <div class="lgu-badge">{{ (row.lgu || "L").slice(0, 1).toUpperCase() }}</div>
                  <div class="lgu-meta">
                    <div class="lgu-name">{{ row.lgu || "—" }}</div>
                    <!-- <div class="lgu-sub">Breakdown ID: {{ row.id }} • ADL Detail: {{ row.adl_detail_id }}</div> -->
                  </div>
                </div>
              </td>

              <td class="numeric"><span class="num-pill">{{ formatNumber(row.beneficiaries || 0) }}</span></td>
              <td class="numeric"><span class="money-pill">{{ formatMoney(row.amount || 0) }}</span></td>
              <td><span class="tag-pill">{{ row.division_name || "—" }}</span></td>
              <td><span class="date-pill">{{ formatDate(row.osh_date) }}</span></td>
              <td><span class="date-pill">{{ formatDate(row.payout_date) }}</span></td>
            </tr>

            <tr v-if="shownRows.length === 0 && !loading">
              <td colspan="6" class="empty">No LGU rows found.</td>
            </tr>

            <tr v-if="loading">
              <td colspan="6" class="empty">Loading...</td>
            </tr>
          </tbody>
        </table>

        <!-- Mobile -->
        <div class="mobile-cards">
          <div v-for="row in shownRows" :key="'m-' + row.id" class="mcard">
            <div class="mcard-top">
              <div class="m-lgu">
                <div class="lgu-badge">{{ (row.lgu || "L").slice(0, 1).toUpperCase() }}</div>
                <div>
                  <div class="m-title">{{ row.lgu || "—" }}</div>
                  <div class="m-sub">{{ row.division_name || "—" }}</div>
                </div>
              </div>
            </div>

            <div class="mgrid">
              <div class="mitem">
                <div class="mlabel">Beneficiaries</div>
                <div class="mvalue">{{ formatNumber(row.beneficiaries || 0) }}</div>
              </div>
              <div class="mitem">
                <div class="mlabel">Amount</div>
                <div class="mvalue">{{ formatMoney(row.amount || 0) }}</div>
              </div>
              <div class="mitem">
                <div class="mlabel">OSH Date</div>
                <div class="mvalue">{{ formatDate(row.osh_date) }}</div>
              </div>
              <div class="mitem">
                <div class="mlabel">Payout Date</div>
                <div class="mvalue">{{ formatDate(row.payout_date) }}</div>
              </div>
            </div>

            <div class="mfoot">
              <div class="mfoot-pill">Breakdown ID: {{ row.id }}</div>
              <div class="mfoot-pill">ADL Detail: {{ row.adl_detail_id }}</div>
            </div>
          </div>

          <div v-if="shownRows.length === 0 && !loading" class="mobile-empty">
            <div class="empty">No LGU rows found.</div>
          </div>
        </div>
        <!-- /Mobile -->
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
const router = useRouter();
const route = useRoute();


const adlId = computed(() => Number(route.query.adl_id || 0) || null);
const adlDetailId = computed(() => Number(route.query.adl_detail_id || 0) || null);

const meta = ref({
  mode: null,
  adl_id: null,
  adl_detail_id: null,
  adl: null,
  sponsor: null,
  allocated: 0,
  balance: 0,
});

const rows = ref([]);
const totalsByDivision = ref([]);

/**
 * IMPORTANT:
 * ""  = ALL
 * "__NONE__" = No Division (from API)
 * other strings = division id string "7", "8", etc.
 */
const selectedDivisionKey = ref("");
const search = ref("");
const loading = ref(false);

const formatMoney = (value) =>
  new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(Number(value || 0));

const formatNumber = (n) => (Number(n || 0)).toLocaleString();

const formatDate = (v) => {
  if (!v) return "—";
  const d = new Date(String(v).slice(0, 10));
  if (isNaN(d.getTime())) return "—";
  return d.toLocaleDateString("en-PH", { year: "numeric", month: "short", day: "2-digit" });
};

const divisionOptions = computed(() => totalsByDivision.value || []);

const shownRows = computed(() => {
  let list = [...rows.value];

  // ✅ Division filter uses division_key (fixes "All vs No Division" bug)
  if (selectedDivisionKey.value !== "") {
    list = list.filter((r) => String(r.division_key || "") === selectedDivisionKey.value);
  }

  // Search
  const q = String(search.value || "").trim().toLowerCase();
  if (q) list = list.filter((r) => String(r.lgu || "").toLowerCase().includes(q));

  list.sort((a, b) => Number(b.id || 0) - Number(a.id || 0));
  return list;
});

const shownTotals = computed(() => {
  return shownRows.value.reduce(
    (acc, r) => {
      acc.totalAmount += Number(r.amount || 0);
      acc.totalBeneficiaries += Number(r.beneficiaries || 0);
      return acc;
    },
    { totalAmount: 0, totalBeneficiaries: 0 }
  );
});

const toggleDivisionKey = (divisionKey) => {
  const key = String(divisionKey ?? "");
  selectedDivisionKey.value = selectedDivisionKey.value === key ? "" : key;
};

const fetchBreakdown = async () => {
  if (!adlId.value && !adlDetailId.value) {
    Swal.fire("Missing", "Provide adl_id or adl_detail_id in the URL.", "warning");
    return;
  }

  loading.value = true;
  try {
    const resp = await axios.get(`${API_BASE}/per-adl-breakdown`, {
      params: {
        adl_id: adlId.value || undefined,
        adl_detail_id: adlDetailId.value || undefined,
      },
    });

    meta.value = resp.data?.meta || meta.value;

    // rows MUST contain: division_key, division_name, etc. from your controller fix
    rows.value = Array.isArray(resp.data?.rows) ? resp.data.rows : [];

    // totals_by_division MUST contain: division_key (controller provides)
    totalsByDivision.value = Array.isArray(resp.data?.totals_by_division) ? resp.data.totals_by_division : [];

    selectedDivisionKey.value = ""; // default = all
  } catch (err) {
    console.error(err);
    Swal.fire("Error", err.response?.data?.message || "Failed to load breakdown.", "error");
    rows.value = [];
    totalsByDivision.value = [];
  } finally {
    loading.value = false;
  }
};

const goBack = () => router.back();

onMounted(fetchBreakdown);

watch(
  () => [adlId.value, adlDetailId.value],
  async () => {
    rows.value = [];
    totalsByDivision.value = [];
    selectedDivisionKey.value = "";
    search.value = "";
    await fetchBreakdown();
  }
);
</script>

<style scoped>
.main-content {
  padding: 18px;
  max-width: 1300px;
  margin: 0 auto;
  font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial;
  color: #0f172a;
}

/* Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 14px;
  flex-wrap: wrap;
  margin-bottom: 14px;
}
.header-left { min-width: 260px; }
h2 { margin: 0; font-size: 22px; font-weight: 900; letter-spacing: -0.02em; }
.subline { margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap; }
.chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #334155;
  font-weight: 800;
  font-size: 12px;
}
.chip.soft { background: #f8fafc; }

.actions { display: flex; gap: 10px; align-items: center; }
.btn {
  padding: 10px 14px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  font-weight: 900;
  transition: transform 0.12s ease, box-shadow 0.12s ease;
}
.btn:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(2, 6, 23, 0.06); }

/* Filters */
.filters {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin: 14px 0 14px;
}
.filter {
  background: #fff;
  border: 1px solid #eef2f7;
  border-radius: 14px;
  padding: 12px;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.04);
}
.filter label {
  display: block;
  font-size: 12px;
  font-weight: 900;
  color: #334155;
  margin-bottom: 8px;
}
.input {
  width: 100%;
  height: 44px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 10px 12px;
  font-weight: 900;
  outline: none;
  background: #fff;
  box-sizing: border-box;
}
.input:focus {
  border-color: rgba(37, 99, 235, 0.55);
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}
.hint { margin-top: 8px; font-size: 12px; font-weight: 800; color: #64748b; }

/* Cards */
.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 12px;
  margin: 12px 0 18px;
}
.card {
  background: #fff;
  border: 1px solid #eef2f7;
  border-radius: 14px;
  padding: 14px;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.05);
}
.card-title {
  color: #64748b;
  font-weight: 900;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
.card-value { margin-top: 8px; font-size: 20px; font-weight: 1000; color: #0f172a; }
.card-sub { margin-top: 8px; font-size: 12px; font-weight: 800; color: #64748b; }

/* Totals by division */
.section {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #eef2f7;
  box-shadow: 0 10px 30px rgba(2, 6, 23, 0.05);
  overflow: hidden;
  margin-bottom: 14px;
}
.section-head { padding: 14px 16px; border-bottom: 1px solid #eef2f7; }
.section-head h3 { margin: 0; font-size: 16px; font-weight: 1000; }
.section-head p { margin: 6px 0 0; font-size: 12px; font-weight: 800; color: #64748b; }

.div-table-wrap { overflow-x: auto; }
.div-table { width: 100%; border-collapse: collapse; min-width: 720px; }
.div-table thead th {
  background: linear-gradient(180deg, #fcfdff, #f2f6ff);
  padding: 12px 14px;
  text-align: left;
  font-weight: 900;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  border-bottom: 1px solid #eef2f7;
  color: #334155;
}
.div-table tbody td {
  padding: 12px 14px;
  border-bottom: 1px solid #f1f5f9;
  font-weight: 900;
  cursor: pointer;
}
.div-table tbody tr:hover td { background: #fbfdff; }
.div-table tbody tr.active td { background: rgba(37, 99, 235, 0.08); }

/* LGU table */
.table-section {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #eef2f7;
  box-shadow: 0 10px 30px rgba(2, 6, 23, 0.05);
  overflow: hidden;
}
.table-head { padding: 14px 16px; border-bottom: 1px solid #eef2f7; }
.table-title h3 { margin: 0; font-size: 16px; font-weight: 1000; }
.table-title p { margin: 6px 0 0; font-size: 12px; font-weight: 800; color: #64748b; }

.table-wrapper { overflow-x: auto; }
.desktop-table { width: 100%; border-collapse: collapse; min-width: 980px; }
.desktop-table thead th {
  background: linear-gradient(180deg, #fcfdff, #f2f6ff);
  padding: 14px 16px;
  text-align: left;
  font-weight: 900;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  border-bottom: 1px solid #eef2f7;
  color: #334155;
}
.desktop-table tbody td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  background: #fff;
  vertical-align: middle;
}
.desktop-table tbody tr:hover td { background: #fbfdff; }

.numeric { text-align: right; font-weight: 900; }
.empty { text-align: center; padding: 22px; font-weight: 900; color: #64748b; }

/* LGU cell */
.lgu-cell { display: flex; align-items: center; gap: 12px; }
.lgu-badge {
  width: 36px; height: 36px;
  border-radius: 10px;
  display: grid; place-items: center;
  font-weight: 900;
  color: #1d4ed8;
  background: rgba(37, 99, 235, 0.1);
  border: 1px solid rgba(37, 99, 235, 0.14);
}
.lgu-meta { display: flex; flex-direction: column; gap: 2px; }
.lgu-name { font-weight: 1000; color: #0b3a66; line-height: 1.1; }
.lgu-sub { font-size: 12px; color: #64748b; font-weight: 800; }

.num-pill {
  display: inline-flex;
  justify-content: flex-end;
  padding: 6px 10px;
  border-radius: 999px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  min-width: 90px;
}
.money-pill {
  display: inline-flex;
  justify-content: flex-end;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.18);
  min-width: 140px;
  color: #047857;
}
.tag-pill {
  display: inline-flex;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(37, 99, 235, 0.08);
  border: 1px solid rgba(37, 99, 235, 0.18);
  font-weight: 900;
  font-size: 12px;
  color: #1d4ed8;
}
.date-pill {
  display: inline-flex;
  padding: 6px 10px;
  border-radius: 999px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  font-weight: 900;
  font-size: 12px;
  color: #334155;
}

/* Mobile */
.mobile-cards { display: none; }
@media (max-width: 980px) {
  .filters { grid-template-columns: 1fr; }
  .desktop-table { display: none; }
  .mobile-cards { display: grid; gap: 12px; padding: 14px; }
  .table-wrapper { overflow: visible; }
}
.mcard {
  border: 1px solid #eef2f7;
  border-radius: 14px;
  padding: 12px;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.05);
  background: #fff;
}
.mcard-top { display: flex; justify-content: space-between; gap: 10px; align-items: center; margin-bottom: 10px; }
.m-lgu { display: flex; gap: 10px; align-items: center; }
.m-title { font-weight: 1000; color: #0b3a66; }
.m-sub { font-size: 12px; font-weight: 900; color: #64748b; margin-top: 2px; }
.mgrid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.mitem { border: 1px solid #eef2f7; background: #f8fafc; border-radius: 12px; padding: 10px; }
.mlabel { font-size: 11px; font-weight: 1000; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; }
.mvalue { margin-top: 6px; font-weight: 1000; color: #0f172a; }
.mfoot { margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap; }
.mfoot-pill {
  display: inline-flex;
  padding: 6px 10px;
  border-radius: 999px;
  background: #fff;
  border: 1px solid #e2e8f0;
  font-weight: 900;
  font-size: 12px;
  color: #334155;
}
.mobile-empty { padding: 0 14px 14px; }
@media (max-width: 420px) { .mgrid { grid-template-columns: 1fr; } }
</style>
