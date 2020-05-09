<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFortressTable extends Migration
{
    /**
     * Table name.
     */
    const TABLE = 'fortress_roles';

    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('model_class');
            $table->string('model_id');

            $table->string('name');

            $table->string('resource_class')
                ->nullable();
            $table->string('resource_id')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::drop(self::TABLE);
    }
}
