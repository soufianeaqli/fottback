<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vérifier si les colonnes existent déjà
        if (!Schema::hasColumn('users', 'username') && 
            !Schema::hasColumn('users', 'phone') && 
            !Schema::hasColumn('users', 'role')) {
            
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->unique()->after('id');
                $table->string('phone')->nullable()->after('email');
                $table->enum('role', ['user', 'admin'])->default('user')->after('password');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ne pas supprimer la table, seulement les colonnes ajoutées
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'phone', 'role']);
        });
    }
};
