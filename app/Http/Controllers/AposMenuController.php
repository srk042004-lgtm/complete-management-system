<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AposMenuController extends Controller
{
    /**
     * @var array<string, array{title: string, icon: string, eyebrow: string, summary: string, sections: array<int, array{title: string, text: string}>}>
     */
    private const PAGES = [
        'customers-with-phone' => [
            'title' => 'Customers With Phone',
            'icon' => '☎',
            'eyebrow' => 'APOS CUSTOMER WORKSPACE',
            'summary' => 'An overview of customer lookup by phone number and the customer-service workflows available in APOS.',
            'sections' => [
                ['title' => 'Find customer records', 'text' => 'The APOS customer workspace is designed to help authorized teams locate a customer record using contact details supplied to their organization.'],
                ['title' => 'Keep service context together', 'text' => 'Where customer history is available in a configured APOS workspace, teams can use it to support more consistent service and follow-up.'],
                ['title' => 'Respect customer privacy', 'text' => 'Customer information belongs in an authorized business workspace. This public page does not display customer records or phone numbers.'],
                ['title' => 'Request a walkthrough', 'text' => 'Contact ArtFlow to discuss customer-management workflows and confirm what is available for your APOS setup.'],
            ],
        ],
        'products' => [
            'title' => 'APOS Products',
            'icon' => '▣',
            'eyebrow' => 'HARDWARE & SOFTWARE',
            'summary' => 'Explore point-of-sale hardware, peripherals, and software options with the ArtFlow team.',
            'sections' => [
                ['title' => 'POS terminals', 'text' => 'Discuss terminal configurations suitable for your counter layout, operating environment, and day-to-day workload.'],
                ['title' => 'Printers and peripherals', 'text' => 'Ask about receipt printers, scanners, and other peripherals, including compatibility with your existing equipment.'],
                ['title' => 'Software and licensing', 'text' => 'Review software options and licensing needs for your organization before choosing a deployment.'],
                ['title' => 'Availability and pricing', 'text' => 'Product availability, specifications, and pricing can change. Contact ArtFlow for a current quote rather than relying on sample figures.'],
            ],
        ],
        'invoice-templates' => [
            'title' => 'Invoice Templates',
            'icon' => '▤',
            'eyebrow' => 'BILLING & RECEIPTS',
            'summary' => 'Learn about invoice and receipt layouts that can be reviewed for your business and point-of-sale workflow.',
            'sections' => [
                ['title' => 'Receipt layouts', 'text' => 'Review compact receipt formats and the business details that should appear on customer-facing bills.'],
                ['title' => 'Invoice presentation', 'text' => 'Discuss branded invoice layouts, line-item presentation, and the information your team needs for record keeping.'],
                ['title' => 'Tax requirements', 'text' => 'Tax fields and invoice wording depend on your jurisdiction and business obligations. Confirm requirements with your qualified tax adviser.'],
                ['title' => 'Request a template review', 'text' => 'Share your current invoice or receipt requirements with ArtFlow to discuss suitable layout options.'],
            ],
        ],
        'essential-softwares' => [
            'title' => 'Essential Software & Drivers',
            'icon' => '↓',
            'eyebrow' => 'SETUP UTILITIES',
            'summary' => 'Find out how to obtain supported drivers and setup utilities for your APOS equipment.',
            'sections' => [
                ['title' => 'Printer drivers', 'text' => 'The correct driver depends on your printer model, connection type, and operating system. Confirm the exact package before installing it.'],
                ['title' => 'Device utilities', 'text' => 'Some devices require manufacturer setup or configuration utilities. Use software only from a trusted, verified source.'],
                ['title' => 'No unverified downloads', 'text' => 'This page does not host executable files. Contact ArtFlow with your device model to ask for the current supported download source.'],
                ['title' => 'Installation support', 'text' => 'If setup is unsuccessful, provide the device model and operating-system version when requesting assistance.'],
            ],
        ],
        'software-requirements' => [
            'title' => 'Software Requirements',
            'icon' => '✓',
            'eyebrow' => 'COMPATIBILITY & PREPARATION',
            'summary' => 'Review the details to confirm before selecting an APOS configuration for your environment.',
            'sections' => [
                ['title' => 'Operating environment', 'text' => 'Supported operating systems and versions depend on the APOS edition and deployment. Confirm compatibility before purchasing or upgrading equipment.'],
                ['title' => 'Hardware configuration', 'text' => 'Processor, memory, storage, display, and network needs vary with the selected setup and expected workload.'],
                ['title' => 'Connected devices', 'text' => 'Verify the model and interface for each printer, scanner, cash drawer, or other peripheral you plan to connect.'],
                ['title' => 'Confirm before deployment', 'text' => 'Contact ArtFlow with your current equipment list to review compatibility for your specific configuration.'],
            ],
        ],
        'payments-return-policy' => [
            'title' => 'Payments & Return Policy',
            'icon' => '↻',
            'eyebrow' => 'ORDERS, RETURNS & SUPPORT',
            'summary' => 'Get in touch with ArtFlow to confirm payment, return, and warranty terms for a specific order.',
            'sections' => [
                ['title' => 'Payment terms', 'text' => 'Accepted payment methods, due dates, and applicable charges should be confirmed in the written quote or order confirmation.'],
                ['title' => 'Returns', 'text' => 'Return eligibility and time limits depend on the product and order terms. Obtain written approval and return instructions before sending an item back.'],
                ['title' => 'Hardware warranty', 'text' => 'Warranty coverage is subject to the manufacturer and the terms provided with the product or order. Keep proof of purchase and device details.'],
                ['title' => 'Ask before ordering', 'text' => 'For terms that apply to your purchase, contact ArtFlow and refer to the written order or subscription agreement.'],
            ],
        ],
        'privacy-policy' => [
            'title' => 'ArtFlow Privacy Policy',
            'icon' => '◆',
            'eyebrow' => 'PRIVACY & DATA HANDLING',
            'summary' => 'An overview of privacy considerations when using ArtFlow services and contacting our team.',
            'sections' => [
                ['title' => 'Information you provide', 'text' => 'Information submitted through registration, support, or contact forms is used to respond to your request and administer the related service.'],
                ['title' => 'Workspace information', 'text' => 'Businesses are responsible for ensuring they have an appropriate basis to enter and use personal information in their workspace. Access should be limited to authorized users.'],
                ['title' => 'Service providers', 'text' => 'Technical providers may process information as needed to operate or support the service. Ask ArtFlow for details relevant to your deployment.'],
                ['title' => 'Privacy questions', 'text' => 'Contact ArtFlow with questions about information submitted through this site or a specific service. Applicable signed agreements and local law govern where relevant.'],
            ],
        ],
        'terms-of-service' => [
            'title' => 'Terms of Service',
            'icon' => '§',
            'eyebrow' => 'SERVICE & ACCOUNT GUIDANCE',
            'summary' => 'General information for organizations evaluating ArtFlow services. Your written order and applicable agreement set the binding terms.',
            'sections' => [
                ['title' => 'Account responsibilities', 'text' => 'Organizations should provide accurate account information, protect credentials, and promptly review who has access to their workspace.'],
                ['title' => 'Acceptable use', 'text' => 'Use the service lawfully and do not attempt to disrupt its operation, access another organization’s data, or misuse the service.'],
                ['title' => 'Service configuration', 'text' => 'Features, availability, support, and fees depend on the selected system, plan, and written order. Confirm the details before subscribing.'],
                ['title' => 'Applicable agreement', 'text' => 'This overview is not a substitute for a signed contract or subscription agreement. Contact ArtFlow for the terms that apply to your account.'],
            ],
        ],
    ];

    public function show(string $page): View
    {
        abort_unless(isset(self::PAGES[$page]), 404);

        return view('apos.pages', ['page' => self::PAGES[$page]]);
    }
}
