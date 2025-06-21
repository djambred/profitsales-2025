<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['name' => 'Nexus Innovations Inc.'],
            ['address' => '123 Tech Avenue', 'state' => 'California', 'country' => 'USA', 'postcode' => '90210']
        );

        // --- Create Branches ---
        Branch::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Los Angeles HQ'],
            ['address' => '456 Innovation Drive', 'state' => 'California', 'country' => 'USA', 'postcode' => '90210', 'phone' => '123-456-7890']
        );
        Branch::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'New York Office'],
            ['address' => '789 Commerce St', 'state' => 'New York', 'country' => 'USA', 'postcode' => '10001', 'phone' => '212-555-0199']
        );

        // --- Define the standard company structure ---
        $structure = [
            'Technology' => ['Chief Technology Officer', 'Software Engineer'],
            'Sales' => ['Sales Manager', 'Sales Representative'],
            'Finance' => ['Finance Manager', 'Accountant', 'Financial Analyst'],
            'Purchasing' => ['Purchasing Manager', 'Buyer'],
        ];

        // --- Apply the structure to all branches ---
        $branches = Branch::all();
        foreach ($branches as $branch) {
            foreach ($structure as $deptName => $positions) {
                $department = Department::firstOrCreate([
                    'branch_id' => $branch->id,
                    'name' => $deptName
                ]);

                foreach ($positions as $posName) {
                    Position::firstOrCreate([
                        'department_id' => $department->id,
                        'name' => $posName
                    ]);
                }
            }
        }
    }
}
