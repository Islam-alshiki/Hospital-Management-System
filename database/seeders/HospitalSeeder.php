<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HospitalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User if not exists
        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@libyacare.ly'],
            [
                'name' => 'Hospital Administrator',
                'password' => bcrypt('password'),
                'employee_id' => 'EMP-001',
                'role' => 'admin',
                'is_active' => true,
                'hire_date' => now(),
            ]
        );

        // Create Departments
        $departments = [
            ['name' => 'Emergency Department', 'name_ar' => 'قسم الطوارئ', 'code' => 'ED', 'emergency_department' => true],
            ['name' => 'Internal Medicine', 'name_ar' => 'الطب الباطني', 'code' => 'IM'],
            ['name' => 'Surgery', 'name_ar' => 'الجراحة', 'code' => 'SUR'],
            ['name' => 'Pediatrics', 'name_ar' => 'طب الأطفال', 'code' => 'PED'],
            ['name' => 'Obstetrics & Gynecology', 'name_ar' => 'النساء والولادة', 'code' => 'OBG'],
            ['name' => 'Cardiology', 'name_ar' => 'أمراض القلب', 'code' => 'CAR'],
            ['name' => 'Orthopedics', 'name_ar' => 'العظام', 'code' => 'ORT'],
            ['name' => 'Laboratory', 'name_ar' => 'المختبر', 'code' => 'LAB'],
            ['name' => 'Pharmacy', 'name_ar' => 'الصيدلية', 'code' => 'PHR'],
            ['name' => 'Radiology', 'name_ar' => 'الأشعة', 'code' => 'RAD'],
        ];

        foreach ($departments as $dept) {
            \App\Models\Department::firstOrCreate(
                ['code' => $dept['code']],
                $dept
            );
        }

        // Create Sample Doctors
        $doctors = [
            [
                'name' => 'Dr. Ahmed Al-Mansouri',
                'email' => 'ahmed.mansouri@libyacare.ly',
                'password' => bcrypt('password'),
                'employee_id' => 'DOC-001',
                'role' => 'doctor',
                'department_id' => 2, // Internal Medicine
                'phone' => '+218-91-1234567',
                'is_active' => true,
                'hire_date' => now()->subYears(5),
            ],
            [
                'name' => 'Dr. Fatima Al-Zahra',
                'email' => 'fatima.zahra@libyacare.ly',
                'password' => bcrypt('password'),
                'employee_id' => 'DOC-002',
                'role' => 'doctor',
                'department_id' => 5, // OBG
                'phone' => '+218-91-2345678',
                'is_active' => true,
                'hire_date' => now()->subYears(3),
            ],
            [
                'name' => 'Dr. Omar Benali',
                'email' => 'omar.benali@libyacare.ly',
                'password' => bcrypt('password'),
                'employee_id' => 'DOC-003',
                'role' => 'doctor',
                'department_id' => 3, // Surgery
                'phone' => '+218-91-3456789',
                'is_active' => true,
                'hire_date' => now()->subYears(7),
            ],
            [
                'name' => 'Dr. Aisha Khalil',
                'email' => 'aisha.khalil@libyacare.ly',
                'password' => bcrypt('password'),
                'employee_id' => 'DOC-004',
                'role' => 'doctor',
                'department_id' => 4, // Pediatrics
                'phone' => '+218-91-4567890',
                'is_active' => true,
                'hire_date' => now()->subYears(4),
            ],
            [
                'name' => 'Dr. Mahmoud Gaddafi',
                'email' => 'mahmoud.gaddafi@libyacare.ly',
                'password' => bcrypt('password'),
                'employee_id' => 'DOC-005',
                'role' => 'doctor',
                'department_id' => 6, // Cardiology
                'phone' => '+218-91-5678901',
                'is_active' => true,
                'hire_date' => now()->subYears(6),
            ],
        ];

        foreach ($doctors as $doctorData) {
            $user = \App\Models\User::firstOrCreate(
                ['email' => $doctorData['email']],
                $doctorData
            );

            // Create corresponding Doctor record if not exists
            \App\Models\Doctor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'license_number' => 'LIC-' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                    'specialization' => match($doctorData['department_id']) {
                        2 => 'Internal Medicine',
                        3 => 'General Surgery',
                        4 => 'Pediatrics',
                        5 => 'Obstetrics & Gynecology',
                        6 => 'Cardiology',
                        default => 'General Practice',
                    },
                    'years_of_experience' => rand(3, 15),
                    'consultation_fee' => rand(50, 200),
                    'is_available' => true,
                ]
            );
        }

        // Create Insurance Providers
        $insuranceProviders = [
            [
                'name' => 'Libya National Insurance',
                'name_ar' => 'التأمين الوطني الليبي',
                'code' => 'LNI',
                'coverage_percentage' => 80.00,
                'covered_services' => ['consultation', 'lab_tests', 'medicines', 'surgery'],
                'is_active' => true,
            ],
            [
                'name' => 'Private Health Insurance',
                'name_ar' => 'التأمين الصحي الخاص',
                'code' => 'PHI',
                'coverage_percentage' => 90.00,
                'covered_services' => ['consultation', 'lab_tests', 'medicines', 'surgery', 'private_room'],
                'is_active' => true,
            ],
        ];

        foreach ($insuranceProviders as $provider) {
            \App\Models\InsuranceProvider::firstOrCreate(
                ['code' => $provider['code']],
                $provider
            );
        }

        // Create Sample Medicines
        $medicines = [
            [
                'name' => 'Paracetamol',
                'name_ar' => 'باراسيتامول',
                'code' => 'MED-001',
                'category' => 'analgesic',
                'dosage_form' => 'tablet',
                'strength' => '500mg',
                'unit' => 'tablet',
                'stock_quantity' => 1000,
                'minimum_stock_level' => 100,
                'selling_price' => 0.50,
                'requires_prescription' => false,
            ],
            [
                'name' => 'Amoxicillin',
                'name_ar' => 'أموكسيسيلين',
                'code' => 'MED-002',
                'category' => 'antibiotic',
                'dosage_form' => 'capsule',
                'strength' => '250mg',
                'unit' => 'capsule',
                'stock_quantity' => 500,
                'minimum_stock_level' => 50,
                'selling_price' => 2.00,
                'requires_prescription' => true,
            ],
            [
                'name' => 'Insulin',
                'name_ar' => 'الأنسولين',
                'code' => 'MED-003',
                'category' => 'antidiabetic',
                'dosage_form' => 'injection',
                'strength' => '100IU/ml',
                'unit' => 'vial',
                'stock_quantity' => 200,
                'minimum_stock_level' => 20,
                'selling_price' => 25.00,
                'requires_prescription' => true,
            ],
        ];

        foreach ($medicines as $medicine) {
            \App\Models\Medicine::firstOrCreate(
                ['code' => $medicine['code']],
                $medicine
            );
        }

        // Create Sample Wards
        $wards = [
            [
                'name' => 'General Ward',
                'name_ar' => 'الجناح العام',
                'code' => 'GW-01',
                'department_id' => 2, // Internal Medicine
                'ward_type' => 'general',
                'total_beds' => 20,
                'available_beds' => 15,
                'location' => 'Building A, Floor 2',
            ],
            [
                'name' => 'ICU',
                'name_ar' => 'العناية المركزة',
                'code' => 'ICU-01',
                'department_id' => 1, // Emergency
                'ward_type' => 'icu',
                'total_beds' => 10,
                'available_beds' => 8,
                'location' => 'Building A, Floor 3',
            ],
        ];

        foreach ($wards as $ward) {
            \App\Models\Ward::create($ward);
        }

        // Create Sample Rooms
        for ($i = 1; $i <= 30; $i++) {
            \App\Models\Room::create([
                'room_number' => 'R-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'ward_id' => $i <= 20 ? 1 : 2, // First 20 in General Ward, rest in ICU
                'department_id' => $i <= 20 ? 2 : 1,
                'room_type' => $i <= 20 ? 'double' : 'single',
                'bed_count' => $i <= 20 ? 2 : 1,
                'available_beds' => $i <= 20 ? (rand(0, 2)) : (rand(0, 1)),
                'daily_rate' => $i <= 20 ? 50.00 : 150.00,
                'has_ac' => true,
                'has_tv' => $i > 10,
                'has_wifi' => true,
                'status' => rand(0, 1) ? 'available' : 'occupied',
            ]);
        }

        echo "Hospital seeder completed successfully!\n";
        echo "Admin credentials: admin@libyacare.ly / password\n";
    }
}
