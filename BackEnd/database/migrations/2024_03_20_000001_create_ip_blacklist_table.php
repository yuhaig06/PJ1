<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIpBlacklistTable extends Migration
{
    public function up()
    {
        Schema::create('ip_blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('ip');
            $table->string('reason');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('ip');
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ip_blacklist');
    }
} 