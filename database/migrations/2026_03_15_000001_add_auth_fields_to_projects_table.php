<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('project_api_username')->nullable()->after('project_api_address');
            $table->string('project_api_password')->nullable()->after('project_api_username');
            $table->string('nebim_integrator_token')->nullable()->after('project_integrator_api_address');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['project_api_username', 'project_api_password', 'nebim_integrator_token']);
        });
    }
};
