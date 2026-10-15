<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\League;
use App\Models\Role;
use App\Models\User;
use http\Env\Request;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        $role=new Role();
        $role->name="user";
        $role->display_name = 'user';
        $role->save();
        $role=new Role();
        $role->name="admin";
        $role->display_name = 'admin';
        $role->save();

        $user = new User();
        $user->name = "reza";
        $user->family = "admin";
        $user->mobile = "09133934677";
        $user->save();
        $role = Role::where("name", "admin")->first();
        $user->attachRole($role);


    }


}
