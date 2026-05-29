<script setup lang="ts">
import { computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useRouter, useRoute } from "vue-router";
import AiFloatingChat from "@/components/ai/AiFloatingChat.vue";

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const API_BASE = import.meta.env.VITE_API_URL?.replace("/api", "") || "";

const avatarUrl = computed(() => {
  if (authStore.user?.avatarUrl) {
    return `${API_BASE}${authStore.user.avatarUrl}`;
  }
  return null;
});

const currentSlug = computed(
  () => route.params.entrepriseSlug as string | undefined,
);
const isAdmin = computed(() => authStore.currentEntreprise?.role === "admin");
const hasEntreprises = computed(
  () => (authStore.user?.entreprises?.length ?? 0) > 0,
);

function isRouteActive(routeName: string) {
  return route.name === routeName;
}

function initialFromName(name?: string | null) {
  return name?.charAt(0)?.toUpperCase() || "U";
}

function handleSwitchEntreprise(slug: string) {
  if (slug === currentSlug.value) return;
  router.push({ name: "dashboard", params: { entrepriseSlug: slug } });
}

function handleLogout() {
  authStore.logout();
  router.push("/login");
}
</script>

<template>
  <div class="dashboard-bg min-h-screen" style="background: #050a14">
    <aside class="sidebar fixed inset-y-0 left-0 z-40">
      <div class="sidebar-shell">
        <router-link
          :to="
            currentSlug
              ? { name: 'dashboard', params: { entrepriseSlug: currentSlug } }
              : '/'
          "
          class="brand"
        >
          <span class="brand-dot"></span>
          <span>Synkro</span>
        </router-link>

        <p class="section-label">Navigation</p>
        <nav class="sidebar-nav">
          <router-link
            :to="{ name: 'dashboard', params: { entrepriseSlug: currentSlug } }"
            class="nav-link"
            :class="{ 'nav-link-active': isRouteActive('dashboard') }"
          >
            <span class="nav-icon">◻</span>
            <span>Dashboard</span>
          </router-link>
          <router-link
            :to="{ name: 'rooms', params: { entrepriseSlug: currentSlug } }"
            class="nav-link"
            :class="{ 'nav-link-active': isRouteActive('rooms') }"
          >
            <span class="nav-icon">◻</span>
            <span>Rooms</span>
          </router-link>
          <router-link
            :to="{ name: 'calendar', params: { entrepriseSlug: currentSlug } }"
            class="nav-link"
            :class="{ 'nav-link-active': isRouteActive('calendar') }"
          >
            <span class="nav-icon">◻</span>
            <span>Calendrier</span>
          </router-link>
          <router-link
            v-if="isAdmin"
            :to="{
              name: 'admin-ai-settings',
              params: { entrepriseSlug: currentSlug },
            }"
            class="nav-link"
            :class="{ 'nav-link-active': isRouteActive('admin-ai-settings') }"
          >
            <span class="nav-icon">◻</span>
            <span>Paramètres IA</span>
          </router-link>
        </nav>

        <div class="sidebar-bottom">
          <div v-if="hasEntreprises" class="switcher-panel">
            <p class="section-label">Entreprise</p>
            <div class="entreprise-select-wrap">
              <select
                class="entreprise-select"
                :value="currentSlug"
                @change="
                  handleSwitchEntreprise(
                    ($event.target as HTMLSelectElement).value,
                  )
                "
              >
                <option
                  v-for="ue in authStore.user?.entreprises"
                  :key="ue.id"
                  :value="ue.slug"
                >
                  {{ ue.name }} ({{ ue.role }})
                </option>
              </select>
              <svg
                class="entreprise-chevron"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 9l-7 7-7-7"
                />
              </svg>
            </div>
          </div>

          <div class="user-panel">
            <details class="user-mini">
              <summary class="user-mini-trigger">
                <span class="user-avatar">
                  <img
                    v-if="avatarUrl"
                    :src="avatarUrl"
                    alt="Avatar"
                    class="avatar-image"
                  />
                  <span v-else>{{
                    initialFromName(authStore.user?.displayName)
                  }}</span>
                </span>
                <span class="user-mini-meta">
                  <span class="user-name">{{
                    authStore.user?.displayName || "Utilisateur"
                  }}</span>
                  <span class="user-email">{{ authStore.user?.email }}</span>
                </span>
                <svg
                  class="user-mini-chevron"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </summary>

              <div class="user-mini-menu">
                <router-link
                  :to="{
                    name: 'profile',
                    params: { entrepriseSlug: currentSlug },
                  }"
                  class="user-mini-action"
                >
                  Profil
                </router-link>
                <router-link
                  v-if="isAdmin"
                  :to="{
                    name: 'admin-users',
                    params: { entrepriseSlug: currentSlug },
                  }"
                  class="user-mini-action"
                >
                  Gestion utilisateurs
                </router-link>
                <button
                  class="user-mini-action user-mini-action-danger"
                  type="button"
                  @click="handleLogout"
                >
                  Déconnexion
                </button>
              </div>
            </details>
          </div>
        </div>
      </div>
    </aside>

    <main class="main-content min-h-screen p-8">
      <slot></slot>
    </main>

    <AiFloatingChat />
  </div>
</template>

<style scoped>
.dashboard-bg {
  background-color: #050a14;
  position: relative;
}

.dashboard-bg::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: transparent;
  pointer-events: none;
  z-index: 0;
}

.sidebar {
  z-index: 40;
  width: 17rem;
}

.sidebar-shell {
  height: 100%;
  display: flex;
  flex-direction: column;
  padding: 1.25rem 1rem;
  border-right: 1px solid rgba(255, 255, 255, 0.08);
  background: linear-gradient(180deg, #08111f 0%, #060d18 100%);
  box-shadow: 18px 0 40px rgba(0, 0, 0, 0.35);
}

.brand {
  display: inline-flex;
  align-items: center;
  gap: 0.625rem;
  color: #dbe8f4;
  font-size: 1.35rem;
  font-weight: 700;
  letter-spacing: 0.01em;
  margin: 0.25rem 0 1.5rem;
}

.brand-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: radial-gradient(
    circle at 30% 30%,
    #9cd8ff 0%,
    #5ba3e8 55%,
    #2e6ba5 100%
  );
  box-shadow: 0 0 14px rgba(91, 163, 232, 0.6);
}

.section-label {
  margin: 0 0 0.5rem;
  font-size: 0.68rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #4d6278;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.nav-link {
  width: 100%;
  min-height: 2.75rem;
  padding: 0.6rem 0.75rem;
  border-radius: 10px;
  border: 1px solid transparent;
  color: #8ea4be;
  font-size: 0.9rem;
  font-weight: 500;
  transition: 0.22s ease;
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.nav-link:hover {
  border-color: rgba(91, 163, 232, 0.25);
  background: rgba(91, 163, 232, 0.08);
  color: #d5e6f7;
}

.nav-link-active {
  border-color: rgba(91, 163, 232, 0.35);
  background: linear-gradient(
    135deg,
    rgba(91, 163, 232, 0.18),
    rgba(91, 163, 232, 0.05)
  );
  color: #e4f1fc;
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.04);
}

.nav-icon {
  width: 20px;
  height: 20px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.05);
  font-size: 0;
}

.switcher-panel {
  width: 100%;
}

.entreprise-select-wrap {
  position: relative;
}

.sidebar-bottom {
  margin-top: auto;
  display: grid;
  gap: 0.6rem;
}

.entreprise-select {
  appearance: none;
  width: 100%;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.06) 0%,
    rgba(255, 255, 255, 0.03) 100%
  );
  color: #c2d7ea;
  font-size: 0.8rem;
  font-weight: 500;
  line-height: 1.2;
  padding: 0.5rem 2rem 0.5rem 0.65rem;
  outline: none;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    background-color 0.2s ease;
}

.entreprise-select:hover {
  border-color: rgba(255, 255, 255, 0.2);
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.08) 0%,
    rgba(255, 255, 255, 0.035) 100%
  );
}

.entreprise-select:focus {
  border-color: rgba(91, 163, 232, 0.5);
  box-shadow: 0 0 0 3px rgba(91, 163, 232, 0.16);
}

.entreprise-select option {
  background: #080f1c;
  color: #c2d7ea;
}

.entreprise-chevron {
  position: absolute;
  right: 0.62rem;
  top: 50%;
  width: 14px;
  height: 14px;
  color: #6f88a3;
  pointer-events: none;
  transform: translateY(-50%);
}

.user-panel {
  position: relative;
}

.user-mini {
  width: 100%;
}

.user-mini > summary::-webkit-details-marker {
  display: none;
}

.user-mini-trigger {
  list-style: none;
  width: 100%;
  display: flex;
  align-items: center;
  gap: 0.55rem;
  border: 1px solid rgba(255, 255, 255, 0.09);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.025);
  padding: 0.5rem 0.55rem;
  cursor: pointer;
}

.user-avatar {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  border: 1px solid rgba(91, 163, 232, 0.3);
  background: rgba(91, 163, 232, 0.12);
  color: #d7e9f9;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 700;
  overflow: hidden;
}

.avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-meta {
  min-width: 0;
  flex: 1;
}

.user-name {
  display: block;
  color: #dce9f6;
  font-size: 0.8rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-email {
  display: block;
  margin-top: 0.08rem;
  color: #70879f;
  font-size: 0.67rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-mini-meta {
  min-width: 0;
  flex: 1;
}

.user-mini-chevron {
  width: 14px;
  height: 14px;
  color: #5d7389;
  transition: transform 0.2s ease;
}

.user-mini[open] .user-mini-chevron {
  transform: rotate(180deg);
}

.user-mini-menu {
  position: absolute;
  left: 0;
  right: 0;
  bottom: calc(100% + 0.42rem);
  z-index: 70;
  display: grid;
  gap: 0.3rem;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  background: #08111f;
  box-shadow: 0 14px 28px rgba(0, 0, 0, 0.45);
  padding: 0.38rem;
}

.user-mini-action {
  width: 100%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 7px;
  background: rgba(255, 255, 255, 0.018);
  color: #9bb0c6;
  font-size: 0.76rem;
  font-weight: 500;
  padding: 0.36rem 0.5rem;
  text-align: center;
  transition: 0.2s ease;
}

.user-mini-action:hover {
  border-color: rgba(91, 163, 232, 0.3);
  color: #d5e6f8;
}

.user-mini-action-danger:hover {
  border-color: rgba(248, 113, 113, 0.35);
  color: #fca5a5;
}

.main-content {
  margin-left: 17rem;
}

@media (max-width: 1024px) {
  .sidebar {
    position: static;
    width: 100%;
  }

  .sidebar-shell {
    border-right: none;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  }

  .main-content {
    margin-left: 0;
    padding: 1rem;
  }
}
</style>
