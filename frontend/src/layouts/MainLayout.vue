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
  <div class="dashboard-bg min-h-screen bg-base-200">
    <aside
      class="sidebar fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-[#08111f] px-4 py-6 text-[#e0e0e0] shadow-2xl"
    >
      <router-link
        :to="
          currentSlug
            ? { name: 'dashboard', params: { entrepriseSlug: currentSlug } }
            : '/'
        "
        class="mb-8 px-2 text-2xl font-semibold tracking-wide text-white"
      >
        Synkro
      </router-link>

      <ul class="menu gap-2">
        <li>
          <router-link
            :to="{
              name: 'dashboard',
              params: { entrepriseSlug: currentSlug },
            }"
          >
            Dashboard
          </router-link>
        </li>
        <li>
          <router-link
            :to="{ name: 'rooms', params: { entrepriseSlug: currentSlug } }"
          >
            Rooms
          </router-link>
        </li>
        <li>
          <router-link
            :to="{
              name: 'calendar',
              params: { entrepriseSlug: currentSlug },
            }"
          >
            Calendrier
          </router-link>
        </li>
        <li v-if="isAdmin">
          <router-link
            :to="{
              name: 'admin-ai-settings',
              params: { entrepriseSlug: currentSlug },
            }"
          >
            Paramètres IA
          </router-link>
        </li>
      </ul>

      <div class="mt-8 space-y-4">
        <div v-if="hasEntreprises" class="dropdown w-full">
          <div
            tabindex="0"
            role="button"
            class="btn btn-ghost w-full justify-between border border-white/10 bg-white/5 px-3"
          >
            <span class="max-w-40 truncate text-left">{{
              authStore.currentEntreprise?.name ??
              authStore.user?.entreprises[0]?.name
            }}</span>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-4 w-4"
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
          <ul
            tabindex="0"
            class="mt-2 z-50 w-full rounded-box border border-white/10 bg-[#0a1628] p-2 shadow-xl menu menu-sm dropdown-content"
          >
            <li v-for="ue in authStore.user?.entreprises" :key="ue.id">
              <a
                @click="handleSwitchEntreprise(ue.slug)"
                class="flex items-center justify-between"
              >
                <span :class="ue.slug === currentSlug ? 'font-semibold' : ''">
                  {{ ue.slug === currentSlug ? "✓ " : "" }}{{ ue.name }}
                </span>
                <span class="badge badge-sm badge-ghost">{{ ue.role }}</span>
              </a>
            </li>
            <li class="mt-1 border-t border-white/10 pt-1">
              <router-link
                :to="{
                  name: 'profile',
                  params: { entrepriseSlug: currentSlug },
                }"
                class="text-primary"
              >
                + Créer une entreprise
              </router-link>
            </li>
          </ul>
        </div>
      </div>

      <div class="mt-auto">
        <div class="dropdown dropdown-top w-full">
          <div
            tabindex="0"
            role="button"
            class="flex w-full items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-3 py-3 text-left"
          >
            <div class="avatar placeholder shrink-0">
              <div
                class="w-10 rounded-full"
                :class="avatarUrl ? '' : 'bg-primary text-primary-content'"
              >
                <img
                  v-if="avatarUrl"
                  :src="avatarUrl"
                  alt="Avatar"
                  class="rounded-full"
                />
                <span v-else>{{
                  authStore.user?.displayName?.charAt(0)?.toUpperCase() || "U"
                }}</span>
              </div>
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate font-medium text-white">
                {{ authStore.user?.displayName || "Utilisateur" }}
              </p>
              <p class="truncate text-xs text-white/60">
                {{ authStore.user?.email }}
              </p>
            </div>
          </div>
          <ul
            tabindex="0"
            class="dropdown-content z-50 mb-3 w-full rounded-lg border border-gray-600 bg-[#0a1628] p-2 shadow-lg menu menu-sm"
          >
            <li>
              <router-link
                :to="{
                  name: 'profile',
                  params: { entrepriseSlug: currentSlug },
                }"
              >
                Profil
              </router-link>
            </li>
            <li v-if="isAdmin">
              <router-link
                :to="{
                  name: 'admin-users',
                  params: { entrepriseSlug: currentSlug },
                }"
              >
                Gestion utilisateurs
              </router-link>
            </li>
            <li><a @click="handleLogout">Déconnexion</a></li>
          </ul>
        </div>
      </div>
    </aside>

    <main class="ml-64 min-h-screen p-8">
      <slot></slot>
    </main>

    <AiFloatingChat />
  </div>
</template>

<style scoped>
.dashboard-bg {
  background-color: #050d1a;
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
  border-right: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar :deep(.menu) {
  width: 100%;
  display: flex;
  flex-direction: column;
  padding: 0;
}

.sidebar :deep(.menu li) {
  width: 100%;
  margin: 0;
}

.sidebar :deep(.menu a) {
  transition: all 0.3s ease;
  color: #e0e0e0;
  padding: 0.75rem 1rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  margin: 0.5rem 0;
}

.sidebar :deep(.menu a:hover) {
  background-color: rgba(26, 58, 82, 0.5);
  color: #ffffff;
  border-radius: 0.5rem;
}

.sidebar :deep(.menu a.active-link) {
  background-color: #05091a;
  color: #ffffff;
  border-radius: 0.5rem;
}
</style>
