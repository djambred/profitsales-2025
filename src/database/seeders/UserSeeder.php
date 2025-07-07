<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use App\Models\Employee;
use App\Models\Client;
use App\Models\Department;
use App\Models\Position;
use App\Models\Sales;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * NOTE: This seeder assumes you are using spatie/laravel-permission.
     * Ensure your App\Models\User uses the HasRoles trait.
     */
    public function run(): void
    {
        // Get Roles
        $superAdminRole = Role::where('name', 'super_admin')->firstOrFail();
        $salesRole = Role::where('name', 'sales')->firstOrFail();
        $clientRole = Role::where('name', 'client')->firstOrFail();
        $purchasingRole = Role::where('name', 'purchasing')->firstOrFail();
        $financeRole = Role::where('name', 'finance')->firstOrFail();

        // Get Branches
        $laBranch = Branch::where('name', 'Los Angeles HQ')->firstOrFail();
        $nyBranch = Branch::where('name', 'New York Office')->firstOrFail();

        // === LA Branch ===

        // Super Admin
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            ['name' => 'Super Admin', 'password' => Hash::make('password')]
        );
        $adminUser->assignRole($superAdminRole);
        $adminDept = Department::where('branch_id', $laBranch->id)->where('name', 'Technology')->firstOrFail();
        $adminPos = Position::where('department_id', $adminDept->id)->where('name', 'Chief Technology Officer')->firstOrFail();
        Employee::firstOrCreate([
            'user_id' => $adminUser->id,
        ], [
            'branch_id' => $laBranch->id,
            'department_id' => $adminDept->id,
            'position_id' => $adminPos->id,
            'employee_code' => 'EMP-00001'
        ]);

        // LA Sales
        $laSalesUser = User::firstOrCreate(
            ['email' => 'sales.la@admin.com'],
            ['name' => 'Jane Sales (LA)', 'password' => Hash::make('password')]
        );
        $laSalesUser->assignRole($salesRole);
        $laSalesDept = Department::where('branch_id', $laBranch->id)->where('name', 'Sales')->firstOrFail();
        $laSalesPos = Position::where('department_id', $laSalesDept->id)->where('name', 'Sales Representative')->firstOrFail();
        $laEmployee = Employee::firstOrCreate([
            'user_id' => $laSalesUser->id,
        ], [
            'branch_id' => $laBranch->id,
            'department_id' => $laSalesDept->id,
            'position_id' => $laSalesPos->id,
            'employee_code' => 'EMP-00002'
        ]);

        Sales::firstOrCreate([
            'user_id' => $laSalesUser->id,
            'employee_id' => $laEmployee->id,
        ], [
            'department_id' => $laSalesDept->id,
            'position_id' => $laSalesPos->id,
            'phone' => '111-222-3333'
        ]);

        $laSalesUser = User::firstOrCreate(
            ['email' => 'sales1.la@admin.com'],
            ['name' => 'Jane Sales 1 (LA)', 'password' => Hash::make('password')]
        );
        $laSalesUser->assignRole($salesRole);
        $laSalesDept = Department::where('branch_id', $laBranch->id)->where('name', 'Sales')->firstOrFail();
        $laSalesPos = Position::where('department_id', $laSalesDept->id)->where('name', 'Sales Representative')->firstOrFail();
        $laEmployee = Employee::firstOrCreate([
            'user_id' => $laSalesUser->id,
        ], [
            'branch_id' => $laBranch->id,
            'department_id' => $laSalesDept->id,
            'position_id' => $laSalesPos->id,
            'employee_code' => 'EMP-00003'
        ]);

        Sales::firstOrCreate([
            'user_id' => $laSalesUser->id,
            'employee_id' => $laEmployee->id,
        ], [
            'department_id' => $laSalesDept->id,
            'position_id' => $laSalesPos->id,
            'phone' => '111-222-3333'
        ]);

        // === NY Branch ===

        // NY Sales
        $nySalesUser = User::firstOrCreate(
            ['email' => 'sales.ny@admin.com'],
            ['name' => 'Mike Sales (NY)', 'password' => Hash::make('password')]
        );
        $nySalesUser->assignRole($salesRole);
        $nySalesDept = Department::where('branch_id', $nyBranch->id)->where('name', 'Sales')->firstOrFail();
        $nySalesPos = Position::where('department_id', $nySalesDept->id)->where('name', 'Sales Representative')->firstOrFail();
        $nyEmployee = Employee::firstOrCreate([
            'user_id' => $nySalesUser->id,
        ], [
            'branch_id' => $nyBranch->id,
            'department_id' => $nySalesDept->id,
            'position_id' => $nySalesPos->id,
            'employee_code' => 'EMP-00004'
        ]);

        Sales::firstOrCreate([
            'user_id' => $nySalesUser->id,
            'employee_id' => $nyEmployee->id,
        ], [
            'department_id' => $nySalesDept->id,
            'position_id' => $nySalesPos->id,
            'phone' => '222-333-4444'
        ]);

        // NY Finance
        $nyFinanceUser = User::firstOrCreate(
            ['email' => 'finance.ny@admin.com'],
            ['name' => 'Sarah Finance (NY)', 'password' => Hash::make('password')]
        );
        $nyFinanceUser->assignRole($financeRole);
        $nyFinanceDept = Department::where('branch_id', $nyBranch->id)->where('name', 'Finance')->firstOrFail();
        $nyFinancePos = Position::where('department_id', $nyFinanceDept->id)->where('name', 'Financial Analyst')->firstOrFail();
        Employee::firstOrCreate([
            'user_id' => $nyFinanceUser->id,
        ], [
            'branch_id' => $nyBranch->id,
            'department_id' => $nyFinanceDept->id,
            'position_id' => $nyFinancePos->id,
            'employee_code' => 'EMP-00006'
        ]);

        // === Clients ===

        $laClientUser = User::firstOrCreate(['email' => 'client.la@admin.com'], [
            'name' => 'LA Client Inc.',
            'password' => Hash::make('password'),
        ]);
        $laClientUser->assignRole($clientRole);
        Client::firstOrCreate(['user_id' => $laClientUser->id], [
            'branch_id' => $laBranch->id,
            'address' => '789 Customer Lane',
            'state' => 'California',
            'country' => 'USA',
            'postcode' => '90210',
            'contact_person' => 'John Smith',
            'phone' => '987-654-3210',
            'code' => 'CLT - LA - 001',
        ]);
        $laClientUser = User::firstOrCreate(['email' => 'client1.la@admin.com'], [
            'name' => 'LA Client 1 Inc.',
            'password' => Hash::make('password'),
        ]);
        $laClientUser->assignRole($clientRole);
        Client::firstOrCreate(['user_id' => $laClientUser->id], [
            'branch_id' => $laBranch->id,
            'address' => '789 Customer Lane',
            'state' => 'California',
            'country' => 'USA',
            'postcode' => '90210',
            'contact_person' => 'John Smith',
            'phone' => '987-654-3210',
            'code' => 'CLT - LA - 002',
        ]);

        $nyClientUser = User::firstOrCreate(['email' => 'client.ny@admin.com'], [
            'name' => 'NY Client Co.',
            'password' => Hash::make('password'),
        ]);
        $nyClientUser->assignRole($clientRole);
        Client::firstOrCreate(['user_id' => $nyClientUser->id], [
            'branch_id' => $nyBranch->id,
            'address' => '321 Buyer Blvd',
            'state' => 'New York',
            'country' => 'USA',
            'postcode' => '10001',
            'contact_person' => 'Maria Garcia',
            'phone' => '917-555-0123',
            'code' => 'CLT - NY - 001',
        ]);
    }
}
