Développement d'un site web pour une agence immobilière ainsi que de son back-office dédié.

Stack technique: PHP 7.4.7 / Symfony 4.4.13 (version LTS)

Le site web comprend:
 - un système de pagination
 - un système de filtre pour affiner les recherches concernant les biens
 - une map via Leaflet permettant de mieux localiser le bien en question
 - un carousel permettant de faire défiler les images
 
Le back-office quant à lui dispose:
 - d'un système d'authentification administrateur via un formulaire de connexion (form_login)
 - d'un encodeur bcrypt permettant d'encoder les mots de passe dans la base de données
 - d'une gestion administrateur
 - de gérer les différentes options associées aux biens

L'administrateur, une fois connecté, pourra réaliser les actions suivantes:
 - créer un nouveau bien
 - éditer un bien
 - supprimer un bien (avec une sécurité demandant la confirmation de la suppression)
 
Il en sera également de même concernant la gestion administrateur ainsi que de celle permettant de gérer les options.
Des messages flash permettront d'indiquer le bon déroulement de ces différentes actions.

URLs:
 - Homepage: http://localhost:8000/
 - Acheter: http://localhost:8000/biens
 - administration des biens (après s'être logué): http://localhost:8000/admin
 - administration des utilisateurs: http://localhost:8000/admin/users
 - administration des options: http://localhost:8000/admin/option/

Pour le téléchargement du pain, celui-ci est disponible à cette adresse:
 - https://www.isabel.eu/knowledge_base_ibs6/fr/03_client_solutions/04_ebanking/02_transactions/01_creating_transactions/isabel-6-supported-banking-file-formats.html
 
Après avoir cloné le projet en local, il faudra exécuter les commandes suivantes:
 - composer install
 - php bin/console doctrine:database:create
 - php bin/console doctrine:schema:update --dump-sql
 - php bin/console doctrine:schema:update --force
 - php bin/console doctrine:fixtures:load