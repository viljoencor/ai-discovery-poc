<?php

namespace Database\Seeders;

use App\Models\AvailabilityBlock;
use App\Models\Property;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Invented sample data ONLY. No data, logos, live prices, or claims of availability.
        $samples = [
            ['name'=>'Hermanus Ocean Nook','city'=>'Hermanus','guest_capacity'=>4,'base_price_zar'=>1750,'pets_allowed'=>true,'amenities'=>['parking','wifi','braai','sea_view','beachfront'],'description'=>'Pet-friendly coastal cottage with ocean views and private braai.'],
            ['name'=>'Hermanus Cliff Studio','city'=>'Hermanus','guest_capacity'=>2,'base_price_zar'=>1100,'pets_allowed'=>false,'amenities'=>['parking','wifi','sea_view'],'description'=>'Quiet studio close to the cliff paths.'],
            ['name'=>'Hermanus Family Escape','city'=>'Hermanus','guest_capacity'=>6,'base_price_zar'=>2250,'pets_allowed'=>true,'amenities'=>['parking','wifi','pool','braai'],'description'=>'Spacious family retreat with a pool and lawn.'],
            ['name'=>'Cape Town Harbour Loft','city'=>'Cape Town','guest_capacity'=>4,'base_price_zar'=>1950,'pets_allowed'=>false,'amenities'=>['parking','wifi','sea_view'],'description'=>'Contemporary city loft with harbour views.'],
            ['name'=>'Cape Town Family Villa','city'=>'Cape Town','guest_capacity'=>8,'base_price_zar'=>3200,'pets_allowed'=>true,'amenities'=>['parking','wifi','pool','braai'],'description'=>'Large family villa with outdoor entertainment area.'],
            ['name'=>'Knysna Lagoon Hideaway','city'=>'Knysna','guest_capacity'=>4,'base_price_zar'=>1650,'pets_allowed'=>true,'amenities'=>['parking','wifi','braai','sea_view'],'description'=>'Easy-going retreat beside the lagoon.'],
            ['name'=>'Paternoster Beach Cottage','city'=>'Paternoster','guest_capacity'=>4,'base_price_zar'=>1850,'pets_allowed'=>true,'amenities'=>['parking','wifi','braai','beachfront'],'description'=>'Whitewashed cottage metres from the shore.'],
            ['name'=>'Stellenbosch Vineyard Stay','city'=>'Stellenbosch','guest_capacity'=>2,'base_price_zar'=>1450,'pets_allowed'=>false,'amenities'=>['parking','wifi','pool'],'description'=>'Vineyard accommodation with a shared pool.'],
            ['name'=>'Plettenberg Bay Seaside Home','city'=>'Plettenberg Bay','guest_capacity'=>6,'base_price_zar'=>2600,'pets_allowed'=>true,'amenities'=>['parking','wifi','braai','beachfront','sea_view'],'description'=>'Family holiday home overlooking the beach.'],
            ['name'=>'Hout Bay Hillside Suite','city'=>'Hout Bay','guest_capacity'=>2,'base_price_zar'=>1350,'pets_allowed'=>false,'amenities'=>['parking','wifi','sea_view'],'description'=>'Relaxed hillside suite with bay views.'],
        ];
        foreach ($samples as $sample) {
            Property::updateOrCreate(['name'=>$sample['name']], $sample + ['active'=>true]);
        }
        $blocked = Property::where('name', 'Hermanus Ocean Nook')->firstOrFail();
        foreach (['2026-11-13', '2026-11-14'] as $day) {
            AvailabilityBlock::firstOrCreate(['property_id'=>$blocked->id,'date'=>$day]);
        }
    }
}
