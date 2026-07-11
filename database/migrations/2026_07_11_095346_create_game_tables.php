<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Game fixture tables. They are never filled with data — the game introspects
 * their schema and the models' relation methods to generate level content.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('number');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('title');
            $table->text('body');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained();
            $table->text('body');
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_ref');
            $table->foreign('customer_ref')->references('id')->on('customers');
            $table->integer('total');
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained();
            $table->foreignId('role_id')->constrained();
        });

        Schema::create('mechanics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mechanic_id')->constrained();
            $table->string('model');
        });

        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained();
            $table->string('name');
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('environments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained();
            $table->string('name');
        });

        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('environment_id')->constrained();
            $table->string('commit_hash');
            $table->timestamp('deployed_at');
        });

        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->morphs('imageable');
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('url');
        });

        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->string('emoji');
            $table->morphs('reactable');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained();
            $table->morphs('taggable');
        });
    }

    public function down(): void
    {
        $tables = [
            'taggables', 'tags', 'reactions', 'videos', 'images',
            'deployments', 'environments', 'projects',
            'owners', 'cars', 'mechanics',
            'role_user', 'roles',
            'orders', 'customers',
            'comments', 'posts', 'phones',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
