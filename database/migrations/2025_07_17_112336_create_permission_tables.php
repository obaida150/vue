<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        if (! Schema::hasTable($tableNames['permissions'])) {
            Schema::create($tableNames['permissions'], function (Blueprint $table) use ($tableNames) {
                $table->bigIncrements('id'); // permission id
                $table->string('name');       // For example: 'edit articles'
                $table->string('guard_name'); // For example: 'web'
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }


        // HIER IST DIE WICHTIGE ÄNDERUNG: Prüfen, ob die 'roles'-Tabelle bereits existiert
        if (! Schema::hasTable($tableNames['roles'])) {
            Schema::create($tableNames['roles'], function (Blueprint $table) use ($teams, $columnNames) {
                $table->bigIncrements('id'); // role id
                if ($teams || config('permission.testing')) { // permission.testing is a fix for sqlite testing
                    $table->uuid($columnNames['team_foreign_key'])->nullable();
                    $table->index($columnNames['team_foreign_key']);
                }
                $table->string('name');       // For example: 'admin'
                $table->string('guard_name'); // For example: 'web'
                $table->timestamps();
                if ($teams || config('permission.testing')) {
                    $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
                } else {
                    $table->unique(['name', 'guard_name']);
                }
            });
        }

        if (! Schema::hasTable($tableNames['model_has_permissions'])) {
            Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
                $table->unsignedBigInteger($pivotPermission);

                $table->string('model_type');
                $table->unsignedBigInteger($columnNames['model_morph_key']);
                $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

                $table->foreign($pivotPermission)
                    ->references('id')
                    ->on($tableNames['permissions'])
                    ->onDelete('cascade');
                if ($teams) {
                    $table->uuid($columnNames['team_foreign_key']);
                    $table->index($columnNames['team_foreign_key']);
                } else {
                    $table->unique([$pivotPermission, $columnNames['model_morph_key'], 'model_type']);
                }
            });
        }

        if (! Schema::hasTable($tableNames['model_has_roles'])) {
            Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
                $table->unsignedBigInteger($pivotRole);

                $table->string('model_type');
                $table->unsignedBigInteger($columnNames['model_morph_key']);
                $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

                $table->foreign($pivotRole)
                    ->references('id')
                    ->on($tableNames['roles'])
                    ->onDelete('cascade');
                if ($teams) {
                    $table->uuid($columnNames['team_foreign_key']);
                    $table->index($columnNames['team_foreign_key']);
                } else {
                    $table->unique([$pivotRole, $columnNames['model_morph_key'], 'model_type']);
                }
            });
        }

        if (! Schema::hasTable($tableNames['role_has_permissions'])) {
            Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
                $table->unsignedBigInteger($pivotPermission);
                $table->unsignedBigInteger($pivotRole);

                $table->foreign($pivotPermission)
                    ->references('id')
                    ->on($tableNames['permissions'])
                    ->onDelete('cascade');

                $table->foreign($pivotRole)
                    ->references('id')
                    ->on($tableNames['roles'])
                    ->onDelete('cascade');

                $table->unique([$pivotPermission, $pivotRole]);
            });
        }

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableNames = config('permission.table_names');

        if (Schema::hasTable($tableNames['role_has_permissions'])) {
            Schema::drop($tableNames['role_has_permissions']);
        }
        if (Schema::hasTable($tableNames['model_has_roles'])) {
            Schema::drop($tableNames['model_has_roles']);
        }
        if (Schema::hasTable($tableNames['model_has_permissions'])) {
            Schema::drop($tableNames['model_has_permissions']);
        }
        if (Schema::hasTable($tableNames['permissions'])) {
            Schema::drop($tableNames['permissions']);
        }
        // HIER IST DIE WICHTIGE ÄNDERUNG: Nur die 'roles'-Tabelle löschen, wenn sie von dieser Migration erstellt wurde
        // Dies ist komplexer, da wir nicht wissen, ob sie ursprünglich von uns erstellt wurde.
        // Für den Fall, dass Sie sie nicht löschen möchten, lassen Sie diesen Teil unverändert oder kommentieren Sie ihn aus,
        // wenn Sie sicher sind, dass Ihre 'roles'-Tabelle nicht von dieser Migration verwaltet werden soll.
        // Wenn Sie sie hier auskommentieren, müssen Sie sie auch in der up()-Methode auskommentieren.
        // Da wir in up() eine Prüfung hinzugefügt haben, ist es sicherer, diesen Teil hier zu belassen,
        // da Schema::drop() keinen Fehler wirft, wenn die Tabelle nicht existiert.
        if (Schema::hasTable($tableNames['roles'])) {
            Schema::drop($tableNames['roles']);
        }
    }
}
