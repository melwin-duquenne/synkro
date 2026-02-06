<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="open && event" 
                 class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                 style="background-color: rgba(0, 0, 0, 0.6);"
                 @click.self="close">
                <div class="w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden"
                     style="max-height: 90vh; display: flex; flex-direction: column;"
                     @click.stop>
                    
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-primary to-primary-focus p-6 text-white relative">
                        <button 
                            class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/20 transition-colors"
                            @click="close"
                            type="button">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <h3 class="text-2xl font-bold pr-10">Détails de l'événement</h3>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-5" style="color: inherit;">
                        
                        <!-- Titre et type -->
                        <div class="flex items-start gap-4">
                            <div v-html="getEventIcon(event.eventType)" class="flex-shrink-0 mt-1"></div>
                            <div class="flex-1">
                                <h4 class="text-2xl font-bold mb-2" style="color: inherit;">{{ event.title }}</h4>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold"
                                      :class="getEventBadgeClass(event.eventType)">
                                    {{ getEventTypeLabel(event.eventType) }}
                                </span>
                            </div>
                        </div>

                        <!-- Horaires -->
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-5">
                            <h5 class="font-semibold mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Horaires
                            </h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <div class="text-xs uppercase tracking-wide opacity-70 mb-1">Début</div>
                                    <div class="font-semibold">{{ formatDateTime(event.startDate) }}</div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase tracking-wide opacity-70 mb-1">Fin</div>
                                    <div class="font-semibold">{{ formatDateTime(event.endDate) }}</div>
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                                <span class="text-sm font-medium">
                                    ⏱️ Durée: {{ formatDuration(event.startDate, event.endDate) }}
                                </span>
                            </div>
                        </div>

                        <!-- Room associée -->
                        <div v-if="event.room" class="bg-primary/10 rounded-xl p-5 border-2 border-primary/20">
                            <h5 class="font-semibold mb-3 flex items-center gap-2 text-primary">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Room associée
                            </h5>
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-bold text-lg text-primary">{{ event.room.name }}</span>
                                <button 
                                    class="px-4 py-2 bg-primary hover:bg-primary-focus text-white rounded-lg font-medium transition-colors"
                                    @click="navigateToRoom(event.room.id)"
                                    type="button">
                                    Accéder à la room →
                                </button>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="event.description" class="bg-gray-100 dark:bg-gray-700 rounded-xl p-5">
                            <h5 class="font-semibold mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                                </svg>
                                Description
                            </h5>
                            <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ event.description }}</p>
                        </div>

                        <!-- Message si pas de description -->
                        <div v-else class="text-center py-4 text-gray-500 text-sm italic">
                            Aucune description disponible pour cet événement
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="border-t border-gray-200 dark:border-gray-700 p-6 bg-gray-50 dark:bg-gray-800/50">
                        <div class="flex gap-3 justify-end">
                            <button 
                                class="px-6 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg font-medium transition-colors"
                                @click="close"
                                type="button">
                                Fermer
                            </button>
                            <button 
                                v-if="event.room" 
                                class="px-6 py-2 bg-primary hover:bg-primary-focus text-white rounded-lg font-medium transition-colors"
                                @click="navigateToRoom(event.room.id)"
                                type="button">
                                Rejoindre la room
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

interface Event {
    id: number
    title: string
    startDate: string
    endDate: string
    eventType: string
    description?: string
    room: { id: number; name: string } | null
}

const props = defineProps<{
    open: boolean
    event: Event | null
}>()

const emit = defineEmits<{
    close: []
}>()

const router = useRouter()

const close = () => {
    emit('close')
}

const navigateToRoom = (roomId?: number) => {
    if (roomId) {
        router.push(`/room/${roomId}`)
        close()
    }
}

const getEventIcon = (type: string): string => {
    const icons: Record<string, string> = {
        meeting: '<svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>',
        absence: '<svg class="w-6 h-6 text-error" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>',
        reminder: '<svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>',
    }
    return icons[type] || '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>'
}

const getEventBadgeClass = (type: string): string => {
    const classes: Record<string, string> = {
        meeting: 'bg-primary text-primary-content',
        absence: 'bg-error text-error-content',
        reminder: 'bg-warning text-warning-content'
    }
    return classes[type] || 'bg-info text-info-content'
}

const getEventTypeLabel = (type: string): string => {
    const labels: Record<string, string> = {
        meeting: 'Réunion',
        absence: 'Absence',
        reminder: 'Rappel'
    }
    return labels[type] || type
}

const formatDateTime = (dateString: string): string => {
    // Parser manuellement la date ISO pour éviter les conversions de timezone
    const match = dateString.match(/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/)
    if (!match) return dateString
    
    const [, year, month, day, hour, minute] = match
    const date = new Date(parseInt(year), parseInt(month) - 1, parseInt(day), parseInt(hour), parseInt(minute))
    
    return date.toLocaleString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatDuration = (start: string, end: string): string => {
    // Parser manuellement les dates pour éviter les conversions de timezone
    const matchStart = start.match(/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/)
    const matchEnd = end.match(/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/)
    
    if (!matchStart || !matchEnd) return ''
    
    const [, yearS, monthS, dayS, hourS, minuteS] = matchStart
    const [, yearE, monthE, dayE, hourE, minuteE] = matchEnd
    
    const startDate = new Date(parseInt(yearS), parseInt(monthS) - 1, parseInt(dayS), parseInt(hourS), parseInt(minuteS))
    const endDate = new Date(parseInt(yearE), parseInt(monthE) - 1, parseInt(dayE), parseInt(hourE), parseInt(minuteE))
    const diffMs = endDate.getTime() - startDate.getTime()
    const diffMins = Math.floor(diffMs / 60000)

    if (diffMins < 60) {
        return `${diffMins} minutes`
    }

    const hours = Math.floor(diffMins / 60)
    const mins = diffMins % 60

    if (mins === 0) {
        return `${hours} heure${hours > 1 ? 's' : ''}`
    }

    return `${hours}h ${mins}min`
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active > div,
.modal-leave-active > div {
    transition: transform 0.3s ease;
}

.modal-enter-from > div,
.modal-leave-to > div {
    transform: scale(0.95);
}
</style>
