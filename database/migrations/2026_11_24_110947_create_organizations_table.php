<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        foreach (['users', 'clients', 'office_actions', 'email_templates', 'activities'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->foreignId('organization_id')->nullable()->constrained()->restrictOnDelete();
            });
        }

        $hasRows = DB::table('users')->exists()
            || DB::table('clients')->exists()
            || DB::table('office_actions')->exists()
            || DB::table('email_templates')->exists()
            || DB::table('activities')->exists();

        if ($hasRows) {
            $now = now();
            $organizationId = DB::table('organizations')->insertGetId([
                'name' => 'HECHA',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach (['users', 'clients', 'office_actions', 'email_templates', 'activities'] as $table) {
                DB::table($table)->whereNull('organization_id')->update([
                    'organization_id' => $organizationId,
                ]);
            }
        }

        Schema::table('email_templates', function (Blueprint $table): void {
            $table->dropUnique(['key']);
            $table->unique(['organization_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table): void {
            $table->dropUnique(['organization_id', 'key']);
            $table->unique('key');
        });

        foreach (['activities', 'email_templates', 'office_actions', 'clients', 'users'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('organization_id');
            });
        }

        Schema::dropIfExists('organizations');
    }
};
