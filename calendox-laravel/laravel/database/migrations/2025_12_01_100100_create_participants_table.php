<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('participant')) return;
        Schema::create('participant', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['appointment_id', 'email']);
            $table->index('appointment_id');
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('participant');
    }
};
