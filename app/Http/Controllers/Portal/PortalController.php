<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function index(): View
    {
        return view('portal', [
            'features' => [
                ['title' => 'Hotel Operations', 'desc' => 'Coordinate daily workflows across rooms, guests, reservations, and services.'],
                ['title' => 'Guest Management', 'desc' => 'Keep guest details and service history organized in one workspace.'],
                ['title' => 'Reservations', 'desc' => 'Manage bookings, check-ins, check-outs, and room availability.'],
                ['title' => 'Inventory Control', 'desc' => 'Track stock and operational supplies with clear records.'],
                ['title' => 'Payments & Billing', 'desc' => 'Keep payment activity and billing records connected to operations.'],
                ['title' => 'Team Workflows', 'desc' => 'Coordinate staff access and responsibilities across daily tasks.'],
                ['title' => 'Analytics & Reporting', 'desc' => 'Review operational activity and performance through clear reports.'],
                ['title' => 'Connected Systems', 'desc' => 'Bring teams, branches, and business workflows together.'],
            ],
            'products' => [
                ['id' => 'hotel', 'symbol' => '⌂', 'name' => 'Hotel Management', 'desc' => 'Coordinate reservations, rooms, guests, payments, and housekeeping.'],
                ['id' => 'clinic', 'symbol' => '✚', 'name' => 'Clinic Management', 'desc' => 'Organize patient care, appointments, billing, and clinic operations.'],
                ['id' => 'school', 'symbol' => '▤', 'name' => 'School Management', 'desc' => 'Connect student, staff, academic, and administrative workflows.'],
                ['id' => 'restaurant', 'symbol' => '◈', 'name' => 'Restaurant Management', 'desc' => 'Manage dining operations, orders, inventory, and reporting.'],
                ['id' => 'retail-pos', 'symbol' => '▣', 'name' => 'Retail & POS Management', 'desc' => 'Connect sales, point of sale, inventory, and business records.'],
            ],
            'reasons' => self::reasons(),
        ]);
    }

    public function whyArtflow(): View
    {
        return view('why-artflow', ['reasons' => self::reasons()]);
    }

    public function pricing(): View
    {
        return view('pricing');
    }

    public function features(): View
    {
        return view('features');
    }

    public function pricingCalculator(): View
    {
        return view('pricing-calculator');
    }

    /**
     * @return array<int, array{title: string, description: string, icon: string, category: string}>
     */
    private static function reasons(): array
    {
        return [
            ['title' => 'Connected Operations', 'description' => 'Bring essential business workflows together in one organized workspace.', 'icon' => 'connected', 'category' => 'operations'],
            ['title' => 'Clear Analytics', 'description' => 'Use timely reports and dashboards to understand business performance.', 'icon' => 'analytics', 'category' => 'analytics'],
            ['title' => 'Faster Workflows', 'description' => 'Keep everyday tasks moving with connected operational tools.', 'icon' => 'workflow', 'category' => 'operations'],
            ['title' => 'Reliable Records', 'description' => 'Keep important operational information organized and accessible.', 'icon' => 'records', 'category' => 'operations'],
            ['title' => 'Practical Control', 'description' => 'Coordinate access, responsibilities, and activity across your team.', 'icon' => 'control', 'category' => 'operations'],
            ['title' => 'Better Customer Experience', 'description' => 'Support consistent service with organized customer and guest information.', 'icon' => 'experience', 'category' => 'experience'],
            ['title' => 'Financial Visibility', 'description' => 'Keep billing and payment activity connected to your operations.', 'icon' => 'finance', 'category' => 'finance'],
            ['title' => 'Flexible Growth', 'description' => 'Build on a practical foundation as your organization and workflows grow.', 'icon' => 'growth', 'category' => 'operations'],
        ];
    }
}
