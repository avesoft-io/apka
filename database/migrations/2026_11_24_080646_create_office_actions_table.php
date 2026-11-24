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
        Schema::create('office_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('application_number')->nullable();
            $table->string('hci_reference')->nullable();
            $table->string('paper_number');
            $table->string('type');
            $table->date('mailing_date');
            $table->unsignedTinyInteger('response_period_months');
            $table->date('original_deadline');
            $table->date('internal_response_date')->nullable();
            $table->date('reminder_date')->nullable();
            $table->date('reminder_sent_on')->nullable();
            $table->date('reminder_sent_for')->nullable();
            $table->date('extended_deadline')->nullable();
            $table->string('extension_status')->default('none');
            $table->date('extension_requested_on')->nullable();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('logged_by_id')->constrained('users')->restrictOnDelete();
            $table->string('outcome')->default('open');
            $table->timestamp('abandoned_at')->nullable();
            $table->text('abandon_reason')->nullable();
            $table->text('response_instructions')->nullable();
            $table->date('instructions_received_on')->nullable();
            $table->date('report_sent_on')->nullable();
            $table->boolean('report_includes_fees')->default(false);
            $table->boolean('report_requests_instructions')->default(false);
            $table->text('draft_notes')->nullable();
            $table->boolean('response_prepared')->default(false);
            $table->boolean('amended_claims_prepared')->default(false);
            $table->boolean('other_documents_prepared')->default(false);
            $table->date('draft_prepared_on')->nullable();
            $table->string('checking_status')->default('none');
            $table->boolean('client_approval_required')->default(false);
            $table->string('client_approval_status')->default('not_required');
            $table->date('filing_date')->nullable();
            $table->date('filing_reported_on')->nullable();
            $table->timestamps();

            $table->index(['outcome', 'assignee_id']);
            $table->index(['outcome', 'reminder_date']);
            $table->index(['outcome', 'checking_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_actions');
    }
};
