# Tests backend — Synkro

## Stack technique

- **Framework de test** : PHPUnit 12
- **Type de tests** : Tests unitaires purs (aucune base de données, aucun serveur HTTP)
- **Localisation** : `backend/tests/Unit/`
- **Commande** : `cd backend && php vendor/bin/phpunit tests/Unit --testdox`

---

## Type de tests mis en place

Tous les tests sont des **tests unitaires** (`PHPUnit\Framework\TestCase`).

Deux stratégies de doublures sont utilisées selon le besoin :

| Stratégie | Quand | Méthode PHPUnit |
|-----------|-------|-----------------|
| **Stub** | Simuler un retour sans vérifier les appels | `createStub()` |
| **Mock** | Vérifier qu'une méthode est appelée (ex : `persist`, `flush`) | `createMock()` + `expects()` |

Les entités Doctrine (`User`, `Room`, `Invitation`, etc.) sont instanciées directement comme de vrais objets PHP (POPO) sans mock, car elles ne dépendent d'aucun service.

---

## Ce qui est testé

### Entités (`tests/Unit/Entity/`)

| Fichier | Classe testée | Tests |
|---------|---------------|-------|
| `UserTest.php` | `App\Entity\User` | `isAtLeast()` retourne false si rôle insuffisant · retourne true si rôle suffisant · `canAssignRole()` owner ne peut pas attribuer le rôle admin |
| `RoomTest.php` | `App\Entity\Room` | `isPrivate()` retourne true si visibility = private · retourne false si visibility = enterprise |
| `InvitationTest.php` | `App\Entity\Invitation` | `isExpired()` retourne true si date passée · retourne false si date future |

> Ces tests sont des **POPO purs** : aucun mock, aucune dépendance externe.

---

### Sécurité (`tests/Unit/Security/`)

| Fichier | Classe testée | Tests |
|---------|---------------|-------|
| `RoomAccessCheckerTest.php` | `App\Security\RoomAccessChecker` | `canAccess()` retourne false si entreprises différentes · retourne true si même entreprise + visibilité enterprise · `canManageMembers()` retourne false si visibilité ≠ private |

> Instanciation directe (`new RoomAccessChecker()`), sans constructeur. Room, User et Entreprise sont de vrais POPOs. Les comparaisons d'accès se font par référence objet (`===`).

---

### Service (`tests/Unit/Service/`)

| Fichier | Classe testée | Tests |
|---------|---------------|-------|
| `WorkloadCalculatorTest.php` | `App\Service\WorkloadCalculator` | `calculateDailyWorkload()` retourne `normal` si charge < 80 % · `busy` si 80–99 % · `overloaded` si ≥ 100 % |

> La chaîne Doctrine (`EntityManager → Repository → QueryBuilder → Query → getResult()`) est entièrement stubbée. Les événements CalendarEvent sont de vrais objets avec `TYPE_BLOCKED` (pour éviter les modificateurs de réunion) et un seul événement par test (pour éviter la détection de pauses insuffisantes).

---

### Processors API Platform (`tests/Unit/State/`)

| Fichier | Classe testée | Tests |
|---------|---------------|-------|
| `AuthProcessorTest.php` | `App\State\AuthProcessor` | Lève `ConflictHttpException` si email déjà existant · hash le mot de passe et persiste l'utilisateur · lève `BadRequestHttpException` si validation échoue |
| `ResetPasswordProcessorTest.php` | `App\State\ResetPasswordProcessor` | Retourne un message générique même si l'email n'existe pas · définit le token et envoie l'email si l'utilisateur existe · lève `BadRequestHttpException` si le token est expiré |
| `InvitationProcessorTest.php` | `App\State\InvitationProcessor` | Lève `ConflictHttpException` si l'utilisateur est déjà dans l'entreprise · si une invitation pending existe déjà · lève `BadRequestHttpException` si l'invitation est expirée |
| `KanbanColumnProcessorTest.php` | `App\State\KanbanColumnProcessor` | Lève `BadRequestHttpException` si la colonne a des tâches actives · supprime la colonne et flush si aucune tâche active · lève `NotFoundHttpException` si la colonne appartient à une autre room |
| `AdminUserProcessorTest.php` | `App\State\AdminUserProcessor` | Lève `BadRequestHttpException` si un utilisateur tente de changer son propre rôle · lève `AccessDeniedHttpException` si un owner tente de modifier le rôle d'un admin · lève `BadRequestHttpException` si un utilisateur tente de supprimer son propre compte |

---

## Résultat actuel

```
Tests: 28, Assertions: 52, OK
```

Tous les tests passent, aucune notice PHPUnit.

---

## Ce qui n'est pas testé (hors scope)

Les entités sans logique métier non-triviale ne sont pas testées (getters/setters simples) :

`CalendarEvent`, `Team`, `Entreprise`, `Module`, `Message`, `Task`, `KanbanColumn`, `FileResource`, `UserRoomPermission`, `ModuleRoom`

Les tests d'intégration (appels HTTP, base de données réelle) ne sont pas encore mis en place.
