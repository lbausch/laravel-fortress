<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFortressTable extends Migration
{
    /**
     * Table name.
     */
    const TABLE = 'fortress_grants';

    /**
     * Run the migrations.
     */
    public function up()
    {
        // Grants: Roles to Objects
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->bigIncrements('id')
                ->unsigned();

            $table->string('model_type');
            $table->string('model_id');

            $table->string('role');

            $table->string('resource_type')
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
