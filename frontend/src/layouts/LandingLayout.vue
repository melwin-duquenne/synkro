<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();
const mobileMenuOpen = ref(false);
const scrolled = ref(false);

function onScroll() {
  scrolled.value = window.scrollY > 20;
}

onMounted(() => window.addEventListener("scroll", onScroll, { passive: true }));
onUnmounted(() => window.removeEventListener("scroll", onScroll));

function scrollToSection(sectionId: string) {
  const element = document.getElementById(sectionId);
  if (element) {
    element.scrollIntoView({ behavior: "smooth" });
    mobileMenuOpen.value = false;
  }
}

function goToLogin() {
  router.push("/login");
}

function goToRegister() {
  router.push("/register");
}
</script>

<template>
  <div class="min-h-screen landing-bg text-[#8ea4be]">
    <!-- Navbar -->
    <nav
      class="landing-nav"
      :class="scrolled ? 'landing-nav-scrolled' : 'landing-nav-top'"
    >
      <div class="nav-inner">
        <!-- Logo -->
        <a
          href="#hero"
          @click.prevent="scrollToSection('hero')"
          class="nav-logo"
        >
          <svg
            class="w-5 h-5 text-[#5ba3e8]"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 10V3L4 14h7v7l9-11h-7z"
            />
          </svg>
          <span>Synkro</span>
        </a>

        <!-- Desktop nav links -->
        <div class="hidden lg:flex items-center gap-6">
          <a @click.prevent="scrollToSection('features')" class="nav-link"
            >Fonctionnalités</a
          >
          <a @click.prevent="scrollToSection('pricing')" class="nav-link"
            >Tarifs</a
          >
          <a @click.prevent="scrollToSection('team')" class="nav-link"
            >Équipe</a
          >
          <a @click.prevent="scrollToSection('changelog')" class="nav-link"
            >Changelog</a
          >
          <a @click.prevent="scrollToSection('contact')" class="nav-link"
            >Contact</a
          >
        </div>

        <!-- CTAs -->
        <div class="hidden lg:flex items-center gap-2">
          <button @click="goToLogin" class="nav-cta-ghost">Se connecter</button>
          <button @click="goToRegister" class="nav-cta-primary">
            Commencer
          </button>
        </div>

        <!-- Mobile hamburger -->
        <button
          @click="mobileMenuOpen = !mobileMenuOpen"
          class="nav-hamburger"
          :aria-expanded="mobileMenuOpen"
          aria-label="Menu"
        >
          <svg
            v-if="!mobileMenuOpen"
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            />
          </svg>
          <svg
            v-else
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>
    </nav>

    <!-- Mobile Menu -->
    <div v-if="mobileMenuOpen" class="mobile-menu">
      <a @click.prevent="scrollToSection('features')" class="mobile-nav-link"
        >Fonctionnalités</a
      >
      <a @click.prevent="scrollToSection('pricing')" class="mobile-nav-link"
        >Tarifs</a
      >
      <a @click.prevent="scrollToSection('team')" class="mobile-nav-link"
        >Équipe</a
      >
      <a @click.prevent="scrollToSection('changelog')" class="mobile-nav-link"
        >Changelog</a
      >
      <a @click.prevent="scrollToSection('contact')" class="mobile-nav-link"
        >Contact</a
      >
      <div class="h-px bg-white/6 my-2"></div>
      <button @click="goToLogin" class="nav-cta-ghost w-full text-center">
        Se connecter
      </button>
      <button @click="goToRegister" class="nav-cta-primary w-full text-center">
        Commencer
      </button>
    </div>

    <!-- Main Content -->
    <main>
      <slot />
    </main>

    <!-- Footer -->
    <footer class="landing-footer">
      <div class="max-w-6xl mx-auto px-6">
        <!-- Top: CTA band -->
        <div class="footer-cta-band">
          <div>
            <h3
              class="text-2xl font-bold text-[#c8daea] mb-1"
              style="letter-spacing: -0.03em"
            >
              Prêt à collaborer autrement ?
            </h3>
            <p class="text-[#3d5060] text-sm">
              Commencez gratuitement, sans carte bancaire.
            </p>
          </div>
          <button @click="goToRegister" class="nav-cta-primary shrink-0">
            Démarrer gratuitement →
          </button>
        </div>

        <!-- Divider -->
        <div class="h-px bg-white/5 mb-10"></div>

        <!-- Links grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-12">
          <div class="col-span-2 md:col-span-1">
            <div class="flex items-center gap-2 mb-3">
              <svg
                class="w-5 h-5 text-[#5ba3e8]"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 10V3L4 14h7v7l9-11h-7z"
                />
              </svg>
              <span class="text-sm font-bold text-[#c8daea]">Synkro</span>
            </div>
            <p class="text-[#2a3a48] text-xs leading-relaxed">
              La plateforme de collaboration tout-en-un pour les équipes
              modernes.
            </p>
          </div>

          <div>
            <h4 class="footer-col-title">Produit</h4>
            <ul class="footer-links">
              <li>
                <a
                  @click.prevent="scrollToSection('features')"
                  class="footer-link"
                  >Fonctionnalités</a
                >
              </li>
              <li>
                <a
                  @click.prevent="scrollToSection('pricing')"
                  class="footer-link"
                  >Tarifs</a
                >
              </li>
              <li>
                <a
                  @click.prevent="scrollToSection('changelog')"
                  class="footer-link"
                  >Changelog</a
                >
              </li>
            </ul>
          </div>

          <div>
            <h4 class="footer-col-title">Support</h4>
            <ul class="footer-links">
              <li><a href="#" class="footer-link">Documentation</a></li>
              <li><a href="#" class="footer-link">FAQ</a></li>
              <li>
                <a
                  @click.prevent="scrollToSection('contact')"
                  class="footer-link"
                  >Contact</a
                >
              </li>
            </ul>
          </div>

          <div>
            <h4 class="footer-col-title">Légal</h4>
            <ul class="footer-links">
              <li><a href="#" class="footer-link">CGU</a></li>
              <li><a href="#" class="footer-link">Confidentialité</a></li>
              <li><a href="#" class="footer-link">Cookies</a></li>
            </ul>
          </div>
        </div>

        <!-- Bottom bar -->
        <div
          class="flex flex-col sm:flex-row items-center justify-between gap-4 py-6 border-t border-white/4"
        >
          <p class="text-[#1e2e3a] text-xs">
            © 2026 Synkro. Tous droits réservés.
          </p>
          <div class="flex items-center gap-4">
            <a href="#" class="footer-social" aria-label="GitHub">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path
                  d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"
                />
              </svg>
            </a>
            <a href="#" class="footer-social" aria-label="LinkedIn">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path
                  d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"
                />
              </svg>
            </a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.landing-bg {
  background: #050a14;
}

/* Navbar */
.landing-nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 50;
  transition:
    background 0.3s,
    border-color 0.3s,
    box-shadow 0.3s,
    backdrop-filter 0.3s;
}

.landing-nav-top {
  background: transparent;
  border-bottom: 1px solid transparent;
}

.landing-nav-scrolled {
  background: rgba(5, 10, 20, 0.85);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  box-shadow: 0 4px 30px rgba(0, 0, 0, 0.4);
}

.nav-inner {
  max-width: 72rem;
  margin: 0 auto;
  padding: 0 1.5rem;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.nav-logo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1rem;
  font-weight: 700;
  color: #c8daea;
  text-decoration: none;
  cursor: pointer;
  letter-spacing: -0.02em;
}
.nav-logo:hover {
  color: #e7f0fa;
}

.nav-link {
  font-size: 0.875rem;
  color: #3d5060;
  cursor: pointer;
  transition: color 0.15s;
  text-decoration: none;
}
.nav-link:hover {
  color: #8ea4be;
}

.nav-cta-ghost {
  padding: 0.4375rem 0.875rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #6b8099;
  background: transparent;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: color 0.15s;
}
.nav-cta-ghost:hover {
  color: #c8daea;
}

.nav-cta-primary {
  padding: 0.4375rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: white;
  background: #408ed6;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: background 0.2s;
}
.nav-cta-primary:hover {
  background: #5ba3e8;
}

.nav-hamburger {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: transparent;
  color: #6b8099;
  cursor: pointer;
  transition:
    color 0.15s,
    border-color 0.15s;
}
.nav-hamburger:hover {
  color: #c8daea;
  border-color: rgba(255, 255, 255, 0.15);
}

/* Hide hamburger on desktop, hide desktop nav on mobile */
@media (min-width: 1024px) {
  .nav-hamburger {
    display: none;
  }
}

/* Mobile menu */
.mobile-menu {
  position: fixed;
  top: 60px;
  left: 0;
  right: 0;
  z-index: 49;
  background: rgba(5, 10, 20, 0.97);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  padding: 1rem 1.5rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.mobile-nav-link {
  display: block;
  padding: 0.625rem 0.5rem;
  font-size: 0.9375rem;
  color: #3d5060;
  cursor: pointer;
  border-radius: 6px;
  transition:
    color 0.15s,
    background 0.15s;
}
.mobile-nav-link:hover {
  color: #8ea4be;
  background: rgba(255, 255, 255, 0.03);
}

/* Footer */
.landing-footer {
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  background: #030710;
  padding: 3rem 0 0;
}

.footer-cta-band {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
  padding: 2rem 2.5rem;
  background: rgba(64, 142, 214, 0.06);
  border: 1px solid rgba(64, 142, 214, 0.12);
  border-radius: 14px;
  margin-bottom: 2.5rem;
}
@media (max-width: 640px) {
  .footer-cta-band {
    flex-direction: column;
    text-align: center;
  }
}

.footer-col-title {
  font-size: 0.75rem;
  font-weight: 700;
  color: #2a3a48;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  margin-bottom: 0.875rem;
}

.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.footer-link {
  font-size: 0.8125rem;
  color: #1e2e3a;
  cursor: pointer;
  transition: color 0.15s;
  text-decoration: none;
}
.footer-link:hover {
  color: #6b8099;
}

.footer-social {
  color: #1e2e3a;
  transition: color 0.15s;
  text-decoration: none;
}
.footer-social:hover {
  color: #5ba3e8;
}
</style>
