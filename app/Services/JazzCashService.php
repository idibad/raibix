<?php

namespace App\Services;

use App\Models\FeeChallan;
use Illuminate\Support\Facades\Log;

// JazzCash Configuration
if (!defined('JAZZCASH_ENVIRONMENT')) {
    define('JAZZCASH_ENVIRONMENT', $_ENV['JAZZCASH_ENVIRONMENT'] ?? ($_SERVER['JAZZCASH_ENVIRONMENT'] ?? (getenv('JAZZCASH_ENVIRONMENT') ?: 'sandbox')));
}
if (!defined('JAZZCASH_MERCHANT_ID')) {
    define('JAZZCASH_MERCHANT_ID', $_ENV['JAZZCASH_MERCHANT_ID'] ?? ($_SERVER['JAZZCASH_MERCHANT_ID'] ?? (getenv('JAZZCASH_MERCHANT_ID') ?: 'MC12345')));
}
if (!defined('JAZZCASH_PASSWORD')) {
    define('JAZZCASH_PASSWORD', $_ENV['JAZZCASH_PASSWORD'] ?? ($_SERVER['JAZZCASH_PASSWORD'] ?? (getenv('JAZZCASH_PASSWORD') ?: 'password123')));
}
if (!defined('JAZZCASH_INTEGRITY_SALT')) {
    define('JAZZCASH_INTEGRITY_SALT', $_ENV['JAZZCASH_INTEGRITY_SALT'] ?? ($_SERVER['JAZZCASH_INTEGRITY_SALT'] ?? (getenv('JAZZCASH_INTEGRITY_SALT') ?: 'salt12345678')));
}
if (!defined('JAZZCASH_POST_URL')) {
    define('JAZZCASH_POST_URL', JAZZCASH_ENVIRONMENT === 'sandbox' ? 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/' : 'https://jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/');
}

class JazzCashService
{
    /**
     * Prepare canonical JazzCash Merchant Form payload and generate HMAC-SHA256 pp_SecureHash.
     */
    public function prepareMerchantFormRequest(FeeChallan $challan, array $options = []): array
    {
        // JazzCash requires Amount in Paisas (Amount * 100 without decimals)
        $amountInPaisas = (int) round($challan->total_amount * 100);
        $txnDateTime = date('YmdHis');
        $expiryDateTime = date('YmdHis', strtotime('+24 hours'));
        $txnRefNo = 'T' . date('YmdHis') . rand(100, 999);

        $params = [
            'pp_Version' => '1.1',
            'pp_TxnType' => $options['txn_type'] ?? '', // Empty string for Redirect Form, or 'MWALLET' for Mobile Account
            'pp_Language' => 'EN',
            'pp_MerchantID' => JAZZCASH_MERCHANT_ID,
            'pp_SubMerchantID' => '',
            'pp_Password' => JAZZCASH_PASSWORD,
            'pp_BankID' => 'TBANK',
            'pp_ProductID' => 'RETAIL',
            'pp_TxnRefNo' => $txnRefNo,
            'pp_Amount' => (string) $amountInPaisas,
            'pp_TxnCurrency' => 'PKR',
            'pp_TxnDateTime' => $txnDateTime,
            'pp_BillReference' => substr(preg_replace('/[^a-zA-Z0-9]/', '', $challan->challan_number), 0, 20),
            'pp_Description' => 'Fee Payment Challan ' . $challan->challan_number,
            'pp_TxnExpiryDateTime' => $expiryDateTime,
            'pp_ReturnURL' => $options['return_url'] ?? url('/parent/payment/callback'),
            'pp_SecureHash' => '',
            'ppmpf_1' => (string) $challan->id, // Custom passthrough parameter for challan ID
        ];

        if (!empty($options['mobile_number']) && ($options['txn_type'] ?? '') === 'MWALLET') {
            $params['pp_MobileNumber'] = $options['mobile_number'];
            $params['pp_CNIC'] = $options['cnic'] ?? '3456789012345';
        }

        // Generate HMAC-SHA256 Secure Hash
        $params['pp_SecureHash'] = $this->calculateSecureHash($params);

        return [
            'post_url' => JAZZCASH_POST_URL,
            'environment' => JAZZCASH_ENVIRONMENT,
            'merchant_id' => JAZZCASH_MERCHANT_ID,
            'payload' => $params,
        ];
    }

    /**
     * Calculate HMAC-SHA256 pp_SecureHash according to JazzCash integrity specification.
     */
    public function calculateSecureHash(array $params): string
    {
        // 1. Remove pp_SecureHash from parameters before hashing
        unset($params['pp_SecureHash']);

        // 2. Filter out any empty parameters
        $filteredParams = array_filter($params, function ($value) {
            return $value !== null && $value !== '';
        });

        // 3. Sort alphabetically by parameter key
        ksort($filteredParams);

        // 4. Concatenate salt + sorted values separated by '&'
        $hashString = JAZZCASH_INTEGRITY_SALT;
        foreach ($filteredParams as $key => $value) {
            $hashString .= '&' . $value;
        }

        // 5. Generate HMAC-SHA256 secure hash in uppercase
        return strtoupper(hash_hmac('sha256', $hashString, JAZZCASH_INTEGRITY_SALT));
    }

    /**
     * Verify incoming callback hash from JazzCash Gateway.
     */
    public function verifyCallbackHash(array $response): bool
    {
        if (empty($response['pp_SecureHash'])) {
            return false;
        }

        $receivedHash = $response['pp_SecureHash'];
        $calculatedHash = $this->calculateSecureHash($response);

        return hash_equals($calculatedHash, $receivedHash);
    }
}
