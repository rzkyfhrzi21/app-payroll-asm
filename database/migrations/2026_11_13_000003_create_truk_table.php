<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel truk: data unit armada.
     */
    public function up(): void
    {
        Schema::create('truk', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_polisi', 15)->unique();
            $table->string('merek_model', 100);
            $table->string('vendor', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('truk');
    }
};
