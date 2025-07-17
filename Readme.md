Api Eco Garden
==========

Eco Garden fournis des conseils à des utilisateurs pour encourager les pratiques respectueuses de l'environnement, l'API ici à pour but d'aider les utilisateurs à cultiver leur plantes, légumes et herbes aromatiques

## Versions Utilisées

Ce projet a été developper avec Symfony 7.3 sous php 8.4,
toutes versions antérieures ne garantissent pas le fonctionnement de l'application

La base de donnée utilisée est une MariaDB 10.4.5 lors du developpement,
tout autre base SGDBR est possible, voir plus bas pour la configuration

## Installation

1 - Commencer par cloner le projet depuis le [gitHub](https://github.com/AD2210/Api_Eco_Garden) dans votre ide

2 - Executer la commande pour initialiser le projet symfony

```bash
composer install
```

3 - Parametrer votre BDD

3.1 - Vérifier dans votre php.ini que le driver dont vous avez besoin est bien activé

**exemple :** si vous utiliser Mysql, vérifier que cette ligne est decommenté
        
`extension=pdo_mysql`

3.2 - Copier le fichier _.env_ et renommer le en _.env.local_

3.3 - Parametrer votre connexion à l'aide de la ligne correspondante a votre BDD

`DATABASE_URL="mysql://!identifiant!:!motdepasse!@!ipconnexion!:!port!/!nomBDD!?serverVersion=!Version!&charset=utf8mb4`

4 - Démarer votre système de base de donnée puis créer la base à l'aide de la commande

```bash
symfony console doctrine:database:create
```

5 - Migrer le schéma de la base avec la commande

```bash
symfony console doctrine:schema:update --force
```

6 - Charger les Fixtures avec la commande

```bash
symfony console doctrine:fixtures:load 
```

7 - Initialiser les clés JWT

```bash
symfony console lexik:jwt:generate-keypair
```

8 - Copier la clé API fourni dans _.env.local_

## Usage

1 - Démarer votre serveur symfony

> [!NOTE]
> Si vous avez la version 5.12.0 de symfony CLI le `-d` ne fonctionne plus sur Windows

```bash
symfony serve -d
```

2 - Acceder à la documentation de l'API

 `https://127.0.0.1:8000/api/doc`

3 - Pour vous logger, en fonction du rôle souhaité :

user : `user@ecogarden.com` ou `admin@ecogarden.com`


mot de passe : `password123`

## License

Ce projet est réalisé dans le cadre d'une formation OpenClassroom