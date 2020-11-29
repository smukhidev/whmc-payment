<?php
/**
 * WHMCS Shurjopay Payment Callback File
 *
 * This shurjopay file demonstrates how a payment gateway callback should be
 * handled within WHMCS.
 *
 * It demonstrates verifying that the payment gateway module is active,
 * validating an Invoice ID, checking for the existence of a Transaction ID,
 * Logging the Transaction for debugging and Adding Payment to an Invoice.
 *
 * For more information, please refer to the contact us.
 *
 * @see https://www.shurjopay.com.bd/#contact
 * 
 */

	// Require libraries needed for gateway module functions.
	require_once __DIR__ . '/../../../init.php';
	require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
	require_once __DIR__ . '/../../../includes/invoicefunctions.php';

	// Detect module name from filename.
	$gatewayModuleName = basename(__FILE__, '.php');

	// Fetch gateway configuration parameters.
	$gatewayParams = getGatewayVariables($gatewayModuleName);
	$isLive = $gatewayParams['testMode'];
	$clientHomePage = $gatewayParams['systemurl'].'/clientarea.php?action=invoices';
	// Fetch Gateway response data
	$response_encrypted = $_POST['spdata'];
	// Die if module is not active.
	if (!$gatewayParams['type']) {
		die("Module Not Activated");
	}


	/**
	 * Validate callback authenticity. 
	 */

	if($isLive == 'on')
	{
		// $response_decrypted = file_get_contents("https://shurjotest.com/merchant/decrypt.php?data=".$response_encrypted);

		$shurjopay_decryption_url = 'https://shurjotest.com/merchant/decrypt.php';
		$payment_url = $shurjopay_decryption_url.'?data='.$response_encrypted;
		$ch = curl_init();  
		curl_setopt($ch,CURLOPT_URL,$payment_url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);    
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		$response_decrypted = curl_exec($ch);
		curl_close ($ch);
	}
	else
	{
		// $response_decrypted = file_get_contents("https://shurjopay.com/merchant/decrypt.php?data=".$response_encrypted);

		$shurjopay_decryption_url = 'https://shurjopay.com/merchant/decrypt.php';
		$payment_url = $shurjopay_decryption_url.'?data='.$response_encrypted;
		$ch = curl_init();  
		curl_setopt($ch,CURLOPT_URL,$payment_url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);    
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		$response_decrypted = curl_exec($ch);
		curl_close ($ch);
	}	
	$data= simplexml_load_string($response_decrypted) or die("Error: Cannot create object");


	/**
	*	Retrieve data returned from payment gateway callback
	*/ 
	
	$transactionId = $returnID = $data->txID;
	$spliteID = explode('_',$returnID);
	$invoiceId = $spliteID[1];
	$bank_tx_id = $data->bankTxID;
	$bank_status = $data->bankTxStatus;
	$sp_code = $data->spCode;
	$sp_code_des = $data->spCodeDes;
	$sp_payment_option = $data->paymentOption;

	if($bank_status == 'SUCCESS' &&  $sp_code == '000')
	{
		$transactionStatus = 'Transaction is Success';
		//$success = true;
	}
	else
	{
		$transactionStatus = 'Transaction is Failed';		
	}


/**
 * Validate Callback Invoice ID.
 *
 * Checks invoice ID is a valid invoice number. Note it will count an
 * invoice in any status as valid.
 *
 * Performs a die upon encountering an invalid Invoice ID.
 *
 * Returns a normalised invoice ID.
 *
 * @param int $invoiceId Invoice ID
 * @param string $gatewayName Gateway Name
 */
	$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

/**
 * Check Callback Transaction ID.
 *
 * Performs a check for any existing transactions with the same given
 * transaction number.
 *
 * Performs a die upon encountering a duplicate.
 *
 * @param string $transactionId Unique Transaction ID
 */
	checkCbTransID($transactionId);

/**
 * Log Transaction.
 *
 * Add an entry to the Gateway Log for debugging purposes.
 *
 * The debug data can be a string or an array. In the case of an
 * array it will be
 *
 * @param string $gatewayName        Display label
 * @param string|array $debugData    Data to log
 * @param string $transactionStatus  Status
 */
	logTransaction($gatewayParams['name'], $_POST, $transactionStatus);


	if($data->bankTxStatus == 'SUCCESS' &&  $data->spCode	 == '000')
	{
	   /**
		 * Add Invoice Payment.
		 *
		 * Applies a payment transaction entry to the given invoice ID.
		 *
		 * @param int $invoiceId         Invoice ID
		 * @param string $transactionId  Transaction ID
		 * @param float $paymentAmount   Amount paid (defaults to full balance)
		 * @param float $paymentFee      Payment fee (optional)
		 * @param string $gatewayModule  Gateway module name
		 */
		addInvoicePayment(
			$invoiceId,
			$transactionId,
			$paymentAmount,
			$paymentFee,
			$gatewayModuleName
		);

		# Successful and redirecting to client page
		echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"1; URL=".$clientHomePage."\"><h2>Redirecting</h2><p>Your payment was <b>sucessful</b> and you are being redirected now.</p><p>If you page does not redirect in 5 seconds, click <a href=\"/".$clientHomePage."\">here</a>.</p>";
		
	}
	else {
		
		# Unsuccessful and redirecting to client page
		echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"1; URL=".$clientHomePage."\"><h2>Redirecting</h2><p>Your payment has <b>failed</b>. An error occured during payment and you are being redirected now.</p><p>If your page does not redirect in 5 seconds, click <a href=\"/".$clientHomePage."\">here</a>.</p>";
		
	}


?>
 
