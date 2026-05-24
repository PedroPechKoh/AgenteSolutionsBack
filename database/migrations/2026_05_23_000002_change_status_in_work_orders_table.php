<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        DB::statement("ALTER TABLE work_orders MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Por Hacer'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE work_orders MODIFY COLUMN status ENUM('Por Hacer', 'En Proceso', 'Listo') NOT NULL DEFAULT 'Por Hacer'");
    }
};
