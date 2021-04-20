<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFbisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fbis', function (Blueprint $table) {
            $table->id();
            $table->string('rujukan');
            $table->string('tarikh');
            $table->string('kepada');
            $table->foreignId('type_id')->constrained();
            $table->foreignId('division_id')->constrained();
            $table->string('perkara');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fbis');
    }
}
