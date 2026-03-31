<template>
  <section id="pricing" class="py-20 bg-base-200">
    <div class="container mx-auto px-4">
      <!-- Header -->
      <div class="text-center mb-16">
        <h2 class="text-4xl md:text-5xl font-bold mb-4">
          Des tarifs adaptés à vos besoins
        </h2>
        <p class="text-xl text-base-content/70 max-w-2xl mx-auto">
          Choisissez l'offre qui correspond le mieux à votre équipe. Toutes les offres incluent les mises à jour et le support technique.
        </p>
      </div>

      <!-- Pricing Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 max-w-7xl mx-auto">
        <!-- Freemium -->
        <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300">
          <div class="card-body">
            <div class="flex items-center gap-2 mb-2">
              <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              <h3 class="card-title">Freemium</h3>
            </div>
            <div class="mb-4">
              <span class="text-4xl font-bold">0€</span>
              <span class="text-base-content/60">/mois</span>
            </div>
            <p class="text-base-content/70 mb-6">Parfait pour découvrir Synkro</p>
            
            <ul class="space-y-3 mb-6 flex-1">
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Jusqu'à 3 utilisateurs</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>3 espaces de travail</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>5 Go de stockage</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Modules de base</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-base-content/30 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span class="text-base-content/50">Support prioritaire</span>
              </li>
            </ul>

            <button class="btn btn-outline btn-primary w-full">
              Commencer gratuitement
            </button>
          </div>
        </div>

        <!-- Premium -->
        <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300 border-2 border-primary relative">
          <div class="absolute -top-4 left-1/2 -translate-x-1/2">
            <span class="badge badge-primary badge-lg">Populaire</span>
          </div>
          <div class="card-body">
            <div class="flex items-center gap-2 mb-2">
              <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
              </svg>
              <h3 class="card-title">Premium</h3>
            </div>
            <div class="mb-4">
              <span class="text-4xl font-bold">12€</span>
              <span class="text-base-content/60">/utilisateur/mois</span>
            </div>
            <p class="text-base-content/70 mb-6">Pour les équipes en croissance</p>
            
            <ul class="space-y-3 mb-6 flex-1">
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Utilisateurs illimités</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Espaces illimités</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>100 Go de stockage</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Tous les modules</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Support prioritaire</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Intégrations avancées</span>
              </li>
            </ul>

            <button class="btn btn-primary w-full">
              Essayer 14 jours gratuits
            </button>
          </div>
        </div>

        <!-- Enterprise -->
        <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300">
          <div class="card-body">
            <div class="flex items-center gap-2 mb-2">
              <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              <h3 class="card-title">Enterprise</h3>
            </div>
            <div class="mb-4">
              <span class="text-4xl font-bold">Sur mesure</span>
            </div>
            <p class="text-base-content/70 mb-6">Pour les grandes organisations</p>
            
            <ul class="space-y-3 mb-6 flex-1">
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Tout Premium +</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Stockage illimité</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>SLA 99.9%</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Account Manager dédié</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>SSO & SAML</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Formation personnalisée</span>
              </li>
            </ul>

            <button class="btn btn-outline btn-warning w-full">
              Nous contacter
            </button>
          </div>
        </div>

        <!-- Local -->
        <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300">
          <div class="card-body">
            <div class="flex items-center gap-2 mb-2">
              <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
              </svg>
              <h3 class="card-title">Local</h3>
            </div>
            <div class="mb-4">
              <span class="text-4xl font-bold">Sur devis</span>
            </div>
            <p class="text-base-content/70 mb-6">Hébergement on-premise</p>
            
            <ul class="space-y-3 mb-6 flex-1">
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Tout Enterprise +</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Hébergement sur vos serveurs</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Contrôle total des données</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Installation & configuration</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Mises à jour prioritaires</span>
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-success mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Conformité RGPD maximale</span>
              </li>
            </ul>

            <button class="btn btn-outline btn-info w-full">
              Demander un devis
            </button>
          </div>
        </div>
      </div>

      <!-- FAQ Section -->
      <div class="mt-20 max-w-3xl mx-auto">
        <h3 class="text-2xl font-bold text-center mb-8">Questions fréquentes</h3>
        <div class="space-y-4">
          <div class="collapse collapse-plus bg-base-100 shadow">
            <input type="radio" name="pricing-faq" checked="false" />
            <div class="collapse-title text-lg font-medium">
              Puis-je changer d'offre à tout moment ?
            </div>
            <div class="collapse-content">
              <p class="text-base-content/70">
                Oui, vous pouvez upgrader ou downgrader votre offre à tout moment. Les changements prennent effet immédiatement et sont proratisés.
              </p>
            </div>
          </div>

          <div class="collapse collapse-plus bg-base-100 shadow">
            <input type="radio" name="pricing-faq" />
            <div class="collapse-title text-lg font-medium">
              Y a-t-il un engagement minimum ?
            </div>
            <div class="collapse-content">
              <p class="text-base-content/70">
                Non, toutes nos offres sont sans engagement. Vous pouvez annuler votre abonnement à tout moment.
              </p>
            </div>
          </div>

          <div class="collapse collapse-plus bg-base-100 shadow">
            <input type="radio" name="pricing-faq" />
            <div class="collapse-title text-lg font-medium">
              Quels moyens de paiement acceptez-vous ?
            </div>
            <div class="collapse-content">
              <p class="text-base-content/70">
                Nous acceptons les cartes bancaires (Visa, Mastercard, American Express) et les virements bancaires pour les offres Enterprise et Local.
              </p>
            </div>
          </div>

          <div class="collapse collapse-plus bg-base-100 shadow">
            <input type="radio" name="pricing-faq" />
            <div class="collapse-title text-lg font-medium">
              Que se passe-t-il si je dépasse mes limites de stockage ?
            </div>
            <div class="collapse-content">
              <p class="text-base-content/70">
                Vous recevrez une notification et pourrez soit upgrader votre offre, soit acheter du stockage supplémentaire à 2€/10Go/mois.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
// Pas de logique nécessaire pour cette section statique
</script>

<style scoped>
.card {
  height: 100%;
}
</style>
