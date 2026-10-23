<?php

namespace Config;

use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function activeTenantResolver(bool $getShared = true): \App\Modules\Tenancy\Services\ActiveTenantResolver
    {
        if ($getShared) {
            return static::getSharedInstance('activeTenantResolver');
        }

        return new \App\Modules\Tenancy\Services\ActiveTenantResolver(db_connect(), service('session'));
    }

    public static function adminAuditService(bool $getShared = true): \App\Modules\Administration\Services\AdminAuditService
    {
        if ($getShared) {
            return static::getSharedInstance('adminAuditService');
        }

        return new \App\Modules\Administration\Services\AdminAuditService(
            new \App\Modules\Administration\Repositories\AdminAuditRepository(db_connect()),
        );
    }

    public static function financialEntryService(bool $getShared = true): \App\Modules\Finance\Services\FinancialEntryService
    {
        if ($getShared) {
            return static::getSharedInstance('financialEntryService');
        }

        return new \App\Modules\Finance\Services\FinancialEntryService(
            new \App\Modules\Finance\Repositories\FinancialEntryRepository(db_connect()),
        );
    }

    public static function preorderService(bool $getShared = true): \App\Modules\Preorders\Services\PreorderService
    {
        if ($getShared) {
            return static::getSharedInstance('preorderService');
        }

        $connection = db_connect();

        return new \App\Modules\Preorders\Services\PreorderService(
            new \App\Modules\Preorders\Repositories\PreorderRepository($connection),
            new \App\Infrastructure\Database\TransactionManager($connection),
        );
    }

    public static function exploreService(bool $getShared = true): \App\Modules\Explore\Services\ExploreService
    {
        if ($getShared) {
            return static::getSharedInstance('exploreService');
        }

        return new \App\Modules\Explore\Services\ExploreService(
            new \App\Modules\Explore\Repositories\ExploreRepository(db_connect()),
        );
    }

    /*
     * public static function example($getShared = true)
     * {
     *     if ($getShared) {
     *         return static::getSharedInstance('example');
     *     }
     *
     *     return new \CodeIgniter\Example();
     * }
     */
}
