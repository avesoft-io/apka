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
        Schema::table('office_actions', function (Blueprint $table) {
            $table->json('applicant_names')->nullable()->after('hci_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('office_actions', function (Blueprint $table) {
            $table->dropColumn('applicant_names');
        });
    }
};
