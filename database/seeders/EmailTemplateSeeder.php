<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailTemplateSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('email_templates')->insert([
            [
                "name"                => "Invite User",
                "slug"                => "INVITE_USER",
                "subject"             => "You've been invited to collaborate",
                "email_body"          => "<h2>Invitation to collaborate</h2><p>{{businessName}} has invited you to collaborate as {{roleName}}</p><p>{{message}}</p><p>Accept the invitation by clicking the button below.</p><p><a href='{{actionUrl}}' style='box-sizing: border-box; position: relative; -webkit-text-size-adjust: none; border-radius: 4px; color: #fff; display: inline-block; overflow: hidden; text-decoration: none; background-color: #2d3748; border-bottom: 8px solid #2d3748; border-left: 18px solid #2d3748; border-right: 18px solid #2d3748; border-top: 8px solid #2d3748;'>Accept Invitation</a></p><p class='subcopy' style='word-break: break-all; font-size: 14px;'>If you're having trouble clicking the 'Accept Invitation' button, copy and paste the URL below into your web browser: <a href='{{actionUrl}}'>{{actionUrl}}</a></p>",
                "sms_body"            => "",
                "notification_body"   => "",
                "shortcode"           => "{{businessName}} {{roleName}} {{message}} {{actionUrl}}",
                "email_status"        => 0,
                "sms_status"          => 0,
                "notification_status" => 0,
                "template_mode"       => 1,
            ],
			[
                "name"                => "New Invoice Created",
                "slug"                => "NEW_INVOICE_CREATED",
                "subject"             => "New Invoice Created",
                "email_body"          => "<div style='font-family: Arial, sans-serif; font-size: 14px;'> <h2 style='color: #333333;'>New Invoice Created</h2> <p>Dear {{customerName}},</p> <p>I am writing to let you know that a new invoice has been created for the Product/Service you ordered. The details of the invoice are as follows:</p> <ul> <li>Invoice number: <strong>{{invoiceNumber}}</strong></li> <li>Invoice date: <strong>{{invoiceDate}}</strong></li> <li>Due date: <strong>{{dueDate}}</strong></li> <li>Total amount due: <strong>{{dueAmount}}</strong></li> </ul> <p>To make the payment, please use the following details:</p> <p>{{invoiceLink}}</p> <p>Thank you for your business. We appreciate your prompt payment.</p> </div>",
                "sms_body"            => "",
                "notification_body"   => "",
                "shortcode"           => "{{customerName}} {{invoiceNumber}} {{invoiceDate}} {{dueDate}} {{dueAmount}} {{invoiceLink}}",
                "email_status"        => 0,
                "sms_status"          => 0,
                "notification_status" => 0,
                "template_mode"       => 1,
            ],
			[
                "name"                => "New Quotation Created",
                "slug"                => "NEW_QUOTATION_CREATED",
                "subject"             => "New Quotation Created",
                "email_body"          => "<div style='font-family: Arial, sans-serif; font-size: 14px;'> <h2 style='color: #333333;'>New Quotation Created</h2> <p>Dear {{customerName}},</p> <p>I am pleased to inform you that we have created a new quotation for the Product/Service you requested. The details of the quotation are as follows:</p> <ul> <li>Quotation number: <strong>{{quotationNumber}}</strong></li> <li>Quotation date: <strong>{{quotationDate}}</strong></li> <li>Quotation expiry date: <strong>{{expiryDate}}</strong></li> <li>Total amount: <strong>{{amount}}</strong></li> </ul> <p>Please note that this quotation is valid until <strong>{{expiryDate}}</strong>. If you have any questions regarding the quotation, please do not hesitate to contact us.</p> <p><a href='{{quotationLink}}'>View Quotation</a></p> <p>To proceed with the order, please confirm your acceptance of the quotation by replying to this email. Once we receive your confirmation, we will proceed with the order and send you the invoice.</p> <p>Thank you for your interest in our products/services. We look forward to doing business with you.</p> </div>",
                "sms_body"            => "",
                "notification_body"   => "",
                "shortcode"           => "{{customerName}} {{quotationDate}} {{expiryDate}} {{amount}} {{quotationNumber}} {{quotationLink}}",
                "email_status"        => 0,
                "sms_status"          => 0,
                "notification_status" => 0,
                "template_mode"       => 1,
            ],
			[
                "name"                => "Invoice Payment Reminder",
                "slug"                => "INVOICE_PAYMENT_REMINDER",
                "subject"             => "Invoice Payment Reminder",
                "email_body"          => "<div style='font-family: Arial, sans-serif; font-size: 14px;'> <h2 style='color: #333333;'>Invoice Payment Reminder</h2> <p>Dear {{customerName}},</p> <p>I hope this email finds you well. This message is to remind you that the payment for invoice <strong>{{invoiceNumber}}</strong> is now due.</p> <p>The total amount due is <strong>{{dueAmount}}</strong>. Please ensure that the payment is made promptly to avoid any late fees or penalties.</p> <p>To make the payment, kindly use the following details:</p> <p>{{invoiceLink}}</p> <p>If you have already made the payment, please disregard this email.</p> <p>Thank you for your prompt attention to this matter. If you have any questions or concerns, please do not hesitate to contact me.</p></div>",
                "sms_body"            => "",
                "notification_body"   => "",
                "shortcode"           => "{{customerName}} {{invoiceNumber}} {{invoiceDate}} {{dueDate}} {{dueAmount}} {{invoiceLink}}",
                "email_status"        => 0,
                "sms_status"          => 0,
                "notification_status" => 0,
                "template_mode"       => 1,
            ],
            [
                "name"                => "Invoice Payment Received",
                "slug"                => "INVOICE_PAYMENT_RECEIVED",
                "subject"             => "Invoice Payment Received",
                "email_body"          => "<div style='font-family: Arial, sans-serif; font-size: 14px;'> <h2 style='color: #333333;'>Invoice Payment Received</h2> <p>Dear {{customerName}},</p> <p>I am writing to confirm that we have received your payment for invoice <strong>{{invoiceNumber}}</strong>.</p> <p>The total amount received is <strong>{{amount}}</strong>.</p> <p>Thank you for your prompt payment. We value your business and look forward to working with you again in the future.</p> <p>If you have any questions or concerns, please do not hesitate to contact us.</p></div>",
                "sms_body"            => "",
                "notification_body"   => "",
                "shortcode"           => "{{customerName}} {{amount}} {{invoiceDate}} {{paymentMethod}} {{invoiceNumber}} {{invoiceLink}}",
                "email_status"        => 0,
                "sms_status"          => 0,
                "notification_status" => 0,
                "template_mode"       => 1,
            ],
			[
                "name"                => "Trial Period Ended",
                "slug"                => "TRIAL_PERIOD_ENDED",
                "subject"             => "Dot Accounts Trial Ended",
                "email_body"          => "<div style='font-family: Arial, sans-serif; font-size: 14px;'> <h2 style='color: #333333;'>Dot Accounts Trial Ended</h2> <p>Dear {{customerName}},</p> <p>We hope this email finds you well. We wanted to remind you that your trial period has ended as of {{trialEndDate}}.</p> <p>We hope you found our service useful during the trial period. If you would like to continue using our service, please pay for subscription.</p> <p>If you have any questions or concerns, please do not hesitate to contact us. We are always here to help.</p> <p>Thank you for your interest in our service. We hope to continue serving you in the future.</p> </div>",
                "sms_body"            => "",
                "notification_body"   => "",
                "shortcode"           => "{{customerName}} {{trialEndDate}} {{packageName}}",
                "email_status"        => 0,
                "sms_status"          => 0,
                "notification_status" => 0,
                "template_mode"       => 1,
            ],
			[
                "name"                => "Subscription Reminder",
                "slug"                => "SUBSCRIPTION_REMINDER",
                "subject"             => "Subscription Reminder",
                "email_body"          => "<div style='font-family: Arial, sans-serif; font-size: 14px;'> <h2 style='color: #333333;'>Dot Accounts Renewal Reminder </h2> <p>Dear {{customerName}},</p> <p>We hope this email finds you well. We wanted to remind you that your subscription is expiring on {{expiryDate}}.</p> <p>If you want to continue using our service, please renew your subscription by visiting our website and selecting a subscription plan that suits your needs.</p> <p>If you have already renewed your subscription, please disregard this email. Otherwise, please renew your subscription before the expiry date to avoid any interruption in your service.</p> <p>If you have any questions or concerns, please do not hesitate to contact us. We are always here to help.</p> <p>Thank you for choosing our service. We appreciate your business and look forward to continuing to serve you.</p> </div>",
                "sms_body"            => "",
                "notification_body"   => "",
                "shortcode"           => "{{customerName}} {{expiryDate}} {{packageName}}",
                "email_status"        => 0,
                "sms_status"          => 0,
                "notification_status" => 0,
                "template_mode"       => 1,
            ],
			[
                "name"                => "Subscription Payment Confirmation",
                "slug"                => "SUBSCRIPTION_PAYMENT_CONFIRMATION",
                "subject"             => "Subscription Payment Confirmation",
                "email_body"          => "<div style='font-family: Arial, sans-serif; font-size: 14px;'> <h2 style='color: #333333;'>Dot Accounts Payment Confirmation</h2> <p>Dear {{customerName}},</p> <p>Thank you for renewing your subscription to (Package Name: {{packageName}}). Your payment has been received and your subscription has been renewed until {{expiryDate}}.</p> <p>You can now continue using our service without any interruption. If you have any questions or concerns, please do not hesitate to contact us. We are always here to help.</p> <p>Thank you for choosing our service. We appreciate your business and look forward to continuing to serve you.</p> </div>",
                "sms_body"            => "",
                "notification_body"   => "",
                "shortcode"           => "{{customerName}} {{expiryDate}} {{packageName}}",
                "email_status"        => 0,
                "sms_status"          => 0,
                "notification_status" => 0,
                "template_mode"       => 1,
            ],
        ]);
    }
}
