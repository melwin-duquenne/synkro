<script setup lang="ts">
import { ref } from 'vue'

const form = ref({
  name: '',
  email: '',
  subject: '',
  message: ''
})

const isSubmitting = ref(false)
const submitStatus = ref<'idle' | 'success' | 'error'>('idle')
const errorMessage = ref('')

const errors = ref({
  name: '',
  email: '',
  subject: '',
  message: ''
})

function validateEmail(email: string): boolean {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return re.test(email)
}

function validateForm(): boolean {
  let isValid = true
  errors.value = { name: '', email: '', subject: '', message: '' }

  if (!form.value.name.trim()) {
    errors.value.name = 'Le nom est requis'
    isValid = false
  }

  if (!form.value.email.trim()) {
    errors.value.email = 'L\'email est requis'
    isValid = false
  } else if (!validateEmail(form.value.email)) {
    errors.value.email = 'Email invalide'
    isValid = false
  }

  if (!form.value.subject.trim()) {
    errors.value.subject = 'Le sujet est requis'
    isValid = false
  }

  if (!form.value.message.trim()) {
    errors.value.message = 'Le message est requis'
    isValid = false
  } else if (form.value.message.trim().length < 10) {
    errors.value.message = 'Le message doit contenir au moins 10 caractères'
    isValid = false
  }

  return isValid
}

async function submitForm() {
  if (!validateForm()) {
    return
  }

  isSubmitting.value = true
  submitStatus.value = 'idle'
  errorMessage.value = ''

  try {
    // TODO: Remplacer par l'appel API réel
    await new Promise(resolve => setTimeout(resolve, 1500))
    
    // Simuler succès
    submitStatus.value = 'success'
    
    // Reset form
    form.value = {
      name: '',
      email: '',
      subject: '',
      message: ''
    }

    // Reset status après 5s
    setTimeout(() => {
      submitStatus.value = 'idle'
    }, 5000)
  } catch (error: any) {
    submitStatus.value = 'error'
    errorMessage.value = error.message || 'Une erreur est survenue. Veuillez réessayer.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <section id="contact" class="py-20 lg:py-32 bg-base-200">
    <div class="container mx-auto px-4">
      <div class="max-w-5xl mx-auto">
        <!-- Section Header -->
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-bold mb-4">
            Contactez-nous
          </h2>
          <p class="text-xl text-base-content/70 max-w-2xl mx-auto">
            Une question ? Une suggestion ? N'hésitez pas à nous contacter
          </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Contact Info -->
          <div class="space-y-6">
            <!-- Email -->
            <div class="card bg-base-100 shadow-lg">
              <div class="card-body">
                <div class="flex items-center gap-3 mb-2">
                  <div class="w-10 h-10 rounded-lg bg-primary/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <h3 class="font-semibold">Email</h3>
                </div>
                <a href="mailto:contact@synkro.com" class="text-primary hover:underline">
                  contact@synkro.com
                </a>
              </div>
            </div>

            <!-- Support -->
            <div class="card bg-base-100 shadow-lg">
              <div class="card-body">
                <div class="flex items-center gap-3 mb-2">
                  <div class="w-10 h-10 rounded-lg bg-secondary/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                  </div>
                  <h3 class="font-semibold">Support</h3>
                </div>
                <a href="mailto:support@synkro.com" class="text-secondary hover:underline">
                  support@synkro.com
                </a>
              </div>
            </div>

            <!-- Social -->
            <div class="card bg-base-100 shadow-lg">
              <div class="card-body">
                <div class="flex items-center gap-3 mb-4">
                  <div class="w-10 h-10 rounded-lg bg-accent/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                  </div>
                  <h3 class="font-semibold">Réseaux sociaux</h3>
                </div>
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
            </div>
          </div>

          <!-- Contact Form -->
          <div class="lg:col-span-2">
            <div class="card bg-base-100 shadow-lg">
              <div class="card-body">
                <form @submit.prevent="submitForm" class="space-y-4">
                  <!-- Name -->
                  <div class="form-control">
                    <label class="label">
                      <span class="label-text font-medium">Nom</span>
                    </label>
                    <input
                      v-model="form.name"
                      type="text"
                      placeholder="Votre nom"
                      class="input input-bordered"
                      :class="{ 'input-error': errors.name }"
                      :disabled="isSubmitting"
                    />
                    <label v-if="errors.name" class="label">
                      <span class="label-text-alt text-error">{{ errors.name }}</span>
                    </label>
                  </div>

                  <!-- Email -->
                  <div class="form-control">
                    <label class="label">
                      <span class="label-text font-medium">Email</span>
                    </label>
                    <input
                      v-model="form.email"
                      type="email"
                      placeholder="votre@email.com"
                      class="input input-bordered"
                      :class="{ 'input-error': errors.email }"
                      :disabled="isSubmitting"
                    />
                    <label v-if="errors.email" class="label">
                      <span class="label-text-alt text-error">{{ errors.email }}</span>
                    </label>
                  </div>

                  <!-- Subject -->
                  <div class="form-control">
                    <label class="label">
                      <span class="label-text font-medium">Sujet</span>
                    </label>
                    <input
                      v-model="form.subject"
                      type="text"
                      placeholder="Sujet de votre message"
                      class="input input-bordered"
                      :class="{ 'input-error': errors.subject }"
                      :disabled="isSubmitting"
                    />
                    <label v-if="errors.subject" class="label">
                      <span class="label-text-alt text-error">{{ errors.subject }}</span>
                    </label>
                  </div>

                  <!-- Message -->
                  <div class="form-control">
                    <label class="label">
                      <span class="label-text font-medium">Message</span>
                    </label>
                    <textarea
                      v-model="form.message"
                      placeholder="Votre message..."
                      class="textarea textarea-bordered h-32"
                      :class="{ 'textarea-error': errors.message }"
                      :disabled="isSubmitting"
                    ></textarea>
                    <label v-if="errors.message" class="label">
                      <span class="label-text-alt text-error">{{ errors.message }}</span>
                    </label>
                  </div>

                  <!-- Success Message -->
                  <div v-if="submitStatus === 'success'" class="alert alert-success">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Message envoyé avec succès ! Nous vous répondrons rapidement.</span>
                  </div>

                  <!-- Error Message -->
                  <div v-if="submitStatus === 'error'" class="alert alert-error">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ errorMessage }}</span>
                  </div>

                  <!-- Submit Button -->
                  <button
                    type="submit"
                    class="btn btn-primary btn-block gap-2"
                    :disabled="isSubmitting"
                  >
                    <span v-if="!isSubmitting">Envoyer le message</span>
                    <span v-else class="loading loading-spinner loading-sm"></span>
                    <span v-if="!isSubmitting">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                      </svg>
                    </span>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
