<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Terrain;

class TerrainSeeder extends Seeder
{
    public function run(): void
    {
        $terrains = [
            [
                'titre' => 'Terrain Principal',
                'description' => 'Grand terrain en gazon synthétique de dernière génération',
                'prix' => 300,
                'image' => '/storage/terrains/terrain1.jpg'
            ],
            [
                'titre' => 'Terrain Couvert',
                'description' => 'Terrain couvert idéal pour jouer par tous les temps',
                'prix' => 400,
                'image' => '/storage/terrains/terrain2.jpg'
            ],
            [
                'titre' => 'Mini-Terrain',
                'description' => 'Parfait pour les matchs à 5 contre 5',
                'prix' => 200,
                'image' => '/storage/terrains/terrain3.jpg'
            ]
        ];

        foreach ($terrains as $terrain) {
            Terrain::create($terrain);
        }
    }
} 