<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. NAS (Network Access Servers / MikroTik Routers)
        if (!Schema::hasTable('nas')) {
            Schema::create('nas', function (Blueprint $table) {
                $table->id();
                $table->string('nasname', 128)->index();
                $table->string('shortname', 32)->nullable();
                $table->string('type', 30)->default('other');
                $table->integer('ports')->nullable();
                $table->string('secret', 60)->default('secret');
                $table->string('server', 64)->nullable();
                $table->string('community', 50)->nullable();
                $table->string('description', 200)->nullable();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->timestamps();
            });
        }

        // 2. radcheck (Subscriber Authentication Credentials)
        if (!Schema::hasTable('radcheck')) {
            Schema::create('radcheck', function (Blueprint $table) {
                $table->increments('id');
                $table->string('username', 64)->default('')->index();
                $table->string('attribute', 64)->default('Cleartext-Password');
                $table->char('op', 2)->default(':=');
                $table->string('value', 253)->default('');
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            });
        }

        // 3. radreply (Subscriber Specific Attributes / Static IP)
        if (!Schema::hasTable('radreply')) {
            Schema::create('radreply', function (Blueprint $table) {
                $table->increments('id');
                $table->string('username', 64)->default('')->index();
                $table->string('attribute', 64)->default('');
                $table->char('op', 2)->default('=');
                $table->string('value', 253)->default('');
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            });
        }

        // 4. radgroupcheck (Package Group Policies e.g. Simultaneous-Use)
        if (!Schema::hasTable('radgroupcheck')) {
            Schema::create('radgroupcheck', function (Blueprint $table) {
                $table->increments('id');
                $table->string('groupname', 64)->default('')->index();
                $table->string('attribute', 64)->default('');
                $table->char('op', 2)->default(':=');
                $table->string('value', 253)->default('');
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            });
        }

        // 5. radgroupreply (Package Rate Limits & IP Pools)
        if (!Schema::hasTable('radgroupreply')) {
            Schema::create('radgroupreply', function (Blueprint $table) {
                $table->increments('id');
                $table->string('groupname', 64)->default('')->index();
                $table->string('attribute', 64)->default('');
                $table->char('op', 2)->default('=');
                $table->string('value', 253)->default('');
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            });
        }

        // 6. radusergroup (Subscriber to Package Group Mapping)
        if (!Schema::hasTable('radusergroup')) {
            Schema::create('radusergroup', function (Blueprint $table) {
                $table->increments('id');
                $table->string('username', 64)->default('')->index();
                $table->string('groupname', 64)->default('');
                $table->integer('priority')->default(1);
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            });
        }

        // 7. radacct (Live Accounting & Traffic Sessions)
        if (!Schema::hasTable('radacct')) {
            Schema::create('radacct', function (Blueprint $table) {
                $table->bigIncrements('radacctid');
                $table->string('acctsessionid', 64)->default('')->index();
                $table->string('acctuniqueid', 64)->default('')->unique();
                $table->string('username', 64)->default('')->index();
                $table->string('groupname', 64)->default('');
                $table->string('realm', 64)->default('');
                $table->string('nasipaddress', 45)->default('')->index();
                $table->string('nasportid', 32)->nullable();
                $table->string('nasporttype', 32)->nullable();
                $table->dateTime('acctstarttime')->nullable()->index();
                $table->dateTime('acctupdatetime')->nullable();
                $table->dateTime('acctstoptime')->nullable()->index();
                $table->integer('acctinterval')->nullable();
                $table->unsignedInteger('acctsessiontime')->nullable();
                $table->string('acctauthentic', 32)->nullable();
                $table->string('connectinfo_start', 50)->nullable();
                $table->string('connectinfo_stop', 50)->nullable();
                $table->bigInteger('acctinputoctets')->nullable();
                $table->bigInteger('acctoutputoctets')->nullable();
                $table->string('calledstationid', 50)->default('');
                $table->string('callingstationid', 50)->default('')->index(); // MAC Address
                $table->string('acctterminatecause', 32)->default('');
                $table->string('servicetype', 32)->nullable();
                $table->string('framedprotocol', 32)->nullable();
                $table->string('framedipaddress', 45)->default('')->index();
                $table->string('framedipv6address', 45)->default('');
                $table->string('framedipv6prefix', 45)->default('');
                $table->string('framedinterfaceid', 44)->default('');
                $table->string('delegatedipv6prefix', 45)->default('');
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            });
        }

        // 8. radpostauth (Authentication Attempts Log)
        if (!Schema::hasTable('radpostauth')) {
            Schema::create('radpostauth', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('username', 64)->default('')->index();
                $table->string('pass', 64)->default('');
                $table->string('reply', 32)->default('');
                $table->timestamp('authdate')->useCurrent();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radpostauth');
        Schema::dropIfExists('radacct');
        Schema::dropIfExists('radusergroup');
        Schema::dropIfExists('radgroupreply');
        Schema::dropIfExists('radgroupcheck');
        Schema::dropIfExists('radreply');
        Schema::dropIfExists('radcheck');
        Schema::dropIfExists('nas');
    }
};
