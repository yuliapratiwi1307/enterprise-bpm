<?php

namespace Tests\Feature;

use Tests\TestCase;

class MmMaterialPriceWorkflowTest extends TestCase
{
    public function test_guest_is_redirected_to_login_from_material_prices(): void
    {
        $this->get(route('mm05.prices.index'))->assertRedirect(route('login'));
    }
}
