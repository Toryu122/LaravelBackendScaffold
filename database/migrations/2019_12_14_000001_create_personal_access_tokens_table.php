<?php

use App\Common\CustomBlueprint;
use App\Common\CustomSchema;
use Illuminate\Database\Migrations\Migration;

class CreatePersonalAccessTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        CustomSchema::create('personal_access_tokens', function (CustomBlueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Sanctum v3 addition, not present in v2 by default
            $table->timestampsDf();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        CustomSchema::dropIfExists('personal_access_tokens');
    }
}
