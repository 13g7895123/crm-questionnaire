<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RmSmelterSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'smelter_id'    => 'CID000001',
                'smelter_name'  => 'Jiangxi Copper Co., Ltd.',
                'metal_type'    => 'Gold',
                'country'       => 'China',
                'facility_type' => 'Refiner',
                'rmi_conformant' => 1,
                'last_updated'  => date('Y-m-d H:i:s'),
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'smelter_id'    => 'CID000002',
                'smelter_name'  => 'Mizuho Precision Co., Ltd.',
                'metal_type'    => 'Tin',
                'country'       => 'Japan',
                'facility_type' => 'Smelter',
                'rmi_conformant' => 1,
                'last_updated'  => date('Y-m-d H:i:s'),
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'smelter_id'    => 'CID000003',
                'smelter_name'  => 'TANAKA Kikinzoku Kogyo K.K.',
                'metal_type'    => 'Gold',
                'country'       => 'Japan',
                'facility_type' => 'Refiner',
                'rmi_conformant' => 1,
                'last_updated'  => date('Y-m-d H:i:s'),
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'smelter_id'    => 'CID002166',
                'smelter_name'  => 'Umicore',
                'metal_type'    => 'Cobalt',
                'country'       => 'Belgium',
                'facility_type' => 'Refiner',
                'rmi_conformant' => 1,
                'last_updated'  => date('Y-m-d H:i:s'),
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]
        ];

        // Using Query Builder
        $this->db->table('rm_smelters')->insertBatch($data);
    }
}
