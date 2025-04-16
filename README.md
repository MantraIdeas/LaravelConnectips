![Laravel Connectips](https://banners.beyondco.de/Laravel%20Connectips.png?theme=light&packageManager=composer+require&packageName=mantraideas%2Flaravel-connectips&pattern=architect&style=style_1&description=Integrate+Connectips+checkout+to+your+Laravel+application&md=1&showWatermark=1&fontSize=100px&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg)

# Laravel Connectips

[![Latest Stable Version](http://poser.pugx.org/mantraideas/laravel-connectips/v)](https://packagist.org/packages/mantraideas/laravel-connectips)
[![Total Downloads](http://poser.pugx.org/mantraideas/laravel-connectips/downloads)](https://packagist.org/packages/mantraideas/laravel-connectips)
[![License](http://poser.pugx.org/mantraideas/laravel-connectips/license)](https://packagist.org/packages/mantraideas/laravel-connectips)

The `mantraideas/laravel-connectips` allows you to integrate ConnectIps payment on your Laravel Application.

## Quick Start

### Install Using Composer

```bash
composer require mantraideas/laravel-connectips
```

### Publish Config File

```bash
php artisan vendor:publish --provider="MantraIdeas\LaravelConnectips\LaravelConnectipsServiceProvider"
```

This will create a `config/connectips.php` file in your Laravel application. If you want to change the default configuration, you can do so in this file.

### Set Environment Variables
You can set the environment variables in your `.env` file:

```dotenv
CONNECTIPS_MERCHANT_ID=""
CONNECTIPS_APP_ID=""
CONNECTIPS_PASSWORD=""
CONNECTIPS_APP_NAME=""
CONNECTIPS_SUCCESS_URL=${APP_URL}/connectips/success/
CONNECTIPS_FAILURE_URL=${APP_URL}/connectips/failure
CONNECTIPS_PEM_PATH="app/private/privatekey.pem"
CONNECTIPS_URL="https://uat.connectips.com"
```
Here is the description of each environment variable:
- `CONNECTIPS_MERCHANT_ID`: Your Connectips Merchant ID. (Provided by Connectips)
- `CONNECTIPS_APP_ID`: Your Connectips App ID. (Provided by Connectips)
- `CONNECTIPS_PASSWORD`: Your Connectips Password. (Provided by Connectips)
- `CONNECTIPS_APP_NAME`: Your Connectips App Name. (Provided by Connectips)
- `CONNECTIPS_SUCCESS_URL`: The URL to redirect to after a successful payment.
- `CONNECTIPS_FAILURE_URL`: The URL to redirect to after a failed/canceled payment.
- `CONNECTIPS_PEM_PATH`: The path to the private key file. (File provided by Connectips) **Note: It must be inside `storage` directory.**
- `CONNECTIPS_URL`: The URL to the Connectips API. (Default is `https://uat.connectips.com` for testing.)

### Usage
```php
 $connectips = new \Mantraideas\LaravelConnectips\LaravelConnectips();
 // Create Unique Transaction Id
    $transactionId = uniqid('txn_');
    // Generate Transaction Details
    $transactionDetails = $connectips->generateData(
        transactionId: $transactionId,
        transactionAmount: 5000, // amount in paisa i.e. 5000 paisa = 50.00 NPR
        referenceId: 'REF_' . uniqid(),
        remarks: 'Payment for service',
        particulars: 'Service payment',
        transactionDate: now()->format('d-m-Y'),
        transactionCurrency: 'NPR'
    );
    // Store Payment on Database
    \App\Models\Payment::create(
        [
            'transaction_id' => $transactionId,
            'amount'=>$transactionDetails['TXNAMT'],
            'status'=>'Pending'
        ]
    );
    // Pass Transaction Details to View
    return view('welcome', [
        'connectIpsUrl' => config('connectips.connectIpsUrl').'/connectipswebgw/loginpage',
        'transaction' => $transactionDetails,
        'successUrl' => route('payment.success'),
        'failureUrl' => route('payment.failure')
    ]);
```
### Create Payment Form
You can create a payment form using the transaction details generated above. Here is an example of how to create a payment form in your Blade view:

```html
<!DOCTYPE html>
<html>
<head>
    <title>Connectips Gateway</title>
</head>
<body>
<div class="form-container">
    <h1>Connect IPS Payment Form</h1>
    <form action="{{ $connectIpsUrl }}" method="POST">
        @foreach($transaction as $key => $value)
        <div class="form-field">
            <label for="{{ $key }}">{{ $key }}:</label>
            <input type="text" id="{{ $key }}" name="{{ $key }}" value="{{ $value }}" readonly>
        </div>
        @endforeach
        <button type="submit" class="submit-btn">Make Payment</button>
    </form>
</div>
</body>
</html>
```
After this user will be redirected to connectips payment webpage and then after the action user will be redirected to Success or Fail callback url based on the payment status. Then you can use the following code to handle the success and failure callback.
### Validate Payment Status
Additionally, you can validate payment status using the `validatePayment` method. This method will check the payment status and return a boolean value.
You will get ```transaction_id``` from the response of Connectips API to your success and failed payment callback url. You can use the following code to validate the payment status.
By using the ```transaction_id``` you can check your payments table to get amount and use this method.

```php
    $connectips = new \Mantraideas\LaravelConnectips\LaravelConnectips();
    $transactionId = request()->query('TXNID'); // Replace with the actual transaction ID
    $transactionAmount = \App\Models\Payment::where('transaction_id',$transactionId)->first()?->amount; // Replace with the actual transaction amount
    $connectips->validatePayment($transactionId,$transactionAmount);
```
This will return transaction status as follows.
```aiignore
array:7 [▼ 
  "merchantId" => "Your Merchant Id"
  "appId" => "Your App Id"
  "referenceId" => "txn_67f8a3e7055fa"
  "txnAmt" => "5000" // Amount in paisa i.e. 5000 paisa = 50.00 NPR
  "token" => null
  "status" => "SUCCESS"
  "statusDesc" => "TRANSACTION SUCCESSFUL"
]
```
### Get Transaction Details
You can also get transaction details using the `getTransactionDetails` method. This method will return the transaction details as an array.

```php
    $connectips = new \Mantraideas\LaravelConnectips\LaravelConnectips();
    $transactionId = request()->query('TXNID'); // Replace with the actual transaction ID
    $transactionAmount = \App\Models\Payment::where('transaction_id',$transactionId)->first()?->amount; // Replace with the actual transaction amount
    $transactionDetails = $connectips->getTransactionDetails($transactionId);
```
This will return transaction details as follows.
```aiignore
array:18 [▼ 
  "status" => "SUCCESS"
  "statusDesc" => "TRANSACTION SUCCESSFUL"
  "merchantId" => "Your Merchant ID"
  "appId" => "Your App ID"
  "referenceId" => "txn_67f8a3e7055fa" //this is your transaction id
  "txnAmt" => 5000.0 // amount in paisa i.e. 5000 paisa = 50.00 NPR
  "token" => null
  "debitBankCode" => "2501"
  "txnId" => 13303786
  "batchId" => 712974908
  "txnDate" => 1744348150748
  "txnCrncy" => null
  "chargeAmt" => 225.0 // amount in pasia i.e. 225 paisa = 2.25 NPR
  "chargeLiability" => "CG"
  "refId" => "REF_67f8a3e705600"
  "remarks" => "Payment for service"
  "particulars" => "Service payment"
  "creditStatus" => "DEFER"
]
```

## License

[MIT](https://choosealicense.com/licenses/mit/)

## Author

- [@Dipesh79](https://www.github.com/Dipesh79)

## Support

For support, email [dipeshkhanal79[at]gmail[dot]com](mailto:dipeshkanal79@gmail.com).
