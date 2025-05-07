<?php

namespace Database\Seeders;

use App\Models\Activities;
use Illuminate\Database\Seeder;

class ActivitiesSeeder extends Seeder
{
  public function run()
  {
    $activities = [
      [
        'type' => 'taxes',
        'title' => 'Pay Taxes',
        'description' => 'Manage and pay various taxes, including federal and state taxes.',
        'meta_info' => [
          [
            'name' => 'Driver\'s License Tax',
            'description' => 'Pay the required tax associated with obtaining or renewing a driver\'s license.',
            'government_agency' => 'Federal Road Safety Commission (FRSC)',
            'payment_frequency' => 'One-time',
            'payment_methods' => ['Bank Transfer', 'Card Payment', 'USSD'],
            'required_documents' => ['Valid ID', 'Previous Driver\'s License (for renewal)'],
            'tax_type' => 'Federal Tax',
            'state' => 'Federal',
          ],
          [
            'name' => 'Personal Income Tax',
            'description' => 'Pay your annual personal income tax.',
            'government_agency' => 'State Internal Revenue Service (IRS)',
            'payment_frequency' => 'Recurring', // e.g., Annually
            'payment_methods' => ['Bank Transfer', 'Card Payment'],
            'required_documents' => ['Tax Identification Number (TIN)', 'Income Statement'],
            'tax_type' => 'State Tax',
            'state' => 'Oyo',
          ],
          [
            'name' => 'Value Added Tax (VAT)',
            'description' => 'Pay the Value Added Tax (VAT) on goods and services.',
            'government_agency' => 'Federal Inland Revenue Service (FIRS)',
            'payment_frequency' => 'Recurring', // e.g., Monthly/Quarterly
            'payment_methods' => ['Bank Transfer', 'Card Payment', 'USSD'],
            'required_documents' => ['VAT Registration Number', 'Sales Records'],
            'tax_type' => 'Federal Tax',
            'state' => 'Federal',
          ],


        ],
      ],
      [
        'type' => 'fees',
        'title' => 'Pay Fees',
        'description' => 'Manage and pay various fees, including government and private fees.',
        'meta_info' => [

          [
            'name' => 'Vehicle Registration Fee',
            'description' => 'Pay the fee for registering a new or used vehicle.',
            'government_agency' => 'State Vehicle Licensing Office',
            'payment_frequency' => 'Recurring', // e.g., Annually
            'payment_methods' => ['POS', 'Card Payment', 'Bank Transfer'],
            'required_documents' => ['Proof of Ownership', 'Valid ID', 'Insurance Certificate'],
            'fee_type' => 'Government Fee',
            'state' => 'Lagos',
          ],
          [
            'name' => 'Comprehensive Insurance Fee',
            'description' => 'Pay the fee for comprehensive vehicle insurance.',
            'government_agency' => 'ABC Insurance Company',
            'payment_frequency' => 'Annual',
            'payment_methods' => ['Bank Transfer', 'Card Payment'],
            'required_documents' => ['Vehicle Registration', 'Valid ID'],
            'fee_type' => 'Private Fee',
            'state' => 'Nationwide',
          ],
          [
            'name' => 'Driver’s License Renewal Fee',
            'description' => 'Fee for renewing a driver’s license in Nigeria.',
            'government_agency' => 'Federal Road Safety Commission (FRSC)',
            'payment_frequency' => 'One-time',
            'payment_methods' => ['Bank Transfer', 'Card Payment', 'USSD'],
            'fee_type' => 'Government',
            'state' => 'Federal',
          ],
        ],
      ],

    ];

    foreach ($activities as $activity) {
      Activities::create($activity);
    }
  }
}
