# Changelog plugin template

>**IMPORTANT**
>
>S'il n'y a pas d'information sur la mise à jour, c'est que celle-ci concerne uniquement de la mise à jour de documentation, de traduction ou de texte.
# 29/01/2024
- ajout d'un cron paramétrable pour forcer le refresh des équipements (si action disponible sur celui-ci)
- forçage du pourcentage de fermeture à 0 si le statut est closed (bug remontée d'info)

# 08/01/2024
- ajout des commandes deactivateCalendar, activateCalendar, setPodLedOn, setPodLedOff et setLightingLedPodMode sur les équipements de type box 

# 29/12/2023
- modification de la fréquence d'appel du listener des évènement suivant les préconisations de l'api (0.5s -> 1s) (source api : Fetch events on /events/{listenerId}/fetch once every second at most)

# 19/12/2023
- ajout d'un restart journalier du daemon en attendant de trouver pourquoi il s'arrête soudainement
  
# 08/12/2023
- ajout des modes confort-1 et confort-2 pour la gestion des radiateurs

# 06/12/2023
- correction creation automatique des actions générique (heatingSystem & HitachiHeatingSystem)

# 03/12/2023
- correction regression sur l'execution de commande sans paramètres

# 02/12/2023
- correction affectation automatique sous-type des commandes de type info par rapport au retour de l'api local Tahoma 

# 01/12/2023
- correction fonctionnement setClosureAndLinearSpeed et suppression des commandes setPositionAndLinearSpeed et setPosition redondantes avec le setClosure
- modification documentation

# 29/11/2023
- ajout des commandes setClosureAndLinearSpeed, setPositionAndLinearSpeed et setPosition

# 19/10/2023

- initialisation du plugin
