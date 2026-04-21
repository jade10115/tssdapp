<template>
  <div class="layout">
    <!-- SIDEBAR -->
    <aside
      :class="[
        'sidebar bg-light border-end d-flex flex-column',
        isOpen ? 'sidebar-open' : 'sidebar-closed',
      ]"
      aria-label="Sidebar"
    >
      <!-- PROFILE -->
      <div class="p-3 d-flex align-items-center sidebar-top">
        <img
          v-if="user.profile_image"
          :src="user.profile_image"
          class="rounded-circle me-2 profile-img"
          width="40"
          height="40"
          alt="User profile"
        />
        <div v-if="isOpen" class="fw-bold text-truncate">
          {{ user.first_name }} {{ user.last_name }}
        </div>
      </div>

      <!-- NAV -->
      <nav class="flex-grow-1 px-2 sidebar-nav">
        <router-link
          to="/dashboard"
          class="d-flex align-items-center p-2 text-decoration-none text-dark rounded mb-2 sidebar-link"
          active-class="active-link"
        >
          <font-awesome-icon :icon="['fas', 'tachometer-alt']" class="me-2" />
          <span v-if="isOpen">Dashboard</span>
        </router-link>

        <!-- OO1 -->
        <div class="mt-2">
          <button
            type="button"
            class="btn w-100 d-flex align-items-center justify-content-between p-2 text-start text-dark bg-transparent border-0 sidebar-link"
            @click="openOO1 = !openOO1"
          >
            <span class="d-flex align-items-center">
              <font-awesome-icon :icon="['fas', 'briefcase']" class="me-2" />
              <span v-if="isOpen">OO1 Employment</span>
            </span>
            <i v-if="isOpen" :class="['bi', openOO1 ? 'bi-chevron-up' : 'bi-chevron-down']"></i>
          </button>

          <transition name="slide-fade">
            <div v-show="openOO1 && isOpen" class="ps-4">
              <router-link to="/oo1/a" class="d-block p-2 text-decoration-none text-dark rounded mb-1 sidebar-link" active-class="active-link">
                GIP
              </router-link>
              <router-link to="/oo1/b" class="d-block p-2 text-decoration-none text-dark rounded mb-1 sidebar-link" active-class="active-link">
                SPES
              </router-link>
              <router-link to="/oo1/c" class="d-block p-2 text-decoration-none text-dark rounded sidebar-link" active-class="active-link">
                Other Program
              </router-link>
            </div>
          </transition>
        </div>

        <!-- OO2 -->
        <div class="mt-2">
          <button
            type="button"
            class="btn w-100 d-flex align-items-center justify-content-between p-2 text-start text-dark bg-transparent border-0 sidebar-link"
            @click="openOO2 = !openOO2"
          >
            <span class="d-flex align-items-center">
              <font-awesome-icon :icon="['fas', 'user-cog']" class="me-2" />
              <span v-if="isOpen">OO2 Engineer</span>
            </span>
            <i v-if="isOpen" :class="['bi', openOO2 ? 'bi-chevron-up' : 'bi-chevron-down']"></i>
          </button>

          <transition name="slide-fade">
            <div v-show="openOO2 && isOpen" class="ps-4">
              <router-link to="/oo2/a" class="d-block p-2 text-decoration-none text-dark rounded mb-1 sidebar-link" active-class="active-link">
                Accreditation
              </router-link>
              <router-link to="/oo2/b" class="d-block p-2 text-decoration-none text-dark rounded mb-1 sidebar-link" active-class="active-link">
                Inspection
              </router-link>
              <router-link to="/oo2/c" class="d-block p-2 text-decoration-none text-dark rounded sidebar-link" active-class="active-link">
                Other Services
              </router-link>
            </div>
          </transition>
        </div>

        <!-- OO3 -->
        <div class="mt-2">
          <button
            type="button"
            class="btn w-100 d-flex align-items-center justify-content-between p-2 text-start text-dark bg-transparent border-0 sidebar-link"
            @click="openOO3 = !openOO3"
          >
            <span class="d-flex align-items-center">
              <font-awesome-icon :icon="['fas', 'hand-holding-usd']" class="me-2" />
              <span v-if="isOpen">OO3 DILEEP</span>
            </span>
            <i v-if="isOpen" :class="['bi', openOO3 ? 'bi-chevron-up' : 'bi-chevron-down']"></i>
          </button>

          <transition name="slide-fade">
            <div v-show="openOO3 && isOpen" class="ps-4">
              <router-link to="/oo3/a" class="d-block p-2 text-decoration-none text-dark rounded mb-1 sidebar-link" active-class="active-link">
                Kabuhayan
              </router-link>
              <router-link to="/tupad" class="d-block p-2 text-decoration-none text-dark rounded mb-1 sidebar-link" active-class="active-link">
                DOLE-TUPAD
              </router-link>
              <router-link to="/adlstatus" class="d-block p-2 text-decoration-none text-dark rounded sidebar-link" active-class="active-link">
                ADL Status
              </router-link>
                <router-link to="/Cashadvance" class="d-block p-2 text-decoration-none text-dark rounded sidebar-link" active-class="active-link">
              Cash advance
              </router-link>
            </div>
          </transition>
        </div>

        <!-- RECORDS -->
        <div class="mt-2" v-if="isOpen">
          <router-link
            to="/records"
            class="d-flex align-items-center p-2 text-decoration-none text-dark rounded mb-1 sidebar-link"
            active-class="active-link"
          >
            <font-awesome-icon :icon="['fas', 'book']" class="me-2" />
            <span>Records</span>
          </router-link>
        </div>

        <!-- SUPPLY -->
        <div class="mt-2">
          <button
            type="button"
            class="btn w-100 d-flex align-items-center justify-content-between p-2 text-start text-dark bg-transparent border-0 sidebar-link"
            @click="openSupply = !openSupply"
          >
            <span class="d-flex align-items-center">
              <font-awesome-icon :icon="['fas', 'shopping-cart']" class="me-2" />
              <span v-if="isOpen">Supply</span>
            </span>
            <i v-if="isOpen" :class="['bi', openSupply ? 'bi-chevron-up' : 'bi-chevron-down']"></i>
          </button>

          <transition name="slide-fade">
            <div v-show="openSupply && isOpen" class="ps-4">
              <router-link to="/supply" class="d-block p-2 text-decoration-none text-dark rounded mb-1 sidebar-link" active-class="active-link">
                Items
              </router-link>
              <router-link to="/supplyrequest" class="d-block p-2 text-decoration-none text-dark rounded mb-1 sidebar-link" active-class="active-link">
                Request
              </router-link>
              <router-link to="/supplyreport" class="d-block p-2 text-decoration-none text-dark rounded sidebar-link" active-class="active-link">
                Reports
              </router-link>
            </div>
          </transition>
        </div>

        <!-- EMPLOYEES -->
        <div class="mt-2">
          <button
            type="button"
            class="btn w-100 d-flex align-items-center justify-content-between p-2 text-start text-dark bg-transparent border-0 sidebar-link"
            @click="openEmployees = !openEmployees"
          >
            <span class="d-flex align-items-center">
              <font-awesome-icon :icon="['fas', 'users']" class="me-2" />
              <span v-if="isOpen">Employees</span>
            </span>
            <i v-if="isOpen" :class="['bi', openEmployees ? 'bi-chevron-up' : 'bi-chevron-down']"></i>
          </button>

          <transition name="slide-fade">
            <div v-show="openEmployees && isOpen" class="ps-4">
              <router-link
                to="/positions"
                class="d-flex align-items-center p-2 text-decoration-none text-dark rounded sidebar-link emp-child-link"
                active-class="active-link"
              >
                <font-awesome-icon :icon="['fas', 'user-plus']" class="me-2 emp-child-icon" />
                <span>Manage Employees</span>
              </router-link>

              <!-- ✅ FIXED CALENDAR LINK + ICON -->
              <router-link
                to="/calendar"
                class="d-flex align-items-center p-2 text-decoration-none text-dark rounded sidebar-link emp-child-link mt-1"
                active-class="active-link"
              >
                <font-awesome-icon :icon="['fas', 'calendar-alt']" class="me-2 emp-child-icon" />
                <span>Calendar</span>
              </router-link>
            </div>
          </transition>
        </div>
      </nav>

      <!-- LOGOUT -->
      <div class="p-3 border-top sidebar-bottom">
        <button class="btn btn-outline-danger w-100" @click="handleLogout">
          <font-awesome-icon :icon="['fas', 'sign-out-alt']" class="me-2" />
          <span v-if="isOpen">Logout</span>
        </button>
      </div>
    </aside>

    <!-- MAIN -->
    <div :class="['content', isOpen ? 'content-shift' : 'content-normal']">
      <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-3 d-flex justify-content-between">
        <div class="d-flex align-items-center">
          <button class="btn btn-outline-primary me-3" @click="toggleSidebar">
            <i class="bi bi-list"></i>
          </button>
          <router-link to="/" class="navbar-brand">TSSD</router-link>
        </div>

        <div class="d-flex align-items-center">
          <img :src="bagongLogo" alt="Bagong Pilipinas" height="40" class="me-3" />
          <img :src="doleLogo" alt="DOLE" height="40" />
        </div>
      </nav>

      <main class="p-4">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from "vue";
import Swal from "sweetalert2";
import axios from "axios";
import bagongLogo from "../../assets/logo/bagongphlogo.png";
import doleLogo from "../../assets/logo/dole.png";
import { useRouter } from "vue-router";

export default {
  setup() {
    const router = useRouter();

    const isOpen = ref(true);
    const openOO1 = ref(false);
    const openOO2 = ref(false);
    const openOO3 = ref(false);
    const openSupply = ref(false);
    const openEmployees = ref(false);

    const user = ref({
      first_name: "",
      last_name: "",
      profile_image: "",
    });

    // ✅ IMPORTANT: you are NOT using Sanctum session now,
    // so read user info from localStorage instead of /api/session.
    onMounted(() => {
      const token = localStorage.getItem("auth_token");
      if (!token) return router.push("/login");

      user.value = {
        first_name: (localStorage.getItem("first_name") || "").trim(),
        last_name: (localStorage.getItem("last_name") || "").trim(),
        profile_image: localStorage.getItem("profile_image") || "",
      };

      // If you only saved full name in "user_name", split it safely:
      if ((!user.value.first_name || !user.value.last_name) && localStorage.getItem("user_name")) {
        const full = (localStorage.getItem("user_name") || "").trim();
        const parts = full.split(" ");
        user.value.first_name = parts[0] || "";
        user.value.last_name = parts.slice(1).join(" ") || "";
      }
    });

    const toggleSidebar = () => (isOpen.value = !isOpen.value);

    const handleLogout = async () => {
      try {
        // optional - if you have logout route
        await axios.post("http://127.0.0.1:8000/api/logout", {}, {
          headers: {
            Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
            Accept: "application/json",
          },
        });
      } catch (e) {
        // ignore
      } finally {
        localStorage.removeItem("auth_token");
        localStorage.removeItem("user_id");
        localStorage.removeItem("userlevel_id");
        localStorage.removeItem("user_name");
        localStorage.removeItem("profile_image");
        localStorage.removeItem("division");
        localStorage.removeItem("first_name");
        localStorage.removeItem("last_name");

        delete axios.defaults.headers.common["Authorization"];

        Swal.fire({
          title: "Logged out!",
          icon: "success",
          timer: 1200,
          showConfirmButton: false,
        });

        setTimeout(() => router.push("/login"), 700);
      }
    };

    return {
      isOpen,
      openOO1,
      openOO2,
      openOO3,
      openSupply,
      openEmployees,
      user,
      toggleSidebar,
      handleLogout,
      bagongLogo,
      doleLogo,
    };
  },
};
</script>

<style>
.layout {
  min-height: 100vh;
}

.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  overflow: hidden;
  z-index: 1030;
}

.sidebar-nav {
  overflow-y: auto;
  padding-bottom: 16px;
}

.sidebar-open {
  width: 240px;
  transition: width 0.25s ease;
}
.sidebar-closed {
  width: 72px;
  transition: width 0.25s ease;
}

.profile-img {
  object-fit: cover;
}
.sidebar-top {
  min-height: 64px;
}
.sidebar-bottom {
  background: #f8f9fa;
}

.content {
  min-height: 100vh;
  transition: margin-left 0.25s ease;
}
.content-shift {
  margin-left: 240px;
}
.content-normal {
  margin-left: 72px;
}

.active-link {
  background: #0d6efd !important;
  color: #fff !important;
}

.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.25s ease;
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  max-height: 0;
  opacity: 0;
  transform: translateY(-4px);
}
.slide-fade-enter-to,
.slide-fade-leave-from {
  max-height: 260px;
  opacity: 1;
  transform: translateY(0);
}

.sidebar-link {
  cursor: pointer;
  transition: all 0.2s ease;
}
.sidebar-link:hover {
  background-color: #e9ecef;
  color: #0d6efd !important;
  transform: translateX(2px);
}

.emp-child-icon {
  font-size: 0.95rem;
}

/* ✅ MOBILE */
@media (max-width: 768px) {
  .sidebar-open {
    width: 80vw;
    max-width: 320px;
  }
  .sidebar-closed {
    width: 0;
    border: none !important;
  }
  .content-shift,
  .content-normal {
    margin-left: 0 !important;
  }
}
</style>
