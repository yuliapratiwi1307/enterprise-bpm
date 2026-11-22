<?php

namespace Tests\Feature;

use App\Models\MmAttributeDefinition;
use App\Models\MmCategory;
use App\Models\MmMaterial;
use App\Models\MmMaterialVariant;
use App\Models\MmUnit;
use App\Services\MmAttributeDefinitionService;
use App\Services\MmMasterDataService;
use App\Services\MmVariantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class MmVariantConcurrencyTest extends TestCase
{
    public function test_reparent_waits_for_specification_write_and_is_rejected_after_specification_commit(): void
    {
        $fixture = $this->createFixture();
        $connection = DB::connection('pgsql');
        $child = null;
        $transactionOpen = false;

        try {
            $connection->beginTransaction();
            $transactionOpen = true;
            $variant = MmMaterialVariant::query()->findOrFail($fixture['variant_id']);
            app(MmVariantService::class)->setAttributes($variant, [
                [
                    'attribute_definition_id' => $fixture['definition_id'],
                    'value_text' => 'Concurrent value',
                ],
            ]);

            $child = $this->startConcurrentOperation(
                'reparent',
                $fixture['variant_id'],
                $fixture['target_material_id'],
                $fixture['definition_id'],
            );
            $blocked = $this->waitForDatabaseLock($child['backend_pid']);
            $connection->commit();
            $transactionOpen = false;
            $result = $this->finishConcurrentOperation($child);
            $child = null;

            $this->assertTrue($blocked, 'The parent update must wait on the variant row lock.');
            $this->assertSame('validation_error', $result['type']);
            $this->assertSame(
                'Material induk tidak dapat diubah karena varian sudah memiliki nilai spesifikasi.',
                $result['errors']['material_id'][0],
            );
            $this->assertDatabaseHas('mm_material_variants', [
                'id' => $fixture['variant_id'],
                'material_id' => $fixture['material_id'],
            ]);
            $this->assertDatabaseHas('mm_variant_attributes', [
                'variant_id' => $fixture['variant_id'],
                'attribute_definition_id' => $fixture['definition_id'],
                'value_text' => 'Concurrent value',
            ]);
        } finally {
            if ($transactionOpen) {
                $connection->rollBack();
            }
            if ($child !== null) {
                $this->stopConcurrentOperation($child);
            }
            $this->deleteFixture($fixture);
        }
    }

    public function test_specification_write_waits_for_parent_change_and_validates_against_committed_parent(): void
    {
        $fixture = $this->createFixture();
        $connection = DB::connection('pgsql');
        $child = null;
        $transactionOpen = false;

        try {
            $connection->beginTransaction();
            $transactionOpen = true;
            $variant = MmMaterialVariant::query()->findOrFail($fixture['variant_id']);
            app(MmVariantService::class)->updateVariant($variant, [
                'material_id' => $fixture['target_material_id'],
            ]);

            $child = $this->startConcurrentOperation(
                'set_attributes',
                $fixture['variant_id'],
                $fixture['target_material_id'],
                $fixture['definition_id'],
            );
            $blocked = $this->waitForDatabaseLock($child['backend_pid']);
            $connection->commit();
            $transactionOpen = false;
            $result = $this->finishConcurrentOperation($child);
            $child = null;

            $this->assertTrue($blocked, 'The specification write must wait on the variant row lock.');
            $this->assertSame('validation_error', $result['type']);
            $this->assertSame(
                'Definisi atribut harus aktif dan sesuai kategori.',
                $result['errors']['attributes'][0],
            );
            $this->assertDatabaseHas('mm_material_variants', [
                'id' => $fixture['variant_id'],
                'material_id' => $fixture['target_material_id'],
            ]);
            $this->assertDatabaseMissing('mm_variant_attributes', [
                'variant_id' => $fixture['variant_id'],
                'attribute_definition_id' => $fixture['definition_id'],
            ]);
        } finally {
            if ($transactionOpen) {
                $connection->rollBack();
            }
            if ($child !== null) {
                $this->stopConcurrentOperation($child);
            }
            $this->deleteFixture($fixture);
        }
    }

    public function test_specification_write_waits_for_material_category_update_and_revalidates_current_category(): void
    {
        $fixture = $this->createFixture();
        $connection = DB::connection('pgsql');
        $child = null;
        $transactionOpen = false;

        try {
            $connection->beginTransaction();
            $transactionOpen = true;
            $material = MmMaterial::query()->findOrFail($fixture['material_id']);
            app(MmMasterDataService::class)->updateMaterial($material, [
                'category_id' => $fixture['target_category_id'],
            ]);

            $child = $this->startConcurrentOperation(
                'set_attributes',
                $fixture['variant_id'],
                $fixture['target_material_id'],
                $fixture['definition_id'],
                $fixture['material_id'],
                $fixture['target_category_id'],
            );
            $this->assertTrue(
                $this->waitForDatabaseLock($child['backend_pid']),
                'The specification write must wait on the material row lock.',
            );
            $connection->commit();
            $transactionOpen = false;
            $result = $this->finishConcurrentOperation($child);
            $child = null;

            $this->assertSame('validation_error', $result['type']);
            $this->assertSame(
                'Definisi atribut harus aktif dan sesuai kategori.',
                $result['errors']['attributes'][0],
            );
            $this->assertDatabaseHas('mm_materials', [
                'id' => $fixture['material_id'],
                'category_id' => $fixture['target_category_id'],
            ]);
            $this->assertDatabaseMissing('mm_variant_attributes', [
                'variant_id' => $fixture['variant_id'],
                'attribute_definition_id' => $fixture['definition_id'],
            ]);
        } finally {
            if ($transactionOpen) {
                $connection->rollBack();
            }
            if ($child !== null) {
                $this->stopConcurrentOperation($child);
            }
            $this->deleteFixture($fixture);
        }
    }

    public function test_material_category_update_waits_for_specification_write_and_is_rejected_after_commit(): void
    {
        $fixture = $this->createFixture();
        $connection = DB::connection('pgsql');
        $child = null;
        $transactionOpen = false;

        try {
            $connection->beginTransaction();
            $transactionOpen = true;
            $variant = MmMaterialVariant::query()->findOrFail($fixture['variant_id']);
            app(MmVariantService::class)->setAttributes($variant, [[
                'attribute_definition_id' => $fixture['definition_id'],
                'value_text' => 'Concurrent value',
            ]]);

            $child = $this->startConcurrentOperation(
                'update_material',
                $fixture['variant_id'],
                $fixture['target_material_id'],
                $fixture['definition_id'],
                $fixture['material_id'],
                $fixture['target_category_id'],
            );
            $this->assertTrue(
                $this->waitForDatabaseLock($child['backend_pid']),
                'The material category update must wait on the material row lock.',
            );
            $connection->commit();
            $transactionOpen = false;
            $result = $this->finishConcurrentOperation($child);
            $child = null;

            $this->assertSame('validation_error', $result['type']);
            $this->assertSame(
                'Kategori material tidak dapat diubah karena varian sudah memiliki nilai spesifikasi.',
                $result['errors']['category_id'][0],
            );
            $this->assertDatabaseHas('mm_materials', [
                'id' => $fixture['material_id'],
                'category_id' => $fixture['category_id'],
            ]);
            $this->assertDatabaseHas('mm_variant_attributes', [
                'variant_id' => $fixture['variant_id'],
                'attribute_definition_id' => $fixture['definition_id'],
                'value_text' => 'Concurrent value',
            ]);
        } finally {
            if ($transactionOpen) {
                $connection->rollBack();
            }
            if ($child !== null) {
                $this->stopConcurrentOperation($child);
            }
            $this->deleteFixture($fixture);
        }
    }

    public function test_specification_write_waits_for_attribute_category_update_and_revalidates_definition(): void
    {
        $fixture = $this->createFixture();
        $connection = DB::connection('pgsql');
        $child = null;
        $transactionOpen = false;

        try {
            $connection->beginTransaction();
            $transactionOpen = true;
            $definition = MmAttributeDefinition::query()->findOrFail($fixture['definition_id']);
            app(MmAttributeDefinitionService::class)->updateDefinition($definition, [
                'category_id' => $fixture['target_category_id'],
            ]);

            $child = $this->startConcurrentOperation(
                'set_attributes',
                $fixture['variant_id'],
                $fixture['target_material_id'],
                $fixture['definition_id'],
                $fixture['material_id'],
                $fixture['target_category_id'],
            );
            $this->assertTrue(
                $this->waitForDatabaseLock($child['backend_pid']),
                'The specification write must wait on the attribute definition row lock.',
            );
            $connection->commit();
            $transactionOpen = false;
            $result = $this->finishConcurrentOperation($child);
            $child = null;

            $this->assertSame('validation_error', $result['type']);
            $this->assertSame(
                'Definisi atribut harus aktif dan sesuai kategori.',
                $result['errors']['attributes'][0],
            );
            $this->assertDatabaseHas('mm_attribute_definitions', [
                'id' => $fixture['definition_id'],
                'category_id' => $fixture['target_category_id'],
            ]);
            $this->assertDatabaseMissing('mm_variant_attributes', [
                'variant_id' => $fixture['variant_id'],
                'attribute_definition_id' => $fixture['definition_id'],
            ]);
        } finally {
            if ($transactionOpen) {
                $connection->rollBack();
            }
            if ($child !== null) {
                $this->stopConcurrentOperation($child);
            }
            $this->deleteFixture($fixture);
        }
    }

    public function test_attribute_category_update_waits_for_specification_write_and_is_rejected_after_commit(): void
    {
        $fixture = $this->createFixture();
        $connection = DB::connection('pgsql');
        $child = null;
        $transactionOpen = false;

        try {
            $connection->beginTransaction();
            $transactionOpen = true;
            $variant = MmMaterialVariant::query()->findOrFail($fixture['variant_id']);
            app(MmVariantService::class)->setAttributes($variant, [[
                'attribute_definition_id' => $fixture['definition_id'],
                'value_text' => 'Concurrent value',
            ]]);

            $child = $this->startConcurrentOperation(
                'update_definition',
                $fixture['variant_id'],
                $fixture['target_material_id'],
                $fixture['definition_id'],
                $fixture['material_id'],
                $fixture['target_category_id'],
            );
            $this->assertTrue(
                $this->waitForDatabaseLock($child['backend_pid']),
                'The attribute category update must wait on the definition row lock.',
            );
            $connection->commit();
            $transactionOpen = false;
            $result = $this->finishConcurrentOperation($child);
            $child = null;

            $this->assertSame('validation_error', $result['type']);
            $this->assertSame(
                'Kategori tidak dapat diubah karena atribut ini sudah dipakai oleh varian material.',
                $result['errors']['category_id'][0],
            );
            $this->assertDatabaseHas('mm_attribute_definitions', [
                'id' => $fixture['definition_id'],
                'category_id' => $fixture['category_id'],
            ]);
            $this->assertDatabaseHas('mm_variant_attributes', [
                'variant_id' => $fixture['variant_id'],
                'attribute_definition_id' => $fixture['definition_id'],
                'value_text' => 'Concurrent value',
            ]);
        } finally {
            if ($transactionOpen) {
                $connection->rollBack();
            }
            if ($child !== null) {
                $this->stopConcurrentOperation($child);
            }
            $this->deleteFixture($fixture);
        }
    }

    /**
     * @return array{category_id: int, target_category_id: int, unit_id: int, material_id: int, target_material_id: int, definition_id: int, variant_id: int}
     */
    private function createFixture(): array
    {
        $suffix = strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 12));
        $category = MmCategory::query()->create(['code' => 'CAT-'.$suffix, 'name' => 'Category '.$suffix]);
        $targetCategory = MmCategory::query()->create(['code' => 'CAT-T-'.$suffix, 'name' => 'Target '.$suffix]);
        $unit = MmUnit::query()->create([
            'code' => 'UNIT-'.$suffix,
            'name' => 'Unit '.$suffix,
            'dimension' => 'count',
        ]);
        $material = MmMaterial::query()->create([
            'category_id' => $category->id,
            'code' => 'MAT-'.$suffix,
            'name' => 'Material '.$suffix,
        ]);
        $targetMaterial = MmMaterial::query()->create([
            'category_id' => $targetCategory->id,
            'code' => 'MAT-T-'.$suffix,
            'name' => 'Target Material '.$suffix,
        ]);
        $definition = MmAttributeDefinition::query()->create([
            'category_id' => $category->id,
            'code' => 'ATTR-'.$suffix,
            'label' => 'Specification '.$suffix,
            'data_type' => 'text',
            'is_active' => true,
        ]);
        $variant = MmMaterialVariant::query()->create([
            'material_id' => $material->id,
            'sku' => 'SKU-'.$suffix,
            'variant_name' => 'Variant '.$suffix,
            'base_unit_id' => $unit->id,
            'is_active' => true,
        ]);

        return [
            'category_id' => (int) $category->id,
            'target_category_id' => (int) $targetCategory->id,
            'unit_id' => (int) $unit->id,
            'material_id' => (int) $material->id,
            'target_material_id' => (int) $targetMaterial->id,
            'definition_id' => (int) $definition->id,
            'variant_id' => (int) $variant->id,
        ];
    }

    /**
     * @param  array{category_id: int, target_category_id: int, unit_id: int, material_id: int, target_material_id: int, definition_id: int, variant_id: int}  $fixture
     */
    private function deleteFixture(array $fixture): void
    {
        DB::table('mm_variant_attributes')->where('variant_id', $fixture['variant_id'])->delete();
        DB::table('mm_material_variants')->where('id', $fixture['variant_id'])->delete();
        DB::table('mm_attribute_definitions')->where('id', $fixture['definition_id'])->delete();
        DB::table('mm_materials')->whereIn('id', [$fixture['material_id'], $fixture['target_material_id']])->delete();
        DB::table('mm_categories')->whereIn('id', [$fixture['category_id'], $fixture['target_category_id']])->delete();
        DB::table('mm_units')->where('id', $fixture['unit_id'])->delete();
    }

    /**
     * @return array{pipes: array<int, resource>, process: resource, backend_pid: int}
     */
    private function startConcurrentOperation(
        string $operation,
        int $variantId,
        int $targetMaterialId,
        int $definitionId,
        ?int $materialId = null,
        ?int $targetCategoryId = null,
    ): array {
        if (! function_exists('proc_open')) {
            $this->markTestSkipped('Independent-process PostgreSQL concurrency testing requires proc_open.');
        }

        $worker = <<<'PHP'
            $payload = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
            putenv('APP_ENV=testing');
            $_ENV['APP_ENV'] = 'testing';
            $_SERVER['APP_ENV'] = 'testing';
            require 'vendor/autoload.php';
            $app = require 'bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            config([
                'database.default' => 'pgsql',
                'database.connections.pgsql' => $payload['connection'],
            ]);
            Illuminate\Support\Facades\DB::purge('pgsql');
            $backendPid = (int) Illuminate\Support\Facades\DB::scalar('select pg_backend_pid()');
            echo json_encode(['type' => 'ready', 'backend_pid' => $backendPid], JSON_THROW_ON_ERROR).PHP_EOL;
            flush();
            try {
                $variant = App\Models\MmMaterialVariant::query()->findOrFail($payload['variant_id']);
                if ($payload['operation'] === 'reparent') {
                    app(App\Services\MmVariantService::class)->updateVariant($variant, [
                        'material_id' => $payload['target_material_id'],
                    ]);
                    $result = ['type' => 'updated'];
                } elseif ($payload['operation'] === 'set_attributes') {
                    app(App\Services\MmVariantService::class)->setAttributes($variant, [[
                        'attribute_definition_id' => $payload['definition_id'],
                        'value_text' => 'Concurrent value',
                    ]]);
                    $result = ['type' => 'saved'];
                } elseif ($payload['operation'] === 'update_material') {
                    $material = App\Models\MmMaterial::query()->findOrFail($payload['material_id']);
                    app(App\Services\MmMasterDataService::class)->updateMaterial($material, [
                        'category_id' => $payload['target_category_id'],
                    ]);
                    $result = ['type' => 'updated'];
                } else {
                    $definition = App\Models\MmAttributeDefinition::query()->findOrFail($payload['definition_id']);
                    app(App\Services\MmAttributeDefinitionService::class)->updateDefinition($definition, [
                        'category_id' => $payload['target_category_id'],
                    ]);
                    $result = ['type' => 'updated'];
                }
            } catch (Illuminate\Validation\ValidationException $exception) {
                $result = ['type' => 'validation_error', 'errors' => $exception->errors()];
            } catch (Throwable $exception) {
                $result = ['type' => 'exception', 'message' => $exception->getMessage()];
            }
            echo json_encode($result, JSON_THROW_ON_ERROR).PHP_EOL;
            PHP;
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open([PHP_BINARY, '-r', $worker], $descriptors, $pipes, base_path());
        if (! is_resource($process)) {
            throw new RuntimeException('Unable to start the independent concurrency worker.');
        }

        fwrite($pipes[0], json_encode([
            'connection' => config('database.connections.pgsql'),
            'operation' => $operation,
            'variant_id' => $variantId,
            'target_material_id' => $targetMaterialId,
            'definition_id' => $definitionId,
            'material_id' => $materialId,
            'target_category_id' => $targetCategoryId,
        ], JSON_THROW_ON_ERROR));
        fclose($pipes[0]);
        stream_set_timeout($pipes[1], 10);
        $readyLine = fgets($pipes[1]);
        $ready = $readyLine === false ? [] : json_decode($readyLine, true, flags: JSON_THROW_ON_ERROR);
        if (($ready['type'] ?? null) !== 'ready') {
            $error = stream_get_contents($pipes[2]);
            proc_terminate($process);
            foreach ([1, 2] as $pipe) {
                fclose($pipes[$pipe]);
            }
            proc_close($process);

            if ($error !== '') {
                throw new RuntimeException('Concurrency worker failed to initialize: '.$error);
            }
            throw new RuntimeException('Concurrency worker did not report readiness.');
        }

        return [
            'pipes' => $pipes,
            'process' => $process,
            'backend_pid' => (int) $ready['backend_pid'],
        ];
    }

    private function waitForDatabaseLock(int $backendPid): bool
    {
        $deadline = hrtime(true) + 10_000_000_000;

        do {
            $activity = DB::selectOne(
                'select wait_event_type from pg_stat_activity where pid = ?',
                [$backendPid],
            );
            if (($activity->wait_event_type ?? null) === 'Lock') {
                return true;
            }

            usleep(10_000);
        } while (hrtime(true) < $deadline);

        return false;
    }

    /**
     * @param  array{pipes: array<int, resource>, process: resource, backend_pid: int}  $child
     * @return array<string, mixed>
     */
    private function finishConcurrentOperation(array $child): array
    {
        $line = fgets($child['pipes'][1]);
        $result = $line === false ? [] : json_decode($line, true, flags: JSON_THROW_ON_ERROR);
        $error = stream_get_contents($child['pipes'][2]);
        foreach ([1, 2] as $pipe) {
            fclose($child['pipes'][$pipe]);
        }
        $exitCode = proc_close($child['process']);
        if ($exitCode !== 0) {
            throw new RuntimeException('Concurrency worker exited unexpectedly: '.$error);
        }

        if ($result === []) {
            throw new RuntimeException('Concurrency worker exited unexpectedly.');
        }

        return $result;
    }

    /**
     * @param  array{pipes: array<int, resource>, process: resource, backend_pid: int}  $child
     */
    private function stopConcurrentOperation(array $child): void
    {
        proc_terminate($child['process']);
        foreach ([1, 2] as $pipe) {
            if (is_resource($child['pipes'][$pipe])) {
                fclose($child['pipes'][$pipe]);
            }
        }
        proc_close($child['process']);
    }
}
