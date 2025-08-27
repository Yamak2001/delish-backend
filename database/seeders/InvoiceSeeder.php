<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Merchant;
use App\Models\User;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $orders = Order::all();
        $merchants = Merchant::all();

        if (!$admin || $orders->isEmpty()) {
            $this->command->warn('Skipping InvoiceSeeder - missing dependencies');
            return;
        }

        $invoicesData = [
            [
                'order' => $orders->first(),
                'customer_name' => 'Sweet Dreams Cafe',
                'customer_email' => 'orders@sweetdreamscafe.com',
                'customer_address' => 'Rainbow Street 45, Amman, Jordan',
                'status' => 'paid',
                'payment_method' => 'bank_transfer',
                'payment_reference' => 'BT20250826001',
                'payment_date' => '2025-08-26',
                'due_days' => 30
            ],
            [
                'order' => $orders->skip(1)->first(),
                'customer_name' => 'Corner Bakery',
                'customer_email' => 'supply@cornerbakery.jo',
                'customer_address' => 'Downtown Circle, Salt, Jordan',
                'status' => 'sent',
                'due_days' => 15
            ],
            [
                'order' => $orders->skip(2)->first(),
                'customer_name' => 'Deluxe Events Catering',
                'customer_email' => 'events@deluxecatering.com',
                'customer_address' => 'Wedding Hall District, Amman, Jordan',
                'status' => 'draft',
                'due_days' => 7
            ],
            [
                'order' => $orders->skip(3)->first(),
                'customer_name' => 'The Cupcake Shop',
                'customer_email' => 'orders@cupcakeshop.jo',
                'customer_address' => 'Mecca Street 123, Amman, Jordan',
                'status' => 'paid',
                'payment_method' => 'cash',
                'payment_reference' => 'CASH20250825001',
                'payment_date' => '2025-08-25',
                'due_days' => 0
            ],
            [
                'order' => $orders->skip(4)->first(),
                'customer_name' => 'Hotel Plaza Restaurant',
                'customer_email' => 'pastry@hotelplaza.com',
                'customer_address' => 'Hotel Plaza, King Hussein Street, Amman',
                'status' => 'overdue',
                'due_days' => 30
            ],
            [
                'order' => $orders->skip(5)->first(),
                'customer_name' => 'Sarah Ahmad',
                'customer_email' => 'sarah.ahmad@gmail.com',
                'customer_address' => 'Jubeiha Area, House 45, Amman',
                'status' => 'sent',
                'due_days' => 0 // Immediate payment
            ],
            // Additional invoices for regular business
            [
                'order' => null, // Direct sale
                'customer_name' => 'Al-Noor Restaurant',
                'customer_email' => 'orders@alnoor.jo',
                'customer_address' => 'University Street, Amman',
                'status' => 'paid',
                'payment_method' => 'credit_card',
                'payment_reference' => 'CC20250824001',
                'payment_date' => '2025-08-24',
                'due_days' => 15,
                'direct_items' => [
                    ['description' => 'Daily Bread (Loaves)', 'quantity' => 50, 'unit_price' => 1.50],
                    ['description' => 'Dinner Rolls', 'quantity' => 100, 'unit_price' => 0.75]
                ]
            ],
            [
                'order' => null, // Catering service
                'customer_name' => 'Golden Wedding Events',
                'customer_email' => 'catering@goldenwedding.com',
                'customer_address' => 'Event District, Zarqa',
                'status' => 'sent',
                'due_days' => 45, // Extended terms for large events
                'direct_items' => [
                    ['description' => 'Wedding Cake (3-tier)', 'quantity' => 1, 'unit_price' => 200.00],
                    ['description' => 'Mini Pastries Assortment', 'quantity' => 300, 'unit_price' => 2.25],
                    ['description' => 'Catering Service Fee', 'quantity' => 1, 'unit_price' => 150.00]
                ]
            ]
        ];

        foreach ($invoicesData as $index => $invoiceData) {
            $invoiceDate = now()->subDays(rand(1, 30));
            $dueDate = $invoiceDate->copy()->addDays($invoiceData['due_days']);

            $invoice = Invoice::create([
                'invoice_number' => 'INV-' . str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                'order_id' => $invoiceData['order']->id ?? null,
                'merchant_id' => $merchants->where('business_name', $invoiceData['customer_name'])->first()->id ?? null,
                'customer_name' => $invoiceData['customer_name'],
                'customer_email' => $invoiceData['customer_email'],
                'customer_address' => $invoiceData['customer_address'],
                'invoice_date' => $invoiceDate->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'currency' => 'JOD',
                'payment_terms' => $invoiceData['due_days'] > 0 ? 'Net ' . $invoiceData['due_days'] : 'Immediate',
                'payment_method' => $invoiceData['payment_method'] ?? null,
                'payment_reference' => $invoiceData['payment_reference'] ?? null,
                'payment_date' => $invoiceData['payment_date'] ?? null,
                'status' => $invoiceData['status'],
                'notes' => match($invoiceData['customer_name']) {
                    'Sweet Dreams Cafe' => 'Regular customer - 5% volume discount applied',
                    'Hotel Plaza Restaurant' => 'Corporate account - monthly billing cycle',
                    'Deluxe Events Catering' => 'VIP event - premium service charge included',
                    default => 'Thank you for your business with Delish ERP!'
                },
                'created_by' => $admin->id
            ]);

            $subtotal = 0;

            if ($invoiceData['order']) {
                // Create invoice items from order items JSON
                foreach ($invoiceData['order']->order_items as $orderItem) {
                    $lineTotal = $orderItem['total_price'];
                    $subtotal += $lineTotal;

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'order_item_id' => null, // No separate order items table
                        'description' => $orderItem['recipe_name'],
                        'item_details' => 'Fresh baked item',
                        'quantity' => $orderItem['quantity'],
                        'unit_price' => $orderItem['unit_price'],
                        'line_total' => $lineTotal,
                        'tax_rate' => 16.0 // 16% VAT
                    ]);
                }
            } else {
                // Create invoice items from direct items
                foreach ($invoiceData['direct_items'] ?? [] as $itemData) {
                    $lineTotal = $itemData['quantity'] * $itemData['unit_price'];
                    $subtotal += $lineTotal;

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'order_item_id' => null,
                        'description' => $itemData['description'],
                        'item_details' => 'Direct sale item',
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'line_total' => $lineTotal,
                        'tax_rate' => 16.0
                    ]);
                }
            }

            // Apply discount for regular customers
            $discountAmount = 0;
            if (in_array($invoiceData['customer_name'], ['Sweet Dreams Cafe', 'The Cupcake Shop'])) {
                $discountAmount = $subtotal * 0.05; // 5% discount
            }

            $discountedSubtotal = $subtotal - $discountAmount;
            $taxAmount = $discountedSubtotal * 0.16; // 16% VAT
            $totalAmount = $discountedSubtotal + $taxAmount;

            $amountPaid = match($invoiceData['status']) {
                'paid' => $totalAmount,
                'overdue' => 0,
                default => 0
            };

            $balanceDue = $totalAmount - $amountPaid;

            $invoice->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
                'balance_due' => $balanceDue
            ]);
        }

        $totalInvoiceValue = Invoice::sum('total_amount');
        $totalPaid = Invoice::sum('amount_paid');
        $totalOutstanding = Invoice::sum('balance_due');

        $this->command->info('✅ Created ' . count($invoicesData) . ' invoices with line items');
        $this->command->info('   • Status: Paid(' . Invoice::where('status', 'paid')->count() . 
                           '), Sent(' . Invoice::where('status', 'sent')->count() . 
                           '), Draft(' . Invoice::where('status', 'draft')->count() . 
                           '), Overdue(' . Invoice::where('status', 'overdue')->count() . ')');
        $this->command->info('   • Total value: ' . number_format($totalInvoiceValue, 2) . ' JOD');
        $this->command->info('   • Amount paid: ' . number_format($totalPaid, 2) . ' JOD');
        $this->command->info('   • Outstanding: ' . number_format($totalOutstanding, 2) . ' JOD');
    }
}