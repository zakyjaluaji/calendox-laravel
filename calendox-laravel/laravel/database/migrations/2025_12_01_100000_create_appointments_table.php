<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('appointments')) return;
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('google_event_id', 64)->nullable();
            $table->string('title');
            $table->string('pic_name');
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('attachment_filename', 255)->nullable();
            $table->string('color', 32)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }
    public function down(): void {
        Schema::dropIfExists('appointments');
    }
};
