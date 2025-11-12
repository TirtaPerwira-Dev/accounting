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
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->date('transaction_date');
            $table->string('reference', 50)->nullable(); // JU-001, SJ-001, etc
            $table->text('description');
            $table->decimal('total_amount', 20, 2)->default(0);
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('posted_by')->nullable()->constrained('users');
            $table->timestamp('posted_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'transaction_date']);
            $table->index(['company_id', 'status']);
            $table->unique(['company_id', 'reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
