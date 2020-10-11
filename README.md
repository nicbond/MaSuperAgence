Développement d'un site web pour une agence immobilière ainsi que de son back-office.

Stack technique: PHP 7.4.7 / Symfony 4.4.13 (version LTS)

Le site web comprend:
 - un système de pagination
 - un système de filtre pour affiner les recherches concernant les biens
 
Le back-office quant à lui dispose:
 - d'un système d'authentification administrateur via un formulaire de connexion (form_login)
 - d'un encodeur bcrypt permettant d'encodé les mots de passe dans la base de données.

L'administrateur, une fois connecté, pourra réaliser les actions suivantes:
 - créer un nouveau bien
 - éditer un bien
 - supprimer un bien (avec une sécurité demandant la confirmation de la suppression)
Des messages flash permettront d'indiquer le bon déroulement de ces différentes actions.

Feature à envisager:
 - permettre la gestion d'administrateur
