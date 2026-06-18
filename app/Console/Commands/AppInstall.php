<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:app-install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign User with #id 1 super_user Role & associate him to Main Team & Create & Assign Basic Permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $role = Role::find(1);

        $user = User::where('id', 1)->first();

        // assign super_user role to $user
        $user->assignRole($role);

        // create Main Team
        $team = new Team;
        $team->name = 'Main';
        $team->save();

        // assign $user to main Team
        $user->team_id = $team->id;
        $user->email_verified_at = now();
        $user->save();

        // create Teams permissions
        Permission::create(['name' => 'view_any_team']);
        Permission::create(['name' => 'view_team']);
        Permission::create(['name' => 'create_team']);
        Permission::create(['name' => 'update_team']);
        Permission::create(['name' => 'delete_team']);

        // assign Teams permissions
        $role->givePermissionTo('view_any_team');
        $role->givePermissionTo('view_team');
        $role->givePermissionTo('create_team');
        $role->givePermissionTo('update_team');
        $role->givePermissionTo('delete_team');

        // create Tenants permissions
        Permission::create(['name' => 'view_any_tenant']);
        Permission::create(['name' => 'view_tenant']);
        Permission::create(['name' => 'create_tenant']);
        Permission::create(['name' => 'update_tenant']);
        Permission::create(['name' => 'delete_tenant']);

        // assign Tenants permissions
        $role->givePermissionTo('view_any_tenant');
        $role->givePermissionTo('view_tenant');
        $role->givePermissionTo('create_tenant');
        $role->givePermissionTo('update_tenant');
        $role->givePermissionTo('delete_tenant');

        $this->info('The command was successful!');

    }
}
