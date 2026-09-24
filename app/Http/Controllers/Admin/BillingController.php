<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Billing/Index', [
            'invoices' => Invoice::with('user')->latest()->paginate(15),
            'payments' => Payment::with('user')->latest()->take(10)->get(),
            'totalRevenue' => Payment::where('status', 'completed')->sum('amount') ?? 0,
        ]);
    }
}
