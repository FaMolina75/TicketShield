<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $database = config('database.connections.auth_logs.database');

        if ($database !== ':memory:' && ! file_exists($database)) {
            File::ensureDirectoryExists(dirname($database));
            touch($database);
        }

        $schema = Schema::connection('auth_logs');

        if ($schema->hasTable('auth_audit_logs')) {
            return;
        }

        $schema->create('auth_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('email')->index();
            $table->string('event');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('auth_logs')->dropIfExists('auth_audit_logs');
    }
};