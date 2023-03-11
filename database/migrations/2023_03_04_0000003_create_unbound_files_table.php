<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnboundFilesTable extends Migration
{
    public function up() {
        Schema::create('unbound_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->text('file_path');
            $table->text('file_name');
            $table->string('extension')->nullable();
            $table->string('title')->default('');
            $table->json('meta_data')->nullable();
            $table->timestamps();
            $table->index('folder_id');
        });

        Schema::disableForeignKeyConstraints();
        Schema::table('unbound_files', function (Blueprint $table) {
            $table->foreign('folder_id')->references('id')->on('unbound_folders');
        });
        Schema::enableForeignKeyConstraints();
    }
    public function down() {
        Schema::dropIfExists('unbound_files');
    }
}
