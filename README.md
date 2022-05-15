# Projet Le Blog de Batman

## Installation

### Cloner le projet

```
git clone https://github.com/Anthony-Dmn/leblogdebatman_sf6.git
```

### Modifier les paramètres d'environnement dans le fichier .env pour les faire correspondre à votre environnement (Accès base de données, clés Google Recaptcha, etc...)
```
# Accès base de données
DATABASE_URL="mysql://root:@127.0.0.1:3306/leblogdebatman_sf6?serverVersion=5.7&charset=utf8mb4"

# Clés Google Recaptcha
GOOGLE_RECAPTCHA_SITE_KEY=XXXXXXXXXXXXXXXXXXXXXXXXXXXX
GOOGLE_RECAPTCHA_PRIVATE_KEY=XXXXXXXXXXXXXXXXXXXXXXXXXXXX
```

### Déplacer le terminal dans le dossier cloné
```
cd leblogdebatman
```

### Taper les commandes suivantes :

```
composer install
symfony console doctrine:database:create
symfony console make:migration
symfony console doctrine:migrations:migrate
symfony console doctrine:fixtures:load
symfony console assets:install public
```

Les fixtures créeront :
* Un compte admin (email: admin@a.a , password : aaaaaaaaA7/ )
* 10 comptes utilisateurs (email aléatoire, password : aaaaaaaaA7/ )
* 200 articles
* entre 0 et 10 commentaires par article