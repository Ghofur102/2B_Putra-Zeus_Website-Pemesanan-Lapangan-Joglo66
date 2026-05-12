<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Field;

class FieldSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');
        $fields = Field::factory(2)->create();

        $managers = User::where('role', 'manager')->get();
        $workers = User::where('role', 'worker')->get();
        $fieldAdmins = $managers->concat($workers);

        foreach ($fields as $field) {
            $this->seedFieldAdmins($field->id, $fieldAdmins);
            $this->seedFieldPrices($field->id, $faker);
            $this->seedFieldAttributes($field->id, $faker);
            $this->seedFinancialReports($field->id, $faker);
        }
    }

    private function seedFieldAdmins(int $fieldId, $admins): void
    {
        foreach ($admins as $admin) {
            DB::table('field_admins')->insert([
                'fk_user_id' => $admin->id,
                'fk_field_id' => $fieldId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedFieldPrices(int $fieldId, $faker): void
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($days as $day) {
            DB::table('field_prices')->insert([
                'fk_field_id' => $fieldId,
                'start_time' => '08:00:00',
                'end_time' => '21:00:00',
                'day_type' => $day,
                'price' => $faker->randomElement([100000, 150000, 200000]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedFieldAttributes(int $fieldId, $faker): void
    {
        for ($i = 0; $i < 3; $i++) {
            DB::table('attributes')->insert([
                'fk_field_id' => $fieldId,
                'name' => $faker->randomElement(['Bola Futsal', 'Sepatu', 'Rompi', 'Sarung Tangan']),
                'stock' => $faker->numberBetween(5, 20),
                'price_hour' => $faker->randomElement([15000, 20000, 35000]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedFinancialReports(int $fieldId, $faker): void
    {
        $months = ['january', 'february', 'march', 'april', 'may', 'june'];

        DB::table('financial_reports')->insert([
            'fk_field_id' => $fieldId,
            'year' => date('Y'),
            'month' => $faker->randomElement($months),
            'total_income' => $faker->numberBetween(5000000, 15000000),
            'total_expense' => $faker->numberBetween(1000000, 5000000),
            'net_profit' => $faker->numberBetween(4000000, 10000000),
            'generate_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
