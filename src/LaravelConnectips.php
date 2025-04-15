<?php

namespace Mantraideas\LaravelConnectips;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use OpenSSLAsymmetricKey;

/**
 * Class LaravelConnectips
 * Handles payment-related operations for ConnectIPS integration.
 */
class LaravelConnectips
{
    private string $merchantId;
    private string $appId;
    private string $appName;
    private string $password;
    private OpenSSLAsymmetricKey $privateKey;

    /**
     * LaravelConnectips constructor.
     * Initializes the class with configuration values and loads the private key.
     *
     * @throws Exception If the private key file is not found or cannot be loaded.
     */
    public function __construct()
    {
        $this->merchantId = config('connectips.merchantId');
        $this->appId = config('connectips.appId');
        $this->password = config('connectips.password');
        $this->appName = config('connectips.appName');
        $this->privateKey = $this->getPrivateKeyFromPem(storage_path(config('connectips.pemPath')));
    }

    /**
     * Loads the private key from a PEM file.
     *
     * @param string $pemPath Path to the PEM file.
     * @return OpenSSLAsymmetricKey The loaded private key.
     * @throws Exception If the file is not found or the key cannot be loaded.
     */
    private function getPrivateKeyFromPem(string $pemPath): \OpenSSLAsymmetricKey
    {
        if (!file_exists($pemPath)) {
            throw new Exception("Private key file not found at: $pemPath");
        }

        $privateKey = file_get_contents($pemPath);
        $key = openssl_pkey_get_private($privateKey);

        if (!$key) {
            throw new Exception('Failed to load private key: ' . openssl_error_string());
        }

        return $key;
    }

    /**
     * Generates transaction data with a signed token.
     *
     * @param string $transactionId Unique transaction ID.
     * @param float $transactionAmount Transaction amount.
     * @param string $referenceId Reference ID for the transaction.
     * @param string $remarks Remarks for the transaction.
     * @param string $particulars Particulars for the transaction.
     * @param string|null $transactionDate Date of the transaction (default: current date) date format: d-m-Y.
     * @param string $transactionCurrency Currency of the transaction (default: NPR).
     * @return array The transaction details with a signed token.
     * @throws Exception If the signature generation fails.
     */
    public function generateData(
        string $transactionId,
        int $transactionAmount,
        string $referenceId,
        string $remarks,
        string $particulars,
        string|null $transactionDate,
        string $transactionCurrency = 'NPR',
    ): array {

        $transactionDetails = [
            'MERCHANTID' => $this->merchantId,
            'APPID' => $this->appId,
            'APPNAME' => $this->appName,
            'TXNID' => $transactionId,
            'TXNDATE' => $transactionDate ?? now()->format('d-m-Y'),
            'TXNCRNCY' => $transactionCurrency,
            'TXNAMT' => $transactionAmount,
            'REFERENCEID' => $referenceId,
            'REMARKS' => $remarks,
            'PARTICULARS' => $particulars,
        ];

        // Construct the exact message string as required
        $message = "MERCHANTID={$this->merchantId},APPID={$this->appId},APPNAME={$this->appName},TXNID={$transactionId},TXNDATE={$transactionDetails['TXNDATE']},TXNCRNCY={$transactionDetails['TXNCRNCY']},TXNAMT={$transactionDetails['TXNAMT']},REFERENCEID={$transactionDetails['REFERENCEID']},REMARKS={$transactionDetails['REMARKS']},PARTICULARS={$transactionDetails['PARTICULARS']},TOKEN=TOKEN";

        // Sign the raw message (not digested)
        $signature = $this->generateSignature($message);

        // Base64 encode the binary signature
        $transactionDetails['TOKEN'] = base64_encode($signature);

        return $transactionDetails;
    }

    /**
     * Generates a digital signature for a given message.
     *
     * @param string $message The message to sign.
     * @return string The generated signature.
     * @throws Exception If the signature generation fails.
     */
    private function generateSignature(string $message): string
    {
        if (!openssl_sign($message, $signature, $this->privateKey, OPENSSL_ALGO_SHA256)) {
            throw new Exception('Signature failed: ' . openssl_error_string());
        }
        return $signature;
    }

    /**
     * Validates a payment with ConnectIPS.
     *
     * @param string $transactionId Unique transaction ID.
     * @param int $transactionAmount Transaction amount.
     * @return array The response from the ConnectIPS API.
     * @throws Exception If the validation request fails.
     */
    public function validatePayment(string $transactionId, int $transactionAmount): array
    {
        $token = $this->generateToken($transactionId, $transactionAmount);
        $requestData = [
            'merchantId' => $this->merchantId,
            'appId' => $this->appId,
            'referenceId' => $transactionId,
            'txnAmt' => $transactionAmount,
            'token' => $token
        ];
        $url = config('connectips.connectIpsUrl') . '/connectipswebws/api/creditor/validatetxn';
        $response = Http::withBasicAuth($this->appId, $this->password)
            ->withBody(json_encode($requestData))
            ->post($url);
        if ($response->failed()) {
            throw new Exception('Failed to validate payment: ' . $response->body());
        }
        return $response->json();
    }

    /**
     * Generates a token for a transaction.
     *
     * @param string $transactionId Unique transaction ID.
     * @param int $transactionAmount Transaction amount.
     * @return string The generated token.
     * @throws Exception If the signature generation fails.
     */
    private function generateToken(string $transactionId, int $transactionAmount): string
    {
        $message = "MERCHANTID={$this->merchantId},APPID={$this->appId},REFERENCEID={$transactionId},TXNAMT={$transactionAmount}";
        $signature = $this->generateSignature($message);
        return base64_encode($signature);
    }

    /**
     * Retrieves transaction details from ConnectIPS.
     *
     * @param string $transactionId Unique transaction ID.
     * @param string $transactionAmount Transaction amount.
     * @return array The response from the ConnectIPS API.
     * @throws ConnectionException If the HTTP request fails.
     * @throws Exception If the request fails or the response is invalid.
     */
    public function getTransactionDetails(string $transactionId, string $transactionAmount): array
    {
        $token = $this->generateToken($transactionId, $transactionAmount);
        $requestData = [
            'merchantId' => $this->merchantId,
            'appId' => $this->appId,
            'referenceId' => $transactionId,
            'txnAmt' => $transactionAmount,
            'token' => $token
        ];
        $url = config('connectips.connectIpsUrl') . '/connectipswebws/api/creditor/gettxndetail';
        $response = Http::withBasicAuth($this->appId, $this->password)
            ->withBody(json_encode($requestData))
            ->post($url);
        if ($response->failed()) {
            throw new Exception('Failed to get Transaction: ' . $response->body());
        }
        return $response->json();
    }

}
