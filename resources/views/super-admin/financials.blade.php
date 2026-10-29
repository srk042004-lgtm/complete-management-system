@extends('super-admin.layouts.app')

@section('title', 'SaaS Financials & Payroll Management')
@section('heading', 'SaaS Financials & Payroll Management')

@section('content')
    <div class="space-y-5 pb-20">
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl bg-white/80 p-4 shadow-sm border border-slate-100">
                <div class="text-xs font-semibold uppercase text-slate-500">Monthly Revenue</div>
                <div class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($monthlyRevenue,2) }}</div>
                <div class="text-xs text-slate-500">Subscriptions & Modules (This Month)</div>
            </div>

            <div class="rounded-2xl bg-white/80 p-4 shadow-sm border border-slate-100">
                <div class="text-xs font-semibold uppercase text-slate-500">Monthly Team Salaries</div>
                <div class="mt-2 text-2xl font-bold text-rose-600">{{ number_format($monthlyPayroll,2) }}</div>
                <div class="text-xs text-slate-500">Payroll (This Month)</div>
            </div>

            <div class="rounded-2xl bg-white/80 p-4 shadow-sm border border-slate-100">
                <div class="text-xs font-semibold uppercase text-slate-500">Net Monthly Savings</div>
                <div class="mt-2 text-2xl font-bold text-sky-700">{{ number_format($monthlyNet,2) }}</div>
                <div class="text-xs text-slate-500">Revenue minus Payroll (This Month)</div>
            </div>

            <div class="rounded-2xl bg-white/80 p-4 shadow-sm border border-slate-100">
                <div class="text-xs font-semibold uppercase text-slate-500">Net Annual Savings</div>
                <div class="mt-2 text-2xl font-bold text-amber-700">{{ number_format($annualNet,2) }}</div>
                <div class="text-xs text-slate-500">Year-to-date Net Savings</div>
            </div>
        </section>

        <section class="mt-4 bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-semibold text-slate-900">Financial Ledgers</h2>
                    <p class="text-sm text-slate-500">Manage inflow and payroll</p>
                </div>
                <div class="flex items-center gap-2">
                    <button data-tab="payments" class="tab-btn px-3 py-2 rounded-lg bg-gradient-to-r from-[#a8c0ff] to-[#3f2b96] text-white">Tenant Payments</button>
                    <button data-tab="salaries" class="tab-btn px-3 py-2 rounded-lg bg-slate-100">Team Salaries</button>
                    <button data-tab="savings" class="tab-btn px-3 py-2 rounded-lg bg-slate-100">Savings</button>
                </div>
            </div>

            <div class="mt-4">
                <div id="tab-payments" class="tab-panel">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <a href="#" data-modal="tenantPaymentModal" class="rounded-xl bg-gradient-to-r from-[#a8c0ff] to-[#3f2b96] px-3 py-2 text-sm font-semibold text-white">+ Record Hotel Payment</a>
                        </div>
                        <form method="GET" class="flex items-center gap-2">
                            <input name="hotel" placeholder="Search Hotel" value="{{ request('hotel') }}" class="rounded-lg border px-3 py-2" />
                            <button class="rounded-lg bg-slate-900 text-white px-3 py-2">Filter</button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-slate-500">
                                <tr>
                                    <th class="px-3 py-2 text-left">Tenant</th>
                                    <th class="px-3 py-2 text-left">Type</th>
                                    <th class="px-3 py-2 text-right">Amount</th>
                                    <th class="px-3 py-2 text-left">Date</th>
                                    <th class="px-3 py-2 text-left">Method</th>
                                    <th class="px-3 py-2 text-left">Status</th>
                                    <th class="px-3 py-2 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ledgerEntries as $entry)
                                    <tr class="border-t">
                                        <td class="px-3 py-3">{{ $entry->tenant?->tenant_name ?? 'Platform' }}</td>
                                        <td class="px-3 py-3">{{ ucfirst($entry->type) }}</td>
                                        <td class="px-3 py-3 text-right">{{ $entry->currency }} {{ number_format($entry->amount,2) }}</td>
                                        <td class="px-3 py-3">{{ optional($entry->payment_date)->format('Y-m-d') }}</td>
                                        <td class="px-3 py-3">{{ $entry->payment_method }}</td>
                                        <td class="px-3 py-3">{{ ucfirst($entry->status) }}</td>
                                        <td class="px-3 py-3">
                                            <a href="#" class="text-slate-600">Download</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">{{ $ledgerEntries->links() }}</div>
                </div>

                <div id="tab-salaries" class="tab-panel hidden">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <a href="#" data-modal="salaryPaymentModal" class="rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white">+ Pay Team Salary</a>
                        </div>
                        <form method="GET" class="flex items-center gap-2">
                            <input name="employee" placeholder="Search Employee" value="{{ request('employee') }}" class="rounded-lg border px-3 py-2" />
                            <button class="rounded-lg bg-slate-900 text-white px-3 py-2">Filter</button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-slate-500">
                                <tr>
                                    <th class="px-3 py-2 text-left">Employee</th>
                                    <th class="px-3 py-2 text-left">Designation</th>
                                    <th class="px-3 py-2 text-left">Month</th>
                                    <th class="px-3 py-2 text-right">Net Salary</th>
                                    <th class="px-3 py-2 text-left">Paid Date</th>
                                    <th class="px-3 py-2 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salaries as $row)
                                    <tr class="border-t">
                                        <td class="px-3 py-3">{{ $row->employee_name }}</td>
                                        <td class="px-3 py-3">{{ $row->designation }}</td>
                                        <td class="px-3 py-3">{{ $row->payment_month }}</td>
                                        <td class="px-3 py-3 text-right">{{ number_format($row->net_salary,2) }}</td>
                                        <td class="px-3 py-3">{{ optional($row->payment_date)->format('Y-m-d') }}</td>
                                        <td class="px-3 py-3">{{ ucfirst($row->status) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">{{ $salaries->links() }}</div>
                </div>

                <div id="tab-savings" class="tab-panel hidden">
                    <canvas id="savingsChart" height="120"></canvas>
                </div>
            </div>
        </section>
    </div>

    <!-- Tenant Payment Modal -->
    <div id="tenantPaymentModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40">
        <div class="bg-white rounded-xl p-6 w-full max-w-2xl">
            <h3 class="text-lg font-semibold">Record Hotel Payment</h3>
            <form method="POST" action="{{ route('super-admin.financials.tenant-payment') }}" class="grid gap-3 mt-3">
                @csrf
                <div class="grid sm:grid-cols-2 gap-3">
                    <select name="tenant_id" required class="rounded-lg border px-3 py-2">
                        <option value="">Select Tenant</option>
                        @foreach($tenants as $t)
                            <option value="{{ $t->id }}">{{ $t->tenant_name }}</option>
                        @endforeach
                    </select>
                    <input name="title" placeholder="Title" class="rounded-lg border px-3 py-2" required />
                </div>
                <div class="grid sm:grid-cols-3 gap-3">
                    <input name="amount" type="number" step="0.01" placeholder="Amount" class="rounded-lg border px-3 py-2" required />
                    <input name="currency" placeholder="Currency (PKR)" class="rounded-lg border px-3 py-2" value="PKR" />
                    <select name="payment_method" class="rounded-lg border px-3 py-2">
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="stripe">Stripe</option>
                        <option value="easypaisa">Easypaisa</option>
                        <option value="jazzcash">JazzCash</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>
                <div class="grid sm:grid-cols-2 gap-3">
                    <input name="payment_date" type="date" class="rounded-lg border px-3 py-2" value="{{ now()->format('Y-m-d') }}" required />
                    <input name="transaction_ref" placeholder="Transaction Ref" class="rounded-lg border px-3 py-2" />
                </div>
                <div class="flex items-center gap-3">
                    <input name="status" value="completed" type="hidden" />
                    <input name="extend_days" type="number" placeholder="Extend days (30)" class="rounded-lg border px-3 py-2" />
                    <button type="submit" class="rounded-lg bg-emerald-600 text-white px-4 py-2">Save Payment</button>
                    <button type="button" data-modal-close class="rounded-lg border px-4 py-2">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Salary Payment Modal -->
    <div id="salaryPaymentModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40">
        <div class="bg-white rounded-xl p-6 w-full max-w-2xl">
            <h3 class="text-lg font-semibold">Pay Team Salary</h3>
            <form method="POST" action="{{ route('super-admin.financials.salary-payment') }}" class="grid gap-3 mt-3">
                @csrf
                <div class="grid sm:grid-cols-2 gap-3">
                    <input name="employee_name" placeholder="Employee Name" class="rounded-lg border px-3 py-2" required />
                    <input name="designation" placeholder="Designation" class="rounded-lg border px-3 py-2" />
                </div>
                <div class="grid sm:grid-cols-3 gap-3">
                    <input name="base_salary" type="number" step="0.01" placeholder="Base Salary" class="rounded-lg border px-3 py-2" required />
                    <input name="bonus_allowances" type="number" step="0.01" placeholder="Bonus" class="rounded-lg border px-3 py-2" value="0" />
                    <input name="deductions" type="number" step="0.01" placeholder="Deductions" class="rounded-lg border px-3 py-2" value="0" />
                </div>
                <div class="grid sm:grid-cols-2 gap-3">
                    <input name="net_salary" type="number" step="0.01" placeholder="Net Salary" class="rounded-lg border px-3 py-2" required />
                    <input name="payment_month" type="month" class="rounded-lg border px-3 py-2" required />
                </div>
                <div class="flex items-center gap-3">
                    <input name="payment_date" type="date" class="rounded-lg border px-3 py-2" value="{{ now()->format('Y-m-d') }}" required />
                    <select name="status" class="rounded-lg border px-3 py-2">
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                    </select>
                    <button type="submit" class="rounded-lg bg-emerald-600 text-white px-4 py-2">Record Salary</button>
                    <button type="button" data-modal-close class="rounded-lg border px-4 py-2">Cancel</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('bg-gradient-to-r'));
                const t = btn.getAttribute('data-tab');
                document.getElementById('tab-' + t).classList.remove('hidden');
            });
        });

        // modal open/close
        document.querySelectorAll('[data-modal]').forEach(el => el.addEventListener('click', (e) => {
            e.preventDefault();
            document.getElementById(el.getAttribute('data-modal')).classList.remove('hidden');
        }));
        document.querySelectorAll('[data-modal-close]').forEach(el => el.addEventListener('click', () => {
            el.closest('[id]').classList.add('hidden');
            document.querySelectorAll('#tenantPaymentModal, #salaryPaymentModal').forEach(m => m.classList.add('hidden'));
        }));

        // Chart
        const labels = {!! json_encode($labels) !!};
        const revenue = {!! json_encode($revenueSeries) !!};
        const payroll = {!! json_encode($payrollSeries) !!};
        const net = revenue.map((v,i) => v - payroll[i]);

        const ctx = document.getElementById('savingsChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Revenue', data: revenue, borderColor: '#10b981', backgroundColor: '#d1fae5', tension: 0.2 },
                        { label: 'Payroll', data: payroll, borderColor: '#ef4444', backgroundColor: '#fee2e2', tension: 0.2 },
                        { label: 'Net', data: net, borderColor: '#3b82f6', backgroundColor: '#dbeafe', tension: 0.2 },
                    ]
                },
                options: { responsive: true, interaction: { mode: 'index', intersect: false } }
            });
        }
    </script>
@endpush
