<?php

namespace Database\Seeders;

use App\Models\InternalRevenueService;
use App\Models\State;
use Illuminate\Database\Seeder;

class StatesAndIRSServicesSeeder extends Seeder
{
    public function run()
    {
        $statesData = [
    [
        'irs_name' => 'Abia State Internal Revenue Service',
        'short_name' => 'AIRS',
        'state_name' => 'Abia',
        'website' => 'https://tat.gov.ng/news?id=62&l=bank-is-not-liable-to-remit-stamp-duty-deduction-to-abia-state-internal-revenue-tax-tribunal-rules',
        'contacts' => [
            'phone' => ['08032488625'],
            'email' => ['support@abiairs.gov']
        ]
    ],
    [
        'irs_name' => 'Adamawa State Internal Revenue Service',
        'short_name' => 'ASBIR',
        'state_name' => 'Adamawa',
        'website' => 'https://saber.adamawastate.gov.ng/wp-content/uploads/2023/12/Adamawa-State-Revenue-Administration-Law-2020_.pdf',
        'contacts' => [
            'phone' => ['+234 803 587 9638'],
            'email' => ['airsofficedirect@gmail.com']
        ]
    ],
    [
        'irs_name' => 'Akwa Ibom State Internal Revenue Service',
        'short_name' => 'AKIRS',
        'state_name' => 'Akwa Ibom',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Anambra State Internal Revenue Service',
        'short_name' => 'AIRS',
        'state_name' => 'Anambra',
        'website' => 'https://anambrastate.gov.ng/old/wp-content/uploads/Anambra-State-Revenue-Administration-Laws.pdf',
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Bauchi State Internal Revenue Service',
        'short_name' => 'BSBIR',
        'state_name' => 'Bauchi',
        'website' => 'https://www.bauchistate.gov.ng/sdm_downloads/bauchi-state-internal-revenue-service-introduction-of-covid-19-palliatives-to-taxpayers-and-businesses/',
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Bayelsa State Internal Revenue Service',
        'short_name' => 'BYBIR',
        'state_name' => 'Bayelsa',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Benue State Internal Revenue Service',
        'short_name' => 'BIRS',
        'state_name' => 'Benue',
        'website' => null,
        'contacts' => [
            'phone' => ['000082019061'],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Borno State Internal Revenue Service',
        'short_name' => 'BOBIR',
        'state_name' => 'Borno',
        'website' => 'https://pfm.bo.gov.ng/wp-content/uploads/2022/02/Borno-State-Internal-Revenue-Service-Law-2020.pdf',
        'contacts' => [
            'phone' => ['+234 803 0523 208', '+234 814 4993 882'],
            'email' => ['info@payborno.com.ng']
        ]
    ],
    [
        'irs_name' => 'Cross River State Internal Revenue Service',
        'short_name' => 'CRIRS',
        'state_name' => 'Cross River',
        'website' => null,
        'contacts' => [
            'phone' => ['08129336969 0'],
            'email' => ['enquiries@crirs.crossriverstate.gov.ng', 'Info@crirs.crossriverstate.gov.ng']
        ]
    ],
    [
        'irs_name' => 'Delta State Internal Revenue Service',
        'short_name' => 'DSIRS',
        'state_name' => 'Delta',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Ebonyi State Internal Revenue Service',
        'short_name' => 'EBSBIR',
        'state_name' => 'Ebonyi',
        'website' => 'https://ebonyistate.gov.ng/storage/documents/min-2-procedures-and-timeline-for-obtaining-business-premesis-permit-by-ebonyi-state-internal-revenue-servicepdf-1735676058.pdf',
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Edo State Internal Revenue Service',
        'short_name' => 'EIRS',
        'state_name' => 'Edo',
        'website' => 'https://edojudiciary.gov.ng/wp-content/uploads/2020/02/Edo-State-Revenue-Administration-Law-2012-1.pdf',
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Ekiti State Internal Revenue Service',
        'short_name' => 'EKIRS',
        'state_name' => 'Ekiti',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Enugu State Internal Revenue Service',
        'short_name' => 'ESBIR',
        'state_name' => 'Enugu',
        'website' => 'https://enugustate.gov.ng/2025/02/04/gov-mbah-signs-into-law-bill-to-create-one-stop-shop-for-taxation-in-enugu/',
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Gombe State Internal Revenue Service',
        'short_name' => 'GSIRS',
        'state_name' => 'Gombe',
        'website' => null,
        'contacts' => [
            'phone' => ['09039876245 3'],
            'email' => ['info@gombestate.gov.ng', 'support@irs.gm.gov.ng']
        ]
    ],
    [
        'irs_name' => 'Imo State Internal Revenue Service',
        'short_name' => 'IIRS',
        'state_name' => 'Imo',
        'website' => null,
        'contacts' => [
            'phone' => ['07044143059'],
            'email' => ['bir@imostate.gov.ng']
        ]
    ],
    [
        'irs_name' => 'Jigawa State Internal Revenue Service',
        'short_name' => 'JSIRS',
        'state_name' => 'Jigawa',
        'website' => null,
        'contacts' => [
            'phone' => ['+234 8030 687 681', '+2348188397000'],
            'email' => ['contactus@odirs.ng']
        ]
    ],
    [
        'irs_name' => 'Kaduna State Internal Revenue Service',
        'short_name' => 'KADIRS',
        'state_name' => 'Kaduna',
        'website' => 'https://kdsg.gov.ng/kaduna-state-internal-revenue-service/',
        'contacts' => [
            'phone' => ['0817 0189 999'],
            'email' => ['info@kdsg.gov.ng']
        ]
    ],
    [
        'irs_name' => 'Kano State Internal Revenue Service',
        'short_name' => 'KIRS',
        'state_name' => 'Kano',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => ['info@kirs.gov.ng', 'info@kanobir.gov.ng']
        ]
    ],
    [
        'irs_name' => 'Katsina State Internal Revenue Service',
        'short_name' => 'KTSBIR',
        'state_name' => 'Katsina',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Kebbi State Internal Revenue Service',
        'short_name' => 'KBBIR',
        'state_name' => 'Kebbi',
        'website' => null,
        'contacts' => [
            'phone' => ['+234 806 940 3332'],
            'email' => ['info@irs.kb.gov.ng', 'info@kebbistate.gov.ng']
        ]
    ],
    [
        'irs_name' => 'Kogi State Internal Revenue Service',
        'short_name' => 'KGIRS',
        'state_name' => 'Kogi',
        'website' => 'https://kogistate.gov.ng/wp-content/uploads/KOGI-STATE-REGULATIONS-ON-ALL-TRADE-RELATED-FEES-AND-LEVIES-ON-INTER-STATE-MOVEMENT-OF-GOODS4.pdf',
        'contacts' => [
            'phone' => ['08083427276', '+234 806 940 3332'],
            'email' => ['info@kirs.kg.gov.ng', 'info@irs.kb.gov.ng']
        ]
    ],
    [
        'irs_name' => 'Kwara State Internal Revenue Service',
        'short_name' => 'KWIRS',
        'state_name' => 'Kwara',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Lagos State Internal Revenue Service',
        'short_name' => 'LIRS',
        'state_name' => 'Lagos',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Nasarawa State Internal Revenue Service',
        'short_name' => 'NSIRS',
        'state_name' => 'Nasarawa',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Niger State Internal Revenue Service',
        'short_name' => 'NGIRS',
        'state_name' => 'Niger',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Ogun State Internal Revenue Service',
        'short_name' => 'OGIRS',
        'state_name' => 'Ogun',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Ondo State Internal Revenue Service',
        'short_name' => 'ODIRS',
        'state_name' => 'Ondo',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Osun State Internal Revenue Service',
        'short_name' => 'OSIRS',
        'state_name' => 'Osun',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Oyo State Internal Revenue Service',
        'short_name' => 'OYIRS',
        'state_name' => 'Oyo',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Plateau State Internal Revenue Service',
        'short_name' => 'PSIRS',
        'state_name' => 'Plateau',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Rivers State Internal Revenue Service',
        'short_name' => 'RIRS',
        'state_name' => 'Rivers',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Sokoto State Internal Revenue Service',
        'short_name' => 'SSIRS',
        'state_name' => 'Sokoto',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Taraba State Internal Revenue Service',
        'short_name' => 'TSBIR',
        'state_name' => 'Taraba',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Yobe State Internal Revenue Service',
        'short_name' => 'YSBIR',
        'state_name' => 'Yobe',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Zamfara State Internal Revenue Service',
        'short_name' => 'ZSBIR',
        'state_name' => 'Zamfara',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ],
    [
        'irs_name' => 'Federal Capital Territory Internal Revenue Service',
        'short_name' => 'FCT-IRS',
        'state_name' => 'FCT',
        'website' => null,
        'contacts' => [
            'phone' => [],
            'email' => []
        ]
    ]
        ];

        foreach ($statesData as $data) {
            $state = State::create([
                'name' => $data['state_name'],
                'code' => strtoupper(substr($data['state_name'], 0, 3)),
            ]);

            InternalRevenueService::create([
                'state_id' => $state->id,
                'irs_name' => $data['irs_name'],
                'short_name' => $data['short_name'],
                'website' => $data['website'],
                'contacts' => $data['contacts'],
            ]);
        }
    }
}