<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-event email notification settings, editable by superadmin. Each row is
 * one aduan event; it defines whether the email is sent and who receives it
 * (by role, plus dynamic parties and extra static addresses), instead of the
 * previously hardcoded recipient logic in the report actions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique();       // e.g. aduan_ditugaskan
            $table->string('label');                     // human label for the UI
            $table->boolean('enabled')->default(true);   // send email or not
            $table->json('roles')->nullable();           // role names to notify
            $table->boolean('include_reporter')->default(false);          // pengadu
            $table->boolean('include_assigned_officer')->default(false);  // pegawai ditugaskan
            $table->json('extra_emails')->nullable();    // extra static addresses
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
