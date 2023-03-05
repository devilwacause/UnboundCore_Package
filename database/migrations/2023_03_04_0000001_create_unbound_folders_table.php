<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnboundFoldersTable extends Migration
{
    public function up() {
        Schema::create('unbound_folders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('folder_name');
            $table->timestamps();
        });

        Schema::disableForeignKeyConstraints();
        Schema::table('unbound_folders', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('unbound_folders');
        });
        Schema::enableForeignKeyConstraints();
    }

    public function down() {
        Schema::dropIfExists('unbound_folders');
    }
}
