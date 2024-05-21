<?php

use core\BaseMigration;

class Create_table_users extends BaseMigration
{
    public function up()
    {
        $this->createTable('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('session_token')->nullable();
        });
    }

    public function down()
    {
        $this->dropTable('users');
    }
}
