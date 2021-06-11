<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportTrailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_trails', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->foreignId('report_id')->constrained();
            $table->foreignId('assigned_to_id')->nullable()->constrained('users');
            $table->foreignId('status_id')->constrained('_report_trail_statuses');
            $table->foreignId('updated_by_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('report_trails');
    }
}
