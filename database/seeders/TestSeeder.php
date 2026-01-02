<?php

namespace Database\Seeders;

use App\Enums\Core\TypeFeature;
use App\Models\Core\Feature;
use App\Models\Core\Plan;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        $this->createFeature();
        $this->createPlan();
        $this->attachFeatureToPlan();
        $this->createModulePlan();
    }

    private function createFeature(): void
    {
        Feature::create([
            "code" => "NTS",
            "name" => "Note de Frais",
            "type" => TypeFeature::MODULE->value,
            "description" => 'Le module "Note de Frais" du logiciel Batistack, permet l\'enregistrement des notes de frais de vous et vos collaborateurs et de les pointer en comptabilité.',
            "is_optional" => true,
        ]);

        Feature::create([
            "code" => "LCT",
            "name" => "Location",
            "type" => TypeFeature::MODULE->value,
            "description" => "Permet la gestion de vos matériels en location.",
            "is_optional" => true,
        ]);

        Feature::create([
            "code" => "FLT",
            "name" => "Flotte",
            "type" => TypeFeature::MODULE->value,
            "description" => "Permet la gestion de votre flotte de vehicules",
            "is_optional" => true,
        ]);

        Feature::create([
            "code" => "SPPT",
            "name" => "Support Technique",
            "type" => TypeFeature::SERVICE->value,
            "description" => 'L\'option "Support Technique" de Batistack ERP offre aux utilisateurs un accès privilégié à une assistance spécialisée pour résoudre rapidement les problèmes techniques, obtenir des conseils d\'utilisation et optimiser la performance de leur solution ERP.',
            "is_optional" => true,
        ]);

        Feature::create([
            "code" => "EXT",
            "name" => "Extension Storage",
            "type" => TypeFeature::OPTION->value,
            "description" => 'L\'option "Extension Stockage" de Batistack ERP permet aux entreprises d\'augmenter la capacité de stockage de leurs données au-delà de l\'offre standard, répondant ainsi aux besoins croissants en matière de volume de documents, d\'archives ou de bases de données.',
            "is_optional" => true,
        ]);

        Feature::create([
            "code" => "SVSR",
            "name" => "Sauvegarde et retention",
            "type" => TypeFeature::OPTION->value,
            "description" => 'L\'option "Sauvegarde & Rétention" de Batistack ERP est une solution essentielle pour la protection des données, garantissant la sécurité, la disponibilité et l\'intégrité des informations critiques de l\'entreprise.',
            "is_optional" => true,
        ]);

        Feature::create([
            "code" => "3DVS",
            "name" => "3D Vision",
            "type" => TypeFeature::MODULE->value,
            "description" => 'Le module "3D Vision" de Batistack ERP est conçu pour intégrer des capacités de visualisation et de modélisation 3D directement dans l\'environnement de gestion. Il permet aux utilisateurs de manipuler et d\'analyser des données spatiales ou des représentations d\'objets en trois dimensions.',
            "is_optional" => true,
        ]);

        Feature::create([
            "code" => "AGGB",
            "name" => "Aggregation Bancaire",
            "type" => TypeFeature::MODULE->value,
            "description" => 'Le module "Aggrégation bancaire" de Batistack ERP vise à centraliser et automatiser la récupération des flux bancaires de différentes banques. Il permet une vision consolidée des comptes et facilite le rapprochement bancaire.',
            "is_optional" => true,
        ]);

        Feature::create([
            "code" => "GED",
            "name" => "GED",
            "type" => TypeFeature::MODULE->value,
            "description" => 'Le module "GED" (Gestion Électronique de Documents) de Batistack ERP est conçu pour centraliser, organiser et sécuriser tous les documents numériques de l\'entreprise. Il permet de dématérialiser les processus et de faciliter l\'accès à l\'information.',
            "is_optional" => false,
        ]);

        Feature::create([
            "code" => "PLS",
            "name" => "Planning",
            "type" => TypeFeature::MODULE->value,
            "description" => 'Le module "Planning" de Batistack ERP est conçu pour optimiser l\'organisation et la gestion des activités, des ressources et des équipes au sein de l\'entreprise. Il permet de visualiser, d\'allouer et de suivre les tâches de manière efficace.',
            "is_optional" => false,
        ]);

        Feature::create([
            "code" => "PAIE",
            "name" => "Paie",
            "type" => TypeFeature::MODULE->value,
            "description" => 'Le module "Paie" de Batistack ERP est conçu pour automatiser et simplifier la gestion de la paie des employés, en assurant la conformité avec la législation sociale et fiscale en vigueur. Il permet de calculer les salaires, les cotisations et les impôts, et de générer les bulletins de paie.',
            "is_optional" => true,
        ]);

        Feature::create([
            "code" => "GRH",
            "name" => "GRH",
            "type" => TypeFeature::MODULE->value,
            "description" => 'Le module "Ressources Humaines" de Batistack ERP est conçu pour optimiser la gestion du personnel au sein de l\'entreprise. Il centralise toutes les informations relatives aux employés et simplifie les processus administratifs liés aux RH.',
            "is_optional" => false,
        ]);

        Feature::create([
            "code" => "CPT",
            "name" => "Comptabilité",
            "type" => TypeFeature::MODULE->value,
            "description" => 'Le module "Comptabilité" de Batistack ERP est conçu pour gérer l\'ensemble des opérations comptables de l\'entreprise, de la saisie des écritures à la génération des bilans et comptes de résultat. Il assure la conformité réglementaire et fiscale, tout en offrant une vision claire et précise de la santé financière de l\'entreprise.',
            "is_optional" => false,
        ]);

        Feature::create([
            "code" => "MBC",
            "name" => "Banques & Caisses",
            "type" => TypeFeature::MODULE->value,
            "description" => 'Le module "Banques et Caisses" de Batistack ERP est conçu pour simplifier et automatiser la gestion de la trésorerie de l\'entreprise. Il permet de suivre en temps réel les flux financiers, d\'enregistrer les opérations bancaires et de caisse, et de faciliter le rapprochement bancaire.',
            "is_optional" => false,
        ]);

        Feature::create([
            "code" => "FAPA",
            "name" => "Facturation & Paiement",
            "type" => TypeFeature::MODULE->value,
            "description" => 'Le module "Facturation/Paiement" de Batistack ERP vous permet de créer facilement des factures, de suivre les paiements de vos clients et de gérer les relances. Il simplifie tout le processus de facturation et assure un bon suivi de vos encaissements.',
            "is_optional" => false,
        ]);

        Feature::create([
            "code" => "COM",
            "name" => "Commerce",
            "type" => TypeFeature::MODULE->value,
            "description" => 'Le module "Gestion Commerciale" de Batistack ERP vous aide à gérer toutes les étapes de vente, du devis à la facturation. Il permet de créer des devis, de les transformer en commandes, de suivre les livraisons et d\'émettre des factures. Il inclut aussi la gestion des remises et des conditions de paiement.',
            "is_optional" => false,
        ]);

        Feature::create([
            "code" => "ARTS",
            "name" => "Articles",
            "type" => TypeFeature::MODULE->value,
            "description" => 'Le module "Gestion des articles et services" de Batistack ERP vous aide à organiser et suivre tous les produits (matériaux, équipements) et services (main d\'œuvre, prestations) que votre entreprise utilise ou propose. Il permet de gérer leurs prix, leurs stocks (pour les articles), et de retrouver facilement toutes les informations nécessaires. En bref, il assure une gestion claire et efficace de tout ce que vous achetez et vendez.',
            "is_optional" => false,
        ]);

        Feature::create([
            "code" => "TIER",
            "name" => "Tiers",
            "type" => TypeFeature::MODULE->value,
            "description" => 'Le module Gestion des tiers de Batistack ERP vous permet de centraliser et de gérer efficacement toutes les informations relatives à vos contacts professionnels : clients, fournisseurs, sous-traitants et partenaires. Il offre une base de données unique et complète pour optimiser vos relations commerciales et administratives. Vous pouvez facilement accéder aux coordonnées, historiques d\'échanges, documents associés et conditions commerciales spécifiques à chaque tiers.',
            "is_optional" => false,
        ]);

        Feature::create([
            "code" => "CHA",
            "name" => "Chantiers",
            "type" => TypeFeature::MODULE->value,
            "description" => 'Le module Gestion de chantier de Batistack ERP vous permet de piloter efficacement tous vos projets de construction. Il centralise la planification (diagrammes de Gantt), le suivi des coûts en temps réel, la gestion des ressources (équipes, matériel) et l\'avancement des travaux. Vous bénéficiez d\'une visibilité complète pour respecter les délais et les budgets, améliorer la rentabilité et faciliter la collaboration sur le terrain.',
            "is_optional" => false,
        ]);

        Feature::create([
            "code" => "SIGN",
            "name" => "Signatures",
            "type" => TypeFeature::MODULE->value,
            "description" => 'Le module "Signature" de Batistack ERP est une solution avancée de gestion de la signature électronique, conçue pour dématérialiser et sécuriser les processus de validation et d\'approbation de documents au sein de l\'entreprise. Il permet de fluidifier les échanges, de réduire les délais et d\'assurer la conformité légale des documents signés.',
            "is_optional" => true,
        ]);

        Feature::create([
            "code" => "GPAO",
            "name" => "GPAO",
            "type" => TypeFeature::MODULE->value,
            "description" => 'Permet de gérer vos lignes de fabrication',
            "is_optional" => true,
        ]);
    }

    private function createPlan(): void
    {
        Plan::create([
            "name" => "Starter",
            "slug" => "starter",
            "description" => 'La licence "Starter" de Batistack ERP est conçue pour les petites entreprises, les startups ou les entrepreneurs individuels qui ont besoin d\'une solution de gestion simple, efficace et économique pour démarrer leurs opérations. Elle offre les fonctionnalités essentielles pour une gestion quotidienne.',
            "price_monthly" => 9.90,
            "price_yearly" => 99.00,
            "is_active" => true,
            "is_public" => true,
            "sort_order" => 0,
        ]);

        Plan::create([
            "name" => "Pro",
            "slug" => "pro",
            "description" => 'La licence "Pro" est idéale pour les PME en croissance qui nécessitent des fonctionnalités plus avancées pour optimiser leurs processus, améliorer leur productivité et gérer des volumes de données plus importants. Elle inclut toutes les fonctionnalités de la licence "Starter" avec des ajouts significatifs.',
            "price_monthly" => 24.90,
            "price_yearly" => 239.90,
            "is_active" => true,
            "is_public" => true,
            "sort_order" => 1,
        ]);

        Plan::create([
            "name" => "Ultimate",
            "slug" => "ultimate",
            "description" => 'La licence "Ultimate" est la solution complète de Batistack ERP, conçue pour les grandes entreprises, les groupes ou les organisations ayant des besoins complexes et des exigences élevées en matière de gestion intégrée, de performance et de sécurité. Elle offre l\'ensemble des fonctionnalités de Batistack ERP.',
            "price_monthly" => 39.90,
            "price_yearly" => 399.90,
            "is_active" => true,
            "is_public" => true,
            "sort_order" => 2,
        ]);
    }

    private function attachFeatureToPlan(): void
    {
        Plan::find(1)->features()->attach(Feature::find(17));
        Plan::find(1)->features()->attach(Feature::find(14));
        Plan::find(1)->features()->attach(Feature::find(19));
        Plan::find(1)->features()->attach(Feature::find(16));
        Plan::find(1)->features()->attach(Feature::find(13));
        Plan::find(1)->features()->attach(Feature::find(15));
        Plan::find(1)->features()->attach(Feature::find(18));

        Plan::find(2)->features()->attach(Feature::find(17));
        Plan::find(2)->features()->attach(Feature::find(14));
        Plan::find(2)->features()->attach(Feature::find(19));
        Plan::find(2)->features()->attach(Feature::find(16));
        Plan::find(2)->features()->attach(Feature::find(13));
        Plan::find(2)->features()->attach(Feature::find(15));
        Plan::find(2)->features()->attach(Feature::find(18));
        Plan::find(2)->features()->attach(Feature::find(9));
        Plan::find(2)->features()->attach(Feature::find(12));

        Plan::find(3)->features()->attach(Feature::find(17));
        Plan::find(3)->features()->attach(Feature::find(14));
        Plan::find(3)->features()->attach(Feature::find(19));
        Plan::find(3)->features()->attach(Feature::find(16));
        Plan::find(3)->features()->attach(Feature::find(13));
        Plan::find(3)->features()->attach(Feature::find(15));
        Plan::find(3)->features()->attach(Feature::find(18));
        Plan::find(3)->features()->attach(Feature::find(9));
        Plan::find(3)->features()->attach(Feature::find(12));
        Plan::find(3)->features()->attach(Feature::find(10));
    }

    private function createModulePlan()
    {
        Plan::create([
            "name" => "3D Vision",
            "slug" => "3d-vision",
            "description" => null,
            "price_monthly" => 14.90,
            "price_yearly" => 149.90,
            "is_active" => true,
            "is_public" => false,
            "sort_order" => 0,
        ])->features()->attach(Feature::find(7));

        Plan::create([
            "name" => "Aggregation Bancaire",
            "slug" => "aggregation-bancaire",
            "description" => null,
            "price_monthly" => 4.90,
            "price_yearly" => 49.90,
            "is_active" => true,
            "is_public" => false,
            "sort_order" => 0,
        ])->features()->attach(Feature::find(8));

        Plan::create([
            "name" => "Flotte",
            "slug" => "flotte",
            "description" => null,
            "price_monthly" => 4.90,
            "price_yearly" => 49.90,
            "is_active" => true,
            "is_public" => false,
            "sort_order" => 0,
        ])->features()->attach(Feature::find(3));

        Plan::create([
            "name" => "GPAO",
            "slug" => "gpao",
            "description" => null,
            "price_monthly" => 5.90,
            "price_yearly" => 59.90,
            "is_active" => true,
            "is_public" => false,
            "sort_order" => 0,
        ])->features()->attach(Feature::find(21));

        Plan::create([
            "name" => "Location",
            "slug" => "location",
            "description" => null,
            "price_monthly" => 4.90,
            "price_yearly" => 49.90,
            "is_active" => true,
            "is_public" => false,
            "sort_order" => 0,
        ])->features()->attach(Feature::find(2));

        Plan::create([
            "name" => "Note de Frais",
            "slug" => "note-de-frais",
            "description" => null,
            "price_monthly" => 2.90,
            "price_yearly" => 9.90,
            "is_active" => true,
            "is_public" => false,
            "sort_order" => 0,
        ])->features()->attach(Feature::find(1));

        Plan::create([
            "name" => "Paie",
            "slug" => "paie",
            "description" => null,
            "price_monthly" => 14.90,
            "price_yearly" => 149.90,
            "is_active" => true,
            "is_public" => false,
            "sort_order" => 0,
        ])->features()->attach(Feature::find(11));

        Plan::create([
            "name" => "Signature",
            "slug" => "signature",
            "description" => null,
            "price_monthly" => 9.90,
            "price_yearly" => 99.90,
            "is_active" => true,
            "is_public" => false,
            "sort_order" => 0,
        ])->features()->attach(Feature::find(20));
    }
}
