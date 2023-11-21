<?php

/**
 * pipilikapay WHMCS Gateway
 *
 * Copyright (c) 2022 pipilikapay
 * Website: https://pipilikapay.com
 * Developer: Sakawat Hossain
 * 
 */

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

use WHMCS\Config\Setting;

class pipilikapay
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @var string
     */
    protected $gatewayModuleName;

    /**
     * @var array
     */
    protected $gatewayParams;

    /**
     * @var boolean
     */
    public $isActive;

    /**
     * @var integer
     */
    protected $customerCurrency;

    /**
     * @var object
     */
    protected $gatewayCurrency;

    /**
     * @var integer
     */
    protected $clientCurrency;

    /**
     * @var float
     */
    protected $convoRate;

    /**
     * @var array
     */
    protected $invoice;

    /**
     * @var float
     */
    protected $due;

    /**
     * @var float
     */
    protected $fee;

    /**
     * @var int
     */
    public $invoiceID;

    /**
     * @var float
     */
    public $total;

}

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        $gatewayModuleName = basename(__FILE__, '.php');
        $gatewayParams = getGatewayVariables($gatewayModuleName);

        $postData = file_get_contents('php://input');

        $systemUrl = $params['systemurl'];
        
        $postDataArray = json_decode($postData, true);

        $paymentID = $postDataArray['payment_id'];
        $transactionID = $postDataArray['transaction_id'];
        
        $amount = $postDataArray['amount'];
        $fee = $postDataArray['fee'];
        $paymentMethod = $postDataArray['payment_method'];

        $customerID = $postDataArray['metadata']['customerID'];
        $orderID = $postDataArray['metadata']['orderID'];

        $command = 'GetInvoice';
        $postData = array(
            'invoiceid' => $orderID,
        );
        $results = localAPI($command, $postData);
        $resultStatus = $results['status'];
        $resultTotal = $results['total'];
        $resultTax = $results['tax'];
        
        if($resultStatus == "Unpaid"){
            //Verify Payment

            $apiKey = $gatewayParams['apiKey'];
            $secretKey = $gatewayParams['secretKey'];
            $panelURL = $gatewayParams['panelURL'];

            $baseURL = $panelURL;

            $requestbody = array(
                'apiKey' => $apiKey,
                'secretkey' => $secretKey,
                'paymentID' => $paymentID
            );
            $url = curl_init("$baseURL/payment/api/verify_payment");                     
            $requestbodyJson = json_encode($requestbody);

            $header = array(
                'Content-Type:application/json'
            );

            curl_setopt($url, CURLOPT_HTTPHEADER, $header);
            curl_setopt($url, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($url, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($url, CURLOPT_POSTFIELDS, $requestbodyJson);
            curl_setopt($url, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($url, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            $resultdata = curl_exec($url);
            curl_close($url);
            $resultdata = json_decode($resultdata, true);

            $paymentStatus = $resultdata['PaymentStatus'];
            //Verify Payment

            if ($paymentStatus == 'Completed') {
                addInvoicePayment(
                    $orderID,
                    $transactionID,
                    $resultTotal,
                    $resultTax,
                    $gatewayModuleName
                );
            }else{
                echo "Failed";
            }
        }else{
            echo "Failed";
        }
    }
    