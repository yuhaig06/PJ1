<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditLogsTable extends Migration
{
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // user_action, system_action, security_event, payment_event, admin_action
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('details')->nullable();
            $table->string('severity')->default('info'); // info, warning, error, critical
            $table->timestamps();

            $table->index('type');
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
} 