<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('codes', function (Blueprint $table) {
            $table->string('hash')->unique();
            $table->string('code');
            $table->integer('expires_at')->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('codes');
    }
};
