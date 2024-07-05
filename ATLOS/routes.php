<?php

use Illuminate\Support\Facades\Route;
use App\Helpers\ExtensionHelper;
use App\Models\Invoice;
use App\Extensions\Gateways\ATLOS\ATLOS;


Route::get('/atlos/payment/{order_id}', function ($order_id) {
    $conversion_rate = ExtensionHelper::getConfig('ATLOS', 'conversion_rate');
    $theme = ExtensionHelper::getConfig('ATLOS', 'theme');
    $invoice_id = ATLOS::extract_invoice_id($order_id);
    $invoice = Invoice::find($invoice_id);
    if (!$invoice) {
        return redirect()->route('clients.invoice.index')->with('error', 'Invoice not found');
    }
    $currency = ExtensionHelper::getCurrency();
    $total = isset($invoice->credits) ? $invoice->credits : $invoice->total();

    if ($conversion_rate != -1) {
        $total = $total * $conversion_rate;
    }

    $merchant_id = ExtensionHelper::getConfig('ATLOS', 'merchant_id');

    if (Invoice::find($invoice_id)->status == 'paid') {
        return redirect()->route('clients.invoice.show', $invoice_id)->with('error', 'Invoice already paid');
    }

    return view('ATLOS::payment', ['order_id' => $order_id, 'total' => $total, 'merchant_id' => $merchant_id, 'currency' => $currency, 'conversion_rate' => $conversion_rate, 'theme' => $theme]);

})->name('ATLOS.payment');

Route::get('/atlos/payment/cancel/{order_id}', function($order_id) {
    $invoice_id = ATLOS::extract_invoice_id($order_id);
    return redirect()->route('clients.invoice.show', $invoice_id)->with('error', 'Payment Cancelled');

})->name('ATLOS.cancel');

Route::get('/atlos/payment/success/{order_id}', function($order_id) {
    $invoice_id = ATLOS::extract_invoice_id($order_id);
    return redirect()->route('clients.invoice.show', $invoice_id)->with('success', 'Payment Successful');

})->name('ATLOS.success');

Route::post('/atlos/payment/webhook', [App\Extensions\Gateways\ATLOS\ATLOS::class, 'webhook'])->name('ATLOS.payment.webhook');
