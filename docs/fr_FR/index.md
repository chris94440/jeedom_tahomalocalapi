# Plugin tahomalocalapi

Ce plugin permet de communiquer en local avec les box Somfy compatible avec le mode développeur.

Prérequis
---

Activer le mode développeur sur sa box Somfy, pour se faire rendez-vous sur le site Internet de Somfy (https://www.somfy.fr/se-connecter) et connectez-vous à votre compte. 
Toujours via le site, accédez à votre box et activez le mode développeur.

Si le mode développeur n'est pas disponible regardez si vous n'avez pas une MAJ de celle-ci en attente.

Paramétrage du plugin
---
Le plugin doit se connecter à votre box, il a donc besoin  
* devos identifiants / mot de passe
* d'infortmations liées à votre box
** adresse ip locale
** code pin 

![alt text](../img/tahomalocalappi_configurationPlugin.JPG "Configuration du plugin")

![alt text](../img/tahomalocalappi_codePin.JPG "Code Pin box domotique")


Le plugin utilise également un daemon pour fonctionner, il faut donc définir un port  en vérifiant bien qu'il n'est pas utilisé au travers d'un autre plugin

![alt text](../img/tahomalocalappi_configurationDaemon.JPG "Code Pin box domotique")

Ne pas oublier de sauvegarder votre configuration sinon le daemon n'aura pas les informations nécessaires pour démarrer correctement.

**Vous pouvez dès à présent lancer le daemon.**

Celui va se connecter, à l'aide de vos identifiants / mot de passe à une api pour générer un token.
Ce token servira par la suite à interargir avec votre box en local.

A chaque redémarrage du daemon l'intégralité des équipements sont scannés, récupérés et crées automatiquement.

![alt text](../img/tahomalocalappi_vueEquipement.JPG "Vue page des équipements")

Si jamais vous intégrez un nouvel équipement à votre box, pas besoin de relancer le daemon... lancez une synchronisation directement depuis cette meme page.

![alt text](../img/tahomalocalappi_synchronisation.JPG "Vue page des équipements")

Si jamais vous avez un soucis de connexion a votre compte Somfy (via le plugin) ou que la connexion à l'api locale est rejetée à cause du token une fonction de "reset" du token est disponble
![alt text](../img/tahomalocalappi_reset_token.JPG "Vue page des équipements")

Tips
---
Les images des équipements sont stockées sous /plugins/tahomalocalapi/data/img
Mais si des images ne vous conviennent pas ou sont manquantes vous pouvez surcharger celle défini de base dans le plugin.
Pour ce faire vous devez 
* récupérer le type de l'équipement (configuration avancée / information / configutation -> type)

![alt text](../img/tahomalocalappi_customImage.JPG "Vue page des équipements")

* déposer l'image souhaitée sous /plugins/tahomalocalapi/data/img/custom avec pour nom le type d'équipement récupéré sans le "io:" et au format png

Pour aider au debug une fonctionnalité a été mise en place pour récupérer toutes les infos qui pourront m'être nécessaire.
Il suffit de cliquer sur le bonton Infos sur la page des équipements
![alt text](../img/tahomalocalappi_infosCommunity.JPG "Vue page des équipements")

Puis de cliquer sur le bouton copier et de coller le résultat dans un post sur le community
![alt text](../img/tahomalocalappi_infosCommunity_copier.JPG "Copier configurations")