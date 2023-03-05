<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnboundImagesTable extends Migration
{
    public function up() {
        Schema::create('unbound_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->text('file_path');
            $table->text('file_name');
            $table->json('meta_data')->nullable();
            $table->timestamps();
            $table->index('folder_id');
        });

        Schema::disableForeignKeyConstraints();
        Schema::table('unbound_images', function (Blueprint $table) {
            $table->foreign('folder_id')->references('id')->on('unbound_folders');
        });
        Schema::enableForeignKeyConstraints();
    }
    public function down() {
        Schema::dropIfExists('unbound_images');
    }
}
