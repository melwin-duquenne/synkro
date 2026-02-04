<script setup lang="ts">
function scrollToSection(sectionId: string) {
  const element = document.getElementById(sectionId)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
  }
}
</script>

<template>
  <footer class="bg-base-300 border-t border-base-content/10">
    <div class="container mx-auto px-4 py-12">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
        <!-- Brand -->
        <div class="lg:col-span-2">
          <div class="flex items-center gap-2 mb-4">
            <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span class="text-2xl font-bold">Synkro</span>
          </div>
          <p class="text-base-content/70 mb-4 max-w-xs">
            Collaborez mieux ensemble. La plateforme tout-en-un pour vos projets d'équipe.
          </p>
          <div class="flex gap-2">
            <a href="https://github.com" target="_blank" class="btn btn-circle btn-ghost btn-sm">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
              </svg>
            </a>
            <a href="https://twitter.com" target="_blank" class="btn btn-circle btn-ghost btn-sm">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/>
              </svg>
            </a>
            <a href="https://linkedin.com" target="_blank" class="btn btn-circle btn-ghost btn-sm">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
              </svg>
            </a>
          </div>
        </div>

        <!-- Product -->
        <div>
          <h3 class="font-semibold text-lg mb-4">Produit</h3>
          <ul class="space-y-2">
            <li>
              <a @click="scrollToSection('features')" class="link link-hover text-base-content/70">
                Fonctionnalités
              </a>
            </li>
            <li>
              <a href="#" class="link link-hover text-base-content/70">Tarifs</a>
            </li>
            <li>
              <a @click="scrollToSection('changelog')" class="link link-hover text-base-content/70">
                Changelog
              </a>
            </li>
            <li>
              <a href="#" class="link link-hover text-base-content/70">Roadmap</a>
            </li>
          </ul>
        </div>

        <!-- Support -->
        <div>
          <h3 class="font-semibold text-lg mb-4">Support</h3>
          <ul class="space-y-2">
            <li>
              <a href="#" class="link link-hover text-base-content/70">Documentation</a>
            </li>
            <li>
              <a href="#" class="link link-hover text-base-content/70">Guides</a>
            </li>
            <li>
              <a href="#" class="link link-hover text-base-content/70">FAQ</a>
            </li>
            <li>
              <a @click="scrollToSection('contact')" class="link link-hover text-base-content/70">
                Contact
              </a>
            </li>
          </ul>
        </div>

        <!-- Legal -->
        <div>
          <h3 class="font-semibold text-lg mb-4">Légal</h3>
          <ul class="space-y-2">
            <li>
              <a href="#" class="link link-hover text-base-content/70">Conditions d'utilisation</a>
            </li>
            <li>
              <a href="#" class="link link-hover text-base-content/70">Politique de confidentialité</a>
            </li>
            <li>
              <a href="#" class="link link-hover text-base-content/70">Cookies</a>
            </li>
            <li>
              <a href="#" class="link link-hover text-base-content/70">Mentions légales</a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Bottom -->
      <div class="divider"></div>
      <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-base-content/60">
        <p>© 2026 Synkro. Tous droits réservés.</p>
        <div class="flex gap-2 items-center">
          <span>Fait avec</span>
          <svg class="w-4 h-4 text-error" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
          </svg>
          <span>en France</span>
        </div>
      </div>
    </div>
  </footer>
</template>
