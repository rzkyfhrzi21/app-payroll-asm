<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel rute: jalur hauling, material, dan tarif.
     */
    public function up(): void
    {
        Schema::create('rute', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('material', 50);
            $table->bigInteger('tarif_per_ton');
            $table->bigInteger('uang_jalan_default');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rute');
    }
};
