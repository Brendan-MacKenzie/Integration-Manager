<?php

use BrendanMacKenzie\IntegrationManager\Models\IntegrationOption;
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
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->nullableMorphs('owner');
            $table->unsignedBigInteger('integration_option_id');
            $table->foreign('integration_option_id')->references('id')->on('integration_options')
                ->onDelete('cascade')
                ->onUpdate('restrict');
            $table->text('credentials')->nullable();
        });

        Schema::create('integration_options', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
        });

        if (count(config('integrations.options')) > 0) {
            foreach (config('integrations.options') as $optionName) {
                IntegrationOption::firstOrCreate(['name' => $optionName]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropForeign('integrations_integration_option_id_foreign');
        });

        Schema::dropIfExists('integration_options');
        Schema::dropIfExists('integrations');
    }
};
