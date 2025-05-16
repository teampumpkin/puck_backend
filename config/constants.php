<?php
const MENTORSHIP_ON_BOARD_PRICE     = 299;
const MENTORSHIP_SUBSCRIPTION_PRICE = 250;

const ZOHO_FREE_PLAN_CODE       = 'free';
const ZOHO_FREE_EVAL_ADDON_CODE = 'one-time-evaluation';
const ZOHO_EVALUATION_PLAN      = 'eval';
const ZOHO_ONE_TO_ONE_CALL_PLAN = 'One-On-One-Conusltaton';
const ZOHO_MENTORSHIP_PLAN      = 'mentorship';

const ZOHO_PLAN_API                         = 'plans?filter_by=PlanStatus.All';
const ZOHO_CREATE_CUSTOMER_API              = 'customers';
const ZOHO_CREATE_PAYMENT_PAGE_API          = 'hostedpages/newsubscription';
const ZOHO_HOSTED_PAGE_DETAIL_API           = 'hostedpages/';
const ZOHO_PLAN_CREATE_API                  = 'plans';
const ZOHO_CANCEL_PLAN_API                  = 'subscriptions/$subscription_id$/cancel?cancel_at_end=true';
const ZOHO_OFFLINE_SUBSCRIPTION_CREATE      = 'subscriptions';
const ZOHO_CREATE_ONE_TIME_PAYMENT_PAGE_API = 'hostedpages/buyonetimeaddon';

