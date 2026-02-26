<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Province;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $country = Country::updateOrCreate(
            ['id' => 507511636180],
            [
                'name' => 'Mexico',
                'shopify_code' => 'MX',
                'fando_code' => 'MEX',
                'tax_name' => 'VAT',
                'tax' => 0.16,
            ]
        );

        $provinces = [
            ['id' => 5304507334868, 'name' => 'Aguascalientes', 'shopify_code' => 'AGS', 'fando_code' => 'AGU', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507367636, 'name' => 'Baja California', 'shopify_code' => 'BC', 'fando_code' => 'BCN', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507400404, 'name' => 'Baja California Sur', 'shopify_code' => 'BCS', 'fando_code' => 'BCS', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507433172, 'name' => 'Campeche', 'shopify_code' => 'CAMP', 'fando_code' => 'CAM', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507465940, 'name' => 'Chiapas', 'shopify_code' => 'CHIS', 'fando_code' => 'CHP', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507498708, 'name' => 'Chihuahua', 'shopify_code' => 'CHIH', 'fando_code' => 'CHH', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507531476, 'name' => 'Ciudad de México', 'shopify_code' => 'DF', 'fando_code' => 'MEX', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507564244, 'name' => 'Coahuila', 'shopify_code' => 'COAH', 'fando_code' => 'COA', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507597012, 'name' => 'Colima', 'shopify_code' => 'COL', 'fando_code' => 'COL', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507629780, 'name' => 'Durango', 'shopify_code' => 'DGO', 'fando_code' => 'DUR', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507662548, 'name' => 'Guanajuato', 'shopify_code' => 'GTO', 'fando_code' => 'GUA', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507695316, 'name' => 'Guerrero', 'shopify_code' => 'GRO', 'fando_code' => 'GRO', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507728084, 'name' => 'Hidalgo', 'shopify_code' => 'HGO', 'fando_code' => 'HID', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507760852, 'name' => 'Jalisco', 'shopify_code' => 'JAL', 'fando_code' => 'JAL', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507793620, 'name' => 'México', 'shopify_code' => 'MEX', 'fando_code' => 'CMX', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507826388, 'name' => 'Michoacán', 'shopify_code' => 'MICH', 'fando_code' => 'MIC', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507859156, 'name' => 'Morelos', 'shopify_code' => 'MOR', 'fando_code' => 'MOR', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507891924, 'name' => 'Nayarit', 'shopify_code' => 'NAY', 'fando_code' => 'NAY', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507924692, 'name' => 'Nuevo León', 'shopify_code' => 'NL', 'fando_code' => 'NLE', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507957460, 'name' => 'Oaxaca', 'shopify_code' => 'OAX', 'fando_code' => 'OAX', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304507990228, 'name' => 'Puebla', 'shopify_code' => 'PUE', 'fando_code' => 'PUE', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304508022996, 'name' => 'Querétaro', 'shopify_code' => 'QRO', 'fando_code' => 'QUE', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304508055764, 'name' => 'Quintana Roo', 'shopify_code' => 'Q ROO', 'fando_code' => 'ROO', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304508088532, 'name' => 'San Luis Potosí', 'shopify_code' => 'SLP', 'fando_code' => 'SLP', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304508121300, 'name' => 'Sinaloa', 'shopify_code' => 'SIN', 'fando_code' => 'SIN', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304508154068, 'name' => 'Sonora', 'shopify_code' => 'SON', 'fando_code' => 'SON', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304508186836, 'name' => 'Tabasco', 'shopify_code' => 'TAB', 'fando_code' => 'TAB', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304508219604, 'name' => 'Tamaulipas', 'shopify_code' => 'TAMPS', 'fando_code' => 'TAM', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304508252372, 'name' => 'Tlaxcala', 'shopify_code' => 'TLAX', 'fando_code' => 'TLA', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304508285140, 'name' => 'Veracruz', 'shopify_code' => 'VER', 'fando_code' => 'VER', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304508317908, 'name' => 'Yucatán', 'shopify_code' => 'YUC', 'fando_code' => 'YUC', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
            ['id' => 5304508350676, 'name' => 'Zacatecas', 'shopify_code' => 'ZAC', 'fando_code' => 'ZAC', 'tax_name' => 'State Tax', 'tax_type' => 'harmonized', 'tax' => 0.16, 'tax_percentage' => 16.0],
        ];

        foreach ($provinces as $province) {
            Province::updateOrCreate(
                ['id' => $province['id']],
                array_merge($province, ['country_id' => $country->id])
            );
        }
    }
}
