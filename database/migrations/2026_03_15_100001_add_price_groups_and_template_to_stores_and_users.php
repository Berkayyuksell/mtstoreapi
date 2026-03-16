<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // stores tablosu — mağaza düzeyinde fiyat grup kodu
        Schema::table('stores', function (Blueprint $table) {
            $table->string('price_group_code')->nullable()->after('StoreName');
            $table->string('disc_price_group_code')->nullable()->after('price_group_code');
        });

        // users tablosu — kullanıcıya özel overrides
        // Öncelik zinciri: users > stores > sabit fallback ('PSF' / 'PSF_IND')
        Schema::table('users', function (Blueprint $table) {
            $table->string('price_group_code')->nullable()->after('warehouse_id');
            $table->string('disc_price_group_code')->nullable()->after('price_group_code');

            // Kullanıcıya atanan ZPL template (nullable = varsayılan template kullanılır)
            $table->foreignId('zpl_label_template_id')
                ->nullable()
                ->after('disc_price_group_code')
                ->constrained('zpl_label_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['zpl_label_template_id']);
            $table->dropColumn(['price_group_code', 'disc_price_group_code', 'zpl_label_template_id']);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['price_group_code', 'disc_price_group_code']);
        });
    }
};
