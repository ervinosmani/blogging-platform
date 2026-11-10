<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kontrollo nese ekziston kolona e vjeter.
        $hasLegacyCategory = Schema::hasColumn('posts', 'category');

        // Mos fshi kategori tekstuale nese ka postime te vjetra.
        if ($hasLegacyCategory && DB::table('posts')->exists()) {
            throw new RuntimeException(
                'Legacy category data exists. It must be migrated before removing the category column.'
            );
        }

        // Shto category_id vetem nese mungon.
        if (!Schema::hasColumn('posts', 'category_id')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable();
            });
        }

        // Sigurohu qe te gjitha referencat jane te vlefshme.
        $invalidReferences = DB::table('posts')
            ->leftJoin(
                'categories',
                'posts.category_id',
                '=',
                'categories.id'
            )
            ->whereNull('categories.id')
            ->count();

        if ($invalidReferences > 0) {
            throw new RuntimeException(
                'Invalid category references found. No category data was removed.'
            );
        }

        // Hiqe kolonen e vjeter vetem kur nuk ka postime
        // qe mund te humbasin informacion.
        if ($hasLegacyCategory) {
            Schema::table('posts', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }

        // Kontrollo nese foreign key ekziston tashme.
        $hasCategoryForeignKey = collect(
            Schema::getForeignKeys('posts')
        )->contains(function (array $foreignKey) {
            return in_array(
                'category_id',
                $foreignKey['columns'] ?? [],
                true
            );
        });

        // SQLite nuk mbeshtet shtimin e foreign keys
        // ne tabela ekzistuese me ALTER TABLE.
        if (
            !$hasCategoryForeignKey &&
            DB::getDriverName() !== 'sqlite'
        ) {
            Schema::table('posts', function (Blueprint $table) {
                $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Mos bej rollback destruktiv automatikisht.
        // Skema e meparshme mund te ndryshoje mes
        // databazes lokale dhe instalimeve te reja.
        throw new RuntimeException(
            'Automatic rollback is disabled to prevent category data loss. Restore a verified backup if reversal is required.'
        );
    }
};
