<?php

namespace Database\Seeders;

use App\Helpers\ZohoHelper;
use App\Models\PrcPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

class SyncZohoPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        $plan = PrcPlan::first();

        if (empty($plan)) {
            $zoho_helper = new ZohoHelper();

            $plans     = $zoho_helper->getPlans();
            $developer = User::where('type', 1)->first();
            foreach ($plans as $plan) {
                if ($plan->status == 'active') {
                    PrcPlan::create([
                        'plan_name'        => $plan->name,
                        'product_id'       => $plan->product_id,
                        'plan_code'        => $plan->plan_code,
                        'plan_price'       => $plan->recurring_price,
                        'interval'         => $plan->interval,
                        'interval_unit'    => $plan->interval_unit,
                        'plan_description' => $plan->description,
                        'extra_data'       => json_encode($plan),
                        'created_by'       => $developer->id,
                    ]);
                }
            }
        }
    }
}
