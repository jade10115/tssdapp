<template>
  <div class="gip-wrap">

    <!-- ══ PAGE HEADER ══ -->
    <div class="pg-header">
      <div class="pg-title">
        <div class="pg-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <div>
          <h1>GIP Employees</h1>
          <p>Government Internship Program — DOLE Region VIII</p>
        </div>
      </div>
      <button class="btn-add" @click="openCreateModal">
        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
        Add GIP Employee
      </button>
    </div>

    <!-- ══ STATS ROW ══ -->
    <div class="stats-row">
      <div class="scard" v-for="s in statCards" :key="s.label" :style="{ '--ac': s.color }">
        <div class="scard-icon">{{ s.icon }}</div>
        <div class="scard-body">
          <div class="scard-val">{{ stats[s.key] ?? 0 }}</div>
          <div class="scard-lbl">{{ s.label }}</div>
        </div>
      </div>
    </div>

    <!-- ══ FILTER BAR (with Year dropdown) ══ -->
    <div class="filter-bar">
      <div class="search-wrap">
        <svg viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
        </svg>
        <input v-model="search" placeholder="Search by name, office..." />
      </div>
      <select v-model="filterStatus">
        <option value="">All Status</option>
        <option value="Active">Active</option>
        <option value="Completed">Completed</option>
        <option value="Terminated">Terminated</option>
        <option value="Resigned">Resigned</option>
      </select>
      <select v-model="filterOffice">
        <option value="">All Offices</option>
        <option v-for="o in offices" :key="o.id" :value="o.id">{{ o.name }}</option>
      </select>
      <!-- Year dropdown -->
      <select v-model="selectedYear" @change="refreshAll">
        <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
      </select>
    </div>

    <!-- ══ TABLE ══ -->
    <div class="tbl-card">
      <div class="tbl-wrap">
        <table>
          <thead>
            <tr>
              <th>Applicant</th>
              <th>Gender</th>
              <th>Office</th>
              <th>Contract Period</th>
              <th class="tc">Months Left</th>
              <th class="tc">Status</th>
              <th class="tc">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="7" class="t-empty"><div class="loader"></div> Loading...</td></tr>
            <tr v-else-if="!filteredGips.length"><td colspan="7" class="t-empty">No GIP employees found.</td></tr>
            <tr v-else v-for="g in filteredGips" :key="g.id">
              <td>
                <div class="emp-cell">
                  <!-- *** PHOTO OR INITIALS *** -->
                  <div class="emp-avatar" :class="[g.photo_url ? 'has-photo' : (g.gender === 'Female' ? 'f' : 'm')]">
                    <img v-if="g.photo_url" :src="g.photo_url" alt="photo" class="avatar-img" />
                    <span v-else>{{ initials(g) }}</span>
                  </div>
                  <div>
                    <div class="emp-name">{{ g.full_name }}</div>
                    <div class="emp-sub">{{ g.email || '—' }}</div>
                  </div>
                </div>
              </td>
              <td>
                <span class="chip" :class="g.gender === 'Female' ? 'chip-pink' : 'chip-blue'">
                  {{ g.gender }}
                </span>
              </td>
              <td>{{ g.office?.name || '—' }}</td>
              <td>
                <div class="contract-cell">
                  <span>{{ fmtDate(g.contract_start_date) }} – {{ fmtDate(g.contract_end_date) }}</span>
                  <span v-if="g.renewal_count > 0" class="chip chip-gold">Renewed ×{{ g.renewal_count }}</span>
                </div>
              </td>
              <td class="tc">
                <!-- Remainder shown as whole number (always integer from backend, but we enforce floor just in case) -->
                <span class="months-pill" :class="monthsClass(Math.floor(g.remaining_months))">
                  {{ Math.floor(g.remaining_months) }}mo
                </span>
              </td>
              <td class="tc">
                <span class="status-pill" :class="statusClass(g.status)">{{ g.status }}</span>
              </td>
              <td class="tc">
                <div class="act-btns">
                  <button class="ab view" @click="viewGip(g)" title="View">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                  </button>
                  <button class="ab edit" @click="openEditModal(g)" title="Edit">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                  </button>
                  <button v-if="g.status === 'Active'" class="ab renew" @click="openRenewModal(g)" title="Renew">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/></svg>
                  </button>
                  <button v-if="g.status === 'Active'" class="ab resign" @click="openResignModal(g)" title="Resign">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                  </button>
                  <button class="ab del" @click="deleteGip(g)" title="Delete">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══════════ APPLICATION FORM MODAL (full) ═══════════ -->
    <Teleport to="body">
      <Transition name="mfade">
        <div v-if="showFormModal" class="m-overlay" @click.self="closeFormModal">
          <div class="m-container">

            <!-- Modal Header -->
            <div class="m-top">
              <div class="m-top-left">
                <div class="annex-tag">ANNEX A</div>
                <div class="m-top-title">
                  <div class="dole-labels">
                    <span class="dole-ro">DOLE REGIONAL OFFICE NO. VIII</span>
                    <span class="dole-prog">GOVERNMENT INTERNSHIP PROGRAM (GIP)</span>
                    <span class="dole-form">APPLICATION FORM</span>
                  </div>
                </div>
              </div>
              <button class="m-close" @click="closeFormModal">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
              </button>
            </div>

            <!-- Step Indicators -->
            <div class="steps-bar">
              <div
                v-for="(step, i) in steps"
                :key="i"
                class="step-item"
                :class="{ active: currentStep === i, done: currentStep > i }"
                @click="goToStep(i)"
              >
                <div class="step-bubble">
                  <svg v-if="currentStep > i" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                  <span v-else>{{ i + 1 }}</span>
                </div>
                <span class="step-label">{{ step }}</span>
              </div>
            </div>

            <!-- Instruction Banner (Step 0 only) -->
            <div v-if="currentStep === 0" class="instruction-banner">
              <strong>INSTRUCTION TO APPLICANTS:</strong> Please fill out all the required information in this form and attach additional documents, if necessary.
            </div>

            <!-- ── STEP 0: Personal Information ── -->
            <div v-show="currentStep === 0" class="m-body">
              <div class="form-section-title">1. NAME OF APPLICANT</div>
              <div class="name-photo-row">
                <div class="name-fields">
                  <div class="three-cols">
                    <div class="fg">
                      <label>Family Name <span class="req">*</span></label>
                      <input v-model="form.family_name" class="fi" placeholder="e.g. Dela Cruz" required />
                    </div>
                    <div class="fg">
                      <label>First Name <span class="req">*</span></label>
                      <input v-model="form.first_name" class="fi" placeholder="e.g. Juan" required />
                    </div>
                    <div class="fg">
                      <label>Middle Name</label>
                      <input v-model="form.middle_name" class="fi" placeholder="e.g. Santos" />
                    </div>
                  </div>
                </div>
                <div class="photo-box">
                  <div class="photo-preview" @click="$refs.photoInput.click()">
                    <img v-if="photoPreview" :src="photoPreview" alt="Photo" />
                    <div v-else class="photo-placeholder">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                      <span>2×2 Photo</span>
                    </div>
                    <input ref="photoInput" type="file" accept="image/*" class="hidden" @change="onPhoto" />
                  </div>
                  <p class="photo-hint">Attach 2×2 photo with name and signature taken within the last 3 months</p>
                </div>
              </div>

              <div class="form-section-title">2. RESIDENTIAL ADDRESS</div>
              <div class="fg">
                <label>Complete Address</label>
                <textarea v-model="form.residential_address" class="fi ta" rows="2" placeholder="House No., Street, Barangay, Municipality/City, Province"></textarea>
              </div>
              <div class="three-cols">
                <div class="fg">
                  <label>Telephone No.</label>
                  <input v-model="form.telephone_no" class="fi" placeholder="(053) 000-0000" />
                </div>
                <div class="fg">
                  <label>Mobile No.</label>
                  <input v-model="form.mobile_no" class="fi" placeholder="09XX XXX XXXX" />
                </div>
                <div class="fg">
                  <label>E-mail Address</label>
                  <input v-model="form.email" type="email" class="fi" placeholder="email@example.com" />
                </div>
              </div>

              <div class="two-cols">
                <div>
                  <div class="form-section-title">3. PLACE OF BIRTH (city/province)</div>
                  <input v-model="form.place_of_birth" class="fi" placeholder="e.g. Tacloban City, Leyte" />
                </div>
                <div>
                  <div class="form-section-title">4. DATE OF BIRTH (mm/dd/yyyy)</div>
                  <input v-model="form.date_of_birth" type="date" class="fi" />
                </div>
              </div>

              <div class="two-cols mt-s">
                <div>
                  <div class="form-section-title">5. GENDER</div>
                  <div class="checkbox-row">
                    <label class="chk-label" :class="{ selected: form.gender === 'Male' }" @click="form.gender = 'Male'">
                      <div class="chk-box" :class="{ checked: form.gender === 'Male' }"></div> Male
                    </label>
                    <label class="chk-label" :class="{ selected: form.gender === 'Female' }" @click="form.gender = 'Female'">
                      <div class="chk-box" :class="{ checked: form.gender === 'Female' }"></div> Female
                    </label>
                  </div>
                </div>
                <div>
                  <div class="form-section-title">6. CIVIL STATUS</div>
                  <div class="checkbox-row">
                    <label v-for="cs in ['Single','Married','Widow/Widower']" :key="cs"
                      class="chk-label" :class="{ selected: form.civil_status === cs }"
                      @click="form.civil_status = cs">
                      <div class="chk-box" :class="{ checked: form.civil_status === cs }"></div> {{ cs }}
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- ── STEP 1: Education & Work ── -->
            <div v-show="currentStep === 1" class="m-body">
              <div class="form-section-title">7. EDUCATIONAL ATTAINMENT</div>
              <div class="grid-table">
                <div class="gt-head">
                  <div class="gt-cell" style="flex:2">Name of School</div>
                  <div class="gt-cell">From</div>
                  <div class="gt-cell">To</div>
                  <div class="gt-cell" style="flex:1.5">Degree or Diploma</div>
                  <div class="gt-cell" style="flex:0 0 40px"></div>
                </div>
                <div class="gt-row" v-for="(ed, i) in form.educational_attainment" :key="i">
                  <div class="gt-cell" style="flex:2"><input v-model="ed.school" class="fi-sm" placeholder="School name" /></div>
                  <div class="gt-cell"><input v-model="ed.from" type="date" class="fi-sm" /></div>
                  <div class="gt-cell"><input v-model="ed.to" type="date" class="fi-sm" /></div>
                  <div class="gt-cell" style="flex:1.5"><input v-model="ed.degree" class="fi-sm" placeholder="e.g. BS Nursing" /></div>
                  <div class="gt-cell" style="flex:0 0 40px">
                    <button class="rm-btn" @click="form.educational_attainment.splice(i,1)">✕</button>
                  </div>
                </div>
                <button class="add-row-btn" @click="form.educational_attainment.push({school:'',from:'',to:'',degree:''})">
                  + Add School
                </button>
              </div>

              <div class="form-section-title mt-s">8. WORK EXPERIENCE</div>
              <div class="grid-table">
                <div class="gt-head">
                  <div class="gt-cell" style="flex:2">Name of Company</div>
                  <div class="gt-cell" style="flex:1.5">Position</div>
                  <div class="gt-cell" style="flex:1.5">Period of Engagement</div>
                  <div class="gt-cell" style="flex:0 0 40px"></div>
                </div>
                <div class="gt-row" v-for="(we, i) in form.work_experience" :key="i">
                  <div class="gt-cell" style="flex:2"><input v-model="we.company" class="fi-sm" placeholder="Company name" /></div>
                  <div class="gt-cell" style="flex:1.5"><input v-model="we.position" class="fi-sm" placeholder="Position" /></div>
                  <div class="gt-cell" style="flex:1.5"><input v-model="we.period" class="fi-sm" placeholder="e.g. Jan 2023 – Dec 2023" /></div>
                  <div class="gt-cell" style="flex:0 0 40px">
                    <button class="rm-btn" @click="form.work_experience.splice(i,1)">✕</button>
                  </div>
                </div>
                <button class="add-row-btn" @click="form.work_experience.push({company:'',position:'',period:''})">
                  + Add Work Experience
                </button>
              </div>
            </div>

            <!-- ── STEP 2: Vulnerable Group & Program ── -->
            <div v-show="currentStep === 2" class="m-body">
              <div class="form-section-title">8. DISADVANTAGED OR VULNERABLE GROUP</div>
              <div class="vuln-grid">
                <label class="vuln-chk" v-for="vg in vulnerableGroups" :key="vg.key">
                  <div class="chk-box lg" :class="{ checked: form[vg.key] }" @click="form[vg.key] = !form[vg.key]"></div>
                  <span>{{ vg.label }}</span>
                </label>
              </div>
              <div class="fg mt-s">
                <label>Others (please specify)</label>
                <input v-model="form.other_vulnerable_group" class="fi" placeholder="Specify other group..." />
              </div>

              <div class="divider"></div>
              <div class="form-section-title">PROGRAM ASSIGNMENT</div>
              <div class="two-cols">
                <div class="fg">
                  <label>Office Assigned <span class="req">*</span></label>
                  <select v-model="form.office_id" class="fi">
                    <option :value="null">— Select Office —</option>
                    <option v-for="o in offices" :key="o.id" :value="o.id">{{ o.name }}</option>
                  </select>
                </div>
                <div class="fg">
                  <label>Status</label>
                  <select v-model="form.status" class="fi">
                    <option value="Active">Active</option>
                    <option value="Completed">Completed</option>
                    <option value="Terminated">Terminated</option>
                  </select>
                </div>
              </div>
              <div class="two-cols">
                <div class="fg">
                  <label>Contract Start Date <span class="req">*</span></label>
                  <input v-model="form.contract_start_date" type="date" class="fi" required />
                </div>
                <div class="fg">
                  <label>Contract End Date <span class="req">*</span></label>
                  <input v-model="form.contract_end_date" type="date" class="fi" required />
                </div>
              </div>
              <div v-if="monthWarning" class="warn-box">⚠ {{ monthWarning }}</div>
            </div>

            <!-- ── STEP 3: Emergency, GSIS & DOLE Use ── -->
            <div v-show="currentStep === 3" class="m-body">
              <div class="two-cols">
                <div>
                  <div class="form-section-title">IN CASE OF EMERGENCY, PLEASE NOTIFY:</div>
                  <div class="fg"><label>Name</label><input v-model="form.emergency_contact_name" class="fi" /></div>
                  <div class="fg"><label>Contact Details</label><input v-model="form.emergency_contact_details" class="fi" /></div>
                  <div class="fg"><label>Address</label><textarea v-model="form.emergency_contact_address" class="fi ta" rows="2"></textarea></div>
                </div>
                <div>
                  <div class="form-section-title">GSIS BENEFICIARY (PARENT/CHILD'S NAME)</div>
                  <div class="fg"><label>Name of Beneficiary</label><input v-model="form.gsis_beneficiary_name" class="fi" /></div>
                  <div class="fg"><label>Relationship</label><input v-model="form.gsis_beneficiary_relationship" class="fi" /></div>
                </div>
              </div>

              <div class="dole-use-section">
                <div class="dole-use-header">— FOR DOLE-RO/FO USE ONLY —</div>
                <div class="two-cols">
                  <div class="fg"><label>Interviewed and Validated By</label><input v-model="form.interviewed_by" class="fi" /></div>
                  <div class="fg"><label>Date Accomplished</label><input v-model="form.date_accomplished" type="date" class="fi" /></div>
                </div>
                <div class="form-section-title mt-s">DOCUMENTS RECEIVED:</div>
                <div class="docs-grid">
                  <label v-for="doc in docsList" :key="doc.key" class="doc-chk">
                    <div class="chk-box" :class="{ checked: form[doc.key] }" @click="form[doc.key] = !form[doc.key]"></div>
                    {{ doc.label }}
                  </label>
                </div>
                <div class="fg mt-s">
                  <label>Others (documents)</label>
                  <input v-model="form.doc_other" class="fi" placeholder="Specify other documents received" />
                </div>
                <div class="fg mt-s">
                  <label>PSOC Code (Based on Assignment/Partner Agency)</label>
                  <input v-model="form.psoc_code" class="fi" placeholder="Enter PSOC Code" />
                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="m-footer">
              <button class="btn-sec" @click="currentStep > 0 ? currentStep-- : closeFormModal()">
                {{ currentStep > 0 ? '← Back' : 'Cancel' }}
              </button>
              <div class="step-dots">
                <div v-for="(_, i) in steps" :key="i" class="sdot" :class="{ active: i === currentStep }"></div>
              </div>
              <button
                v-if="currentStep < steps.length - 1"
                class="btn-pri"
                @click="nextStep"
              >Next →</button>
              <button v-else class="btn-pri" :disabled="saving" @click="saveGip">
                <svg v-if="saving" class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>
                {{ saving ? 'Saving…' : (editing ? 'Update Record' : 'Submit Application') }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- VIEW MODAL -->
    <Teleport to="body">
      <Transition name="mfade">
        <div v-if="showViewModal" class="m-overlay" @click.self="showViewModal = false">
          <div class="m-container view-modal">
            <div class="m-top">
              <div class="m-top-left">
                <div class="annex-tag">ANNEX A</div>
                <div class="dole-labels">
                  <span class="dole-ro">DOLE REGIONAL OFFICE NO. VIII</span>
                  <span class="dole-prog">GOVERNMENT INTERNSHIP PROGRAM (GIP)</span>
                </div>
              </div>
              <button class="m-close" @click="showViewModal = false">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
              </button>
            </div>
            <div class="m-body view-body" v-if="viewingGip">
              <div class="view-header-row">
                <div class="view-photo">
                  <img v-if="viewingGip.photo_url" :src="viewingGip.photo_url" />
                  <div v-else class="photo-placeholder sm">{{ initials(viewingGip) }}</div>
                </div>
                <div class="view-name-block">
                  <h2>{{ viewingGip.full_name }}</h2>
                  <div class="view-chips">
                    <span class="chip" :class="viewingGip.gender === 'Female' ? 'chip-pink' : 'chip-blue'">{{ viewingGip.gender }}</span>
                    <span class="status-pill" :class="statusClass(viewingGip.status)">{{ viewingGip.status }}</span>
                  </div>
                  <p>{{ viewingGip.office?.name || 'No Office Assigned' }}</p>
                </div>
              </div>
              <div class="view-grid">
                <div class="vg-item"><span class="vg-lbl">Mobile</span><span>{{ viewingGip.mobile_no || '—' }}</span></div>
                <div class="vg-item"><span class="vg-lbl">Email</span><span>{{ viewingGip.email || '—' }}</span></div>
                <div class="vg-item"><span class="vg-lbl">Date of Birth</span><span>{{ fmtDate(viewingGip.date_of_birth) || '—' }}</span></div>
                <div class="vg-item"><span class="vg-lbl">Place of Birth</span><span>{{ viewingGip.place_of_birth || '—' }}</span></div>
                <div class="vg-item"><span class="vg-lbl">Civil Status</span><span>{{ viewingGip.civil_status || '—' }}</span></div>
                <div class="vg-item"><span class="vg-lbl">Address</span><span>{{ viewingGip.residential_address || '—' }}</span></div>
                <div class="vg-item"><span class="vg-lbl">Contract Start</span><span>{{ fmtDate(viewingGip.contract_start_date) }}</span></div>
                <div class="vg-item"><span class="vg-lbl">Contract End</span><span>{{ fmtDate(viewingGip.contract_end_date) }}</span></div>
                <div class="vg-item"><span class="vg-lbl">Months Remaining</span><span>{{ viewingGip.remaining_months }} months</span></div>
                <div class="vg-item"><span class="vg-lbl">Renewals</span><span>{{ viewingGip.renewal_count }}</span></div>
              </div>
            </div>
            <div class="m-footer">
              <button class="btn-sec" @click="showViewModal = false">Close</button>
              <button class="btn-pri" @click="openEditModal(viewingGip); showViewModal = false">Edit Record</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- RENEW MODAL -->
    <Teleport to="body">
      <Transition name="mfade">
        <div v-if="showRenewModal" class="m-overlay" @click.self="closeRenewModal">
          <div class="m-container" style="max-width:500px">
            <div class="m-top">
              <div class="m-top-left">
                <div class="dole-labels"><span class="dole-prog">Renew Contract</span></div>
              </div>
              <button class="m-close" @click="closeRenewModal"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></button>
            </div>
            <div class="m-body">
              <div class="renew-info" v-if="selectedGip">
                <div class="renew-emp">{{ selectedGip.full_name }}</div>
                <div class="renew-dates">
                  <span>Current End: <strong>{{ fmtDate(selectedGip.contract_end_date) }}</strong></span>
                  <span>Months Used: <strong>{{ selectedGip.total_months_used ?? '—' }}</strong> / 12</span>
                </div>
              </div>
              <div class="fg mt-s">
                <label>New End Date <span class="req">*</span></label>
                <input v-model="renewEndDate" type="date" class="fi" />
              </div>
              <div class="info-box">ℹ Maximum total contract length is 12 months from original start date.</div>
              <div v-if="renewError" class="error-box">{{ renewError }}</div>
            </div>
            <div class="m-footer">
              <button class="btn-sec" @click="closeRenewModal">Cancel</button>
              <button class="btn-pri" @click="renewContract" :disabled="!renewEndDate || saving">
                {{ saving ? 'Renewing…' : 'Renew Contract' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- RESIGN MODAL -->
    <Teleport to="body">
      <Transition name="mfade">
        <div v-if="showResignModal" class="m-overlay" @click.self="closeResignModal">
          <div class="m-container" style="max-width:500px">
            <div class="m-top">
              <div class="m-top-left">
                <div class="dole-labels"><span class="dole-prog">Resignation</span></div>
              </div>
              <button class="m-close" @click="closeResignModal"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></button>
            </div>
            <div class="m-body">
              <div class="renew-info" v-if="selectedGip">
                <div class="renew-emp">{{ selectedGip.full_name }}</div>
                <div class="renew-dates">
                  <span>Current End: <strong>{{ fmtDate(selectedGip.contract_end_date) }}</strong></span>
                  <span>Months Used: <strong>{{ selectedGip.total_months_used ?? '—' }}</strong> / 12</span>
                </div>
              </div>
              <div class="fg mt-s">
                <label>Resignation Date <span class="req">*</span></label>
                <input v-model="resignDate" type="date" class="fi" />
              </div>
              <div v-if="resignError" class="error-box">{{ resignError }}</div>
            </div>
            <div class="m-footer">
              <button class="btn-sec" @click="closeResignModal">Cancel</button>
              <button class="btn-pri" @click="confirmResign" :disabled="!resignDate || saving">
                {{ saving ? 'Processing…' : 'Confirm Resignation' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Swal from 'sweetalert2'
// Adjust the path '../axios.js' depending on where your axios.js is located relative to these components.
import axios from '../../axios.js'

// Keep the /api prefix so it appends correctly to your https://tssdapp-1.onrender.com baseURL
const API = '/api'


// ── State ──────────────────────────────────────────────────────
const gips        = ref([])
const offices     = ref([])
const stats       = ref({ total: 0, active: 0, female: 0, male: 0, pwd: 0, '4ps': 0 })
const loading     = ref(false)
const saving      = ref(false)
const search      = ref('')
const filterStatus  = ref('')
const filterOffice  = ref('')
const selectedYear  = ref(new Date().getFullYear())
const availableYears = ref([])

const showFormModal  = ref(false)
const showViewModal  = ref(false)
const showRenewModal = ref(false)
const showResignModal = ref(false)
const editing        = ref(false)
const selectedGip    = ref(null)
const viewingGip     = ref(null)
const currentStep    = ref(0)
const photoPreview   = ref(null)
const photoFile      = ref(null)

const renewEndDate = ref('')
const renewError   = ref('')
const resignDate   = ref('')
const resignError  = ref('')

const blankForm = () => ({
  family_name: '', first_name: '', middle_name: '',
  residential_address: '', telephone_no: '', mobile_no: '', email: '',
  place_of_birth: '', date_of_birth: '',
  gender: 'Male', civil_status: 'Single',
  educational_attainment: [{ school: '', from: '', to: '', degree: '' }],
  work_experience: [{ company: '', position: '', period: '' }],
  is_pwd: false, is_ip: false, is_disaster_victim: false,
  is_armed_conflict_victim: false, is_rebel_returnee: false, is_4ps_beneficiary: false,
  other_vulnerable_group: '',
  office_id: null, status: 'Active',
  contract_start_date: '', contract_end_date: '',
  emergency_contact_name: '', emergency_contact_details: '', emergency_contact_address: '',
  gsis_beneficiary_name: '', gsis_beneficiary_relationship: '',
  interviewed_by: '', date_accomplished: '',
  doc_birth_cert: false, doc_transcript: false, doc_barangay_cert: false,
  doc_form_137: false, doc_diploma: false, doc_school_cert: false, doc_other: '',
  psoc_code: '',
})
const form = ref(blankForm())

const steps = ['Personal Info', 'Education & Work', 'Program Details', 'Documents & DOLE']

const statCards = [
  { key: 'total',  label: 'Total GIP',  icon: '👥', color: '#2563eb' },
  { key: 'active', label: 'Active',     icon: '✅', color: '#16a34a' },
  { key: 'female', label: 'Female',     icon: '👩', color: '#db2777' },
  { key: 'male',   label: 'Male',       icon: '👨', color: '#0369a1' },
  { key: 'pwd',    label: 'PWDs',       icon: '♿', color: '#7c3aed' },
  { key: '4ps',    label: '4Ps Beneficiary', icon: '🏠', color: '#b45309' },
]

const vulnerableGroups = [
  { key: 'is_pwd',                  label: 'PWDs (Persons with Disabilities)' },
  { key: 'is_ip',                   label: 'IPs (Indigenous Peoples)' },
  { key: 'is_disaster_victim',      label: 'Victims of Natural or Man-Made Disasters' },
  { key: 'is_armed_conflict_victim',label: 'Victims of Armed Conflict' },
  { key: 'is_rebel_returnee',       label: 'Rebel Returnee' },
  { key: 'is_4ps_beneficiary',      label: '4Ps Beneficiary' },
]

const docsList = [
  { key: 'doc_birth_cert',   label: 'Birth Certificate or Equivalent' },
  { key: 'doc_transcript',   label: 'Transcript of Records' },
  { key: 'doc_barangay_cert',label: 'Barangay Certification' },
  { key: 'doc_form_137',     label: 'Form 137/138' },
  { key: 'doc_diploma',      label: 'Diploma' },
  { key: 'doc_school_cert',  label: 'Certification from School or equivalent' },
]

// ── Computed ───────────────────────────────────────────────────
const filteredGips = computed(() => {
  let list = [...gips.value]
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(g =>
      g.full_name?.toLowerCase().includes(q) ||
      g.email?.toLowerCase().includes(q) ||
      g.office?.name?.toLowerCase().includes(q)
    )
  }
  if (filterStatus.value) list = list.filter(g => g.status === filterStatus.value)
  if (filterOffice.value) list = list.filter(g => g.office_id === filterOffice.value)
  return list
})

const monthWarning = computed(() => {
  const s = form.value.contract_start_date
  const e = form.value.contract_end_date
  if (!s || !e) return null
  const diff = (new Date(e) - new Date(s)) / (1000 * 60 * 60 * 24 * 30.4375)
  if (diff > 12) return 'Contract period exceeds the 12-month maximum!'
  return null
})

// ── Helpers ────────────────────────────────────────────────────
const initials = (g) => {
  const fn = (g.first_name || g.full_name || '?')[0] || '?'
  const ln = g.family_name ? g.family_name[0] : ''
  return (fn + ln).toUpperCase()
}

const fmtDate = (d) =>
  d ? new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) : ''

const statusClass = (s) => ({
  'Active': 'sp-green', 'Completed': 'sp-blue', 'Terminated': 'sp-red', 'Resigned': 'sp-orange'
}[s] || 'sp-gray')

const monthsClass = (m) => m <= 2 ? 'mp-red' : m <= 5 ? 'mp-yellow' : 'mp-green'

const goToStep = (i) => { if (i < currentStep.value || validateStep(currentStep.value)) currentStep.value = i }

const nextStep = () => {
  if (validateStep(currentStep.value)) currentStep.value++
}

const validateStep = (step) => {
  if (step === 0 && (!form.value.family_name || !form.value.first_name)) {
    Swal.fire({ title: 'Required', text: 'Please fill in first and family name.', icon: 'warning', confirmButtonColor: '#003087' })
    return false
  }
  if (step === 2 && (!form.value.contract_start_date || !form.value.contract_end_date)) {
    Swal.fire({ title: 'Required', text: 'Please enter contract start and end dates.', icon: 'warning', confirmButtonColor: '#003087' })
    return false
  }
  return true
}

const onPhoto = (e) => {
  const file = e.target.files[0]
  if (!file) return
  photoFile.value = file
  photoPreview.value = URL.createObjectURL(file)
}

// ── API ────────────────────────────────────────────────────────
const fetchAll = async () => {
  loading.value = true
  try {
    const [gRes, oRes, sRes] = await Promise.all([
      axios.get(`${API}/gip/employees?year=${selectedYear.value}`),
      axios.get(`${API}/gip/offices`),
      axios.get(`${API}/gip/employees/stats?year=${selectedYear.value}`),
    ])
    gips.value    = gRes.data
    offices.value = oRes.data
    stats.value   = sRes.data
  } catch (e) { console.error(e) }
  finally { loading.value = false }
}

const fetchYears = async () => {
  try {
    const res = await axios.get(`${API}/gip/employees/years`)
    availableYears.value = res.data
    if (!availableYears.value.includes(selectedYear.value)) {
      selectedYear.value = availableYears.value[0] || new Date().getFullYear()
    }
  } catch (e) { console.error(e); availableYears.value = [new Date().getFullYear()] }
}

const refreshAll = async () => {
  await Promise.all([fetchAll(), fetchYears()])
}

// duplicate check
const checkDuplicate = async () => {
  if (!form.value.family_name || !form.value.first_name || !form.value.date_of_birth) return true
  try {
    const { data } = await axios.post(`${API}/gip/employees/check-duplicate`, {
      family_name: form.value.family_name,
      first_name: form.value.first_name,
      middle_name: form.value.middle_name,
      date_of_birth: form.value.date_of_birth,
    })
    if (data.exists) {
      const records = data.records.map(r => `- ${r.full_name} | ${r.total_months_used} months used | Status: ${r.status}`).join('\n')
      const proceed = await Swal.fire({
        title: 'Duplicate GIP Found',
        html: `<p><b>This person already exists in the system:</b></p><pre>${records}</pre><p>Do you still want to add a new record?</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, add anyway',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#003087',
      })
      return proceed.isConfirmed
    }
    return true
  } catch (e) {
    console.error(e)
    return true
  }
}

const saveGip = async () => {
  if (!editing.value) {
    const ok = await checkDuplicate()
    if (!ok) return
  }

  saving.value = true
  try {
    const fd = new FormData()
    Object.entries(form.value).forEach(([k, v]) => {
      if (Array.isArray(v)) fd.append(k, JSON.stringify(v))
      else if (typeof v === 'boolean') fd.append(k, v ? '1' : '0')
      else if (v !== null && v !== undefined) fd.append(k, v)
    })
    if (photoFile.value) fd.append('photo', photoFile.value)

    if (editing.value) {
      fd.append('_method', 'PUT')
      await axios.post(`${API}/gip/employees/${selectedGip.value.id}`, fd)
      Swal.fire({ title: 'Updated!', text: 'GIP record updated successfully.', icon: 'success', confirmButtonColor: '#003087' })
    } else {
      await axios.post(`${API}/gip/employees`, fd)
      Swal.fire({ title: 'Saved!', text: 'GIP employee added successfully.', icon: 'success', confirmButtonColor: '#003087' })
    }
    closeFormModal()
    await fetchAll()
  } catch (err) {
    const msg = err.response?.data?.message || err.response?.data?.error || 'Error saving GIP record.'
    Swal.fire({ title: 'Error', text: msg, icon: 'error', confirmButtonColor: '#003087' })
  } finally { saving.value = false }
}

const openCreateModal = () => {
  editing.value     = false
  form.value        = blankForm()
  photoPreview.value = null
  photoFile.value   = null
  currentStep.value = 0
  showFormModal.value = true
}

const openEditModal = (g) => {
  editing.value     = true
  selectedGip.value = g
  form.value        = {
    ...blankForm(),
    family_name: g.family_name || '',
    first_name:  g.first_name  || '',
    middle_name: g.middle_name || '',
    residential_address: g.residential_address || '',
    telephone_no: g.telephone_no || '',
    mobile_no: g.mobile_no || '',
    email: g.email || '',
    place_of_birth: g.place_of_birth || '',
    date_of_birth: g.date_of_birth || '',
    gender: g.gender || 'Male',
    civil_status: g.civil_status || 'Single',
    educational_attainment: g.educational_attainment?.length
      ? g.educational_attainment
      : [{ school: '', from: '', to: '', degree: '' }],
    work_experience: g.work_experience?.length
      ? g.work_experience
      : [{ company: '', position: '', period: '' }],
    is_pwd: !!g.is_pwd, is_ip: !!g.is_ip, is_disaster_victim: !!g.is_disaster_victim,
    is_armed_conflict_victim: !!g.is_armed_conflict_victim, is_rebel_returnee: !!g.is_rebel_returnee,
    is_4ps_beneficiary: !!g.is_4ps_beneficiary, other_vulnerable_group: g.other_vulnerable_group || '',
    office_id: g.office_id, status: g.status || 'Active',
    contract_start_date: g.contract_start_date || '', contract_end_date: g.contract_end_date || '',
    emergency_contact_name: g.emergency_contact_name || '',
    emergency_contact_details: g.emergency_contact_details || '',
    emergency_contact_address: g.emergency_contact_address || '',
    gsis_beneficiary_name: g.gsis_beneficiary_name || '',
    gsis_beneficiary_relationship: g.gsis_beneficiary_relationship || '',
    interviewed_by: g.interviewed_by || '', date_accomplished: g.date_accomplished || '',
    doc_birth_cert: !!g.doc_birth_cert, doc_transcript: !!g.doc_transcript,
    doc_barangay_cert: !!g.doc_barangay_cert, doc_form_137: !!g.doc_form_137,
    doc_diploma: !!g.doc_diploma, doc_school_cert: !!g.doc_school_cert,
    doc_other: g.doc_other || '', psoc_code: g.psoc_code || '',
  }
  photoPreview.value = g.photo_url || null
  photoFile.value   = null
  currentStep.value = 0
  showFormModal.value = true
}

const viewGip = (g) => { viewingGip.value = g; showViewModal.value = true }

const deleteGip = async (g) => {
  const res = await Swal.fire({
    title: 'Delete GIP Employee?',
    html: `This will permanently delete <strong>${g.full_name}</strong>.`,
    icon: 'warning', showCancelButton: true,
    confirmButtonText: 'Yes, Delete', confirmButtonColor: '#dc2626',
  })
  if (!res.isConfirmed) return
  try {
    await axios.delete(`${API}/gip/employees/${g.id}`)
    Swal.fire({ title: 'Deleted', icon: 'success', confirmButtonColor: '#003087' })
    await fetchAll()
  } catch (e) { Swal.fire({ title: 'Error', text: 'Could not delete.', icon: 'error' }) }
}

const openRenewModal = (g) => {
  selectedGip.value = g; renewEndDate.value = ''; renewError.value = ''; showRenewModal.value = true
}
const closeRenewModal = () => { showRenewModal.value = false }

const renewContract = async () => {
  if (!renewEndDate.value) return
  saving.value = true
  try {
    await axios.post(`${API}/gip/employees/${selectedGip.value.id}/renew`, { new_end_date: renewEndDate.value })
    Swal.fire({ title: 'Renewed!', icon: 'success', confirmButtonColor: '#003087' })
    closeRenewModal()
    await fetchAll()
  } catch (err) {
    renewError.value = err.response?.data?.error || 'Renewal failed.'
  } finally { saving.value = false }
}

const openResignModal = (g) => {
  selectedGip.value = g
  resignDate.value = ''
  resignError.value = ''
  showResignModal.value = true
}

const closeResignModal = () => { showResignModal.value = false }

const confirmResign = async () => {
  if (!resignDate.value) return
  saving.value = true
  try {
    await axios.post(`${API}/gip/employees/${selectedGip.value.id}/resign`, { resignation_date: resignDate.value })
    Swal.fire({ title: 'Resigned!', text: 'Employee status updated.', icon: 'success', confirmButtonColor: '#003087' })
    closeResignModal()
    await fetchAll()
  } catch (err) {
    resignError.value = err.response?.data?.error || 'Resignation failed.'
  } finally { saving.value = false }
}

const closeFormModal = () => { showFormModal.value = false; currentStep.value = 0 }

onMounted(refreshAll)
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Source+Serif+4:wght@400;600;700&family=DM+Sans:wght@400;500;600;700&display=swap');

/* ─── Root ─────────────────────────────────────────────── */
.gip-wrap {
  padding: 1.5rem;
  max-width: 1400px;
  margin: 0 auto;
  font-family: 'DM Sans', sans-serif;
  color: #1e293b;
}

/* ─── Page Header ───────────────────────────────────────── */
.pg-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;
}
.pg-title { display: flex; align-items: center; gap: 1rem; }
.pg-icon {
  width: 52px; height: 52px; border-radius: 14px;
  background: linear-gradient(135deg, #003087, #1d4ed8);
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 8px 20px rgba(0,48,135,.3);
}
.pg-icon svg { width: 28px; height: 28px; color: #fff; }
.pg-title h1 { font-family: 'Source Serif 4', serif; font-size: 1.75rem; font-weight: 700; margin: 0; color: #0f172a; }
.pg-title p  { margin: 0; font-size: .82rem; color: #64748b; }
.btn-add {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .7rem 1.4rem; background: linear-gradient(135deg, #003087, #1d4ed8);
  color: #fff; border: none; border-radius: 10px; font-size: .9rem;
  font-weight: 600; cursor: pointer; font-family: 'DM Sans', sans-serif;
  box-shadow: 0 6px 18px rgba(0,48,135,.3); transition: transform .2s, box-shadow .2s;
}
.btn-add svg { width: 18px; height: 18px; }
.btn-add:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,48,135,.35); }

/* ─── Stats Row ─────────────────────────────────────────── */
.stats-row {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(150px,1fr));
  gap: 1rem; margin-bottom: 1.5rem;
}
.scard {
  background: #fff; border-radius: 14px; padding: 1.1rem 1rem;
  display: flex; align-items: center; gap: .85rem;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 8px rgba(0,0,0,.05);
  transition: transform .2s;
}
.scard:hover { transform: translateY(-2px); }
.scard-icon { font-size: 1.6rem; }
.scard-val  { font-size: 1.6rem; font-weight: 700; color: var(--ac, #003087); line-height: 1; }
.scard-lbl  { font-size: .75rem; color: #64748b; margin-top: .1rem; }

/* ─── Filter Bar ────────────────────────────────────────── */
.filter-bar {
  display: flex; gap: .75rem; flex-wrap: wrap; margin-bottom: 1rem; align-items: center;
}
.search-wrap {
  display: flex; align-items: center; gap: .5rem;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
  padding: .55rem .85rem; flex: 1; min-width: 200px;
}
.search-wrap svg { width: 16px; height: 16px; color: #94a3b8; flex-shrink: 0; }
.search-wrap input { border: none; outline: none; font-size: .875rem; width: 100%; font-family: 'DM Sans', sans-serif; color: #1e293b; }
.filter-bar select {
  padding: .55rem .85rem; border: 1px solid #e2e8f0; border-radius: 10px;
  font-size: .875rem; background: #fff; color: #1e293b; cursor: pointer;
  font-family: 'DM Sans', sans-serif; outline: none;
}
.filter-bar select:focus { border-color: #003087; }

/* ─── Table Card ────────────────────────────────────────── */
.tbl-card { background: #fff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,.06); }
.tbl-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; min-width: 900px; }
thead th {
  background: #f8fafc; padding: .85rem 1rem;
  text-align: left; font-size: .75rem; font-weight: 700;
  color: #475569; text-transform: uppercase; letter-spacing: .06em;
  border-bottom: 1px solid #e2e8f0; white-space: nowrap;
}
thead th.tc { text-align: center; }
tbody td { padding: .85rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: .875rem; vertical-align: middle; }
tbody tr:hover { background: #f8faff; }
tbody td.tc { text-align: center; }
.t-empty { text-align: center; padding: 3rem; color: #94a3b8; font-size: .9rem; }

.emp-cell { display: flex; align-items: center; gap: .75rem; }
.emp-avatar {
  width: 38px; height: 38px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; font-size: .8rem; font-weight: 700; flex-shrink: 0;
  overflow: hidden; /* ensure image doesn't bleed out */
}
.emp-avatar.f { background: #fce7f3; color: #be185d; }
.emp-avatar.m { background: #dbeafe; color: #1d4ed8; }
.emp-avatar.has-photo { background: transparent; }  /* no background for photo */
.avatar-img {
  width: 100%; height: 100%; object-fit: cover; border-radius: 10px;
}
.emp-name { font-weight: 600; color: #0f172a; }
.emp-sub  { font-size: .75rem; color: #94a3b8; }

.chip { display: inline-flex; padding: .2rem .65rem; border-radius: 999px; font-size: .72rem; font-weight: 600; }
.chip-pink { background: #fce7f3; color: #be185d; }
.chip-blue { background: #dbeafe; color: #1e40af; }
.chip-gold { background: #fef3c7; color: #b45309; }

.contract-cell { display: flex; flex-direction: column; gap: .25rem; }

.months-pill { display: inline-flex; padding: .2rem .65rem; border-radius: 999px; font-size: .72rem; font-weight: 700; }
.mp-green { background: #dcfce7; color: #15803d; }
.mp-yellow { background: #fef9c3; color: #a16207; }
.mp-red   { background: #fee2e2; color: #b91c1c; }

.status-pill { display: inline-flex; padding: .25rem .75rem; border-radius: 999px; font-size: .72rem; font-weight: 700; }
.sp-green { background: #dcfce7; color: #15803d; }
.sp-blue  { background: #dbeafe; color: #1e40af; }
.sp-red   { background: #fee2e2; color: #b91c1c; }
.sp-gray  { background: #f1f5f9; color: #475569; }
.sp-orange { background: #fff7ed; color: #c2410c; }   /* Resigned */

.act-btns { display: flex; gap: .4rem; justify-content: center; }
.ab {
  width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0;
  display: flex; align-items: center; justify-content: center; cursor: pointer;
  background: #fff; transition: all .15s;
}
.ab svg { width: 14px; height: 14px; }
.ab.view  { color: #2563eb; } .ab.view:hover  { background: #eff6ff; border-color: #bfdbfe; }
.ab.edit  { color: #059669; } .ab.edit:hover  { background: #ecfdf5; border-color: #a7f3d0; }
.ab.renew { color: #7c3aed; } .ab.renew:hover { background: #ede9fe; border-color: #c4b5fd; }
.ab.resign { color: #ea580c; } .ab.resign:hover { background: #fff7ed; border-color: #fdba74; }
.ab.del   { color: #dc2626; } .ab.del:hover   { background: #fef2f2; border-color: #fca5a5; }

.loader { width: 18px; height: 18px; border: 2px solid #e2e8f0; border-top-color: #003087; border-radius: 50%; animation: spin 1s linear infinite; display: inline-block; margin-right: .5rem; vertical-align: middle; }

/* ─── Modal Overlay ─────────────────────────────────────── */
.m-overlay {
  position: fixed; inset: 0; background: rgba(15,23,42,.65);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000; padding: 1rem; backdrop-filter: blur(4px);
}
.m-container {
  background: #fff; border-radius: 18px; width: 100%; max-width: 820px;
  max-height: 92vh; display: flex; flex-direction: column;
  box-shadow: 0 32px 80px rgba(0,0,0,.25); overflow: hidden;
}
.view-modal { max-width: 620px; }

/* Modal Top / Header */
.m-top {
  display: flex; align-items: flex-start; justify-content: space-between;
  padding: 1.1rem 1.4rem; background: linear-gradient(135deg, #003087, #0052cc);
  flex-shrink: 0;
}
.m-top-left { display: flex; align-items: center; gap: 1rem; }
.annex-tag {
  background: #FCD116; color: #003087; font-size: .7rem;
  font-weight: 800; padding: .2rem .6rem; border-radius: 5px; white-space: nowrap;
  font-family: 'Source Serif 4', serif; letter-spacing: .05em;
}
.dole-labels { display: flex; flex-direction: column; gap: .1rem; }
.dole-ro   { font-size: .7rem; font-weight: 700; color: rgba(255,255,255,.7); text-transform: uppercase; letter-spacing: .08em; }
.dole-prog { font-size: .95rem; font-weight: 700; color: #fff; font-family: 'Source Serif 4', serif; }
.dole-form { font-size: .78rem; color: #FCD116; font-weight: 600; }
.m-close {
  width: 34px; height: 34px; border-radius: 8px; border: 1px solid rgba(255,255,255,.25);
  background: rgba(255,255,255,.12); color: #fff; display: flex;
  align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0;
}
.m-close svg { width: 16px; height: 16px; }
.m-close:hover { background: rgba(255,255,255,.22); }

/* Steps Bar */
.steps-bar {
  display: flex; background: #f8fafc; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; overflow-x: auto;
}
.step-item {
  flex: 1; display: flex; align-items: center; justify-content: center; gap: .5rem;
  padding: .7rem .5rem; cursor: pointer; font-size: .78rem; font-weight: 600;
  color: #94a3b8; transition: all .2s; white-space: nowrap; border-bottom: 3px solid transparent;
}
.step-item.active { color: #003087; border-bottom-color: #003087; background: #eff6ff; }
.step-item.done   { color: #16a34a; }
.step-bubble {
  width: 24px; height: 24px; border-radius: 50%; border: 2px solid currentColor;
  display: flex; align-items: center; justify-content: center; font-size: .72rem; font-weight: 700; flex-shrink: 0;
}
.step-item.active .step-bubble { background: #003087; color: #fff; border-color: #003087; }
.step-item.done   .step-bubble { background: #dcfce7; border-color: #16a34a; color: #16a34a; }
.step-item.done   .step-bubble svg { width: 12px; height: 12px; }

/* Instruction banner */
.instruction-banner {
  background: #fffbeb; border-bottom: 1px solid #fde68a;
  padding: .6rem 1.4rem; font-size: .8rem; color: #78350f; flex-shrink: 0;
}

/* Modal Body */
.m-body { padding: 1.4rem; overflow-y: auto; flex: 1; }
.form-section-title {
  font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em;
  color: #003087; border-bottom: 2px solid #003087; padding-bottom: .3rem;
  margin-bottom: .85rem; margin-top: 1.1rem;
}
.form-section-title:first-child { margin-top: 0; }

/* Name + Photo Row */
.name-photo-row { display: flex; gap: 1rem; align-items: flex-start; margin-bottom: .5rem; }
.name-fields { flex: 1; }
.photo-box { flex-shrink: 0; width: 130px; }
.photo-preview {
  width: 110px; height: 130px; border: 2px dashed #cbd5e1; border-radius: 10px;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  cursor: pointer; overflow: hidden; background: #f8fafc;
}
.photo-preview img { width: 100%; height: 100%; object-fit: cover; }
.photo-placeholder { display: flex; flex-direction: column; align-items: center; gap: .3rem; color: #94a3b8; }
.photo-placeholder svg { width: 32px; height: 32px; }
.photo-placeholder span { font-size: .7rem; font-weight: 600; }
.photo-placeholder.sm {
  width: 80px; height: 80px; border-radius: 12px; background: #003087; color: #fff;
  font-size: 1.2rem; font-weight: 700; display: flex; align-items: center; justify-content: center;
}
.photo-hint { font-size: .65rem; color: #64748b; text-align: center; margin-top: .4rem; line-height: 1.3; }
.hidden { display: none; }

/* Form Fields */
.three-cols { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .75rem; }
.two-cols   { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.fg { display: flex; flex-direction: column; gap: .3rem; margin-bottom: .5rem; }
.fg label   { font-size: .75rem; font-weight: 600; color: #475569; }
.fi {
  width: 100%; padding: .55rem .75rem; border: 1px solid #e2e8f0; border-radius: 8px;
  font-size: .875rem; color: #1e293b; background: #fff; outline: none;
  font-family: 'DM Sans', sans-serif; transition: border .15s, box-shadow .15s; box-sizing: border-box;
}
.fi:focus { border-color: #003087; box-shadow: 0 0 0 3px rgba(0,48,135,.12); }
.fi.ta    { resize: vertical; min-height: 60px; }
.req { color: #dc2626; }
.mt-s { margin-top: .75rem; }

/* Checkbox Row */
.checkbox-row { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: .25rem; }
.chk-label {
  display: flex; align-items: center; gap: .45rem; padding: .4rem .75rem;
  border-radius: 8px; border: 1px solid #e2e8f0; cursor: pointer;
  font-size: .82rem; font-weight: 500; transition: all .15s; user-select: none;
}
.chk-label.selected { background: #eff6ff; border-color: #93c5fd; color: #1d4ed8; }
.chk-label:hover    { background: #f8fafc; }
.chk-box {
  width: 16px; height: 16px; border-radius: 4px; border: 1.5px solid #cbd5e1;
  flex-shrink: 0; transition: all .15s;
}
.chk-box.checked { background: #003087; border-color: #003087; background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M4 8l3 3 5-5' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); }
.chk-box.lg { width: 20px; height: 20px; border-radius: 5px; }

/* Grid Table (Education / Work) */
.grid-table { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; margin-bottom: .5rem; }
.gt-head { display: flex; background: #f1f5f9; }
.gt-row  { display: flex; border-top: 1px solid #e2e8f0; }
.gt-cell { flex: 1; padding: .5rem .6rem; }
.gt-head .gt-cell { font-size: .7rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .06em; }
.fi-sm { width: 100%; border: 1px solid #e2e8f0; border-radius: 6px; padding: .35rem .55rem; font-size: .82rem; font-family: 'DM Sans', sans-serif; color: #1e293b; outline: none; }
.fi-sm:focus { border-color: #003087; box-shadow: 0 0 0 2px rgba(0,48,135,.1); }
.rm-btn { background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626; border-radius: 6px; width: 28px; height: 28px; cursor: pointer; font-size: .8rem; display: flex; align-items: center; justify-content: center; }
.add-row-btn { width: 100%; padding: .55rem; background: #f8fafc; border: none; border-top: 1px solid #e2e8f0; font-size: .8rem; font-weight: 600; color: #003087; cursor: pointer; font-family: 'DM Sans', sans-serif; }
.add-row-btn:hover { background: #eff6ff; }

/* Vulnerable Groups */
.vuln-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
.vuln-chk {
  display: flex; align-items: center; gap: .6rem; padding: .6rem .85rem;
  border: 1px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: all .15s;
}
.vuln-chk:hover { background: #f8faff; border-color: #bfdbfe; }
.vuln-chk span  { font-size: .82rem; font-weight: 500; color: #334155; }

.divider { border: none; border-top: 2px dashed #e2e8f0; margin: 1.1rem 0; }

/* DOLE Use Section */
.dole-use-section { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; margin-top: 1rem; }
.dole-use-header { text-align: center; font-size: .72rem; font-weight: 800; letter-spacing: .12em; color: #475569; text-transform: uppercase; margin-bottom: .85rem; }
.docs-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .4rem; }
.doc-chk {
  display: flex; align-items: center; gap: .5rem; padding: .4rem .6rem;
  border-radius: 8px; cursor: pointer; font-size: .8rem; font-weight: 500; color: #334155;
  transition: background .15s;
}
.doc-chk:hover { background: #eff6ff; }

/* Boxes */
.warn-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: .6rem .9rem; font-size: .82rem; color: #92400e; margin-top: .5rem; }
.info-box  { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: .6rem .9rem; font-size: .82rem; color: #1e40af; margin-top: .75rem; }
.error-box { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: .6rem .9rem; font-size: .82rem; color: #b91c1c; margin-top: .5rem; }

/* Modal Footer */
.m-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding: .9rem 1.4rem; border-top: 1px solid #e2e8f0;
  background: #f8fafc; flex-shrink: 0; gap: 1rem;
}
.step-dots { display: flex; gap: .4rem; }
.sdot { width: 8px; height: 8px; border-radius: 50%; background: #e2e8f0; transition: background .2s; }
.sdot.active { background: #003087; }
.btn-pri {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .65rem 1.4rem; background: linear-gradient(135deg, #003087, #1d4ed8);
  color: #fff; border: none; border-radius: 9px; font-size: .875rem;
  font-weight: 600; cursor: pointer; font-family: 'DM Sans', sans-serif;
  box-shadow: 0 4px 12px rgba(0,48,135,.25); transition: all .2s;
}
.btn-pri:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0,48,135,.35); }
.btn-pri:disabled { opacity: .5; cursor: not-allowed; transform: none; }
.btn-pri svg { width: 16px; height: 16px; }
.btn-sec {
  padding: .65rem 1.2rem; background: #fff; border: 1px solid #e2e8f0;
  color: #475569; border-radius: 9px; font-size: .875rem; font-weight: 600;
  cursor: pointer; font-family: 'DM Sans', sans-serif; transition: all .2s;
}
.btn-sec:hover { background: #f1f5f9; }

/* View Modal */
.view-body { padding: 1.2rem 1.4rem; overflow-y: auto; }
.view-header-row { display: flex; gap: 1.25rem; align-items: center; margin-bottom: 1.25rem; }
.view-name-block h2 { font-family: 'Source Serif 4', serif; font-size: 1.4rem; margin: 0 0 .4rem; }
.view-name-block p  { font-size: .82rem; color: #64748b; margin: .4rem 0 0; }
.view-chips { display: flex; gap: .4rem; flex-wrap: wrap; }
.view-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
.vg-item { background: #f8fafc; border-radius: 10px; padding: .6rem .85rem; border: 1px solid #e2e8f0; }
.vg-lbl  { display: block; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: #94a3b8; margin-bottom: .15rem; }

/* Renew */
.renew-info  { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: .85rem 1rem; margin-bottom: .75rem; }
.renew-emp   { font-weight: 700; color: #0f172a; margin-bottom: .4rem; }
.renew-dates { display: flex; gap: 1.5rem; flex-wrap: wrap; font-size: .82rem; color: #334155; }

/* Transitions */
.mfade-enter-active, .mfade-leave-active { transition: opacity .22s ease; }
.mfade-enter-from,  .mfade-leave-to      { opacity: 0; }
.mfade-enter-active .m-container,
.mfade-leave-active .m-container         { transition: transform .22s ease, opacity .22s ease; }
.mfade-enter-from   .m-container,
.mfade-leave-to     .m-container         { opacity: 0; transform: translateY(-16px) scale(.97); }

@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin 1s linear infinite; }

/* ─── Responsive ────────────────────────────────────────── */
@media (max-width: 768px) {
  .gip-wrap  { padding: 1rem; }
  .three-cols, .two-cols { grid-template-columns: 1fr; }
  .name-photo-row { flex-direction: column-reverse; }
  .photo-box { width: 100%; display: flex; flex-direction: column; align-items: center; }
  .vuln-grid, .docs-grid, .view-grid { grid-template-columns: 1fr; }
  .stats-row { grid-template-columns: repeat(3,1fr); }
  .step-label { display: none; }
}
@media (max-width: 480px) {
  .stats-row { grid-template-columns: 1fr 1fr; }
}
</style>