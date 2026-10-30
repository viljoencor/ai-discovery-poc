<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('city', 80)->index();
            $table->text('description');
            $table->unsignedTinyInteger('guest_capacity');
            $table->decimal('base_price_zar', 10, 2);
            $table->json('amenities');
            $table->boolean('pets_allowed')->default(false);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->index(['city', 'active', 'base_price_zar']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
