<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_idempotency_records', function (Blueprint $table) {
            $table->id();

            $table->string('key')
                ->unique();

            $table->string('fingerprint');

            $table->string('job_class');

            $table->timestamp('expires_at');

            $table->timestamps();

            $table->index('expires_at');
        });
    }
};
