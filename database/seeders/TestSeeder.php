<?php

namespace Database\Seeders;

use App\Enums\Core\TypeFeature;
use App\Models\Core\Feature;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    public function run(): void
    {

    }

    private function createFeature()
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
    }
}
