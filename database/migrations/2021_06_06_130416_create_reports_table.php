<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no')->nullable();
            $table->string('subject');
            $table->text('remark');
            $table->foreignId('report_by_id')->constrained('users');
            $table->foreignId('report_for_id')->nullable()->constrained('users');
            $table->foreignId('report_status_id')->constrained('_report_statuses');
            $table->foreignId('report_category_id')->constrained();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
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
        Schema::dropIfExists('reports');
    }
}
