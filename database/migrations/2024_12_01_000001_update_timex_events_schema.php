<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('timex.tables.event.name');

        $connection = Schema::getConnection();
        $usingSqlite = $connection->getDriverName() === 'sqlite';

        $columnsToDrop = [];

        $hasOrganizer = Schema::hasColumn($tableName, 'organizer');
        if ($hasOrganizer) {
            if (! $usingSqlite) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['organizer']);
                });
            }

            $columnsToDrop[] = 'organizer';
        }

        foreach (['date', 'time', 'participants'] as $column) {
            if (Schema::hasColumn($tableName, $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if ($columnsToDrop !== []) {
            Schema::table($tableName, function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->dateTime('start')->after('number');
            $table->dateTime('end')->nullable()->after('start');
            $table->json('participants')->nullable()->after('end');
            $table->foreignId('barber_id')->nullable()->after('participants')->constrained('users')->nullOnDelete();
            $table->foreignId('organizer_id')->nullable()->after('barber_id')->constrained('users')->nullOnDelete();
            $table->string('status')->default('scheduled')->after('organizer_id');
        });
    }

    public function down(): void
    {
        $tableName = config('timex.tables.event.name');
        $connection = Schema::getConnection();
        $usingSqlite = $connection->getDriverName() === 'sqlite';

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $usingSqlite) {
            if (! $usingSqlite && Schema::hasColumn($tableName, 'barber_id')) {
                $table->dropForeign(['barber_id']);
            }

            if (! $usingSqlite && Schema::hasColumn($tableName, 'organizer_id')) {
                $table->dropForeign(['organizer_id']);
            }

            $table->dropColumn(['start', 'end', 'participants', 'barber_id', 'organizer_id', 'status']);
            $table->date('date');
            $table->time('time')->nullable();
            $table->uuid('organizer')->nullable();
            $table->string('participants')->nullable();
        });
    }
};
