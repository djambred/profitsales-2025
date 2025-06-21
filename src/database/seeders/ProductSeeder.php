<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::firstOrCreate(
            ['sku' => 'MED-PARA-500'],
            [
                'name' => 'Paracetamol 500mg',
                'description' => 'For fever and mild pain relief.',
                'price' => 1.50,
                'cost' => 0.50,
                'stock' => 1000,
            ]
        );

        Product::firstOrCreate(
            ['sku' => 'MED-IBUP-200'],
            [
                'name' => 'Ibuprofen 200mg',
                'description' => 'Anti-inflammatory drug for pain relief.',
                'price' => 2.50,
                'cost' => 0.75,
                'stock' => 800,
            ]
        );

        Product::firstOrCreate(
            ['sku' => 'MED-AMOX-250'],
            [
                'name' => 'Amoxicillin 250mg',
                'description' => 'Antibiotic for bacterial infections.',
                'price' => 5.00,
                'cost' => 1.20,
                'stock' => 500,
            ]
        );

        Product::firstOrCreate(
            ['sku' => 'MED-LORAT-10'],
            [
                'name' => 'Loratadine 10mg',
                'description' => 'Antihistamine for allergies.',
                'price' => 3.00,
                'cost' => 0.90,
                'stock' => 750,
            ]
        );
    }
}
