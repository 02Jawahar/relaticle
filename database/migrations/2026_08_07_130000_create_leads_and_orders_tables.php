<?php

declare(strict_types=1);

use App\Enums\CreationSource;
use App\Enums\Pipeline\LeadStage;
use App\Enums\Pipeline\OrderStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Leads feed Deals, Deals feed Orders. Each carries its own stage/sub_stage
     * pair, and the downstream record keeps a nullable link back to the record
     * it was converted from so the chain stays traceable.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUlid('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignUlid('contact_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('name');
            $table->string('stage')->default(LeadStage::NEW->value)->index();
            $table->string('sub_stage')->nullable();
            $table->string('creation_source')->default(CreationSource::WEB->value);
            $table->double('order_column')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'stage']);
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUlid('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignUlid('contact_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignUlid('deal_id')->nullable()->constrained('deals')->nullOnDelete();
            $table->string('name');
            $table->string('stage')->default(OrderStage::ORDER_RECEIVED->value)->index();
            $table->string('sub_stage')->nullable();
            $table->string('creation_source')->default(CreationSource::WEB->value);
            $table->double('order_column')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'stage']);
        });

        Schema::table('deals', function (Blueprint $table): void {
            if (! Schema::hasColumn('deals', 'lead_id')) {
                $table->foreignUlid('lead_id')->nullable()->after('contact_id')
                    ->constrained('leads')->nullOnDelete();
            }
        });
    }
};
