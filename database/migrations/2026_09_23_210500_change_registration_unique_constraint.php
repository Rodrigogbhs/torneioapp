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
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique(['tournament_id', 'user_id']);

            $table->unique(['tournament_id', 'athlete_id']);
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique(['tournament_id', 'athlete_id']);

            $table->unique(['tournament_id', 'user_id']);
        });
    }
};
