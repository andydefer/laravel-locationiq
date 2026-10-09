# Laravel LocationIQ

**Intégration Laravel du SDK LocationIQ/Nominatim pour le géocodage, les itinéraires, les fuseaux horaires et les matrices de durées/distances.**

---

## Table des matières

1. [Introduction](#1-introduction)
2. [Installation](#2-installation)
3. [Configuration](#3-configuration)
4. [Routes exposées](#4-routes-exposées)
5. [Utilisation](#5-utilisation)
6. [Contrats et extension](#6-contrats-et-extension)
7. [Gestion des erreurs](#7-gestion-des-erreurs)
8. [Tests](#8-tests)
9. [Sécurité](#9-sécurité)
10. [Compatibilité](#10-compatibilité)

---

## 1. Introduction

### Objectif

`andydefer/laravel-locationiq` expose les opérations LocationIQ et Nominatim (solde de quota, fuseau horaire, itinéraires, matrices de durées/distances, reverse geocoding) sous forme d'endpoints HTTP Laravel prêts à l'emploi.

Le package s'appuie sur `andydefer/php-locationiq`, qui contient toute la logique métier et les objets typés (`Record`, `Response`, `Data`, `Graph`, `Collection`). Laravel LocationIQ n'ajoute que la couche transport : routes, validation, injection.

### Ce que le package fournit

- **5 endpoints HTTP** : solde de quota, fuseau horaire, itinéraires, matrices, reverse geocoding.
- **4 `FormRequest`** : validation stricte des payloads et construction des `Record` typés du SDK.
- **5 `Action`** : résolvent le client LocationIQ ou Nominatim via la configuration, appellent l'opération correspondante et renvoient la `Data` typée. En cas d'erreur, l'action propage un `ErrorResponseData` normalisé.
- **Une configuration unique** : clé API, URL régionale, URL Nominatim, User-Agent, profil de directions par défaut.
- **Deux points d'extension** : remplacer le client LocationIQ (`locationiq_client_fqcn`) ou le client Nominatim (`nominatim_client_fqcn`) sans modifier le package.

### Ce que le package ne fait pas

- Aucune authentification. Les routes sont publiées sans middleware.
- Aucune persistance. Le package ne touche pas à la base de données.
- Aucune mise en cache. À implémenter côté application hôte selon les besoins.

---

## 2. Installation

```bash
composer require andydefer/laravel-locationiq
```

Le `LocationIqServiceProvider` est découvert automatiquement par Laravel.

---

## 3. Configuration

### 3.1 Publier les fichiers

```bash
php artisan vendor:publish --tag=laravel-locationiq-config
php artisan vendor:publish --tag=laravel-locationiq-routes
```

- Config publiée dans `config/locationiq.php`.
- Routes publiées dans `routes/locationiq.php`.

### 3.2 Charger les routes

Les routes ne sont pas chargées automatiquement. Charge-les explicitement depuis ton application :

```php
// routes/api.php
require base_path('routes/locationiq.php');
```

Tu contrôles ainsi le préfixe, les middlewares et l'ordre de chargement.

### 3.3 Fichier de configuration

```php
<?php

declare(strict_types=1);

use AndyDefer\PhpLocationIq\Enums\LocationIqBaseUrl;
use AndyDefer\PhpLocationIq\Enums\NominatimBaseUrl;
use AndyDefer\PhpLocationIq\LocationIqClient;
use AndyDefer\PhpLocationIq\NominatimClient;

return [
    'api_key' => env('LOCATIONIQ_API_KEY', ''),

    'base_url' => env('LOCATIONIQ_BASE_URL', LocationIqBaseUrl::US1->value),

    'locationiq_client_fqcn' => LocationIqClient::class,

    'nominatim' => [
        'base_url' => env('NOMINATIM_BASE_URL', NominatimBaseUrl::PUBLIC->value),
        'user_agent' => env('NOMINATIM_USER_AGENT', 'andydefer/laravel-locationiq'),
        'accept_language' => env('NOMINATIM_ACCEPT_LANGUAGE', 'fr'),
    ],

    'nominatim_client_fqcn' => NominatimClient::class,

    'directions' => [
        'profile' => env('LOCATIONIQ_DIRECTIONS_PROFILE', 'driving'),
        'accept_language' => env('LOCATIONIQ_ACCEPT_LANGUAGE'),
    ],

    'matrix' => [
        'profile' => env('LOCATIONIQ_MATRIX_PROFILE', 'driving'),
    ],
];
```

### 3.4 Clés de configuration

| Clé | Type | Défaut | Description |
|-----|------|--------|-------------|
| `api_key` | `string` | `''` | Clé API LocationIQ |
| `base_url` | `string` | `https://us1.locationiq.com` | URL régionale LocationIQ (`us1`, `eu1`) |
| `locationiq_client_fqcn` | `class-string` | `LocationIqClient::class` | Implémentation de `LocationIqClientInterface` |
| `nominatim.base_url` | `string` | `https://nominatim.openstreetmap.org` | URL Nominatim |
| `nominatim.user_agent` | `string` | `andydefer/laravel-locationiq` | User-Agent obligatoire pour Nominatim |
| `nominatim.accept_language` | `string` | `fr` | Langue préférée des résultats Nominatim |
| `nominatim_client_fqcn` | `class-string` | `NominatimClient::class` | Implémentation de `NominatimClientInterface` |
| `directions.profile` | `string` | `driving` | Profil par défaut pour Directions (`driving`, `walking`) |
| `directions.accept_language` | `string\|null` | `null` | Langue préférée des réponses LocationIQ |
| `matrix.profile` | `string` | `driving` | Profil par défaut pour Matrix (`driving`, `walking`) |

### 3.5 Variables d'environnement

```env
LOCATIONIQ_API_KEY=pk.xxxxxxxxxxxxxxxxxxxxxxxx
LOCATIONIQ_BASE_URL=us1
NOMINATIM_USER_AGENT=MonApp/1.0 (contact@exemple.com)
NOMINATIM_ACCEPT_LANGUAGE=fr
LOCATIONIQ_DIRECTIONS_PROFILE=driving
LOCATIONIQ_MATRIX_PROFILE=driving
```

### 3.6 Cas concret : configuration Afya (RDC)

```php
// config/locationiq.php
return [
    'api_key' => env('LOCATIONIQ_API_KEY', ''),
    'base_url' => LocationIqBaseUrl::EU1->value,

    'nominatim' => [
        'base_url' => NominatimBaseUrl::PUBLIC->value,
        'user_agent' => 'AfyaMedical/1.0 (ops@afya-medical.com)',
        'accept_language' => 'fr',
    ],

    'directions' => [
        'profile' => 'driving',
    ],

    'matrix' => [
        'profile' => 'driving',
    ],
];
```

---

## 4. Routes exposées

Le fichier `routes/locationiq.php` déclare cinq endpoints, tous en `POST`, sous le préfixe `locationiq`.

| Méthode | URI | Nom | Description |
|---------|-----|-----|-------------|
| POST | `/locationiq/balance` | `locationiq.balance` | Récupérer le solde de requêtes du jour |
| POST | `/locationiq/timezone` | `locationiq.timezone` | Résoudre le fuseau horaire d'un point GPS |
| POST | `/locationiq/directions` | `locationiq.directions` | Calculer un itinéraire |
| POST | `/locationiq/matrix` | `locationiq.matrix` | Calculer une matrice de durées/distances |
| POST | `/locationiq/reverse` | `locationiq.reverse` | Reverse geocoding via Nominatim |

---

## 5. Utilisation

### 5.1 Récupérer le solde de quota

Requête :

```bash
curl -X POST https://app.test/locationiq/balance
```

Aucun paramètre.

Réponse succès (200) :

```json
{
  "balance": {
    "day": 30000
  }
}
```

### 5.2 Résoudre un fuseau horaire

Requête :

```bash
curl -X POST https://app.test/locationiq/timezone \
  -H "Content-Type: application/json" \
  -d '{
    "lat": 19.0760,
    "lon": 72.8777,
    "timestamp": 1609459200
  }'
```

Paramètres :

| Champ | Type | Requis | Contrainte |
|-------|------|--------|------------|
| `lat` | `numeric` | ✅ | Entre -90 et 90 |
| `lon` | `numeric` | ✅ | Entre -180 et 180 |
| `timestamp` | `integer` | ❌ | Unix timestamp, ≥ 0 |

Réponse succès (200) :

```json
{
  "timezone": {
    "name": "Asia/Kolkata",
    "nowInDst": false,
    "offsetSeconds": 19800,
    "shortName": "IST",
    "fullName": "India Standard Time"
  }
}
```

### 5.3 Calculer un itinéraire

Requête :

```bash
curl -X POST https://app.test/locationiq/directions \
  -H "Content-Type: application/json" \
  -d '{
    "coordinates": [
      [15.3222, -4.3250],
      [15.4446, -4.3858]
    ],
    "profile": "driving",
    "overview": "full",
    "steps": true,
    "alternatives": false,
    "geometries": "polyline"
  }'
```

Paramètres :

| Champ | Type | Requis | Contrainte |
|-------|------|--------|------------|
| `coordinates` | `array` | ✅ | 2 à 25 paires `[lon, lat]` |
| `coordinates.*` | `array` | ✅ | Exactement 2 valeurs |
| `coordinates.*.0` | `numeric` | ✅ | Entre -180 et 180 (longitude) |
| `coordinates.*.1` | `numeric` | ✅ | Entre -90 et 90 (latitude) |
| `profile` | `DirectionsProfile` | ❌ | `driving`, `walking` |
| `overview` | `OverviewType` | ❌ | `simplified`, `full`, `false` |
| `steps` | `boolean` | ❌ | Inclure les étapes détaillées |
| `alternatives` | `boolean` | ❌ | Retourner des itinéraires alternatifs |
| `geometries` | `GeometriesType` | ❌ | `polyline`, `polyline6`, `geojson` |

Réponse succès (200) :

```json
{
  "directions": {
    "code": "ok",
    "waypoints": [...],
    "routes": [...]
  }
}
```

> **Polymorphisme géré :** LocationIQ retourne tantôt un objet unique, tantôt un tableau. Le SDK normalise toujours en collection typée.

### 5.4 Calculer une matrice de durées/distances

Calcule les durées et/ou distances des trajets les plus rapides entre **toutes les paires** de coordonnées fournies. Utile pour comparer rapidement plusieurs points (livraisons, tournées, répartition géographique).

Requête minimale :

```bash
curl -X POST https://app.test/locationiq/matrix \
  -H "Content-Type: application/json" \
  -d '{
    "coordinates": [
      [-0.127627, 51.503355],
      [-0.087199, 51.509562],
      [-0.142001, 51.501284]
    ]
  }'
```

Requête complète :

```bash
curl -X POST https://app.test/locationiq/matrix \
  -H "Content-Type: application/json" \
  -d '{
    "coordinates": [
      [-0.127627, 51.503355],
      [-0.087199, 51.509562],
      [-0.142001, 51.501284]
    ],
    "profile": "driving",
    "annotations": ["duration", "distance"],
    "sources": [0],
    "destinations": [1, 2],
    "fallback_speed": 15.5,
    "fallback_coordinate": "snapped"
  }'
```

Paramètres :

| Champ | Type | Requis | Contrainte |
|-------|------|--------|------------|
| `coordinates` | `array` | ✅ | 2 à 25 paires `[lon, lat]` |
| `coordinates.*` | `array` | ✅ | Exactement 2 valeurs |
| `coordinates.*.0` | `numeric` | ✅ | Entre -180 et 180 (longitude) |
| `coordinates.*.1` | `numeric` | ✅ | Entre -90 et 90 (latitude) |
| `profile` | `DirectionsProfile` | ❌ | `driving`, `walking` |
| `annotations` | `array<MatrixAnnotation>` | ❌ | `duration`, `distance` (au moins un si fourni) |
| `sources` | `array<int>` | ❌ | Index de coordonnées, entre 0 et N-1 |
| `destinations` | `array<int>` | ❌ | Index de coordonnées, entre 0 et N-1 |
| `fallback_speed` | `numeric` | ❌ | Strictement supérieur à 0 |
| `fallback_coordinate` | `FallbackCoordinate` | ❌ | `input`, `snapped` |

Réponse succès (200) :

```json
{
  "durations": {
    "rows": [[0.0, 529.0, 185.7]]
  },
  "distances": {
    "rows": [[0.0, 3833.6, 1552.8]]
  },
  "sources": [
    {
      "name": "Downing Street",
      "distance": 85.752389,
      "longitude": -0.12643,
      "latitude": 51.503164,
      "hint": "..."
    }
  ],
  "destinations": [
    {
      "name": "King William Street",
      "distance": 0.069405,
      "longitude": -0.0872,
      "latitude": 51.509562,
      "hint": "..."
    }
  ],
  "error": null
}
```

#### Comprendre la matrice

- **Non symétrique.** `durations[0][1]` peut différer de `durations[1][0]` à cause des sens uniques, priorités aux carrefours et feux de circulation. C'est la réalité du trafic — et c'est précisément l'intérêt de l'endpoint Matrix.
- **Diagonale à zéro.** Le trajet d'un point vers lui-même est toujours `0.0`.
- **Distances routières.** Ce ne sont pas des distances à vol d'oiseau, mais la longueur du chemin routier le plus rapide.
- **Cellules sans route.** Si LocationIQ ne trouve pas de route, la cellule vaut `-1.0` (sentinel `FloatMatrixVO::NO_ROUTE_SENTINEL`). Vérifie avec `isNoRoute($row, $column)`.

#### Restreindre les sources et destinations

Par défaut, LocationIQ calcule la matrice complète `N×N`. Tu peux restreindre avec des index de coordonnées (base 0) :

- `sources: [0]` → matrice `1×N` (départs depuis la coordonnée #0 uniquement).
- `destinations: [1, 2]` → matrice `N×2`.
- `sources: [0]` **et** `destinations: [1, 2]` → matrice `1×2`.

Utile quand tu as un entrepôt fixe et plusieurs destinations. Économie de quota et de temps de calcul.

#### Fallback : paires non routables

Si tu veux garantir qu'aucune cellule ne soit vide, active le fallback :

```json
{
  "coordinates": [[...], [...]],
  "fallback_speed": 15.5,
  "fallback_coordinate": "snapped"
}
```

- **`fallback_speed`** : vitesse en **m/s** utilisée pour estimer la durée à partir de la distance à vol d'oiseau.
- **`fallback_coordinate`** : `input` (coordonnée envoyée) ou `snapped` (coordonnée projetée sur le réseau routier). `snapped` est plus cohérent avec le reste de la matrice.

### 5.5 Reverse geocoding

Requête :

```bash
curl -X POST https://app.test/locationiq/reverse \
  -H "Content-Type: application/json" \
  -d '{
    "lat": -4.3617,
    "lon": 15.2183,
    "format": "jsonv2",
    "accept_language": "fr",
    "zoom": 18,
    "address_details": true
  }'
```

Paramètres :

| Champ | Type | Requis | Contrainte |
|-------|------|--------|------------|
| `lat` | `numeric` | ✅ | Entre -90 et 90 |
| `lon` | `numeric` | ✅ | Entre -180 et 180 |
| `format` | `NominatimFormat` | ❌ | `json`, `jsonv2`, `geojson`, `geocodejson` |
| `accept_language` | `string` | ❌ | Code ISO 639-1, max 10 caractères |
| `zoom` | `integer` | ❌ | Entre 0 et 18 |
| `address_details` | `boolean` | ❌ | Inclure le bloc `address` |

Réponse succès (200) :

```json
{
  "reverse": {
    "licence": "Data © OpenStreetMap contributors",
    "osmType": "way",
    "osmId": 434892314,
    "location": { "longitude": 15.2185794, "latitude": -4.3619926 },
    "category": "highway",
    "type": "residential",
    "placeRank": 26,
    "importance": 0.0534,
    "addressType": "road",
    "name": "",
    "displayName": "Kasi, Lukunga, Ngaliema, Kinshasa, République démocratique du Congo",
    "address": {
      "cityDistrict": "Kasi",
      "city": "Lukunga",
      "municipality": "Ngaliema",
      "state": "Kinshasa",
      "iso3166Lvl4": "CD-KN",
      "country": "République démocratique du Congo",
      "countryCode": "cd"
    },
    "boundingBox": {
      "minLatitude": -4.3631191,
      "maxLatitude": -4.3619147,
      "minLongitude": 15.2171727,
      "maxLongitude": 15.2186610
    }
  }
}
```

---

## 6. Contrats et extension

### 6.1 `LocationIqConfigInterface`

Contrat de la configuration. Utilisé par le ServiceProvider pour construire les clients et par les `Action` pour les résoudre.

```php
<?php

declare(strict_types=1);

namespace AndyDefer\LaravelLocationIq\Contracts;

use AndyDefer\PhpLocationIq\Contracts\LocationIqClientInterface;
use AndyDefer\PhpLocationIq\Contracts\NominatimClientInterface;
use AndyDefer\PhpLocationIq\Enums\LocationIqBaseUrl;
use AndyDefer\PhpLocationIq\Enums\NominatimBaseUrl;

interface LocationIqConfigInterface
{
    public function getApiKey(): string;

    public function getBaseUrl(): LocationIqBaseUrl;

    /**
     * @return class-string<LocationIqClientInterface>
     */
    public function getLocationIqClientFqcn(): string;

    public function getNominatimBaseUrl(): NominatimBaseUrl;

    public function getUserAgent(): string;

    /**
     * @return class-string<NominatimClientInterface>
     */
    public function getNominatimClientFqcn(): string;

    public function getDefaultDirectionsProfile(): string;

    public function getDefaultAcceptLanguage(): ?string;
}
```

### 6.2 `ErrorDescribable`

Contrat hérité du package `andydefer/laravel-nemesis`. Tout enum d'erreur qui l'implémente expose une API cohérente :

- `getHttpStatusCode(): HttpStatusCode`
- `getMessage(): string`
- `getLabel(): string`
- `toResponseData(): ErrorResponseData`
- `toJsonResponseFactory(): ResponseFactory`

### 6.3 `ErrorCode`

Enum qui implémente `ErrorDescribable`. Couvre les codes d'erreur LocationIQ et Nominatim.

```php
enum ErrorCode: string implements ErrorDescribable
{
    case INVALID_REQUEST = 'INVALID_REQUEST';
    case INVALID_KEY = 'INVALID_KEY';
    case ACCESS_RESTRICTED = 'ACCESS_RESTRICTED';
    case UNABLE_TO_GEOCODE = 'UNABLE_TO_GEOCODE';
    case RATE_LIMITED_DAY = 'RATE_LIMITED_DAY';
    case INVALID_OPTIONS = 'INVALID_OPTIONS';
    case INVALID_COORDINATES = 'INVALID_COORDINATES';
    case MISSING_API_KEY = 'MISSING_API_KEY';
    case NO_TABLE = 'NO_TABLE';
    case NOT_IMPLEMENTED = 'NOT_IMPLEMENTED';
    case UNKNOWN_ERROR = 'UNKNOWN_ERROR';

    public static function fromApiMessage(string $message): self;
}
```

### 6.4 Remplacer le client LocationIQ

```php
// config/locationiq.php
'locationiq_client_fqcn' => \App\Geo\AfyaLocationIqClient::class,
```

Contraintes :

- La classe doit implémenter `LocationIqClientInterface`.
- Son constructeur doit être auto-résolvable par le conteneur Laravel.

### 6.5 Remplacer le client Nominatim

```php
// config/locationiq.php
'nominatim_client_fqcn' => \App\Geo\AfyaNominatimClient::class,
```

Contraintes :

- La classe doit implémenter `NominatimClientInterface`.
- Son constructeur doit être auto-résolvable par le conteneur Laravel.

### 6.6 Binder les interfaces manuellement

```php
$this->app->bind(
    LocationIqClientInterface::class,
    AfyaLocationIqClient::class,
);

$this->app->bind(
    NominatimClientInterface::class,
    AfyaNominatimClient::class,
);
```

L'option bind n'a d'effet que si les clés `locationiq_client_fqcn` et `nominatim_client_fqcn` restent égales à leurs valeurs par défaut.

---

## 7. Gestion des erreurs

Le package distingue trois niveaux.

### 7.1 Validation (HTTP 422)

Les `FormRequest` rejettent les payloads invalides avant toute exécution.

| Situation | Message type |
|-----------|--------------|
| `lat` manquant | `The lat field is required.` |
| `lat` hors bornes | `The lat field must be between -90 and 90.` |
| `lon` hors bornes | `The lon field must be between -180 and 180.` |
| `timestamp` négatif | `The timestamp field must be at least 0.` |
| `coordinates` moins de 2 paires | `The coordinates field must have at least 2 items.` |
| `coordinates` plus de 25 paires | `The coordinates field must not have more than 25 items.` |
| `coordinates.*` non conforme | `The coordinates.0 field must contain 2 items.` |
| `profile` invalide | `The selected profile is invalid.` |
| `overview` invalide | `The selected overview is invalid.` |
| `geometries` invalide | `The selected geometries is invalid.` |
| `annotations` vide | `The annotations field must have at least 1 item.` |
| `annotations.*` invalide | `The selected annotations.0 is invalid.` |
| `sources.*` hors plage | `The sources.0 field must be between 0 and {max}.` |
| `destinations.*` hors plage | `The destinations.0 field must be between 0 and {max}.` |
| `fallback_speed` nul ou négatif | `The fallback speed field must be greater than 0.` |
| `fallback_coordinate` invalide | `The selected fallback coordinate is invalid.` |
| `format` invalide | `The selected format is invalid.` |
| `zoom` hors bornes | `The zoom field must be between 0 and 18.` |
| `accept_language` trop long | `The accept language field must not be greater than 10 characters.` |

### 7.2 Erreurs LocationIQ

L'action map le message brut vers un `ErrorCode`, puis renvoie un `ErrorResponseData` avec le bon code HTTP.

```json
{
  "errorCode": "INVALID_KEY",
  "message": "Invalid Key",
  "status": 401
}
```

Correspondances message → code :

| Message brut LocationIQ | `ErrorCode` | HTTP |
|------------------------|-------------|------|
| `Invalid Request` | `INVALID_REQUEST` | 422 |
| `Invalid Key` | `INVALID_KEY` | 401 |
| `Access restricted` | `ACCESS_RESTRICTED` | 403 |
| `Unable to geocode` | `UNABLE_TO_GEOCODE` | 404 |
| `Rate Limited Day` | `RATE_LIMITED_DAY` | 429 |
| `InvalidOptions` | `INVALID_OPTIONS` | 422 |
| `NoTable` | `NO_TABLE` | 422 |
| `NotImplemented` | `NOT_IMPLEMENTED` | 422 |
| `Unknown error - Please try again after some time` | `UNKNOWN_ERROR` | 500 |

> LocationIQ peut renvoyer `InvalidOptions`, `NoTable` ou `NotImplemented` dans le champ `code` **avec un HTTP 200**. Le SDK détecte ce cas et les mappe vers les codes d'erreur typés.

### 7.3 Erreurs SDK

Les `Action` capturent les exceptions levées par le SDK et les transforment en `ErrorResponseData`.

| Exception SDK | `ErrorCode` | HTTP |
|---------------|-------------|------|
| `InvalidArgumentException` (coordonnées Directions ou Matrix hors bornes, index sources/destinations invalides, fallback speed invalide) | `INVALID_COORDINATES` | 422 |
| `RuntimeException` (erreur réseau Guzzle) | Non capturée → 500 Laravel | 500 |

**Note** : les index `sources` et `destinations` hors plage sont rejetés **au niveau de la validation** (`FormRequest::withValidator`) avant d'atteindre le SDK. Ils produisent une erreur `422` sur `sources.0` / `destinations.0`, pas sur `errorCode`.

---

## 8. Tests

```bash
composer test
```

Le package fournit un `IntegrationTestCase` basé sur `orchestra/testbench`. Chaque test enregistre le vrai endpoint via `action_route(...)` et remplace les clients HTTP bas niveau par des mocks (`MockLocationIqClient`, `MockNominatimClient`).

### Structure d'un test

Les tests suivent la convention AAA :

```php
public function test_it_returns_day_balance_on_success(): void
{
    // Arrange: the API will answer 200 with a positive balance
    $this->locationIqClient->addBalanceSuccessResponse(30000);

    // Act: hit the route
    $response = $this->postJson('/api/locationiq-balance');

    // Assert: 200 and balance.day exposed
    $response->assertOk();
    $response->assertJsonPath('balance.day', 30000);
}
```

### Restreindre les valeurs en test

Si tu veux tester avec une configuration alternative :

```php
$this->app->instance(LocationIqConfigInterface::class, new RestrictedLocationIqConfig);
```

---

## 9. Sécurité

- **Routes non protégées par défaut.** Ajoute tes middlewares (`auth:sanctum`, `throttle:60,1`, signature HMAC…) dans le fichier publié.
- **`LOCATIONIQ_API_KEY` côté serveur uniquement.** Ne jamais l'exposer au frontend.
- **`NOMINATIM_USER_AGENT` obligatoire en production.** Nominatim peut bloquer les requêtes sans User-Agent identifiable.
- **Rate limit Nominatim** : 1 requête/seconde sur l'instance publique. Prévoir un throttling côté application.
- **Rate limit LocationIQ** : sur les plans gratuits, minimum 1 seconde entre deux requêtes (`Rate Limited Second`). Prévoir un throttling côté application si tu enchaînes plusieurs appels Matrix ou Directions.
- **Validation stricte** : toutes les entrées (coordonnées, formats, profils, langues, annotations, sources, destinations) sont validées par les `FormRequest` avant tout appel HTTP.
- **Restriction des formats Nominatim** : seuls `json`, `jsonv2`, `geojson` et `geocodejson` sont acceptés.
- **Restriction des profils Directions et Matrix** : seuls `driving` et `walking` sont acceptés.
- **Limite de coordonnées** : 2 à 25 par requête Directions ou Matrix (limite imposée par l'API).
- **Aucune donnée sensible persistée.** Le package ne stocke rien.

---

## 10. Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.2+ | ✅ Complet |
| Laravel 10 | ✅ Complet |
| Laravel 11 | ✅ Complet |
| Laravel 12 | ✅ Complet |

---

## Licence
