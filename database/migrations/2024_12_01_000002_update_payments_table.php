<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'who_created')) {
                $table->dropColumn('who_created');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('who_created')->default('administrator')->after('payment_date');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('who_created');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->date('who_created')->nullable();
        });
    }
};
