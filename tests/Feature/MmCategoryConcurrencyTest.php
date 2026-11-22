<?php

namespace Tests\Feature;

use App\Models\MmCategory;
use App\Services\MmMasterDataService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class MmCategoryConcurrencyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_category_codes_are_generated_without_parent_relationships(): void
    {
        $service = app(MmMasterDataService::class);
        $first = $service->createCategory(['name' => 'Generated Category One']);
        $second = $service->createCategory(['name' => 'Generated Category Two']);

        $this->assertMatchesRegularExpression('/\\ACAT-\\d{3,}\\z/', $first->code);
        $this->assertMatchesRegularExpression('/\\ACAT-\\d{3,}\\z/', $second->code);
        $this->assertNotSame($first->code, $second->code);
        $this->assertNull($first->parent_id);
        $this->assertNull($second->parent_id);
    }

    /**
     * @return array{first_category_id: int, second_category_id: int}
     */
    private function createCategoryPair(): array
    {
        $suffix = Str::upper(substr(str_replace('-', '', (string) Str::uuid()), 0, 12));
        $firstCategory = MmCategory::query()->create([
            'code' => 'CAT-CC-A-'.$suffix,
            'name' => 'Category Concurrency A '.$suffix,
        ]);
        $secondCategory = MmCategory::query()->create([
            'code' => 'CAT-CC-B-'.$suffix,
            'name' => 'Category Concurrency B '.$suffix,
        ]);

        return [
            'first_category_id' => (int) $firstCategory->id,
            'second_category_id' => (int) $secondCategory->id,
        ];
    }

    /**
     * @param  array{first_category_id: int, second_category_id: int}  $fixture
     */
    private function deleteCategoryPair(array $fixture): void
    {
        DB::table('mm_categories')
            ->whereIn('id', [$fixture['first_category_id'], $fixture['second_category_id']])
            ->update(['parent_id' => null]);
        DB::table('mm_categories')
            ->whereIn('id', [$fixture['first_category_id'], $fixture['second_category_id']])
            ->delete();
    }

    /**
     * @return array{pipes: array<int, resource>, process: resource, backend_pid: int}
     */
    private function startConcurrentParentUpdate(int $categoryId, int $parentId): array
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
                app(App\Services\MmMasterDataService::class)->updateCategory(
                    $payload['category_id'],
                    ['parent_id' => $payload['parent_id']],
                );
                $result = ['type' => 'updated'];
            } catch (Illuminate\Validation\ValidationException $exception) {
                $result = ['type' => 'validation_error', 'errors' => $exception->errors()];
            } catch (Throwable $exception) {
                $result = ['type' => 'exception', 'class' => get_class($exception)];
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
            throw new RuntimeException('Unable to start the independent category worker.');
        }

        fwrite($pipes[0], json_encode([
            'connection' => config('database.connections.pgsql'),
            'category_id' => $categoryId,
            'parent_id' => $parentId,
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

            throw new RuntimeException('Category worker failed to initialize: '.$error);
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
    private function finishConcurrentParentUpdate(array $child): array
    {
        $line = fgets($child['pipes'][1]);
        $result = $line === false ? [] : json_decode($line, true, flags: JSON_THROW_ON_ERROR);
        $error = stream_get_contents($child['pipes'][2]);
        foreach ([1, 2] as $pipe) {
            fclose($child['pipes'][$pipe]);
        }
        $exitCode = proc_close($child['process']);
        if ($exitCode !== 0) {
            throw new RuntimeException('Category worker exited unexpectedly: '.$error);
        }

        if ($result === []) {
            throw new RuntimeException('Category worker exited without a result.');
        }

        return $result;
    }

    /**
     * @param  array{pipes: array<int, resource>, process: resource, backend_pid: int}  $child
     */
    private function stopConcurrentParentUpdate(array $child): void
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
