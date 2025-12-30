# 🏰 Chasse aux Sorcières - API

Ce dépôt contient le backend développé avec **Symfony** et **API Platform** du projet.

## 🛠️ Installation
1. Cloner le repo dans le dossier parent `docker`
2. Configurer le fichier `.env.local`
3. Installer les dépendances :
```bash
composer install
```

## 📜 Fonctionnalités
- Authentification JWT
- Gestion des sorcières et des chasses
- Export des données pour le Bot Discord

## 🚀 Déploiement
Le projet utilise une **CI/CD via GitHub Actions**. Tout push sur la branche `main` déclenche un déploiement automatique sur le VPS.
