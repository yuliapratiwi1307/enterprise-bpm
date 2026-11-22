<?php

namespace Tests\Feature;

use App\Models\BpmRole;
use App\Models\MmCategory;
use App\Models\MmMaterial;
use App\Models\MmMaterialVariant;
use App\Models\MmSupplier;
use App\Models\MmSupplierQuote;
use App\Models\MmUnit;
use App\Models\User;
use App\Services\MmSupplierQuoteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class MmSupplierQuoteConcurrencyTest extends TestCase
{
    public function test_concurrent_deactivation_records_one_consistent_event(): void
    {
        $fixture = $this->createFixture();
        $connection = DB::connection('pgsql');
        $child = null;
        $transactionOpen = false;

        try {
            $connection->beginTransaction();
            $transactionOpen = true;
            $quote = MmSupplierQuote::query()
                ->whereKey($fixture['quote_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $actor = User::query()->findOrFail($fixture['second_approver_id']);
            $child = $this->startConcurrentApproval(
                $fixture['quote_id'],
                $fixture['second_approver_id'],
                'deactivate',
            );
            $blocked = $this->waitForDatabaseLock($child['backend_pid']);

            app(MmSupplierQuoteService::class)->setQuoteActive($quote, false, $actor);
            $connection->commit();
            $transactionOpen = false;
            $result = $this->finishConcurrentApproval($child);
            $child = null;

            $this->assertTrue($blocked, 'The competing deactivation must wait on the quote row lock.');
            $this->assertSame('active_updated', $result['type']);
            $this->assertFalse($result['is_active']);
            $this->assertDatabaseHas('mm_supplier_quotes', [
                'id' => $fixture['quote_id'],
                'is_active' => false,
            ]);
            $this->assertDatabaseCount('mm_supplier_quote_approval_events', 1);
            $this->assertDatabaseHas('mm_supplier_quote_approval_events', [
                'quote_id' => $fixture['quote_id'],
                'event_type' => 'DEACTIVATED',
                'actor_id' => $fixture['second_approver_id'],
                'previous_is_active' => true,
                'new_is_active' => false,
            ]);
        } finally {
            if ($transactionOpen) {
                $connection->rollBack();
            }
            if ($child !== null) {
                $this->stopConcurrentApproval($child);
            }
            $this->deleteFixture($fixture);
        }
    }

    public function test_a_concurrent_second_approval_waits_and_cannot_overwrite_the_committed_decision(): void
    {
        $fixture = $this->createFixture();
        $connection = DB::connection('pgsql');
        $child = null;
        $transactionOpen = false;

        try {
            $connection->beginTransaction();
            $transactionOpen = true;
            $quote = MmSupplierQuote::query()
                ->whereKey($fixture['quote_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $child = $this->startConcurrentApproval($fixture['quote_id'], $fixture['second_approver_id']);
            $blocked = $this->waitForDatabaseLock($child['backend_pid']);
            app(MmSupplierQuoteService::class)->approveQuote(
                $quote,
                User::query()->findOrFail($fixture['first_approver_id']),
            );
            $connection->commit();
            $transactionOpen = false;
            $result = $this->finishConcurrentApproval($child);
            $child = null;

            $this->assertTrue($blocked, 'The competing approval must wait on the quote row lock.');
            $this->assertSame('validation_error', $result['type']);
            $this->assertSame(
                'Penawaran ini sudah diputuskan oleh pengguna lain.',
                $result['errors']['quote'][0],
            );
            $this->assertDatabaseHas('mm_supplier_quotes', [
                'id' => $fixture['quote_id'],
                'approval_status' => MmSupplierQuote::STATUS_APPROVED,
                'approved_by' => $fixture['first_approver_id'],
            ]);
            $this->assertDatabaseCount('mm_supplier_quote_approval_events', 1);
        } finally {
            if ($transactionOpen) {
                $connection->rollBack();
            }
            if ($child !== null) {
                $this->stopConcurrentApproval($child);
            }
            $this->deleteFixture($fixture);
        }
    }

    /**
     * @return array{category_id: int, unit_id: int, material_id: int, variant_id: int, supplier_id: int, quote_id: int, creator_id: int, first_approver_id: int, second_approver_id: int}
     */
    private function createFixture(): array
    {
        $suffix = Str::upper(substr(str_replace('-', '', (string) Str::uuid()), 0, 12));
        $category = MmCategory::query()->create([
            'code' => 'CAT-QC-'.$suffix,
            'name' => 'Quote Concurrency Category '.$suffix,
        ]);
        $unit = MmUnit::query()->create([
            'code' => 'UNIT-QC-'.$suffix,
            'name' => 'Quote Concurrency Unit '.$suffix,
            'dimension' => 'count',
        ]);
        $material = MmMaterial::query()->create([
            'category_id' => $category->id,
            'code' => 'MAT-QC-'.$suffix,
            'name' => 'Quote Concurrency Material '.$suffix,
        ]);
        $variant = MmMaterialVariant::query()->create([
            'material_id' => $material->id,
            'sku' => 'SKU-QC-'.$suffix,
            'variant_name' => 'Quote Concurrency Variant '.$suffix,
            'base_unit_id' => $unit->id,
        ]);
        $supplier = MmSupplier::query()->create([
            'code' => 'SUP-QC-'.$suffix,
            'name' => 'Quote Concurrency Supplier '.$suffix,
        ]);
        $creator = $this->createUserWithRole('ADMIN_PRODUKSI', $suffix.'-creator');
        $quote = MmSupplierQuote::query()->create([
            'material_variant_id' => $variant->id,
            'supplier_id' => $supplier->id,
            'price_unit_id' => $unit->id,
            'amount' => '100.0000',
            'currency' => 'IDR',
            'quoted_on' => today()->toDateString(),
            'recorded_by' => $creator->id,
        ]);
        $firstApprover = $this->createUserWithRole('ADMIN_BPM', $suffix.'-first');
        $secondApprover = $this->createUserWithRole('PROCUREMENT', $suffix.'-second');

        return [
            'category_id' => (int) $category->id,
            'unit_id' => (int) $unit->id,
            'material_id' => (int) $material->id,
            'variant_id' => (int) $variant->id,
            'supplier_id' => (int) $supplier->id,
            'quote_id' => (int) $quote->id,
            'creator_id' => (int) $creator->id,
            'first_approver_id' => (int) $firstApprover->id,
            'second_approver_id' => (int) $secondApprover->id,
        ];
    }

    private function createUserWithRole(string $roleCode, string $suffix): User
    {
        $user = User::query()->create([
            'name' => $roleCode.' Quote Concurrency '.$suffix,
            'email' => Str::lower($suffix).'@quote-concurrency.test',
            'password' => Hash::make('test-password'),
        ]);
        $role = BpmRole::query()->firstOrCreate(
            ['code' => $roleCode],
            ['name' => str_replace('_', ' ', $roleCode)],
        );
        $user->roles()->attach($role);

        return $user;
    }

    /**
     * @param  array{category_id: int, unit_id: int, material_id: int, variant_id: int, supplier_id: int, quote_id: int, creator_id: int, first_approver_id: int, second_approver_id: int}  $fixture
     */
    private function deleteFixture(array $fixture): void
    {
        DB::table('mm_supplier_quote_approval_events')->where('quote_id', $fixture['quote_id'])->delete();
        DB::table('mm_supplier_quotes')->where('id', $fixture['quote_id'])->delete();
        DB::table('mm_suppliers')->where('id', $fixture['supplier_id'])->delete();
        DB::table('mm_material_variants')->where('id', $fixture['variant_id'])->delete();
        DB::table('mm_materials')->where('id', $fixture['material_id'])->delete();
        DB::table('mm_categories')->where('id', $fixture['category_id'])->delete();
        DB::table('mm_units')->where('id', $fixture['unit_id'])->delete();

        $userIds = [$fixture['creator_id'], $fixture['first_approver_id'], $fixture['second_approver_id']];
        DB::table('bpm_role_user')->whereIn('user_id', $userIds)->delete();
        DB::table('users')->whereIn('id', $userIds)->delete();
    }

    /**
     * @return array{pipes: array<int, resource>, process: resource, backend_pid: int}
     */
    private function startConcurrentApproval(int $quoteId, int $actorId, string $action = 'approve'): array
    {
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
                $quote = App\Models\MmSupplierQuote::query()->findOrFail($payload['quote_id']);
                $actor = App\Models\User::query()->findOrFail($payload['actor_id']);
                if ($payload['action'] === 'deactivate') {
                    $updatedQuote = app(App\Services\MmSupplierQuoteService::class)
                        ->setQuoteActive($quote, false, $actor);
                    $result = ['type' => 'active_updated', 'is_active' => $updatedQuote->is_active];
                } else {
                    app(App\Services\MmSupplierQuoteService::class)->approveQuote($quote, $actor);
                    $result = ['type' => 'approved'];
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
            throw new RuntimeException('Unable to start the independent approval worker.');
        }

        fwrite($pipes[0], json_encode([
            'connection' => config('database.connections.pgsql'),
            'quote_id' => $quoteId,
            'actor_id' => $actorId,
            'action' => $action,
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

            throw new RuntimeException('Approval worker failed to initialize: '.$error);
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
    private function finishConcurrentApproval(array $child): array
    {
        $line = fgets($child['pipes'][1]);
        $result = $line === false ? [] : json_decode($line, true, flags: JSON_THROW_ON_ERROR);
        $error = stream_get_contents($child['pipes'][2]);
        foreach ([1, 2] as $pipe) {
            fclose($child['pipes'][$pipe]);
        }
        $exitCode = proc_close($child['process']);
        if ($exitCode !== 0) {
            throw new RuntimeException('Approval worker exited unexpectedly: '.$error);
        }

        if ($result === []) {
            throw new RuntimeException('Approval worker exited without a result.');
        }

        return $result;
    }

    /**
     * @param  array{pipes: array<int, resource>, process: resource, backend_pid: int}  $child
     */
    private function stopConcurrentApproval(array $child): void
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
