<template>
  <div class="page">
    <!-- Header -->
    <div class="header">
      <div class="title">
        <h1>Employees</h1>
        <p>Manage employee records, user accounts, and profiles.</p>
      </div>

      <div class="header-actions">
        <div class="search">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
          <input v-model="search" placeholder="Search name / email / division..." />
        </div>

        <button class="btn btn-outline" @click="fetchAll" :disabled="loading">
          {{ loading ? "Loading..." : "Refresh" }}
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
        <div class="stat-value">{{ filteredEmployees.length }}</div>
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

    <!-- Desktop Table -->
    <div class="card table-card">
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th class="col-photo">Photo</th>
              <th>Name</th>
              <th>Email</th>
              <th>Position</th>
              <th>Division</th>
              <th class="right">Phone</th>
              <th class="right">Action</th>
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

            <tr v-else-if="filteredEmployees.length === 0">
              <td colspan="7" class="empty">No employees found.</td>
            </tr>

            <tr v-else v-for="emp in filteredEmployees" :key="emp.user_id">
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
                <button class="icon-btn danger" @click="remove(emp)">
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Mobile Cards -->
    <div class="mobile-list">
      <div v-if="loading" class="mobile-empty">
        <div class="spinner"></div>
        <span>Loading...</span>
      </div>

      <div v-else-if="filteredEmployees.length === 0" class="mobile-empty">
        No employees found.
      </div>

      <div v-else class="mobile-cards">
        <div class="mobile-card" v-for="emp in filteredEmployees" :key="emp.user_id">
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
            <button class="btn btn-outline danger" @click="remove(emp)">
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <Transition name="modal" appear>
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
          <div class="modal" @click.stop>
            <div class="modal-head">
              <div class="modal-title">
                <div class="badge">+</div>
                <div>
                  <div class="mt">Add Employee</div>
                  <div class="ms">Creates user + profile</div>
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
                      <div v-else class="ph">2×2</div>
                    </div>

                    <div class="upload-controls">
                      <input type="file" accept="image/*" @change="onFileChange" />
                      <small>Saved to <b>public/userprofile</b></small>
                    </div>
                  </div>
                </div>

                <!-- Names -->
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

                <!-- Position + Division -->
                <div class="field">
                  <label>Position <span class="req">*</span></label>
                  <select v-model="form.position" required>
                    <option value="" disabled>Select position</option>
                    <option v-for="p in positions" :key="p" :value="p">{{ p }}</option>
                  </select>
                </div>

                <div class="field">
                  <label>Division <span class="req">*</span></label>
                  <select v-model="form.division" required>
                    <option value="" disabled>Select division</option>
                    <option v-for="d in divisions" :key="d" :value="d">{{ d }}</option>
                  </select>
                  <small>Stored in DB as division <b>name</b>, not ID.</small>
                </div>

                <!-- ✅ NEW: Userlevel -->
              

                <!-- Phone + Address -->
                <div class="field">
                  <label>Phone</label>
                  <input v-model="form.phone" />
                </div>

                <div class="field">
                  <label>Address</label>
                  <input v-model="form.address" />
                </div>

                  <div class="field">
                  <label>User Level <span class="req">*</span></label>
                  <select v-model="form.userlevel" required>
                    <option value="" disabled>Select user level</option>
                    <option :value="1">1 - Admin</option>
                    <option :value="2">2 - Supply</option>
                    <option :value="3">3 - FO</option>
                  </select>
                </div>

                <!-- Account -->
                <div class="field">
                  <label>Email <span class="req">*</span></label>
                  <input v-model="form.email" type="email" required />
                </div>

                <div class="field">
                  <label>Password <span class="req">*</span></label>
                  <input v-model="form.password" type="password" required />
                  <small>Server will store this as a <b>hash</b>.</small>
                </div>

                <div class="actions">
                  <button type="button" class="btn btn-outline" @click="closeModal" :disabled="saving">
                    Cancel
                  </button>
                  <button type="submit" class="btn btn-primary" :disabled="saving">
                    {{ saving ? "Saving..." : "Save Employee" }}
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
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";
import { inject } from "vue";

const API_BASE = inject("API_BASE");
const router = useRouter();

const loading = ref(false);
const saving = ref(false);

const employees = ref([]);
const divisions = ref([]);

const positions = ref([
  "Project Development Officer",
  "Administrative Aide",
  "Administrative Assistant",
  "IT Officer",
  "HR Officer",
  "Accountant",
  "Staff",
]);

const search = ref("");

const showModal = ref(false);
const imgFile = ref(null);
const imgPreview = ref("");

const form = ref({
  first_name: "",
  middle_name: "",
  last_name: "",
  suffix: "",
  position: "",
  division: "",
  userlevel_id: "", // ✅ NEW
  phone: "",
  address: "",
  email: "",
  password: "",
});

const initials = (name) => {
  const s = String(name || "").trim();
  if (!s) return "U";
  const parts = s.split(/\s+/).slice(0, 2);
  return parts.map((p) => p[0]?.toUpperCase()).join("");
};

const filteredEmployees = computed(() => {
  const s = search.value.trim().toLowerCase();
  if (!s) return employees.value;

  return employees.value.filter((e) => {
    const name = String(e.full_name || "").toLowerCase();
    const email = String(e.email || "").toLowerCase();
    const div = String(e.division || "").toLowerCase();
    return name.includes(s) || email.includes(s) || div.includes(s);
  });
});

const resetForm = () => {
  form.value = {
    first_name: "",
    middle_name: "",
    last_name: "",
    suffix: "",
    position: "",
    division: "",
    userlevel_id: "", // ✅ NEW
    phone: "",
    address: "",
    email: "",
    password: "",
  };
  imgFile.value = null;
  imgPreview.value = "";
};

const openCreate = () => {
  resetForm();
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

const fetchEmployees = async () => {
  const res = await axios.get(`${API_BASE}/employees`);
  // if your backend returns { employees: ... } keep fallback:
  employees.value = Array.isArray(res.data) ? res.data : (res.data?.employees?.data ?? res.data?.employees ?? []);
};

const fetchAll = async () => {
  loading.value = true;
  try {
    await Promise.all([fetchDivisions(), fetchEmployees()]);
  } catch (err) {
    console.error(err);
    Swal.fire("Error", err.response?.data?.message || err.message, "error");
  } finally {
    loading.value = false;
  }
};

const submit = async () => {
  if (!form.value.position || !form.value.division) {
    Swal.fire("Required", "Position and Division are required.", "warning");
    return;
  }
  if (!form.value.userlevel_id) {
    Swal.fire("Required", "User Level is required.", "warning");
    return;
  }

  saving.value = true;
  try {
    const fd = new FormData();
    Object.entries(form.value).forEach(([k, v]) => fd.append(k, v ?? ""));
    if (imgFile.value) fd.append("profile_image", imgFile.value);

    await axios.post(`${API_BASE}/employees`, fd, {
      headers: { "Content-Type": "multipart/form-data" },
    });

    Swal.fire("Success", "Employee created successfully.", "success");
    closeModal();
    await fetchEmployees();
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
    Swal.fire("Deleted", "Employee removed.", "success");
    await fetchEmployees();
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
*::after {
  box-sizing: border-box;
}

.page {
  min-height: 100vh;
  background: #f8fafc;
  padding: clamp(12px, 2.2vw, 22px);
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
  color: #0f172a;
}

.card {
  background: #fff;
  border: 1px solid #eef2f7;
  border-radius: 16px;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.05);
}

/* Header */
.header {
  display: flex;
  gap: 16px;
  justify-content: space-between;
  align-items: flex-end;
  flex-wrap: wrap;
  padding: 18px;
  border-radius: 16px;
  border: 1px solid #eef2f7;
  background: #fff;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.05);
}

.title h1 {
  margin: 0;
  font-size: 26px;
  font-weight: 900;
  letter-spacing: -0.02em;
}

.title p {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
}

.header-actions {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.search {
  display: flex;
  align-items: center;
  gap: 8px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 10px 12px;
  background: #fff;
  min-width: min(420px, 100%);
}

.search svg {
  color: #94a3b8;
  flex: 0 0 auto;
}

.search input {
  border: none;
  outline: none;
  width: 100%;
  font-weight: 700;
  font-size: 14px;
  min-width: 0;
}

.btn {
  border: none;
  border-radius: 12px;
  padding: 10px 14px;
  font-weight: 900;
  cursor: pointer;
  transition: 0.2s ease;
  font-size: 14px;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #fff;
  box-shadow: 0 10px 22px rgba(37, 99, 235, 0.18);
}

.btn-primary:hover {
  transform: translateY(-1px);
}

.btn-outline {
  background: #fff;
  border: 1px solid #e2e8f0;
  color: #334155;
}

.btn-outline.danger {
  border-color: rgba(239, 68, 68, 0.3);
  color: #b91c1c;
}

.btn-outline:hover {
  background: #f8fafc;
}

/* Stats */
.stats {
  margin-top: 12px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 10px;
}

.stat {
  padding: 14px 16px;
  border-radius: 16px;
  border: 1px solid #eef2f7;
  background: #fff;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.05);
}

.stat-label {
  font-size: 12px;
  font-weight: 900;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.stat-value {
  margin-top: 6px;
  font-size: 22px;
  font-weight: 1000;
}

/* Table */
.table-card {
  margin-top: 12px;
  overflow: hidden;
}

.table-wrap {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.table {
  width: 100%;
  border-collapse: collapse;
  min-width: 920px;
}

.table thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background: linear-gradient(180deg, #fcfdff, #f2f6ff);
  padding: 14px 16px;
  font-size: 12px;
  font-weight: 1000;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  border-bottom: 1px solid #eef2f7;
  white-space: nowrap;
}

.table tbody td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 14px;
  vertical-align: middle;
}

.table tbody tr:hover {
  background: #fbfdff;
}

.right {
  text-align: right;
}

.col-photo {
  width: 84px;
}

.avatar {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  display: grid;
  place-items: center;
  background: #f8fafc;
}

.avatar.lg {
  width: 56px;
  height: 56px;
  border-radius: 14px;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-fallback {
  font-weight: 1000;
  color: #475569;
}

.name .full {
  font-weight: 1000;
}

.name .sub {
  margin-top: 2px;
  font-size: 12px;
  color: #94a3b8;
  font-weight: 700;
}

.mono {
  font-variant-numeric: tabular-nums;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}

.icon-btn {
  padding: 8px 12px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-weight: 900;
  cursor: pointer;
}

.icon-btn.danger {
  border-color: rgba(239, 68, 68, 0.25);
  color: #b91c1c;
}

.icon-btn.danger:hover {
  background: #fef2f2;
}

.empty {
  text-align: center;
  padding: 36px 16px;
  color: #94a3b8;
  font-weight: 800;
}

.loading-row {
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.spinner {
  width: 18px;
  height: 18px;
  border: 2px solid #e2e8f0;
  border-top: 2px solid #2563eb;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Mobile list */
.mobile-list {
  margin-top: 12px;
  display: none;
}

.mobile-cards {
  display: grid;
  gap: 10px;
}

.mobile-card {
  background: #fff;
  border: 1px solid #eef2f7;
  border-radius: 16px;
  padding: 14px;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.05);
}

.mc-top {
  display: flex;
  gap: 12px;
  align-items: center;
}

.mc-meta {
  min-width: 0;
}

.mc-name {
  font-weight: 1000;
  font-size: 16px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.mc-sub {
  margin-top: 2px;
  color: #64748b;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.mc-pill {
  margin-top: 8px;
  display: inline-flex;
  gap: 8px;
  padding: 6px 10px;
  border-radius: 999px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  font-size: 12px;
  font-weight: 900;
  color: #475569;
}

.mc-row {
  margin-top: 10px;
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.mc-k {
  color: #94a3b8;
  font-weight: 900;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.mc-v {
  text-align: right;
  font-weight: 800;
  color: #334155;
  word-break: break-word;
}

.mc-actions {
  margin-top: 12px;
  display: flex;
  justify-content: flex-end;
}

.mobile-empty {
  padding: 22px;
  border: 1px solid #eef2f7;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.05);
  display: flex;
  gap: 10px;
  align-items: center;
  justify-content: center;
  color: #64748b;
  font-weight: 800;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.72);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 14px;
  z-index: 2000;
}

.modal {
  width: 100%;
  max-width: 860px;
  max-height: 92vh;
  overflow: hidden;
  border-radius: 18px;
  border: 1px solid rgba(226, 232, 240, 0.9);
  background: #fff;
  box-shadow: 0 30px 50px rgba(2, 6, 23, 0.22);
  display: flex;
  flex-direction: column;
}

.modal-head {
  padding: 16px 16px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  display: flex;
  align-items: center;
  gap: 12px;
}

.badge {
  width: 36px;
  height: 36px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.12);
  border: 1px solid rgba(37, 99, 235, 0.18);
  color: #1d4ed8;
  display: grid;
  place-items: center;
  font-weight: 1000;
}

.mt {
  font-weight: 1000;
  font-size: 16px;
}

.ms {
  font-size: 12px;
  color: #64748b;
  font-weight: 700;
}

.x {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  font-weight: 900;
  color: #64748b;
}

.x:hover {
  background: #f1f5f9;
}

.modal-body {
  padding: 16px;
  overflow: auto;
}

.form {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}

.field.full {
  grid-column: 1 / -1;
}

label {
  font-size: 12px;
  font-weight: 900;
  color: #334155;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.req {
  color: #ef4444;
}

input, select {
  width: 100%;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  outline: none;
  font-weight: 800;
  font-size: 14px;
  background: #fff;
}

input:focus, select:focus {
  border-color: rgba(37, 99, 235, 0.55);
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}

small {
  color: #64748b;
  font-size: 12px;
  font-weight: 600;
}

.upload-row {
  display: flex;
  gap: 14px;
  align-items: center;
  flex-wrap: wrap;
}

.preview2x2 {
  width: 150px;
  height: 150px;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  background: #f8fafc;
  display: grid;
  place-items: center;
}

.preview2x2 img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.preview2x2 .ph {
  font-weight: 1000;
  color: #94a3b8;
}

.upload-controls {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.actions {
  grid-column: 1 / -1;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 6px;
}

/* Modal transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

/* Responsive switches */
@media (max-width: 900px) {
  .form {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 760px) {
  .table-card {
    display: none;
  }
  .mobile-list {
    display: block;
  }
  .header-actions {
    width: 100%;
    justify-content: stretch;
  }
  .search {
    width: 100%;
  }
  .btn {
    width: 100%;
  }
}
</style>
