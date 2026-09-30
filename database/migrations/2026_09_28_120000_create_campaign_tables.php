<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type', 50);
            $table->text('description')->nullable();
            $table->json('parameters');
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_exclusive')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'code']);
            $table->index(['user_id', 'is_active', 'starts_at', 'ends_at']);
            $table->index(['user_id', 'priority']);
        });

        Schema::create('campaign_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('target_type', 30);
            $table->unsignedBigInteger('target_id');
            $table->timestamps();

            $table->unique(['campaign_id', 'target_type', 'target_id']);
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('sale_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 100);
            $table->string('campaign_name');
            $table->string('campaign_type', 50);
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'campaign_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_discounts');
        Schema::dropIfExists('campaign_targets');
        Schema::dropIfExists('campaigns');
    }
};
