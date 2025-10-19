<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('barbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Храним рабочие дни как JSON
            $table->json('working_days')->nullable();

            // Время начала и окончания рабочего дня для всех дней недели
            $table->time('start_working_time')->nullable();  // Время начала работы
            $table->time('end_working_time')->nullable();    // Время окончания работы

            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('barbers');
    }
};
