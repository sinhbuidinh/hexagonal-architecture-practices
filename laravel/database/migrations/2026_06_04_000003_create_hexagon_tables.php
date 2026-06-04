<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->json('specialties')->nullable();
            $table->json('languages')->nullable();
            $table->string('license_number')->nullable();
            $table->boolean('accepting_new_patients')->default(true);
            $table->unsignedSmallInteger('slot_duration_minutes')->default(15);
            $table->json('weekly_schedule')->nullable();
            $table->string('schedule_timezone', 64)->default('UTC');
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('preferred_language')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone', 32)->nullable();
            $table->timestamps();
        });

        Schema::create('bookable_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practitioner_id')->constrained('doctors')->cascadeOnDelete();
            $table->date('slot_date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('status', 20)->default('available');
            $table->timestamps();

            $table->unique(['practitioner_id', 'slot_date', 'starts_at', 'ends_at'], 'bookable_slots_unique_window');
        });

        Schema::create('practitioner_availability', function (Blueprint $table) {
            $table->unsignedBigInteger('practitioner_id')->primary();
            $table->unsignedInteger('slots')->default(0);
            $table->foreign('practitioner_id')->references('id')->on('doctors')->cascadeOnDelete();
        });

        Schema::create('appointment_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practitioner_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('bookable_slot_id')->nullable()->constrained('bookable_slots')->nullOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->unsignedInteger('slots');
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('medication');
            $table->string('dosage');
            $table->text('instructions')->default('');
            $table->string('status');
            $table->text('pharmacy_notes')->default('');
            $table->unsignedInteger('version')->default(1);
            $table->string('last_updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->string('outcome');
            $table->timestamp('occurred_at');
            $table->string('actor_id');
            $table->string('actor_role');
            $table->string('patient_id')->nullable();
            $table->string('action_type');
            $table->string('ip_address');
            $table->string('device_id');
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->text('state_diff')->nullable();
            $table->string('exception_class')->nullable();
            $table->text('exception_message')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('expiration_queue', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->json('payload');
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expiration_queue');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('appointment_holds');
        Schema::dropIfExists('practitioner_availability');
        Schema::dropIfExists('bookable_slots');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('doctors');
    }
};
