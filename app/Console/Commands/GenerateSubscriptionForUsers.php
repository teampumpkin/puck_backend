<?php

namespace App\Console\Commands;

use App\Helpers\ZohoHelper;
use App\Models\PrcSubscription;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;

/**
 * Class GenerateSubscriptionForUsers
 * @package App\Console\Commands
 */
class GenerateSubscriptionForUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:subscription';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Subscription for the Users';
    /**
     * @var User
     */
    private $prc_user;
    /**
     * @var ZohoHelper
     */
    private $zoho_helper;
    /**
     * @var PrcSubscription
     */
    private $prc_subscription;


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->prc_user         = new User();
        $this->prc_subscription = new PrcSubscription();
        $this->zoho_helper      = new ZohoHelper();
    }

    /**
     * @return int
     * @throws Exception
     */
    public function handle()
    {
        $count     = 0;
        $prc_users = $this->prc_user->where('type', 2)
            ->doesntHave('current_subscription')
            ->get();

        if (empty($prc_users->toArray())) {
            $this->line('<fg=red>All Users have subscription</>');
            return 0;
        }

        $this->output->createProgressBar();
        $this->output->progressStart(count($prc_users->toArray()));
        foreach ($prc_users as $prc_user) {
            if (!empty($prc_user->current_subscription)) {
                continue;
            }

            $customer_id = $prc_user->zoho_customer_id;

            if (empty($customer_id)) {
                $customer_id = $this->zoho_helper->createCustomer($prc_user);

                $prc_user->zoho_customer_id = $customer_id;
                $prc_user->save();
            }

            $subscription = $this->zoho_helper->createOfflineSubscription($customer_id, ZOHO_FREE_PLAN_CODE);

            if (empty($subscription->subscription)) {
                continue;
            }
            $count++;

            $this->prc_subscription->create([
                'user_id'         => $prc_user->id,
                'subscription_id' => $subscription->subscription->subscription_id,
                'plan_code'       => $subscription->subscription->plan->plan_code,
                'start_from'      => $subscription->subscription->start_date,
                'card_id'         => "",
                'renew_on'        => $subscription->subscription->next_billing_at,
                'extra_data'      => json_encode($subscription->subscription)
            ]);
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        if ($count == 0) {
            $this->line('<fg=red>All Users have subscription</>');
            return 0;
        }
        $this->line('<fg=green>' . $count . ' subscriptions have been created</>');
        return 0;
    }
}
