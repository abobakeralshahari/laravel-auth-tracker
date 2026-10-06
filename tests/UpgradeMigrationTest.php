<?php

namespace Awsan\AuthTracker\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpgradeMigrationTest extends TestCase
{
    public function test_duplicated_devices_are_merged_and_the_identifier_becomes_unique(): void
    {
        // Back to the v1 schema.
        $this->artisan('migrate:rollback', ['--step' => 3])->run();

        $this->assertFalse(Schema::hasColumn('devices', 'app_type'));
        $this->assertTrue(Schema::hasColumn('logins', 'device'));

        $keep = DB::table('devices')->insertGetId(['udid' => 'DUP', 'os' => 'ios', 'created_at' => now()]);
        $dupe = DB::table('devices')->insertGetId(['udid' => 'DUP', 'os' => 'ios', 'created_at' => now()]);

        DB::table('logins')->insert([
            'authenticatable_type' => User::class, 'authenticatable_id' => 1,
            'device_id' => $dupe, 'device' => 'iPhone', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('migrate')->run();

        $this->assertSame(1, DB::table('devices')->where('udid', 'DUP')->count());
        $this->assertSame($keep, DB::table('logins')->value('device_id'));
        $this->assertSame('iPhone', DB::table('logins')->value('device_name'));
        $this->assertTrue(Schema::hasColumn('devices', 'app_type'));
        $this->assertContains('devices_udid_unique', Schema::getIndexListing('devices'));
    }
}
