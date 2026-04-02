/**
 * Extrait un message d'erreur lisible depuis une réponse API.
 * Gère les deux formats :
 *  - API Platform : { "hydra:description": "..." } ou { "detail": "..." }
 *  - Lexik JWT    : { "message": "..." }
 */
export function extractApiError(
  data: unknown,
  fallback = 'Une erreur est survenue, veuillez réessayer'
): string {
  if (!data || typeof data !== 'object') return fallback
  const d = data as Record<string, unknown>
  return (
    (typeof d['hydra:description'] === 'string' && d['hydra:description']) ||
    (typeof d.detail === 'string' && d.detail) ||
    (typeof d.message === 'string' && d.message) ||
    (typeof d.error === 'string' && d.error) ||
    fallback
  )
}
