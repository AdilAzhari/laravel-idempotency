<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_records', function (Blueprint $table) {
            $table->id();

            $table->string('key')
                ->unique();

            $table->string('fingerprint');

            $table->integer('status');

            $table->json('headers');

            $table->longText('body');

            $table->timestamp('expires_at');

            $table->timestamps();

            $table->index('expires_at');
        });
    }
};
