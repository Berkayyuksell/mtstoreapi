<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zpl_label_templates', function (Blueprint $table) {
            $table->id();

            // NULL = tüm projelere global; dolu = o projeye özel
            $table->foreignId('project_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Flutter/web tarafında seçim için kısa benzersiz kod
            $table->string('template_code');

            $table->string('template_name');

            // ZPL içeriği; dinamik alanlar {{VariableName}} formatında
            $table->longText('zpl_template');

            /*
             * Değişken tanımları — JSON dizisi:
             * [
             *   {"variable_name": "ItemCode",      "description": "Ürün kodu",    "default_value": ""},
             *   {"variable_name": "ProductPrice",  "description": "Fiyat",        "default_value": "0,00"},
             *   ...
             * ]
             * Backend bu listeyi okuyarak API verisini map eder.
             */
            $table->json('variables');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Aynı proje içinde template_code tekrar edemez.
            // NULL project_id SQL NULL semantiğinden dolayı unique kısıtını atlar.
            $table->unique(['project_id', 'template_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zpl_label_templates');
    }
};
