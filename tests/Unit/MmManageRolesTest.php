<?php

namespace Tests\Unit;

use App\Services\MmAuthorization;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MmManageRolesTest extends TestCase
{
    private function userWithRole(string $role): object
    {
        return new class($role) {
            public function __construct(private string $role) {}
            public function roles(): object
            {
                return new class($this->role) {
                    private array $allowed = [];
                    public function __construct(private string $role) {}
                    public function whereIn(string $column, array $roles): self
                    {
                        if ($column !== 'code') { throw new \RuntimeException('Unexpected column'); }
                        $this->allowed = $roles;
                        return $this;
                    }
                    public function exists(): bool
                    {
                        return in_array($this->role, $this->allowed, true);
                    }
                };
            }
        };
    }

    public function test_admin_and_procurement_can_manage(): void
    {
        foreach (['ADMIN_BPM', 'PROCUREMENT'] as $role) {
            $user = $this->userWithRole($role);
            $this->assertTrue(MmAuthorization::canManage($user), $role);
            $request = Request::create('/material-master/materials/create');
            $request->setUserResolver(fn () => $user);
            MmAuthorization::authorizeManage($request);
            $this->assertTrue(true);
        }
    }

    public function test_read_only_and_unrelated_roles_cannot_manage(): void
    {
        foreach (['ADMIN_PRODUKSI', 'ASST_MANAGER_PRODUKSI', 'MARKETING', 'DESIGNER'] as $role) {
            $this->assertFalse(MmAuthorization::canManage($this->userWithRole($role)), $role);
        }
        $this->assertFalse(MmAuthorization::canManage(null));
    }

    public function test_read_only_role_gets_http_403(): void
    {
        $request = Request::create('/material-master/materials/create');
        $request->setUserResolver(fn () => $this->userWithRole('ADMIN_PRODUKSI'));
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(0);
        MmAuthorization::authorizeManage($request);
    }
}
