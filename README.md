## 🏰 Chasse aux Sorcières — API

Backend principal du projet **Chasse aux Sorcières**, développé avec **Symfony** et **API Platform**.
Cette API centralise la logique métier du jeu et fournit les données au **site web Angular** ainsi qu’au **bot Discord**.

---

### 🛠️ Installation

1. Cloner le dépôt dans le dossier parent `docker`
2. Créer et configurer le fichier `.env.local`
3. Installer les dépendances PHP :

```bash
composer install
```

---

### 📜 Fonctionnalités principales

* Authentification sécurisée via **JWT**
* Gestion des entités du jeu (joueurs, rôles, parties, etc.)
* Exposition d’une API REST via **API Platform**
* Fourniture des données nécessaires au **Bot Discord**
* Base de données relationnelle MySQL

---

### 🚀 Déploiement

Le projet est intégré dans une **CI/CD via GitHub Actions**.
Chaque push sur la branche `main` déclenche automatiquement :

* le build
* le déploiement sur le VPS
* la mise à jour du service API via Docker

