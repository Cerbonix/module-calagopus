<?php

return [
    'connection' => [
        'ok' => 'Connexion réussie. Panel Calagopus :version, version supportée.',
        'ok_unknown_version' => 'Connexion réussie, mais le panel n\'a pas renvoyé son numéro de version. Les vérifications de compatibilité sont désactivées.',
        'ok_untested_version' => 'Connexion réussie, mais le panel est en version :version, hors de la plage vérifiée (:min à :below exclue). Le provisioning peut échouer si l\'API a changé.',
        'empty_key' => 'Aucune clé API lisible. Soit le champ est vide, soit la clé enregistrée ne peut plus être déchiffrée, ce qui arrive lorsque la clé d\'application du site a été changée. Saisissez à nouveau la clé API.',
        'unreachable' => 'Panel injoignable. Vérifiez le nom d\'hôte, le port et le certificat TLS. Détail : :detail',
        'malformed_key' => 'La clé API est mal formée. Le panel attend exactement 48 caractères : la clé a probablement été tronquée ou collée avec un espace.',
        'invalid_key' => 'Clé API refusée par le panel. Elle est invalide, expirée, ou a été révoquée.',
        'ip_not_allowed' => 'Le panel refuse l\'adresse IP source de cette requête. La clé restreint les adresses autorisées et celle-ci n\'y figure pas. Déclarez toutes vos adresses de sortie, pas seulement celle observée aujourd\'hui.',
        'missing_permission' => 'Le panel refuse l\'accès. La clé ou le compte qui la porte n\'a pas la permission requise. Détail : :detail',
        'missing_permissions' => 'Connexion établie, mais la clé n\'a pas toutes les permissions nécessaires. Manquantes : :permissions',
        'conflict' => 'Le panel signale un conflit avec une ressource existante. Détail : :detail',
        'panel_refused' => 'Le panel a refusé d\'exécuter l\'opération. Détail : :detail',
        'rate_limited' => 'Trop de requêtes envoyées au panel. Réessayez dans quelques instants.',
        'unexpected' => 'Réponse inattendue du panel. Détail : :detail',
    ],
];
