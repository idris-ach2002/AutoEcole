# AutoÉcole Manager - application Symfony dockerisée

Application web de gestion d'auto-école développée avec Symfony. Le projet gère les candidats, les sessions d'examen, les inscriptions, les paiements, l'historique administratif et les exports. Cette version a été restructurée pour fournir un environnement Docker reproductible, une base PostgreSQL intégrée, une interface d'exploitation plus riche et une meilleure séparation entre logique métier, contrôleurs, templates et scripts d'installation.

## 1. Objectif du projet

AutoÉcole Manager vise à centraliser les opérations quotidiennes d'une auto-école : création des dossiers candidats, planification des examens, contrôle de la progression pédagogique, suivi des paiements et production d'extractions administratives. L'application a été renforcée pour répondre à trois exigences : lancement reproductible, maintenabilité du code et exploitation métier plus complète.

Le projet est conçu pour être exécuté sans installation locale de PHP, Composer ou PostgreSQL. Docker construit l'environnement applicatif, installe les dépendances Composer, démarre PostgreSQL, applique les migrations et peut charger automatiquement un jeu de démonstration.

## 2. Stack technique

| Couche | Choix retenu |
|---|---|
| Framework | Symfony 7.3 |
| Langage | PHP 8.3 dans Docker |
| Persistance | PostgreSQL 16 |
| ORM | Doctrine ORM + Doctrine Migrations |
| Interface | Twig + CSS applicatif local |
| Exports | CSV, JSON, PDF avec Dompdf |
| Conteneurisation | Docker, Docker Compose |
| Services annexes | Adminer, Mailpit |
| Qualité | PHP lint, lints Symfony, PHPUnit, commande métier d'audit |

## 3. Lancement rapide

### Option recommandée : script multiplateforme

Le script détecte le système et vérifie la présence de Docker et de Docker Compose. Il fonctionne sur Linux, macOS et Windows si Python est disponible.

```bash
python scripts/bootstrap_environment.py
```

Pour demander au script de proposer l'installation de Docker si nécessaire :

```bash
python scripts/bootstrap_environment.py --install
```

Pour repartir d'une base propre :

```bash
python scripts/bootstrap_environment.py --reset
```

### Option Docker directe

```bash
docker compose up --build
```

Puis ouvrir :

```text
http://localhost:8080
```

Services utiles :

```text
Application : http://localhost:8080
Adminer     : http://localhost:8081
Mailpit     : http://localhost:8025
Healthcheck : http://localhost:8080/healthz
```

Identifiants PostgreSQL pour Adminer :

```text
Système      : PostgreSQL
Serveur      : database
Utilisateur  : autoecole
Mot de passe : password
Base         : autoecole
```

## 4. Fonctionnalités ajoutées ou renforcées

La version enrichie ajoute un bloc fonctionnel dense. Les fonctionnalités suivantes sont présentes dans l'application ou dans son environnement d'exploitation :

1. Tableau de bord de pilotage global.
2. Indicateurs financiers sur les contrats permis.
3. Suivi du reste à payer côté permis.
4. Suivi du reste à payer côté examens.
5. Alertes sur les dossiers avec solde important.
6. Alertes sur les résultats d'examen non saisis après passage.
7. Liste des prochaines sessions d'examen.
8. Répartition des inscriptions par statut.
9. Répartition des examens par type.
10. Recherche avancée des candidats.
11. Filtrage des candidats par statut de paiement.
12. Filtrage des candidats par groupe sanguin.
13. Tri des candidats par nom, âge, prix ou reste à payer.
14. Export CSV des candidats.
15. Export JSON des candidats.
16. Dossier candidat détaillé.
17. Progression permis calculée automatiquement.
18. Progression paiement calculée automatiquement.
19. Timeline pédagogique code, créneau, conduite.
20. Détection du prochain examen éligible.
21. Paiement partiel du permis depuis le dossier candidat.
22. Filtrage des examens par type, lieu et période.
23. Export CSV des examens.
24. Vue détaillée d'une session d'examen avec candidats inscrits.
25. Création d'inscription avec contrôle de progression métier.
26. Filtrage des inscriptions par candidat, statut, type et paiement.
27. Mise à jour rapide du statut d'une inscription.
28. Paiement partiel d'un examen depuis la liste opérationnelle.
29. Historique administratif filtrable par nom, prénom et dates.
30. Export CSV de l'historique.
31. Export JSON de l'historique.
32. Export PDF de l'historique avec Dompdf.
33. Page healthcheck JSON pour supervision simple.
34. Jeu de démonstration idempotent via commande Symfony.
35. Commande d'audit métier des incohérences.
36. Interface responsive sans dépendance CDN obligatoire.
37. Scripts de lancement Linux/macOS/Windows.
38. Conteneur Adminer pour inspecter la base.
39. Conteneur Mailpit pour préparer les évolutions mail.
40. Migration Doctrine nettoyée et reproductible.

## 5. Architecture applicative

```text
autoecole/
├── src/
│   ├── Command/              # Commandes seed et audit métier
│   ├── Controller/           # Contrôleurs HTTP
│   ├── Entity/               # Modèle Doctrine
│   ├── Form/                 # Formulaires Symfony
│   ├── Repository/           # Requêtes métier et filtres
│   └── Service/              # Règles de progression et dossiers
├── templates/                # Vues Twig
├── public/                   # Front controller, CSS et JS runtime
├── migrations/               # Migration Doctrine propre
├── docker/                   # Apache, PHP, entrypoint
├── scripts/                  # Bootstrap multiplateforme et qualité
├── compose.yaml              # Stack Docker complète
└── Dockerfile                # Image PHP-Apache Symfony
```

L'organisation cherche à éviter les contrôleurs trop lourds. Les règles qui déterminent la progression pédagogique sont isolées dans `AutoEcoleManager`. Les repositories portent les requêtes de filtrage et d'agrégation. Les contrôleurs orchestrent les flux HTTP sans concentrer toute la logique métier.

## 6. Modèle de données

Le modèle principal repose sur trois entités.

### Candidat

Un candidat contient son identité, ses coordonnées, ses informations administratives, le prix du permis, le reste à payer et le statut de paiement. Il expose aussi des méthodes calculées : âge, nom complet, solde, progression paiement.

### Examen

Un examen représente une session de code, créneau ou conduite. Il contient la date, le lieu, les frais, l'état administratif de paiement et la liste des candidats inscrits.

### CandidatExamen

Cette entité d'association matérialise l'inscription d'un candidat à une session. Elle porte le statut opérationnel de l'inscription et le reste à payer propre aux frais d'examen.

## 7. Règles métier principales

La progression pédagogique est volontairement stricte :

```text
Code réussi -> Créneau autorisé -> Conduite autorisée
```

Un candidat peut toujours être inscrit au code. Le créneau nécessite la réussite du code. La conduite nécessite la réussite du créneau. Cette règle est appliquée dans le service `AutoEcoleManager`, puis utilisée dans les formulaires et les contrôleurs.

Les paiements sont traités comme des paiements partiels. Un paiement réduit le reste à payer sans pouvoir descendre sous zéro. Lorsque le reste à payer devient nul, le statut concerné passe automatiquement à l'état soldé ou payé selon le contexte.

## 8. Commandes utiles

Démarrer :

```bash
docker compose up --build
```

Arrêter :

```bash
docker compose down
```

Réinitialiser la base :

```bash
docker compose down -v
docker compose up --build
```

Entrer dans le conteneur :

```bash
docker compose exec app bash
```

Lancer les migrations :

```bash
docker compose exec app php bin/console doctrine:migrations:migrate
```

Charger les données de démonstration :

```bash
docker compose exec app php bin/console app:seed-demo
```

Lancer l'audit métier :

```bash
docker compose exec app php bin/console app:quality-audit
```

Lancer les tests :

```bash
docker compose exec app php bin/phpunit
```

## 9. Qualité et maintenabilité

La version améliorée applique plusieurs principes :

- configuration Docker explicite et isolée ;
- suppression des secrets réels de la configuration par défaut ;
- migration initiale propre au lieu d'un historique fragile ;
- séparation claire entre règles métier, accès données et affichage ;
- formulaires typés et validations Symfony ;
- commandes CLI pour seed et audit ;
- exports administratifs séparés ;
- styles locaux sans dépendre d'un CDN externe ;
- healthcheck HTTP pour intégration future en supervision.

Commande de contrôle local :

```bash
scripts/quality.sh
```

Dans Docker :

```bash
docker compose exec app scripts/quality.sh
```

## 10. Évolutions possibles

Les prochaines extensions naturelles sont : authentification avec rôles `ADMIN` et `MONITEUR`, gestion des leçons de conduite, planning des moniteurs, notifications mail, dépôt de documents, factures PDF, import CSV de candidats, API REST publique, tests fonctionnels plus complets et déploiement sur un orchestrateur.

## 11. Auteur et contexte

Projet académique Symfony restructuré et enrichi pour un usage de démonstration technique. Le travail met en avant la conteneurisation, les règles métier, la qualité de code et la reproductibilité d'exécution.
