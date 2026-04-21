<template>
  <div class="page">
    <!-- Header -->
    <div class="header">
      <div class="title">
        <h1>Employees</h1>
        <p>Manage employee records, user accounts, profiles, and positions.</p>
      </div>

      <div class="header-actions">
        <div class="search">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
          <input v-model="search" @input="onSearch" placeholder="Search name / email / division / position..." />
        </div>

        <button class="btn btn-outline" @click="fetchAll" :disabled="loading">
          {{ loading ? "Loading..." : "Refresh" }}
        </button>

        <button class="btn btn-outline" @click="openPositionsModal">
          Manage Positions
        </button>

        <button class="btn btn-primary" @click="openCreate">
          + Add Employee
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats">
      <div class="stat">
        <div class="stat-label">Employees</div>
        <div class="stat-value">{{ pagination.total }}</div>
      </div>
      <div class="stat">
        <div class="stat-label">Divisions</div>
        <div class="stat-value">{{ divisions.length }}</div>
      </div>
      <div class="stat">
        <div class="stat-label">Positions</div>
        <div class="stat-value">{{ positions.length }}</div>
      </div>
    </div>

    <!-- Division Counts -->
    <div class="card mini-card">
      <div class="mini-head">
        <div class="mini-title">Employees per Division</div>
        <div class="mini-sub">Breakdown</div>
      </div>

      <div v-if="loading" class="mini-loading">
        <div class="spinner"></div>
        <span>Loading counts...</span>
      </div>

      <div v-else class="chips">
        <div class="chip" v-for="c in divisionCounts" :key="c.division">
          <span class="chip-name">{{ c.division || "—" }}</span>
          <span class="chip-count">{{ c.total }}</span>
        </div>
        <div v-if="divisionCounts.length === 0" class="chip empty-chip">
          No division counts yet.
        </div>
      </div>
    </div>

    <!-- Position Counts -->
    <div class="card mini-card">
      <div class="mini-head">
        <div class="mini-title">Employees per Position</div>
        <div class="mini-sub">Counts are computed from <b>tbl_user_profile.position_id</b></div>
      </div>

      <div v-if="loading" class="mini-loading">
        <div class="spinner"></div>
        <span>Loading counts...</span>
      </div>

      <div v-else class="chips">
        <div class="chip" v-for="p in positionCounts" :key="p.id">
          <span class="chip-name">{{ p.position || "—" }}</span>
          <span class="chip-count">{{ p.total }}</span>
        </div>
        <div v-if="positionCounts.length === 0" class="chip empty-chip">
          No position counts yet.
        </div>
      </div>
    </div>

    <!-- Desktop Table -->
    <div class="card table-card">
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th class="col-photo">Photo</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Position</th>
              <th>Division</th>
              <th class="right">Phone</th>
              <th class="right">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="empty">
                <div class="loading-row">
                  <div class="spinner"></div>
                  <span>Loading employees...</span>
                </div>
              </td>
            </tr>

            <tr v-else-if="employees.length === 0">
              <td colspan="7" class="empty">No employees found.</td>
            </tr>

            <tr v-else v-for="emp in employees" :key="emp.user_id">
              <td class="col-photo">
                <div class="avatar">
                  <img v-if="emp.profile_image_url" :src="emp.profile_image_url" alt="Profile" />
                  <div v-else class="avatar-fallback">
                    {{ initials(emp.full_name || emp.email || "U") }}
                  </div>
                </div>
              </td>

              <td>
                <div class="name">
                  <div class="full">{{ emp.full_name || "—" }}</div>
                  <div class="sub">User ID: {{ emp.user_id }}</div>
                </div>
              </td>

              <td class="mono">{{ emp.email || "—" }}</td>
              <td>{{ emp.position || "—" }}</td>
              <td>{{ emp.division || "—" }}</td>
              <td class="right mono">{{ emp.phone || "—" }}</td>

              <td class="right">
                <div class="row-actions">
                  <button class="icon-btn" @click.stop="openEdit(emp)">Edit</button>
                  <button class="icon-btn danger" @click.stop="remove(emp)">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pager" v-if="!loading && pagination.last_page > 1">
        <button class="btn btn-outline" :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">
          Prev
        </button>

        <div class="pager-info">
          Page <b>{{ pagination.current_page }}</b> of <b>{{ pagination.last_page }}</b>
          <span class="sep">•</span>
          <span>{{ pagination.from || 0 }}-{{ pagination.to || 0 }} of {{ pagination.total }}</span>
        </div>

        <button class="btn btn-outline" :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)">
          Next
        </button>

        <div class="perpage">
          <span>Rows</span>
          <select v-model.number="perPage" @change="goPage(1)">
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="15">15</option>
            <option :value="20">20</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Mobile Cards -->
    <div class="mobile-list">
      <div v-if="loading" class="mobile-empty">
        <div class="spinner"></div>
        <span>Loading...</span>
      </div>

      <div v-else-if="employees.length === 0" class="mobile-empty">
        No employees found.
      </div>

      <div v-else class="mobile-cards">
        <div class="mobile-card" v-for="emp in employees" :key="emp.user_id">
          <div class="mc-top">
            <div class="avatar lg">
              <img v-if="emp.profile_image_url" :src="emp.profile_image_url" alt="Profile" />
              <div v-else class="avatar-fallback">
                {{ initials(emp.full_name || emp.email || "U") }}
              </div>
            </div>

            <div class="mc-meta">
              <div class="mc-name">{{ emp.full_name || "—" }}</div>
              <div class="mc-sub mono">{{ emp.email || "—" }}</div>
              <div class="mc-pill">
                <span>{{ emp.position || "—" }}</span>
                <span>•</span>
                <span>{{ emp.division || "—" }}</span>
              </div>
            </div>
          </div>

          <div class="mc-row">
            <span class="mc-k">Phone</span>
            <span class="mc-v mono">{{ emp.phone || "—" }}</span>
          </div>

          <div class="mc-row">
            <span class="mc-k">Address</span>
            <span class="mc-v">{{ emp.address || "—" }}</span>
          </div>

          <div class="mc-actions">
            <button class="btn btn-outline" @click="openEdit(emp)">Edit</button>
            <button class="btn btn-outline danger" @click="remove(emp)">Delete</button>
          </div>
        </div>

        <!-- Pagination mobile -->
        <div class="pager mobile" v-if="pagination.last_page > 1">
          <button class="btn btn-outline" :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">
            Prev
          </button>
          <div class="pager-info">
            Page <b>{{ pagination.current_page }}</b> / <b>{{ pagination.last_page }}</b>
          </div>
          <button class="btn btn-outline" :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)">
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Employee Modal -->
    <Teleport to="body">
      <Transition name="modal" appear>
        <div v-if="showModal" class="app-modal-overlay" @click.self="closeModal">
          <div class="app-modal app-modal-lg" @click.stop>
            <div class="modal-head">
              <div class="modal-title">
                <div class="badge">{{ modalMode === "create" ? "+" : "✎" }}</div>
                <div>
                  <div class="mt">{{ modalMode === "create" ? "Add Employee" : "Edit Employee" }}</div>
                  <div class="ms">
                    {{ modalMode === "create" ? "Creates user + profile" : "Updates user + profile" }}
                  </div>
                </div>
              </div>
              <button class="x" @click="closeModal">✕</button>
            </div>

            <div class="modal-body">
              <form class="form" @submit.prevent="submit">
                <!-- Profile image -->
                <div class="field full">
                  <label>Profile Image (2x2 preview)</label>
                  <div class="upload-row">
                    <div class="preview2x2">
                      <img v-if="imgPreview" :src="imgPreview" alt="Preview" />
                      <img v-else-if="formExistingImage" :src="formExistingImage" alt="Preview" />
                      <div v-else class="ph">2×2</div>
                    </div>

                    <div class="upload-controls">
                      <input type="file" accept="image/*" @change="onFileChange" />
                      <small>Saved to <b>public/userprofile</b></small>
                    </div>
                  </div>
                </div>

                <div class="field">
                  <label>First Name <span class="req">*</span></label>
                  <input v-model="form.first_name" required />
                </div>

                <div class="field">
                  <label>Middle Name</label>
                  <input v-model="form.middle_name" />
                </div>

                <div class="field">
                  <label>Last Name <span class="req">*</span></label>
                  <input v-model="form.last_name" required />
                </div>

                <div class="field">
                  <label>Suffix</label>
                  <input v-model="form.suffix" placeholder="Jr., Sr., III..." />
                </div>

                <!-- Position -->
                <div class="field">
                  <label>Position <span class="req">*</span></label>
                  <select v-model="form.position_id" required>
                    <option value="" disabled>Select position</option>
                    <option v-for="p in positions" :key="p.id" :value="p.id">
                      {{ p.position }}
                    </option>
                  </select>
                  <small>
                    Need a new one?
                    <button type="button" class="linklike" @click="openPositionsModal">
                      Add position
                    </button>
                  </small>
                </div>

                <!-- Division -->
                <div class="field">
                  <label>Division <span class="req">*</span></label>
                  <select v-model="form.division" required>
                    <option value="" disabled>Select division</option>
                    <option v-for="d in divisions" :key="d" :value="d">{{ d }}</option>
                  </select>
                  <small>Stored in DB as division <b>name</b>, not ID.</small>
                </div>

                <!-- ✅ NEW: USER LEVEL dropdown (stored as users.userlevel_id) -->
                <div class="field">
                  <label>User Level <span class="req">*</span></label>
                  <select v-model.number="form.userlevel_id" required>
                    <option value="" disabled>Select user level</option>
                    <option :value="1">1 - Admin</option>
                    <option :value="2">2 - Supply</option>
                    <option :value="3">3 - FO</option>
                  </select>
                  <small>Saved to <b>users.userlevel_id</b></small>
                </div>

                <div class="field">
                  <label>Phone</label>
                  <input v-model="form.phone" />
                </div>

                <div class="field">
                  <label>Address</label>
                  <input v-model="form.address" />
                </div>

                <div class="field">
                  <label>Email <span class="req">*</span></label>
                  <input v-model="form.email" type="email" required />
                </div>

                <div class="field" v-if="modalMode === 'create'">
                  <label>Password <span class="req">*</span></label>
                  <input v-model="form.password" type="password" required />
                  <small>Password handling is controlled in your backend.</small>
                </div>

                <div class="actions">
                  <button type="button" class="btn btn-outline" @click="closeModal" :disabled="saving">
                    Cancel
                  </button>
                  <button type="submit" class="btn btn-primary" :disabled="saving">
                    {{ saving ? "Saving..." : modalMode === "create" ? "Save Employee" : "Update Employee" }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Positions Modal (CRUD) -->
    <Teleport to="body">
      <Transition name="modal" appear>
        <div v-if="showPositionsModal" class="app-modal-overlay" @click.self="closePositionsModal">
          <div class="app-modal app-modal-sm" @click.stop>
            <div class="modal-head">
              <div class="modal-title">
                <div class="badge">⚙</div>
                <div>
                  <div class="mt">Positions</div>
                  <div class="ms">Create / update / delete positions</div>
                </div>
              </div>
              <button class="x" @click="closePositionsModal">✕</button>
            </div>

            <div class="modal-body">
              <div class="pos-head">
                <button class="btn btn-primary" @click="openAddPositionModal">+ Add Position</button>
                <button class="btn btn-outline" @click="fetchPositions" :disabled="posLoading">
                  {{ posLoading ? "Loading..." : "Refresh" }}
                </button>
              </div>

              <div class="pos-list" v-if="posLoading">
                <div class="loading-row">
                  <div class="spinner"></div>
                  <span>Loading positions...</span>
                </div>
              </div>

              <div class="pos-list" v-else>
                <div v-if="positions.length === 0" class="empty">No positions yet.</div>

                <div v-else class="pos-item" v-for="p in positions" :key="p.id">
                  <input class="pos-input" v-model="p._edit" />
                  <div class="pos-actions">
                    <button class="icon-btn" @click="updatePosition(p)">Save</button>
                    <button class="icon-btn danger" @click="deletePosition(p)">Delete</button>
                  </div>
                </div>

                <small class="hint">Tip: edit the text then click “Save”.</small>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Add Position Modal -->
    <Teleport to="body">
      <Transition name="modal" appear>
        <div v-if="showAddPositionModal" class="app-modal-overlay" @click.self="closeAddPositionModal">
          <div class="app-modal app-modal-xs" @click.stop>
            <div class="modal-head">
              <div class="modal-title">
                <div class="badge">+</div>
                <div>
                  <div class="mt">Add Position</div>
                  <div class="ms">Create a new position</div>
                </div>
              </div>
              <button class="x" @click="closeAddPositionModal">✕</button>
            </div>

            <div class="modal-body">
              <form @submit.prevent="createPosition" class="pos-create">
                <label>Position Name <span class="req">*</span></label>
                <input v-model="newPosition" placeholder="e.g. Administrative Aide" required />
                <div class="actions">
                  <button type="button" class="btn btn-outline" @click="closeAddPositionModal">Cancel</button>
                  <button type="submit" class="btn btn-primary" :disabled="posSaving">
                    {{ posSaving ? "Saving..." : "Save" }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

import { inject } from "vue";

const API_BASE = inject("API_BASE");


/**
 * ✅ Toast (top-right notification)
 * - stays above modals because of z-index rule in CSS
 */
const Toast = Swal.mixin({
  toast: true,
  position: "top-end",
  showConfirmButton: false,
  timer: 2400,
  timerProgressBar: true,
});

const loading = ref(false);
const saving = ref(false);

const employees = ref([]);
const divisions = ref([]);
const divisionCounts = ref([]);
const positionCounts = ref([]);

const positions = ref([]);
const posLoading = ref(false);
const posSaving = ref(false);

const search = ref("");

const showModal = ref(false);
const modalMode = ref("create");
const editingUserId = ref(null);

const imgFile = ref(null);
const imgPreview = ref("");
const formExistingImage = ref("");

const showPositionsModal = ref(false);
const showAddPositionModal = ref(false);
const newPosition = ref("");

const perPage = ref(10);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  from: 0,
  to: 0,
});

const form = ref({
  first_name: "",
  middle_name: "",
  last_name: "",
  suffix: "",
  position_id: "",
  division: "",
  userlevel_id: "", // ✅ NEW
  phone: "",
  address: "",
  email: "",
  password: "",
});

let searchTimer = null;

const initials = (name) => {
  const s = String(name || "").trim();
  if (!s) return "U";
  const parts = s.split(/\s+/).slice(0, 2);
  return parts.map((p) => p[0]?.toUpperCase()).join("");
};

const resetForm = () => {
  form.value = {
    first_name: "",
    middle_name: "",
    last_name: "",
    suffix: "",
    position_id: "",
    division: "",
    userlevel_id: "", // ✅ NEW
    phone: "",
    address: "",
    email: "",
    password: "",
  };
  imgFile.value = null;
  imgPreview.value = "";
  formExistingImage.value = "";
  editingUserId.value = null;
  modalMode.value = "create";
};

const openCreate = () => {
  resetForm();
  showModal.value = true;
};

const openEdit = (emp) => {
  resetForm();
  modalMode.value = "edit";
  editingUserId.value = emp.user_id;

  form.value = {
    first_name: emp.first_name || "",
    middle_name: emp.middle_name || "",
    last_name: emp.last_name || "",
    suffix: emp.suffix || "",
    position_id: emp.position_id || "",
    division: emp.division || "",
    userlevel_id: emp.userlevel_id ?? "", // ✅ show current level when editing (if backend returns it)
    phone: emp.phone || "",
    address: emp.address || "",
    email: emp.email || "",
    password: "",
  };

  formExistingImage.value = emp.profile_image_url || "";
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  saving.value = false;
};

const onFileChange = (e) => {
  const file = e.target.files?.[0];
  if (!file) return;
  imgFile.value = file;
  imgPreview.value = URL.createObjectURL(file);
};

const fetchDivisions = async () => {
  const res = await axios.get(`${API_BASE}/divisions/list`);
  const rows = Array.isArray(res.data) ? res.data : [];
  divisions.value = rows.map((r) => r.division ?? r.name).filter(Boolean);
};

const fetchPositions = async () => {
  posLoading.value = true;
  try {
    const res = await axios.get(`${API_BASE}/positions`);
    const rows = Array.isArray(res.data) ? res.data : [];
    positions.value = rows.map((r) => ({ ...r, _edit: r.position }));
  } finally {
    posLoading.value = false;
  }
};

const fetchEmployees = async (page = 1) => {
  const res = await axios.get(`${API_BASE}/employees`, {
    params: { page, per_page: perPage.value, search: search.value },
  });

  const data = res.data || {};
  employees.value = Array.isArray(data.employees?.data) ? data.employees.data : [];
  divisionCounts.value = Array.isArray(data.division_counts) ? data.division_counts : [];
  positionCounts.value = Array.isArray(data.position_counts) ? data.position_counts : [];

  pagination.value = {
    current_page: data.employees?.current_page ?? 1,
    last_page: data.employees?.last_page ?? 1,
    total: data.employees?.total ?? employees.value.length,
    from: data.employees?.from ?? 0,
    to: data.employees?.to ?? 0,
  };
};

const fetchAll = async () => {
  loading.value = true;
  try {
    await Promise.all([fetchDivisions(), fetchPositions(), fetchEmployees(1)]);
  } catch (err) {
    console.error(err);
    Swal.fire("Error", err.response?.data?.message || err.message, "error");
  } finally {
    loading.value = false;
  }
};

const goPage = async (p) => {
  const page = Math.max(1, Math.min(p, pagination.value.last_page || 1));
  loading.value = true;
  try {
    await fetchEmployees(page);
  } finally {
    loading.value = false;
  }
};

const onSearch = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => goPage(1), 300);
};

const submit = async () => {
  if (!form.value.position_id || !form.value.division) {
    Swal.fire("Required", "Position and Division are required.", "warning");
    return;
  }
  if (!form.value.userlevel_id) {
    Swal.fire("Required", "User Level is required.", "warning");
    return;
  }

  const safeSuffix = form.value.suffix == null ? "" : String(form.value.suffix);

  saving.value = true;
  try {
    const fd = new FormData();
    Object.entries(form.value).forEach(([k, v]) => {
      if (k === "suffix") fd.append("suffix", safeSuffix);
      else fd.append(k, v ?? "");
    });
    if (imgFile.value) fd.append("profile_image", imgFile.value);

    if (modalMode.value === "create") {
      await axios.post(`${API_BASE}/employees`, fd, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      Toast.fire({ icon: "success", title: "Employee created successfully." });
    } else {
      await axios.post(`${API_BASE}/employees/${editingUserId.value}?_method=PUT`, fd, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      Toast.fire({ icon: "success", title: "Employee updated successfully." });
    }

    closeModal();
    await goPage(pagination.value.current_page || 1);
  } catch (err) {
    console.error(err);
    Swal.fire("Error", err.response?.data?.message || err.message, "error");
  } finally {
    saving.value = false;
  }
};

const remove = async (emp) => {
  const confirm = await Swal.fire({
    title: "Delete employee?",
    text: `This will delete user #${emp.user_id} and profile.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete",
    confirmButtonColor: "#ef4444",
  });

  if (!confirm.isConfirmed) return;

  try {
    await axios.delete(`${API_BASE}/employees/${emp.user_id}`);
    Toast.fire({ icon: "success", title: "Employee removed." });
    await goPage(1);
  } catch (err) {
    console.error(err);
    Swal.fire("Error", err.response?.data?.message || err.message, "error");
  }
};

const openPositionsModal = async () => {
  showPositionsModal.value = true;
  await fetchPositions();
};

const closePositionsModal = () => (showPositionsModal.value = false);

const openAddPositionModal = () => {
  newPosition.value = "";
  showAddPositionModal.value = true;
};

const closeAddPositionModal = () => {
  showAddPositionModal.value = false;
  posSaving.value = false;
};

const createPosition = async () => {
  const name = String(newPosition.value || "").trim();
  if (!name) return;

  posSaving.value = true;
  try {
    await axios.post(`${API_BASE}/positions`, { position: name });
    Toast.fire({ icon: "success", title: "Position added." });
    closeAddPositionModal();
    await fetchPositions();
    await fetchEmployees(pagination.value.current_page || 1);
  } catch (err) {
    console.error(err);
    Swal.fire("Error", err.response?.data?.message || err.message, "error");
  } finally {
    posSaving.value = false;
  }
};

const updatePosition = async (p) => {
  const name = String(p._edit || "").trim();
  if (!name) {
    Swal.fire("Required", "Position name cannot be empty.", "warning");
    return;
  }

  try {
    await axios.put(`${API_BASE}/positions/${p.id}`, { position: name });
    Toast.fire({ icon: "success", title: "Position updated." });
    await fetchPositions();
    await fetchEmployees(pagination.value.current_page || 1);
  } catch (err) {
    console.error(err);
    Swal.fire("Error", err.response?.data?.message || err.message, "error");
  }
};

const deletePosition = async (p) => {
  const confirm = await Swal.fire({
    title: "Delete position?",
    text: `Delete "${p.position}"?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete",
    confirmButtonColor: "#ef4444",
  });

  if (!confirm.isConfirmed) return;

  try {
    await axios.delete(`${API_BASE}/positions/${p.id}`);
    Toast.fire({ icon: "success", title: "Position removed." });
    await fetchPositions();
    await fetchEmployees(pagination.value.current_page || 1);
  } catch (err) {
    console.error(err);
    Swal.fire("Error", err.response?.data?.message || err.message, "error");
  }
};

onMounted(fetchAll);
</script>

<style scoped>
*,
*::before,
*::after { box-sizing: border-box; }

.page{
  min-height:100vh;
  background:#f8fafc;
  padding:clamp(12px,2.2vw,22px);
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
  color:#0f172a;
}

.card{
  background:#fff;
  border:1px solid #eef2f7;
  border-radius:16px;
  box-shadow:0 10px 26px rgba(2,6,23,.05);
}

/* ✅ SweetAlert always above modals + toast top-right */
:global(.swal2-container){ z-index: 999999 !important; }
:global(.swal2-popup){ border-radius:14px !important; }
:global(.swal2-toast){ margin-top: 10px !important; }

/* Header */
.header{
  display:flex; gap:16px;
  justify-content:space-between;
  align-items:flex-end;
  flex-wrap:wrap;
  padding:18px;
  border-radius:16px;
  border:1px solid #eef2f7;
  background:#fff;
  box-shadow:0 10px 26px rgba(2,6,23,.05);
}
.title h1{ margin:0; font-size:26px; font-weight:900; letter-spacing:-.02em; }
.title p{ margin:6px 0 0; color:#64748b; font-size:13px; font-weight:600; }

.header-actions{
  display:flex; gap:10px;
  align-items:center;
  flex-wrap:wrap;
  justify-content:flex-end;
}
.search{
  display:flex; align-items:center; gap:8px;
  border:1px solid #e2e8f0;
  border-radius:12px;
  padding:10px 12px;
  background:#fff;
  min-width:min(420px,100%);
}
.search svg{ color:#94a3b8; flex:0 0 auto; }
.search input{
  border:none; outline:none; width:100%;
  font-weight:700; font-size:14px; min-width:0;
}

.btn{
  border:none;
  border-radius:12px;
  padding:10px 14px;
  font-weight:900;
  cursor:pointer;
  transition:.2s ease;
  font-size:14px;
}
.btn:disabled{ opacity:.6; cursor:not-allowed; }

.btn-primary{
  background:linear-gradient(135deg,#2563eb,#1d4ed8);
  color:#fff;
  box-shadow:0 10px 22px rgba(37,99,235,.18);
}
.btn-primary:hover{ transform:translateY(-1px); }

.btn-outline{
  background:#fff;
  border:1px solid #e2e8f0;
  color:#334155;
}
.btn-outline.danger{ border-color:rgba(239,68,68,.3); color:#b91c1c; }
.btn-outline:hover{ background:#f8fafc; }

.linklike{
  background:transparent;
  border:none;
  padding:0;
  margin:0;
  font-weight:900;
  color:#1d4ed8;
  cursor:pointer;
  text-decoration:underline;
}

/* Stats */
.stats{
  margin-top:12px;
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
  gap:10px;
}
.stat{
  padding:14px 16px;
  border-radius:16px;
  border:1px solid #eef2f7;
  background:#fff;
  box-shadow:0 10px 26px rgba(2,6,23,.05);
}
.stat-label{
  font-size:12px;
  font-weight:900;
  color:#64748b;
  text-transform:uppercase;
  letter-spacing:.06em;
}
.stat-value{ margin-top:6px; font-size:22px; font-weight:1000; }

/* Counts cards */
.mini-card{ margin-top:12px; padding:14px; }
.mini-head{ display:flex; flex-direction:column; gap:4px; }
.mini-title{ font-weight:1000; font-size:14px; color:#0f172a; }
.mini-sub{ font-size:12px; color:#64748b; font-weight:700; }
.mini-loading{
  display:flex; gap:10px; align-items:center; justify-content:center;
  padding:14px; color:#64748b; font-weight:900;
}
.chips{
  margin-top:10px;
  display:flex;
  flex-wrap:wrap;
  gap:8px;
}
.chip{
  display:inline-flex; align-items:center; gap:10px;
  padding:8px 10px;
  border-radius:999px;
  border:1px solid #e2e8f0;
  background:#f8fafc;
  font-weight:900;
}
.chip-name{ color:#334155; font-size:12px; }
.chip-count{
  background:rgba(37,99,235,.12);
  border:1px solid rgba(37,99,235,.18);
  color:#1d4ed8;
  border-radius:999px;
  padding:4px 8px;
  font-size:12px;
}
.empty-chip{ color:#94a3b8; font-weight:900; }

/* Table */
.table-card{ margin-top:12px; overflow:hidden; }
.table-wrap{ overflow-x:auto; -webkit-overflow-scrolling:touch; }
.table{
  width:100%;
  border-collapse:collapse;
  min-width:980px;
}
.table thead th{
  position:sticky; top:0; z-index:2;
  background:linear-gradient(180deg,#fcfdff,#f2f6ff);
  padding:14px 16px;
  font-size:12px;
  font-weight:1000;
  color:#64748b;
  text-transform:uppercase;
  letter-spacing:.06em;
  border-bottom:1px solid #eef2f7;
  white-space:nowrap;
}
.table tbody td{
  padding:14px 16px;
  border-bottom:1px solid #f1f5f9;
  font-size:14px;
  vertical-align:middle;
}
.table tbody tr:hover{ background:#fbfdff; }
.right{ text-align:right; }
.col-photo{ width:84px; }

.avatar{
  width:44px; height:44px;
  border-radius:12px;
  border:1px solid #e2e8f0;
  overflow:hidden;
  display:grid;
  place-items:center;
  background:#f8fafc;
}
.avatar.lg{ width:56px; height:56px; border-radius:14px; }
.avatar img{ width:100%; height:100%; object-fit:cover; }
.avatar-fallback{ font-weight:1000; color:#475569; }

.name .full{ font-weight:1000; }
.name .sub{
  margin-top:2px;
  font-size:12px;
  color:#94a3b8;
  font-weight:700;
}
.mono{
  font-variant-numeric:tabular-nums;
  font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;
}
.row-actions{ display:inline-flex; gap:8px; justify-content:flex-end; }
.icon-btn{
  padding:8px 12px;
  border-radius:10px;
  border:1px solid #e2e8f0;
  background:#fff;
  font-weight:900;
  cursor:pointer;
}
.icon-btn:hover{ background:#f8fafc; }
.icon-btn.danger{
  border-color:rgba(239,68,68,.25);
  color:#b91c1c;
}
.icon-btn.danger:hover{ background:#fef2f2; }

.empty{
  text-align:center;
  padding:36px 16px;
  color:#94a3b8;
  font-weight:800;
}
.loading-row{
  display:inline-flex;
  align-items:center;
  gap:10px;
}
.spinner{
  width:18px; height:18px;
  border:2px solid #e2e8f0;
  border-top:2px solid #2563eb;
  border-radius:50%;
  animation:spin 1s linear infinite;
}
@keyframes spin{ to{ transform:rotate(360deg); } }

/* Pagination */
.pager{
  display:flex;
  gap:10px;
  align-items:center;
  justify-content:flex-end;
  padding:12px 14px;
  border-top:1px solid #eef2f7;
  background:#fff;
}
.pager.mobile{ justify-content:space-between; }
.pager-info{ color:#475569; font-weight:800; font-size:13px; }
.sep{ margin:0 8px; color:#cbd5e1; }
.perpage{ display:flex; align-items:center; gap:8px; font-weight:900; color:#475569; }
.perpage select{
  padding:8px 10px;
  border-radius:10px;
  border:1px solid #e2e8f0;
  font-weight:900;
  background:#fff;
}

/* Mobile */
.mobile-list{ margin-top:12px; display:none; }
.mobile-cards{ display:grid; gap:10px; }
.mobile-card{
  background:#fff;
  border:1px solid #eef2f7;
  border-radius:16px;
  padding:14px;
  box-shadow:0 10px 26px rgba(2,6,23,.05);
}
.mc-top{ display:flex; gap:12px; align-items:center; }
.mc-meta{ min-width:0; }
.mc-name{
  font-weight:1000;
  font-size:16px;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}
.mc-sub{
  margin-top:2px;
  color:#64748b;
  font-size:12px;
  font-weight:700;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}
.mc-pill{
  margin-top:8px;
  display:inline-flex;
  gap:8px;
  padding:6px 10px;
  border-radius:999px;
  background:#f8fafc;
  border:1px solid #e2e8f0;
  font-size:12px;
  font-weight:900;
  color:#475569;
}
.mc-row{
  margin-top:10px;
  display:flex;
  justify-content:space-between;
  gap:12px;
}
.mc-k{
  color:#94a3b8;
  font-weight:900;
  font-size:12px;
  text-transform:uppercase;
  letter-spacing:.06em;
}
.mc-v{
  text-align:right;
  font-weight:800;
  color:#334155;
  word-break:break-word;
}
.mc-actions{
  margin-top:12px;
  display:flex;
  justify-content:flex-end;
  gap:10px;
}
.mobile-empty{
  padding:22px;
  border:1px solid #eef2f7;
  border-radius:16px;
  background:#fff;
  box-shadow:0 10px 26px rgba(2,6,23,.05);
  display:flex;
  gap:10px;
  align-items:center;
  justify-content:center;
  color:#64748b;
  font-weight:800;
}

/* ✅ MODAL */
.app-modal-overlay{
  position: fixed;
  inset: 0;
  background: rgba(15,23,42,.72);
  backdrop-filter: blur(6px);
  z-index: 2000;

  display: grid;
  place-items: center;

  padding: 14px;
}

.app-modal{
  width: min(860px, calc(100vw - 28px));
  max-height: 92vh;
  overflow: hidden;

  border-radius: 18px;
  border: 1px solid rgba(226,232,240,.9);
  background: #fff;
  box-shadow: 0 30px 50px rgba(2,6,23,.22);

  display: flex;
  flex-direction: column;
}

.app-modal-sm{ width: min(720px, calc(100vw - 28px)); }
.app-modal-xs{ width: min(520px, calc(100vw - 28px)); }
.app-modal-lg{ width: min(860px, calc(100vw - 28px)); }

.modal-head{
  padding:16px;
  background:#f8fafc;
  border-bottom:1px solid #e2e8f0;
  display:flex;
  justify-content:space-between;
  align-items:center;
}
.modal-title{ display:flex; align-items:center; gap:12px; }
.badge{
  width:36px;
  height:36px;
  border-radius:12px;
  background:rgba(37,99,235,.12);
  border:1px solid rgba(37,99,235,.18);
  color:#1d4ed8;
  display:grid;
  place-items:center;
  font-weight:1000;
}
.mt{ font-weight:1000; font-size:16px; }
.ms{ font-size:12px; color:#64748b; font-weight:700; }
.x{
  width:38px; height:38px;
  border-radius:12px;
  border:1px solid #e2e8f0;
  background:#fff;
  cursor:pointer;
  font-weight:900;
  color:#64748b;
}
.x:hover{ background:#f1f5f9; }

/* ✅ keep modal scroll safe on small devices */
.modal-body{
  padding:16px;
  overflow:auto;
  max-height: calc(92vh - 86px);
}

.form{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:12px;
}
.field{ display:flex; flex-direction:column; gap:6px; min-width:0; }
.field.full{ grid-column:1 / -1; }
label{
  font-size:12px;
  font-weight:900;
  color:#334155;
  text-transform:uppercase;
  letter-spacing:.06em;
}
.req{ color:#ef4444; }
input,select{
  width:100%;
  padding:10px 12px;
  border-radius:12px;
  border:1px solid #e2e8f0;
  outline:none;
  font-weight:800;
  font-size:14px;
  background:#fff;
}
input:focus,select:focus{
  border-color:rgba(37,99,235,.55);
  box-shadow:0 0 0 4px rgba(37,99,235,.12);
}
small{ color:#64748b; font-size:12px; font-weight:600; }

.upload-row{ display:flex; gap:14px; align-items:center; flex-wrap:wrap; }
.preview2x2{
  width:150px; height:150px;
  border-radius:14px;
  border:1px solid #e2e8f0;
  overflow:hidden;
  background:#f8fafc;
  display:grid;
  place-items:center;
}
.preview2x2 img{ width:100%; height:100%; object-fit:cover; }
.preview2x2 .ph{ font-weight:1000; color:#94a3b8; }
.upload-controls{ display:flex; flex-direction:column; gap:8px; }

.actions{
  grid-column:1 / -1;
  display:flex;
  justify-content:flex-end;
  gap:10px;
  margin-top:6px;
}

/* Positions modal */
.pos-head{ display:flex; gap:10px; align-items:center; margin-bottom:12px; flex-wrap:wrap; }
.pos-list{ display:grid; gap:10px; }
.pos-item{
  display:grid;
  grid-template-columns:1fr auto;
  gap:10px;
  align-items:center;
}
.pos-actions{ display:inline-flex; gap:8px; }
.hint{ margin-top:6px; display:block; }

/* Modal transitions */
.modal-enter-active,.modal-leave-active{ transition:opacity .2s ease; }
.modal-enter-from,.modal-leave-to{ opacity:0; }

/* Responsive */
@media (max-width:900px){ .form{ grid-template-columns:1fr; } }
@media (max-width:760px){
  .table-card{ display:none; }
  .mobile-list{ display:block; }
  .header-actions{ width:100%; justify-content:stretch; }
  .search{ width:100%; }
  .btn{ width:100%; }
  .pager{ justify-content:space-between; flex-wrap:wrap; }
}
</style>
