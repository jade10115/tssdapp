<template>
  <div class="layout">
    <!-- ✅ Mobile backdrop -->
    <div
      v-if="isMobile && isOpen"
      class="sidebar-backdrop"
      @click="toggleSidebar"
      aria-hidden="true"
    ></div>

    <!-- SIDEBAR -->
    <aside
      :class="[
        'sidebar bg-light border-end d-flex flex-column',
        isOpen ? 'sidebar-open' : 'sidebar-closed',
        isMobile ? 'sidebar-mobile' : '',
      ]"
      aria-label="Sidebar"
    >
      <!-- PROFILE -->
      <div class="p-3 d-flex align-items-center sidebar-top">
        <img
          v-if="user?.profile_image"
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
        <!-- ✅ SUPPLY DASHBOARD -->
        <router-link
          to="/supply/DashboardSupply"
          class="d-flex align-items-center p-2 text-decoration-none text-dark rounded mb-2 sidebar-link"
          active-class="active-link"
        >
          <font-awesome-icon :icon="['fas', 'tachometer-alt']" class="me-2" />
          <span v-if="isOpen">Dashboard</span>
        </router-link>

        <!-- ✅ SUPPLY MENU -->
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
              <router-link
                to="/supply/items"
                class="d-block p-2 text-decoration-none text-dark rounded mb-1 sidebar-link"
                active-class="active-link"
              >
                Items
              </router-link>

              <router-link
                to="/supply/requests"
                class="d-block p-2 text-decoration-none text-dark rounded mb-1 sidebar-link"
                active-class="active-link"
              >
                Requests
              </router-link>

              <router-link
                to="/supply/reports"
                class="d-block p-2 text-decoration-none text-dark rounded sidebar-link"
                active-class="active-link"
              >
                Reports
              </router-link>
            </div>
          </transition>
        </div>

        <!-- ✅ CALENDAR LINK (SUPPLY) -->
        <div class="mt-2">
          <router-link
            to="/supply/calendar"
            class="d-flex align-items-center p-2 text-decoration-none text-dark rounded mb-2 sidebar-link"
            active-class="active-link"
          >
            <font-awesome-icon :icon="['fas', 'calendar-alt']" class="me-2" />
            <span v-if="isOpen">Calendar</span>
          </router-link>
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
    <div :class="['content', isOpen && !isMobile ? 'content-shift' : 'content-normal']">
      <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-3 d-flex justify-content-between">
        <div class="d-flex align-items-center">
          <button class="btn btn-outline-primary me-3" @click="toggleSidebar">
            <i class="bi bi-list"></i>
          </button>
          <router-link to="/supply/dashboard" class="navbar-brand">TSSD (Supply)</router-link>
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
import { ref, onMounted, onBeforeUnmount } from "vue";
import Swal from "sweetalert2";
import axios from "axios";
import bagongLogo from "../../assets/logo/bagongphlogo.png";
import doleLogo from "../../assets/logo/dole.png";
import { useRouter } from "vue-router";

export default {
  setup() {
    const router = useRouter();

    const isOpen = ref(true);
    const isMobile = ref(false);

    const openSupply = ref(false);

    const user = ref({
      first_name: "",
      last_name: "",
      profile_image: "",
    });

    const onResize = () => {
      isMobile.value = window.matchMedia("(max-width: 768px)").matches;
      if (isMobile.value) isOpen.value = false;
      else isOpen.value = true;
    };

    const toggleSidebar = () => (isOpen.value = !isOpen.value);

    onMounted(async () => {
      onResize();
      window.addEventListener("resize", onResize);

      try {
        const token = localStorage.getItem("auth_token");
        if (token) axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;

        const res = await axios.get("http://localhost:8000/api/session", {
          headers: { Accept: "application/json" },
        });

        if (res.data.logged_in) {
          user.value = {
            first_name: res.data.user.first_name || "",
            last_name: res.data.user.last_name || "",
            profile_image: res.data.user.profile_image || "",
          };
        } else {
          router.push("/login");
        }
      } catch (e) {
        router.push("/login");
      }
    });

    onBeforeUnmount(() => {
      window.removeEventListener("resize", onResize);
    });

    const handleLogout = async () => {
      try {
        await axios.post("http://localhost:8000/api/logout", {}, { headers: { Accept: "application/json" } });
      } catch (e) {}
      localStorage.removeItem("auth_token");
      localStorage.removeItem("userlevel_id");
      delete axios.defaults.headers.common["Authorization"];

      Swal.fire({
        title: "Logged out!",
        icon: "success",
        timer: 1200,
        showConfirmButton: false,
      });

      setTimeout(() => router.push("/login"), 900);
    };

    return {
      isOpen,
      isMobile,
      openSupply,
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
.layout { min-height: 100vh; }

.sidebar {
  position: fixed; top: 0; left: 0;
  height: 100vh; overflow: hidden;
  z-index: 1030; background: #f8f9fa;
}

.sidebar-nav { overflow-y: auto; padding-bottom: 16px; }

.sidebar-open { width: 240px; transition: width 0.25s ease, transform 0.25s ease; }
.sidebar-closed { width: 72px; transition: width 0.25s ease, transform 0.25s ease; }

.profile-img { object-fit: cover; }
.sidebar-top { min-height: 64px; }
.sidebar-bottom { background: #f8f9fa; }

.content { min-height: 100vh; transition: margin-left 0.25s ease; }
.content-shift { margin-left: 240px; }
.content-normal { margin-left: 72px; }

.active-link { background: #0d6efd !important; color: #fff !important; }

.slide-fade-enter-active, .slide-fade-leave-active { transition: all 0.25s ease; }
.slide-fade-enter-from, .slide-fade-leave-to { max-height: 0; opacity: 0; transform: translateY(-4px); }
.slide-fade-enter-to, .slide-fade-leave-from { max-height: 240px; opacity: 1; transform: translateY(0); }

.sidebar-link { cursor: pointer; transition: all 0.2s ease; }
.sidebar-link:hover { background-color: #e9ecef; color: #0d6efd !important; transform: translateX(2px); }

/* Mobile */
.sidebar-backdrop {
  position: fixed; inset: 0;
  background: rgba(15, 23, 42, 0.35);
  z-index: 1025;
}

.sidebar-mobile.sidebar-open {
  width: 80vw; max-width: 320px;
  transform: translateX(0);
  box-shadow: 0 18px 40px rgba(0,0,0,0.25);
}
.sidebar-mobile.sidebar-closed {
  width: 80vw; max-width: 320px;
  transform: translateX(-110%);
  border: none !important;
}

@media (max-width: 768px) {
  .content-shift, .content-normal { margin-left: 0 !important; }
}
</style>
