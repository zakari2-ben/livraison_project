# 🚚 Delivery Management System – PHP / Bootstrap / JavaScript

## 📖 Description
Ce projet est une **application web complète de gestion des livraisons**, développée avec **PHP**, **MySQL (PDO)**, **Bootstrap 5** et **JavaScript**.  
Elle permet de gérer les **commandes**, les **livreurs**, les **admins**, et le **suivi des livraisons** à travers un **tableau de bord dynamique** et moderne.

---

## ✨ Fonctionnalités principales
### 👥 Côté administrateur :
- 🔐 **Authentification sécurisée** (login / logout via session)
- 🧾 **Gestion complète des commandes** : ajout, suppression, filtrage par date ou livreur, mise à jour du statut (en attente, affectée, en cours, terminée, annulée)
- 🚴‍♂️ **Gestion des livreurs** : ajout, archivage (soft delete), restauration, mise à jour du statut actif/inactif
- 📊 **Statistiques dynamiques** :
  - Graphique des statuts de commandes (Chart.js)
  - Graphique du statut des livreurs (actif/inactif)
  - Graphique des augmentations de salaire
- 💰 **Calcul automatique des salaires** selon l’ancienneté du livreur
- 🧑‍💼 **Gestion des sous-admins** avec photo, email, CIN et date d’embauche
- 📱 **Interface moderne et responsive** grâce à Bootstrap 5 et des effets CSS personnalisés

### 🧍‍♂️ Côté client :
- 📝 **Formulaire de commande** (nom, prénom, téléphone, email, adresse, type de commande, demande)
- ⏰ **Enregistrement automatique** de la date et l’heure de chaque commande

---

## 🛠️ Technologies utilisées
| Technologie | Rôle |
|--------------|------|
| **PHP (PDO)** | Gestion des données et logique serveur |
| **MySQL** | Base de données des clients, livreurs et commandes |
| **Bootstrap 5** | Design responsive et moderne |
| **JavaScript** | Dynamisme du tableau de bord et graphiques |
| **Chart.js** | Visualisation des statistiques |
| **HTML / CSS** | Structure et mise en forme du site |

---

## 🗂️ Structure du projet
```
projet-livraison/
│
├── admin.php               # Tableau de bord principal (gestion commandes, livreurs, stats)
├── admin_login.php         # Page de connexion administrateur
├── admin_logout.php        # Déconnexion
├── connexion.php           # Connexion à la base de données (PDO)
├── images/                 # Photos des admins et livreurs
├── css/                    # Fichiers CSS personnalisés (si ajoutés)
├── js/                     # Scripts JavaScript
└── README.md               # Documentation du projet
```

---

## ⚙️ Installation et exécution
1. 📦 **Cloner le dépôt**
   ```bash
   git clone https://github.com/votre-utilisateur/nom-du-repo.git
   ```
2. 📁 Placer le projet dans le dossier `htdocs` (si vous utilisez **XAMPP**) ou `www` (si **WAMP**).
3. 🗃️ Créer une base de données MySQL (ex: `livraison_db`) et importer le fichier SQL si disponible.
4. ⚙️ Configurer les identifiants dans `connexion.php` :
   ```php
   $pdo = new PDO("mysql:host=localhost;dbname=livraison_db", "root", "");
   ```
5. ▶️ Ouvrir le projet dans le navigateur :
   ```
   http://localhost/nom_du_projet/admin_login.php
   ```

---

## 👨‍💻 Auteur
Développé par **[zakaria ben fatah]**  
email : **[riariazakaria6@gmail.com]**
Projet académique – Application de gestion des livraisons en ligne.

---

## 🏷️ Licence
Ce projet est libre pour un usage éducatif et non commercial.
