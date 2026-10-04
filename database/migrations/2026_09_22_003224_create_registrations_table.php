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
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tournament_id')
                ->constrained();

            $table->foreignId('user_id')
                ->constrained();

            $table->string('payment_status')
                ->default('pending');

            $table->timestamp('payment_confirmed_at')
                ->nullable();

            $table->unique(['tournament_id', 'user_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
