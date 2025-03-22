<?php

namespace App\Extensions\Gateways\ATLOS;

use App\Classes\Extensions\Gateway;
use Illuminate\Http\Request;
use App\Helpers\ExtensionHelper;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

class ATLOS extends Gateway
{
    /**
    * Get the extension metadata
    * 
    * @return array
    */
    public function getMetadata()
    {
        return [
            'display_name' => 'ATLOS',
            'version' => '1.0.2',
            'author' => 'Vaibhav Dhiman',
            'website' => 'https://github.com/VaibhavSys/ATLOS-Paymenter-Extension',
        ];
    }
    
    /**
    * Get all the configuration for the extension
    * 
    * @return array
    */
    public function getConfig()
    {
        return [
            [
                'name' => 'order_id_prefix',
                'friendlyName' => 'Order ID Prefix',
                'type' => 'text',
                'description' => 'Order ID Prefix',
                'required' => false,
            ],
            [
                'name' => 'conversion_rate',
                'friendlyName' => 'Conversion Rate',
                'type' => 'text',
                'description' => 'Conversion Rate',
                'required' => true,
            ],
            [
                'name' => 'theme',
                'friendlyName' => 'Theme',
                'type' => 'dropdown',
                'description' => 'Theme',
                'required' => true,
                'options' => [
                    [
                        'name' => 'Light',
                        'value' => 'light',
                    ],
                    [
                        'name' => 'Dark',
                        'value' => 'dark',
                    ],
                ],
            ],
            [
                'name' => 'merchant_id',
                'friendlyName' => 'Merchant ID',
                'type' => 'text',
                'description' => 'Merchant ID',
                'required' => false,
            ],
            [
                'name' => 'api_secret',
                'friendlyName' => 'API Secret',
                'type' => 'text',
                'description' => 'API Secret',
                'required' => false,
            ],
        ];
    }
    
    /**
    * Get the URL to redirect to
    * 
    * @param int $total
    * @param array $products
    * @param int $invoiceId
    * @return string
    */
    public function pay($total, $products, $invoiceId)
    {
        $order_id = ExtensionHelper::getConfig('ATLOS', 'order_id_prefix') . $invoiceId;
        return route('ATLOS.payment', ['order_id' => $order_id]);
    }
    
    public static function extract_invoice_id($order_id)
    {
        $order_id_prefix = ExtensionHelper::getConfig('ATLOS', 'order_id_prefix');
        $invoice_id = (int) substr($order_id, strlen($order_id_prefix));
        return $invoice_id;
    }
    
    public function webhook(Request $request)
    {
        $api_secret = ExtensionHelper::getConfig('ATLOS', 'api_secret');
        $rawPostData = file_get_contents('php://input');
        $signature = $request->header('signature');
        Log::debug('ATLOS Webhook: Received request: ' . $rawPostData);
        
        if (!$signature) {
            Log::debug('ATLOS Webhook: Signature not found');
            return response()->json(['error' => 'Signature not found'], 400);
        }
        
        if (!$request->has('OrderId')) {
            Log::debug('ATLOS Webhook: Order ID not found');
            return response()->json(['error' => 'Order ID not found'], 400);
        }
        
        $calculatedSignature = base64_encode(hash_hmac('sha256', $rawPostData, $api_secret, true));
        
        if ($signature !== $calculatedSignature) {
            Log::debug('ATLOS Webhook: Signature mismatch. Received ' . $signature . ' Expected ' . $calculatedSignature);
            return response()->json(['error' => 'Payment verification failed'], 401);
        }
        
        $posted = $request->all();
        $order_id = $posted['OrderId'];
        $invoice_id = $this->extract_invoice_id($order_id);
        $invoice = Invoice::find($invoice_id);
        
        if (!$invoice) {
            Log::error('ATLOS Webhook: Invoice not found for order ID ' . $order_id);
            return response()->json(['error' => 'Invoice not found'], 401);
        }
        
        ExtensionHelper::paymentDone($invoice_id);
        Log::debug('ATLOS Webhook: Payment verification successful for' . $order_id);
        
        return response()->json(['success' => 'Payment verification successful'], 200);
    }
}
