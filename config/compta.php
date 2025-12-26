<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Plan Comptable Général (PCG) par défaut
    |--------------------------------------------------------------------------
    |
    | Ce tableau définit un plan comptable de base qui sera créé pour
    | chaque nouvelle entreprise.
    |
    */

    'default_accounts' => [
        // Classe 1 : Comptes de capitaux
        ['number' => '101000', 'name' => 'Capital', 'class_code' => '1'],
        ['number' => '120000', 'name' => 'Résultat de l\'exercice (bénéfice)', 'class_code' => '1'],
        ['number' => '129000', 'name' => 'Résultat de l\'exercice (perte)', 'class_code' => '1'],

        // Classe 4 : Comptes de tiers
        ['number' => '401000', 'name' => 'Fournisseurs', 'class_code' => '4', 'is_auxiliary' => true],
        ['number' => '411000', 'name' => 'Clients', 'class_code' => '4', 'is_auxiliary' => true],
        ['number' => '421000', 'name' => 'Personnel - Rémunérations dues', 'class_code' => '4', 'is_auxiliary' => true],
        ['number' => '431000', 'name' => 'URSSAF', 'class_code' => '4'],
        ['number' => '445660', 'name' => 'TVA sur autres biens et services', 'class_code' => '4'],
        ['number' => '445710', 'name' => 'TVA collectée', 'class_code' => '4'],
        ['number' => '471000', 'name' => 'Comptes d\'attente', 'class_code' => '4'],

        // Classe 5 : Comptes financiers
        ['number' => '512000', 'name' => 'Banque', 'class_code' => '5'],
        ['number' => '530000', 'name' => 'Caisse', 'class_code' => '5'],

        // Classe 6 : Comptes de charges
        ['number' => '601000', 'name' => 'Achats stockés - Matières premières', 'class_code' => '6'],
        ['number' => '607000', 'name' => 'Achats de marchandises', 'class_code' => '6'],
        ['number' => '622000', 'name' => 'Rémunérations d\'intermédiaires et honoraires', 'class_code' => '6'],
        ['number' => '641000', 'name' => 'Rémunérations du personnel', 'class_code' => '6'],
        ['number' => '645100', 'name' => 'Cotisations à l\'URSSAF', 'class_code' => '6'],

        // Classe 7 : Comptes de produits
        ['number' => '701000', 'name' => 'Ventes de produits finis', 'class_code' => '7'],
        ['number' => '706000', 'name' => 'Prestations de services', 'class_code' => '7'],
        ['number' => '707000', 'name' => 'Ventes de marchandises', 'class_code' => '7'],
    ],
];
