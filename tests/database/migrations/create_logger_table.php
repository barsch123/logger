<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            /**
             * WHAT happened
             */
            $table->string('event', 32);                 // created | updated | deleted | restored
            $table->string('action', 64)->nullable();    // optional semantic action (approved, assigned, paid)
            $table->string('log', 64)->default('default'); // grouping / channel
            $table->text('description')->nullable();     // human-readable sentence

            /**
             * SUBJECT (model being acted on)
             */
            $table->string('subject_type');              // App\Models\Order
            $table->unsignedBigInteger('subject_id');    // 123

            /**
             * ACTOR (who caused it)
             */
            $table->string('causer_type')->nullable();   // App\Models\User | system | job
            $table->unsignedBigInteger('causer_id')->nullable();

            /**
             * CONTEXT / METADATA
             */
            $table->json('properties')->nullable();      // before/after values
            $table->json('meta')->nullable();            // ip, ua, route, request id

            /**
             * SYSTEM
             */
            $table->string('origin', 32)->nullable();    // web | api | console | queue
            $table->string('batch_id', 36)->nullable();  // UUID for grouped actions

            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);
            $table->index(['event', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
};
