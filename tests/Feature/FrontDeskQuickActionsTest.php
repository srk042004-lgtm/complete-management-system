<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\HotelPayment;
use App\Models\FoodCategory;
use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FrontDeskQuickActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_action_pages_load_for_authorized_hotel_admin(): void
    {
        [$tenant, $admin] = $this->hotelWithAdmin();

        $this->actingAs($admin)->get(route('frontdesk.guests.create'))->assertOk()->assertSee('Add Guest');
        $this->get(route('frontdesk.check-in'))->assertOk()->assertSee('Check-in');
        $this->get(route('frontdesk.in-house'))->assertOk()->assertSee('In-House Guests Directory');
        $this->get(route('frontdesk.check-out'))->assertOk()->assertSee('Check-out');
        $this->get(route('frontdesk.check-out.history'))->assertOk()->assertSee('Check-out History');
        $this->get(route('frontdesk.rooms.status'))->assertOk()->assertSee('Room Status');
        $this->get(route('frontdesk.payments.create'))->assertOk()->assertSee('Add Payment');
        $this->get(route('reservations.index'))->assertOk()->assertSee('Confirmed reservations');
    }

    public function test_guest_can_be_added_and_audit_log_is_written(): void
    {
        [$tenant, $admin] = $this->hotelWithAdmin();

        $this->actingAs($admin)
            ->post(route('frontdesk.guests.store'), [
                'name' => 'Farah Ali',
                'mobile' => '03001234567',
                'email' => 'farah@example.test',
                'opening_balance' => '25.00',
                'address' => 'Peshawar',
                'id_type' => 'cnic_national_id',
                'id_number' => '12345-1234567-1',
            ])
            ->assertRedirect(route('frontdesk.guests.create'))
            ->assertSessionHas('success');

        $guest = Guest::query()->where('email', 'farah@example.test')->firstOrFail();
        $this->assertSame($tenant->id, $guest->tenant_id);
        $this->assertSame('Farah Ali', $guest->full_name);
        $this->assertDatabaseHas('hotel_operation_logs', [
            'tenant_id' => $tenant->id,
            'actor_id' => $admin->id,
            'action' => 'guest.created',
            'subject_id' => $guest->id,
        ]);
    }

    public function test_check_in_posts_folio_charges_and_updates_room_and_booking(): void
    {
        [$tenant, $admin, $booking, $room] = $this->hotelWithBooking();

        $this->actingAs($admin)
            ->post(route('frontdesk.check-in.store', $booking))
            ->assertRedirect(route('frontdesk.check-in'))
            ->assertSessionHas('success');

        $this->assertSame('checked_in', $booking->fresh()->booking_status);
        $this->assertNotNull($booking->fresh()->actual_check_in);
        $this->assertSame('occupied', $room->fresh()->occupancy_status);
        $this->assertDatabaseHas('folios', [
            'booking_id' => $booking->id,
            'type' => 'charge',
            'item_category' => 'Room accommodation',
            'total' => 200,
        ]);
        $this->assertDatabaseHas('hotel_operation_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'booking.checked_in',
            'subject_id' => $booking->id,
        ]);
    }

    public function test_in_house_guest_charge_is_room_and_tenant_bound_and_immediate_payment_is_recorded(): void
    {
        [$tenant, $admin, $booking, $room] = $this->hotelWithBooking('checked_in');

        $this->actingAs($admin)->get(route('frontdesk.in-house'))
            ->assertOk()
            ->assertSee('Guest Example')
            ->assertSee('201')
            ->assertSee('Add Charge')
            ->assertSee('View Folio');

        $this->post(route('frontdesk.in-house.charges.store', $booking), [
            'room_id' => $room->id,
            'category' => 'food_beverage',
            'description' => 'Breakfast club sandwiches',
            'amount' => '750.00',
            'quantity' => 2,
            'payment_option' => 'paid_immediately',
        ])->assertRedirect(route('frontdesk.in-house'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('folios', [
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'room_id' => $room->id,
            'type' => 'charge',
            'amount' => 1500,
            'tax' => 150,
            'total' => 1650,
        ]);
        $this->assertDatabaseHas('folios', [
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'room_id' => $room->id,
            'type' => 'receipt',
            'amount' => 1650,
            'tax' => 150,
            'total' => 1650,
        ]);
        $this->assertDatabaseHas('hotel_payments', [
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'amount' => 1650,
            'payment_method' => 'cash',
        ]);
        $this->assertSame('1850.00', $booking->fresh()->grand_total);
        $this->assertSame('1650.00', $booking->fresh()->paid_amount);
        $this->assertSame('1700.00', $booking->fresh()->subtotal_amount);
        $this->assertSame('150.00', $booking->fresh()->tax_amount);
        $this->assertSame(2, $room->folios()->where('booking_id', $booking->id)->count());
        $this->assertDatabaseHas('hotel_operation_logs', [
            'tenant_id' => $tenant->id,
            'actor_id' => $admin->id,
            'action' => 'folio.charge_added',
        ]);
    }

    public function test_in_house_charge_can_be_added_to_room_bill_without_recording_payment(): void
    {
        [$tenant, $admin, $booking, $room] = $this->hotelWithBooking('checked_in');

        $this->actingAs($admin)->post(route('frontdesk.in-house.charges.store', $booking), [
            'room_id' => $room->id,
            'category' => 'laundry',
            'description' => 'Dry cleaning',
            'amount' => '500',
            'quantity' => 2,
            'payment_option' => 'post_to_room',
        ])->assertRedirect(route('frontdesk.in-house'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('folios', [
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'room_id' => $room->id,
            'type' => 'charge',
            'amount' => 1000,
            'tax' => 100,
            'total' => 1100,
        ]);
        $this->assertDatabaseCount('hotel_payments', 0);
        $this->assertDatabaseCount('folios', 1);
        $this->assertSame('1300.00', $booking->fresh()->grand_total);
        $this->assertSame('0.00', $booking->fresh()->paid_amount);
        $this->assertSame('1200.00', $booking->fresh()->subtotal_amount);
        $this->assertSame('100.00', $booking->fresh()->tax_amount);
        $this->assertSame(1, $room->folios()->where('booking_id', $booking->id)->count());

        $this->actingAs($admin)->get(route('frontdesk.in-house'))
            ->assertOk()
            ->assertSee('Item / service details')
            ->assertSee('Unit price')
            ->assertSee('Dry cleaning')
            ->assertSee('(Qty: 2)')
            ->assertSee('PKR 500.00')
            ->assertSee('PKR 1,000.00')
            ->assertSee('PKR 100.00')
            ->assertSee('PKR 1,100.00');
    }

    public function test_in_house_pos_shows_laundry_minibar_and_housekeeping_service_presets(): void
    {
        [$tenant, $admin] = $this->hotelWithBooking('checked_in');
        $foodCategory = FoodCategory::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Food & Beverage'],
            ['sort_order' => 0],
        );
        MenuItem::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'food_category_id' => $foodCategory->id, 'name' => 'Breakfast special'],
            ['description' => 'Breakfast menu item', 'price' => 250, 'billing_type' => 'one_time_order', 'is_available' => true],
        );

        $response = $this->actingAs($admin)->get(route('frontdesk.in-house'));
        $response->assertOk()
            ->assertSee('Laundry &amp; clothing', false)
            ->assertSee('Minibar &amp; drinks', false)
            ->assertSee('Housekeeping')
            ->assertSee('Shirt / Trouser Ironing')
            ->assertSee('Suit Dry Cleaning (2-Piece)')
            ->assertSee('Washing &amp; Folding (per KG)', false)
            ->assertSee('Express Steam Pressing')
            ->assertSee('Mineral Water (Large 1.5L)')
            ->assertSee('Extra Bed / Mattress')
            ->assertSee('data-filter="housekeeping"', false)
            ->assertSee('data-service-group="food"', false)
            ->assertSee('data-service-group="laundry"', false)
            ->assertSee('data-service-group="minibar"', false)
            ->assertSee('data-service-toggle aria-expanded="false"', false)
            ->assertSee('Food &amp; Dining', false)
            ->assertSee('Laundry &amp; Pressing', false)
            ->assertSee('data-service-items hidden', false)
            ->assertSee('.pos-product[hidden]{display:none!important}', false)
            ->assertSee('group.hidden = !matchesFilter;', false)
            ->assertSee('applyCategoryFilter(button.dataset.filter);', false)
            ->assertSee('data-pos-item data-menu-item', false)
            ->assertSee('aria-label="Select Shirt / Trouser Ironing"', false)
            ->assertSee('data-pos-option', false)
            ->assertSee('data-pos-multi-item', false)
            ->assertSee('data-add-selected-items disabled', false)
            ->assertSee('selectedMultiItems.set(String(product.dataset.id)', false)
            ->assertSee('selectedItems.forEach(addItem);', false)
            ->assertSee('No items selected for bulk add. Each selected item starts at quantity 1.', false)
            ->assertSee('data-pos-filter-empty', false)
            ->assertSee('data-category-group="food"', false)
            ->assertSee('data-category-group="laundry"', false)
            ->assertSee('data-category-group="minibar"', false)
            ->assertSee('data-category-group="housekeeping"', false)
            ->assertSee('selectedProduct = button;', false)
            ->assertSee('priceInput.value = button.dataset.price;', false)
            ->assertSee('categoryInput.value = selectedCategory;', false)
            ->assertSee('quantity: quantity,', false)
            ->assertSee('type: isCatalogItem ? \'menu\' : \'custom\',', false)
            ->assertSee('category: categoryInput.value,', false)
            ->assertSee('data-entry-subtotal', false)
            ->assertSee('data-entry-tax', false)
            ->assertSee('data-entry-total', false)
            ->assertSee('isSubmitting = true;', false)
            ->assertSee('submitButton.textContent = \'Posting charges...\';', false)
            ->assertSee('quantityInput.addEventListener(\'input\', updateEntryPreview);', false)
            ->assertSee('entrySubtotalElement.textContent = lineTotal.toLocaleString', false)
            ->assertSee("remove.setAttribute('aria-label', 'Remove item');", false)
            ->assertSee('payload.value = JSON.stringify(cart.map(function (item)', false)
            ->assertSee('data-save-order disabled', false)
            ->assertSee('name="payment_option" value="post_to_room"', false)
            ->assertSee('name="payment_option" value="paid_immediately"', false)
            ->assertSee('value="laundry"', false);
        $html = $response->getContent();
        $this->assertTrue(
            strpos($html, '</table>') < strpos($html, '<dialog class="inhouse-dialog pos-dialog"'),
            'The charge dialog must be outside the guest table so the browser keeps all fields inside its form.',
        );

        $this->assertDatabaseHas('menu_items', [
            'tenant_id' => $tenant->id,
            'name' => 'Shirt / Trouser Ironing',
            'price' => 150,
            'is_available' => true,
        ]);
    }

    public function test_in_house_extra_service_posts_the_tenant_catalog_price_to_guest_folio(): void
    {
        [$tenant, $admin, $booking, $room] = $this->hotelWithBooking('checked_in');
        $this->actingAs($admin)->get(route('frontdesk.in-house'))->assertOk();

        $service = MenuItem::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Shirt / Trouser Ironing')
            ->firstOrFail();

        $this->post(route('frontdesk.in-house.charges.store', $booking), [
            'room_id' => $room->id,
            'payment_option' => 'post_to_room',
            'cart_items' => json_encode([
                ['type' => 'menu', 'id' => $service->id, 'quantity' => 2, 'unit_price' => 1],
            ]),
        ])->assertRedirect(route('frontdesk.in-house'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('folios', [
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'room_id' => $room->id,
            'type' => 'charge',
            'item_category' => 'Laundry & Pressing: Shirt / Trouser Ironing (Qty: 2) · Room '.$room->room_number,
            'amount' => 300,
            'tax' => 30,
            'total' => 330,
        ]);
        $this->assertSame('530.00', $booking->fresh()->grand_total);
    }

    public function test_in_house_cart_uses_catalog_prices_and_records_multiple_card_paid_lines(): void
    {
        [$tenant, $admin, $booking, $room] = $this->hotelWithBooking('checked_in');
        $category = FoodCategory::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Room Service',
            'sort_order' => 1,
        ]);
        $menuItem = MenuItem::query()->create([
            'tenant_id' => $tenant->id,
            'food_category_id' => $category->id,
            'name' => 'Club sandwich',
            'description' => 'Toasted sandwich',
            'price' => 120,
            'billing_type' => 'one_time_order',
            'is_available' => true,
        ]);

        $this->actingAs($admin)->post(route('frontdesk.in-house.charges.store', $booking), [
            'room_id' => $room->id,
            'payment_option' => 'paid_immediately',
            'payment_method' => 'card',
            'cart_items' => json_encode([
                ['type' => 'menu', 'id' => $menuItem->id, 'quantity' => 2, 'unit_price' => 0.01],
                ['type' => 'custom', 'name' => 'Laundry', 'unit_price' => 50, 'category' => 'laundry', 'quantity' => 2, 'note' => 'Deliver to room'],
            ]),
        ])->assertRedirect(route('frontdesk.in-house'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('folios', [
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'type' => 'charge',
            'item_category' => 'Room Service: Club sandwich (Qty: 2) · Room '.$room->room_number,
            'amount' => 240,
            'tax' => 24,
            'total' => 264,
        ]);
        $this->assertDatabaseHas('folios', [
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'type' => 'charge',
            'item_category' => 'Laundry: Laundry (Qty: 2) · Room '.$room->room_number.' · Note: Deliver to room',
            'amount' => 100,
            'tax' => 10,
            'total' => 110,
        ]);
        $this->assertDatabaseHas('hotel_payments', [
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'amount' => 374,
            'payment_method' => 'card',
        ]);
        $this->assertSame('574.00', $booking->fresh()->grand_total);
        $this->assertSame('374.00', $booking->fresh()->paid_amount);
        $this->assertDatabaseCount('folios', 3);
    }

    public function test_in_house_cart_rejects_menu_items_owned_by_another_tenant(): void
    {
        [$tenant, $admin, $booking, $room] = $this->hotelWithBooking('checked_in');
        [$otherTenant] = $this->hotelWithAdmin('catalog-owner');
        $category = FoodCategory::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Private menu',
            'sort_order' => 1,
        ]);
        $menuItem = MenuItem::query()->create([
            'tenant_id' => $otherTenant->id,
            'food_category_id' => $category->id,
            'name' => 'Other hotel item',
            'price' => 1,
            'billing_type' => 'one_time_order',
            'is_available' => true,
        ]);

        $this->actingAs($admin)->post(route('frontdesk.in-house.charges.store', $booking), [
            'room_id' => $room->id,
            'payment_option' => 'post_to_room',
            'cart_items' => json_encode([
                ['type' => 'menu', 'id' => $menuItem->id, 'quantity' => 1],
            ]),
        ])->assertNotFound();

        $this->assertDatabaseCount('folios', 0);
        $this->assertNotSame($tenant->id, $otherTenant->id);
    }

    public function test_in_house_directory_excludes_other_tenant_and_charge_cannot_target_another_room(): void
    {
        [$tenant, $admin, $booking, $room] = $this->hotelWithBooking('checked_in');
        [$otherTenant, , $otherBooking, $otherRoom] = $this->hotelWithBooking('checked_in', 'other');

        $this->actingAs($admin)->get(route('frontdesk.in-house'))
            ->assertOk()
            ->assertSee('Guest Example')
            ->assertDontSee($otherBooking->booking_code);

        $this->post(route('frontdesk.in-house.charges.store', $booking), [
            'room_id' => $otherRoom->id,
            'category' => 'minibar',
            'description' => 'Water',
            'amount' => '100',
            'quantity' => 1,
            'payment_option' => 'post_to_room',
        ])->assertSessionHasErrors('room_id');

        $this->assertDatabaseCount('folios', 0);
        $this->assertNotSame($tenant->id, $otherTenant->id);
    }

    public function test_card_checkout_requires_a_transaction_id_refuses_duplicates_and_keeps_the_screenshot(): void
    {
        Storage::fake('public');
        [$tenant, $admin, $booking] = $this->hotelWithBooking('checked_in');
        $booking->forceFill(['actual_check_in' => now()])->save();
        DB::table('folios')->insert([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'invoice_number' => 'FOL-TEST-CARD',
            'type' => 'charge',
            'item_category' => 'Room accommodation',
            'amount' => 200,
            'tax' => 0,
            'total' => 200,
            'is_reversed' => false,
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('frontdesk.check-out.store', $booking), ['payment_method' => 'card'])
            ->assertSessionHasErrors('transaction_reference');
        $this->assertDatabaseCount('hotel_payments', 0);

        $this->actingAs($admin)->post(route('frontdesk.check-out.store', $booking), [
            'payment_method' => 'card',
            'transaction_reference' => 'TRX-DUP-1',
            'payment_proof' => UploadedFile::fake()->createWithContent('receipt.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=')),
        ])->assertSessionHasNoErrors();
        $payment = HotelPayment::query()->where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame('TRX-DUP-1', $payment->transaction_reference);
        $this->assertNotNull($payment->proof_path);
        Storage::disk('public')->assertExists($payment->proof_path);

        $otherBooking = Booking::query()->create(['tenant_id' => $tenant->id, 'guest_id' => $booking->guest_id, 'booking_code' => 'DUP-2', 'check_in_date' => now()->toDateString(), 'check_out_date' => now()->addDay()->toDateString(), 'booking_status' => 'checked_in', 'total_guests' => 1, 'adults' => 1, 'children' => 0, 'subtotal_amount' => 0, 'tax_amount' => 0, 'grand_total' => 0, 'paid_amount' => 0]);
        $otherBooking->rooms()->attach($booking->rooms->first()->id, ['price_per_night' => 5000, 'tenant_id' => $tenant->id]);
        DB::table('folios')->insert(['tenant_id' => $tenant->id, 'booking_id' => $otherBooking->id, 'invoice_number' => 'FOL-TEST-DUP', 'type' => 'charge', 'item_category' => 'Room accommodation', 'amount' => 100, 'tax' => 0, 'total' => 100, 'is_reversed' => false, 'created_at' => now()]);
        $this->actingAs($admin)->post(route('frontdesk.check-out.store', $otherBooking), [
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'TRX-DUP-1',
        ])->assertSessionHasErrors('transaction_reference');
    }

    public function test_checkout_settles_positive_folio_and_updates_booking_and_rooms_atomically(): void
    {
        [$tenant, $admin, $booking, $room] = $this->hotelWithBooking('checked_in');
        $booking->forceFill([
            'actual_check_in' => now(),
        ])->save();
        DB::table('folios')->insert([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'invoice_number' => 'FOL-TEST-CHARGE',
            'type' => 'charge',
            'item_category' => 'Room accommodation',
            'amount' => 200,
            'tax' => 0,
            'total' => 200,
            'is_reversed' => false,
            'created_at' => now(),
        ]);
        $room->forceFill(['occupancy_status' => 'occupied'])->save();
        $this->actingAs($admin)
            ->get(route('frontdesk.check-out'))
            ->assertOk()
            ->assertSee('Settle &amp; check out', false)
            ->assertSee('Total bill')
            ->assertSee('Paid to date')
            ->assertSee('Balance due')
            ->assertSee('Credit Card');

        $this->actingAs($admin)
            ->post(route('frontdesk.check-out.store', $booking))
            ->assertSessionHasErrors('payment_method');
        $this->assertSame('checked_in', $booking->fresh()->booking_status);
        $this->assertSame('occupied', $room->fresh()->occupancy_status);
        $this->assertDatabaseCount('hotel_payments', 0);

        $this->post(route('frontdesk.check-out.store', $booking), [
            'payment_method' => 'card',
            'transaction_reference' => 'TRX-TEST-1001',
        ])->assertRedirect(route('frontdesk.check-out.history'))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('hotel_payments', ['booking_id' => $booking->id, 'payment_method' => 'card', 'transaction_reference' => 'TRX-TEST-1001']);

        $this->assertSame('200.00', (string) $booking->fresh()->paid_amount);
        $this->assertDatabaseHas('hotel_payments', [
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'recorded_by' => $admin->id,
            'amount' => 200,
            'payment_method' => 'card',
        ]);
        $this->assertDatabaseHas('folios', [
            'booking_id' => $booking->id,
            'type' => 'receipt',
            'total' => 200,
            'item_category' => 'Checkout settlement - card',
        ]);
        $this->assertSame('checked_out', $booking->fresh()->booking_status);
        $this->assertSame('vacant', $room->fresh()->occupancy_status);
        $this->assertSame('dirty', $room->fresh()->housekeeping_status);
        $this->assertDatabaseHas('hotel_operation_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'payment.recorded',
        ]);
        $this->assertDatabaseHas('hotel_operation_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'booking.checked_out',
            'subject_id' => $booking->id,
        ]);
        $this->get(route('frontdesk.check-out.history'))
            ->assertOk()
            ->assertSee($booking->booking_code)
            ->assertSee('Check-out History');
    }

    public function test_fully_paid_folio_can_check_out_without_creating_an_extra_payment(): void
    {
        [$tenant, $admin, $booking, $room] = $this->hotelWithBooking('checked_in');
        DB::table('folios')->insert([
            [
                'tenant_id' => $tenant->id,
                'booking_id' => $booking->id,
                'invoice_number' => 'FOL-FULLY-PAID-CHARGE',
                'type' => 'charge',
                'item_category' => 'Room accommodation',
                'amount' => 200,
                'tax' => 0,
                'total' => 200,
                'is_reversed' => false,
                'created_at' => now(),
            ],
            [
                'tenant_id' => $tenant->id,
                'booking_id' => $booking->id,
                'invoice_number' => 'FOL-FULLY-PAID-RECEIPT',
                'type' => 'receipt',
                'item_category' => 'Payment received',
                'amount' => 200,
                'tax' => 0,
                'total' => 200,
                'is_reversed' => false,
                'created_at' => now(),
            ],
        ]);

        $this->actingAs($admin)
            ->post(route('frontdesk.check-out.store', $booking))
            ->assertRedirect(route('frontdesk.check-out.history'))
            ->assertSessionHas('success');

        $this->assertSame('checked_out', $booking->fresh()->booking_status);
        $this->assertSame('vacant', $room->fresh()->occupancy_status);
        $this->assertSame('dirty', $room->fresh()->housekeeping_status);
        $this->assertDatabaseCount('hotel_payments', 0);
        $this->assertDatabaseCount('folios', 2);
    }

    public function test_room_housekeeping_status_can_be_updated_with_audit_log(): void
    {
        [$tenant, $admin, , $room] = $this->hotelWithBooking();

        $this->actingAs($admin)
            ->patch(route('frontdesk.rooms.status.update', $room), [
                'housekeeping_status' => 'dirty',
                'category' => $room->room_type_id,
            ])
            ->assertRedirect(route('frontdesk.rooms.status', ['category' => $room->room_type_id]))
            ->assertSessionHas('success');

        $this->assertSame('dirty', $room->fresh()->housekeeping_status);
        $this->assertDatabaseHas('hotel_operation_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'room.status_updated',
            'subject_id' => $room->id,
        ]);
    }

    public function test_owner_cannot_use_mutating_quick_actions(): void
    {
        [$tenant, , $booking, $room] = $this->hotelWithBooking();
        $owner = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Read Only Owner',
            'email' => 'quick-actions-owner@example.test',
            'password' => bcrypt('Password123'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->post(route('frontdesk.check-in.store', $booking))
            ->assertForbidden();
        $this->patch(route('frontdesk.rooms.status.update', $room), ['housekeeping_status' => 'dirty'])
            ->assertForbidden();
        $this->post(route('frontdesk.payments.store'), [
            'booking_id' => $booking->id,
            'amount' => 1,
            'payment_method' => 'cash',
        ])->assertForbidden();
    }

    private function hotelWithAdmin(string $suffix = ''): array
    {
        $slug = 'quick-action-hotel'.($suffix === '' ? '' : '-'.$suffix);
        $tenant = Tenant::query()->create([
            'name' => 'Quick Action Hotel'.($suffix === '' ? '' : ' '.$suffix),
            'tenant_name' => 'Quick Action Hotel'.($suffix === '' ? '' : ' '.$suffix),
            'hotel_name' => 'Quick Action Hotel'.($suffix === '' ? '' : ' '.$suffix),
            'slug' => $slug,
            'email' => $slug.'@example.test',
            'hotel_type' => 'large',
            'enabled_modules' => [
                'rooms' => true,
                'reservations' => true,
                'front_desk' => true,
                'guest_management' => true,
                'housekeeping' => true,
                'finance' => true,
            ],
            'status' => 'active',
        ]);
        $admin = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Quick Action Admin',
            'email' => $slug.'-admin@example.test',
            'password' => bcrypt('Password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        return [$tenant, $admin];
    }

    private function hotelWithBooking(string $status = 'confirmed', string $suffix = ''): array
    {
        [$tenant, $admin] = $this->hotelWithAdmin($suffix);
        $guest = Guest::query()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Guest',
            'last_name' => 'Example',
            'phone' => '03001234567',
            'id_type' => 'cnic_national_id',
            'id_number' => '12345-1234567-1',
            'opening_balance' => 0,
        ]);
        $propertyId = DB::table('properties')->insertGetId([
            'property_name' => 'Quick Action Property'.($suffix === '' ? '' : ' '.$suffix),
            'currency_code' => 'PKR',
            'default_tax_rate' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $propertyId,
            'tenant_id' => $tenant->id,
            'type_name' => 'Standard',
            'title' => 'Standard Room',
            'base_capacity' => 2,
            'max_capacity' => 2,
            'adults' => 2,
            'children' => 0,
            'base_price_per_night' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $roomId = DB::table('rooms')->insertGetId([
            'tenant_id' => $tenant->id,
            'room_type_id' => $roomTypeId,
            'room_number' => '201'.($suffix === '' ? '' : '-'.strtoupper($suffix)),
            'floor' => 2,
            'occupancy_status' => $status === 'checked_in' ? 'occupied' : 'vacant',
            'housekeeping_status' => 'clean',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $booking = Booking::query()->create([
            'tenant_id' => $tenant->id,
            'guest_id' => $guest->id,
            'booking_code' => 'BK-QUICKACTION'.($suffix === '' ? '' : '-'.strtoupper($suffix)),
            'check_in_date' => today(),
            'check_out_date' => today()->addDays(2),
            'total_guests' => 1,
            'adults' => 1,
            'children' => 0,
            'booking_status' => $status,
            'subtotal_amount' => 200,
            'tax_amount' => 0,
            'grand_total' => 200,
            'paid_amount' => 0,
        ]);
        $booking->rooms()->attach($roomId, [
            'tenant_id' => $tenant->id,
            'price_per_night' => 100,
        ]);

        return [$tenant, $admin, $booking, Room::query()->findOrFail($roomId)];
    }
}
