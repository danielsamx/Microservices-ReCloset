<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('categories')->count() === 0) {
            DB::table('categories')->insert([
                ['name'=>'Camisetas','slug'=>'camisetas'],
                ['name'=>'Pantalones','slug'=>'pantalones'],
                ['name'=>'Vestidos','slug'=>'vestidos'],
                ['name'=>'Abrigos','slug'=>'abrigos'],
                ['name'=>'Zapatos','slug'=>'zapatos'],
                ['name'=>'Accesorios','slug'=>'accesorios'],
            ]);
        }
        if (DB::table('sizes')->count() === 0) {
            DB::table('sizes')->insert([
                ['label'=>'XS','position'=>1],['label'=>'S','position'=>2],
                ['label'=>'M','position'=>3],['label'=>'L','position'=>4],
                ['label'=>'XL','position'=>5],['label'=>'XXL','position'=>6],
            ]);
        }
        if (DB::table('colors')->count() === 0) {
            DB::table('colors')->insert([
                ['name'=>'Negro','hex'=>'#000000'],['name'=>'Blanco','hex'=>'#FFFFFF'],
                ['name'=>'Rojo','hex'=>'#E11D48'],['name'=>'Azul','hex'=>'#2563EB'],
                ['name'=>'Verde','hex'=>'#16A34A'],['name'=>'Beige','hex'=>'#D6C6A5'],
                ['name'=>'Gris','hex'=>'#6B7280'],
            ]);
        }
    }
}
