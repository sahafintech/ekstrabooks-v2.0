<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UtilitySeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        //Default Settings
        DB::table('settings')->insert([
            [
                'name'  => 'mail_type',
                'value' => 'smtp',
            ],
            [
                'name'  => 'backend_direction',
                'value' => 'ltr',
            ],
            [
                'name'  => 'email_verification',
                'value' => 0,
            ],
            [
                'name'  => 'member_signup',
                'value' => 1,
            ],
            [
                'name'  => 'language',
                'value' => 'English---us',
            ],
            [
                'name'  => 'currency',
                'value' => 'USD',
            ],
        ]);

        //Payment Gateways
        DB::table('payment_gateways')->insert([
            [
                'name'                 => 'PayPal',
                'slug'                 => 'PayPal',
                'image'                => 'paypal.png',
                'status'               => 0,
                'is_crypto'            => 0,
                'parameters'           => '{"client_id":"","client_secret":"","environment":"sandbox"}',
            ],
            [
                'name'                 => 'Stripe',
                'slug'                 => 'Stripe',
                'image'                => 'stripe.png',
                'status'               => 0,
                'is_crypto'            => 0,
                'parameters'           => '{"secret_key":"","publishable_key":""}',
            ],
            [
                'name'                 => 'Razorpay',
                'slug'                 => 'Razorpay',
                'image'                => 'razorpay.png',
                'status'               => 0,
                'is_crypto'            => 0,
                'parameters'           => '{"razorpay_key_id":"","razorpay_key_secret":""}',
            ],
            [
                'name'                 => 'Paystack',
                'slug'                 => 'Paystack',
                'image'                => 'paystack.png',
                'status'               => 0,
                'is_crypto'            => 0,
                'parameters'           => '{"paystack_public_key":"","paystack_secret_key":""}',
            ],
            [
                'name'                 => 'Flutterwave',
                'slug'                 => 'Flutterwave',
                'image'                => 'flutterwave.png',
                'status'               => 0,
                'is_crypto'            => 0,
                'parameters'           => '{"public_key":"","secret_key":"","encryption_key":"","environment":"sandbox"}',
            ],
            [
                'name'                 => 'Mollie',
                'slug'                 => 'Mollie',
                'image'                => 'Mollie.png',
                'status'               => 0,
                'is_crypto'            => 0,
                'parameters'           => '{"api_key":""}',
            ],
            [
                'name'                 => 'Instamojo',
                'slug'                 => 'Instamojo',
                'image'                => 'instamojo.png',
                'status'               => 0,
                'is_crypto'            => 0,
                'parameters'           => '{"api_key":"","auth_token":"","salt":"","environment":"sandbox"}',
            ],
        ]);

    }
}
