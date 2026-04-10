<?php

namespace Database\Seeders;

use App\Models\BudgetCategory;
use App\Models\Department;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Truncate all seeded tables first so re-running is safe
        Transaction::truncate();
        BudgetCategory::truncate();
        Department::truncate();
        User::where('email', 'admin@city.gov')->orWhere('email', 'parks@city.gov')->delete();

        $departments = [
            ['name' => 'Public Works', 'code' => 'PW', 'head_name' => 'Robert Chen'],
            ['name' => 'Parks & Recreation', 'code' => 'PR', 'head_name' => 'Maria Santos'],
            ['name' => 'Police Department', 'code' => 'PD', 'head_name' => 'James Walker'],
            ['name' => 'Fire Department', 'code' => 'FD', 'head_name' => 'Sarah Thompson'],
            ['name' => 'City Clerk', 'code' => 'CC', 'head_name' => 'David Kim'],
            ['name' => 'Information Technology', 'code' => 'IT', 'head_name' => 'Lisa Patel'],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }

        $categoryTemplates = [
            'Public Works' => [
                ['name' => 'Personnel', 'allocated' => 2500000],
                ['name' => 'Contractors', 'allocated' => 1800000],
                ['name' => 'Equipment', 'allocated' => 950000],
                ['name' => 'Supplies', 'allocated' => 320000],
                ['name' => 'Training', 'allocated' => 75000],
            ],
            'Parks & Recreation' => [
                ['name' => 'Personnel', 'allocated' => 1200000],
                ['name' => 'Contractors', 'allocated' => 600000],
                ['name' => 'Equipment', 'allocated' => 400000],
                ['name' => 'Supplies', 'allocated' => 180000],
                ['name' => 'Programs & Events', 'allocated' => 250000],
            ],
            'Police Department' => [
                ['name' => 'Personnel', 'allocated' => 4200000],
                ['name' => 'Equipment', 'allocated' => 850000],
                ['name' => 'Vehicles', 'allocated' => 620000],
                ['name' => 'Training', 'allocated' => 180000],
                ['name' => 'Technology', 'allocated' => 340000],
            ],
            'Fire Department' => [
                ['name' => 'Personnel', 'allocated' => 3800000],
                ['name' => 'Equipment', 'allocated' => 720000],
                ['name' => 'Vehicles', 'allocated' => 550000],
                ['name' => 'Supplies', 'allocated' => 210000],
                ['name' => 'Training', 'allocated' => 150000],
            ],
            'City Clerk' => [
                ['name' => 'Personnel', 'allocated' => 450000],
                ['name' => 'Office Supplies', 'allocated' => 35000],
                ['name' => 'Technology', 'allocated' => 85000],
                ['name' => 'Records Management', 'allocated' => 60000],
            ],
            'Information Technology' => [
                ['name' => 'Personnel', 'allocated' => 1100000],
                ['name' => 'Software Licenses', 'allocated' => 480000],
                ['name' => 'Hardware', 'allocated' => 350000],
                ['name' => 'Contractors', 'allocated' => 520000],
                ['name' => 'Cybersecurity', 'allocated' => 200000],
            ],
        ];

        $vendors = [
            'Personnel' => [null],
            'Contractors' => ['Meridian Construction', 'Atlas Infrastructure', 'Summit Engineering', 'Pacific Contractors', 'Greenfield Services'],
            'Equipment' => ['CAT Equipment Co.', 'John Deere Municipal', 'Grainger Industrial', 'Fastenal Supply', 'HD Supply'],
            'Supplies' => ['Staples Business', 'Office Depot', 'Grainger Industrial', 'Ferguson Enterprises', 'Uline Shipping'],
            'Training' => ['National Training Institute', 'SafetyFirst Consulting', 'Municipal Excellence Academy', 'GovLearn LLC'],
            'Programs & Events' => ['Community Events LLC', 'CityFest Productions', 'Youth Sports Association', null],
            'Vehicles' => ['Ford Government Sales', 'Chevrolet Fleet', 'Pierce Manufacturing', 'Spartan Motors'],
            'Technology' => ['Dell Technologies', 'CDW Government', 'Axon Enterprise', 'Motorola Solutions'],
            'Software Licenses' => ['Microsoft', 'Adobe Systems', 'Esri GIS', 'Tyler Technologies', 'Salesforce Gov'],
            'Hardware' => ['Dell Technologies', 'HP Enterprise', 'Cisco Systems', 'CDW Government'],
            'Cybersecurity' => ['CrowdStrike', 'Palo Alto Networks', 'Fortinet', 'KnowBe4'],
            'Office Supplies' => ['Staples Business', 'Office Depot', 'Amazon Business'],
            'Records Management' => ['Iron Mountain', 'Laserfiche', 'DocuWare'],
        ];

        $descriptionTemplates = [
            'Personnel' => ['Monthly payroll', 'Overtime compensation', 'Benefits administration', 'Seasonal staff wages', 'Holiday pay'],
            'Contractors' => ['Road resurfacing project', 'Building maintenance', 'HVAC system repair', 'Plumbing services', 'Electrical upgrades', 'Landscaping contract', 'Snow removal services'],
            'Equipment' => ['Heavy equipment rental', 'Tool replacement', 'Safety equipment purchase', 'Maintenance parts', 'Equipment repair'],
            'Supplies' => ['Office supplies order', 'Cleaning supplies', 'Safety materials', 'Fuel purchase', 'Uniform order'],
            'Training' => ['Annual certification renewal', 'Leadership workshop', 'Safety training seminar', 'Technical skills course', 'Conference registration'],
            'Programs & Events' => ['Summer concert series', 'Youth basketball league', 'Senior fitness program', 'Community garden supplies', 'Holiday celebration event'],
            'Vehicles' => ['Patrol vehicle purchase', 'Fleet maintenance', 'Vehicle fuel', 'Tire replacement', 'Emergency vehicle repair'],
            'Technology' => ['Body camera systems', 'Radio equipment upgrade', 'Dispatch system maintenance', 'Mobile data terminals'],
            'Software Licenses' => ['Annual license renewal', 'Cloud service subscription', 'GIS platform license', 'ERP system maintenance', 'Security software'],
            'Hardware' => ['Server replacement', 'Workstation upgrade', 'Network switch installation', 'Printer fleet maintenance'],
            'Cybersecurity' => ['Security audit', 'Penetration testing', 'Employee phishing training', 'Firewall upgrade'],
            'Office Supplies' => ['Quarterly supply order', 'Printer toner', 'Paper stock replenishment'],
            'Records Management' => ['Document scanning project', 'Archive storage fees', 'Records system maintenance'],
        ];

        foreach (Department::all() as $department) {
            $categories = $categoryTemplates[$department->name];

            foreach ($categories as $catData) {
                $category = BudgetCategory::create([
                    'department_id' => $department->id,
                    'name' => $catData['name'],
                    'fiscal_year' => 2024,
                    'allocated_amount' => $catData['allocated'],
                ]);

                $transactionCount = rand(30, 50);
                $catVendors = $vendors[$catData['name']] ?? [null];
                $catDescriptions = $descriptionTemplates[$catData['name']] ?? ['General expense'];

                for ($i = 0; $i < $transactionCount; $i++) {
                    $month = rand(1, 12);
                    $day = rand(1, 28);
                    $date = sprintf('2024-%02d-%02d', $month, $day);
                    $quarter = ceil($month / 3);

                    $baseAmount = $catData['allocated'] / $transactionCount;
                    $variance = $baseAmount * (rand(-40, 60) / 100);
                    $amount = round(max(100, $baseAmount + $variance), 2);

                    Transaction::create([
                        'department_id' => $department->id,
                        'budget_category_id' => $category->id,
                        'description' => $catDescriptions[array_rand($catDescriptions)],
                        'amount' => $amount,
                        'vendor' => $catVendors[array_rand($catVendors)],
                        'transaction_date' => $date,
                        'quarter' => $quarter,
                    ]);
                }
            }
        }

        $parksId = Department::where('code', 'PR')->first()->id;

        User::updateOrCreate(
            ['email' => 'admin@city.gov'],
            [
                'name' => 'City Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'department_id' => null,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'parks@city.gov'],
            [
                'name' => 'Maria Santos',
                'password' => Hash::make('password'),
                'role' => 'department_head',
                'department_id' => $parksId,
                'email_verified_at' => now(),
            ]
        );
    }
}
