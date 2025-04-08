<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestLogsTable extends Migration
{
    public function up()
    {
        Schema::create('request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip');
            $table->string('method');
            $table->string('url');
            $table->string('user_agent')->nullable();
            $table->integer('status_code');
            $table->integer('response_time')->nullable();
            $table->timestamps();

            $table->index('ip');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('request_logs');
    }
} 