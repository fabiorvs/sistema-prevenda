<?php

use App\Filters\SuperAdminFilter;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Filters;

/**
 * @internal
 */
final class AdministrationRoutingTest extends CIUnitTestCase
{
    public function testAdministrationUsesExclusiveSuperAdminFilter(): void
    {
        $filters = new Filters();
        $this->assertSame(SuperAdminFilter::class, $filters->aliases['superadmin']);

        $routes = (string) file_get_contents(APPPATH . 'Config/Routes.php');
        $this->assertStringContainsString("\$routes->group('admin', ['filter' => ['session', 'superadmin']]", $routes);
        $this->assertStringNotContainsString("\$routes->group('admin', ['filter' => ['session', 'tenant']]", $routes);
    }
}
