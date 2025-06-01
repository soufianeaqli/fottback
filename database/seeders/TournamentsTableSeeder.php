<?php

namespace Database\Seeders;

use App\Models\Tournament;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TournamentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tournament::create([
            'name' => 'Coupe de la Ville',
            'date' => '2024-04-15',
            'max_teams' => 16,
            'registered_teams' => 8,
            'prize_pool' => '10000 DH',
            'description' => 'Tournoi annuel opposant les meilleures équipes',
            'format' => 'Élimination directe',
            'entry_fee' => '500 DH',
            'teams' => [
                [
                    'id' => time(),
                    'name' => 'Les Champions',
                    'captain' => 'Mohammed Ali',
                    'phone' => '0600000001',
                    'email' => 'champions@example.com',
                    'registration_date' => now()->toDateString(),
                ],
                [
                    'id' => time() + 1,
                    'name' => 'Les Aigles',
                    'captain' => 'Hassan Ahmed',
                    'phone' => '0600000002',
                    'email' => 'aigles@example.com',
                    'registration_date' => now()->toDateString(),
                ],
            ],
        ]);

        Tournament::create([
            'name' => 'Championnat Amateur',
            'date' => '2024-05-01',
            'max_teams' => 12,
            'registered_teams' => 6,
            'prize_pool' => '5000 DH',
            'description' => 'Tournoi réservé aux équipes amateurs',
            'format' => 'Phase de groupes + Élimination directe',
            'entry_fee' => '300 DH',
            'teams' => [
                [
                    'id' => time() + 2,
                    'name' => 'Les Étoiles',
                    'captain' => 'Karim Benali',
                    'phone' => '0600000003',
                    'email' => 'etoiles@example.com',
                    'registration_date' => now()->toDateString(),
                ],
            ],
        ]);

        Tournament::create([
            'name' => 'Tournoi Ramadan',
            'date' => '2024-03-20',
            'max_teams' => 20,
            'registered_teams' => 12,
            'prize_pool' => '15000 DH',
            'description' => 'Grand tournoi nocturne pendant le mois de Ramadan',
            'format' => 'Phase de groupes + Élimination directe',
            'entry_fee' => '600 DH',
            'teams' => [],
        ]);
    }
}
