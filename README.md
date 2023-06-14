# Projet Le Blog de Batman

## 1 - Cloner le projet

```
git clone https://github.com/Anthony-Dmn/leblogdebatman_sf6.git
```

## 2 - Déplacer le terminal dans le dossier cloné
```
cd leblogdebatman_sf6
```

## 3 - Installer les vendors Composer (pour recréer le dossier vendor)
```
composer install
```

## 4 - Changer les paramètres d'environnement
Il faut changer les paramètres dans le fichier .env pour les faire correspondre à l'environnement du projet (Accès base de données, clés Google Recaptcha, etc...).
```dotenv
DATABASE_URL="mysql://root:@127.0.0.1:3306/leblogdebatman?serverVersion=8.0.30&charset=utf8mb4"
GOOGLE_RECAPTCHA_SITE_KEY=XXXXXXXXXXXXXXXXXXXXXXXXXXXX
GOOGLE_RECAPTCHA_PRIVATE_KEY=XXXXXXXXXXXXXXXXXXXXXXXXXXXX
```

## 5 - Création de la base de données
```
symfony console doctrine:database:create
symfony console doctrine:migrations:migrate
```

## 6 - Création des fixtures (fausses données de test)
```
symfony console doctrine:fixtures:load
```
Cette commande créera :
* Un compte admin (email: a@a.a , password : 'aaaaaaaaA7/')
* 10 compte utilisateurs (email aléatoire , password : 'aaaaaaaaA7/')
* 200 articles
* Entre 0 et 10 commentaires par article

## 7 - Installation fichiers front-end des bundles (CKEditor)
```
symfony console assets:install public
```

## 8 - Lancer le serveur
```
symfony serve
```
Accès au site maintenant via l'adresse [http://localhost:8000/](http://localhost:8000/)