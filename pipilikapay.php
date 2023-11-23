<?php

/**
 * pipilikapay WHMCS Gateway
 *
 * Copyright (c) 2022 pipilikapay
 * Website: https://pipilikapay.com
 * Developer: Jasim Uddin
 * 
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function pipilikapay_MetaData()
{
    return array(
        'DisplayName' => 'PipilikaPay Gateway',
        'APIVersion' => '1.0',
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

function pipilikapay_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'pipilikapay Gateway',
        ),
        'apiKey' => array(
            'FriendlyName' => 'API Key',
            'Type' => 'text',
            'Size' => '60',
            'Default' => '',
            'Description' => 'Get API key from your portal',
        ),
        'secretKey' => array(
            'FriendlyName' => 'Secret Key',
            'Type' => 'text',
            'Size' => '60',
            'Default' => '',
            'Description' => 'Get API URL from your portal',
        ),
        'panelURL' => array(
            'FriendlyName' => 'Panel URL',
            'Type' => 'text',
            'Size' => '60',
            'Default' => '',
            'Description' => 'Payment Panel URL',
        ),
        'currencyRate' => array(
            'FriendlyName' => 'Currency Rate',
            'Type' => 'text',
            'Size' => '60',
            'Default' => '0',
            'Description' => 'Enter Currency Rate If Your Site Currency USD',
        )
    );
}


function pipilikapay_link($params){
    if (isset($_GET['pipilikapay'])) {
        $response = pipilikapay_payment_url($params);
    }
    
    $systemUrl = $params['systemurl'];
    
    $invoiceId = $params['invoiceid'];
    
    return '<form action="'. $systemUrl.'viewinvoice.php" method="GET">
    <input type="hidden" name="id" value="'.$invoiceId.'" />
    <input type="hidden" name="pipilikapay" value="pipilikapay" />
    <input class="btn btn-primary" type="submit" value="' . $params['langpaynow'] . '" />
    </form>';
}

function pipilikapay_payment_url($params)
{
    // Gateway Configuration Parameters
    $apiKey = $params['apiKey'];
    $secretKey = $params['secretKey'];
    $currencyRate = $params['currencyRate'];
    $panelURL = $params['panelURL'];

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];

    $amount = $params['amount'];

    if($currencyRate == "0"){
        
    }else{
        $amount = $amount*$currencyRate;
    }

    // Client Parameters
    $fullname = $params['clientdetails']['firstname'] . " " . $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];

    // System Parameters
    $systemUrl = $params['systemurl'];

    $baseURL = $panelURL;

    $callbackURL= $systemUrl . 'viewinvoice.php?id=' . $invoiceId;
    $webhookURL= $systemUrl . 'modules/gateways/callback/pipilikapay.php';
    $cancelURL= $systemUrl . 'viewinvoice.php?id=' . $invoiceId;


        $metadata = array(
            'customerID' => $email,
            'orderID' => $invoiceId
        );

        $data = array(
            'apiKey' => $apiKey,
            'secretkey' => $secretKey,
            'fullname' => $fullname,
            'email' => $email,
            'amount' => $amount,
            'successurl' => $callbackURL,
            'cancelurl' => $cancelURL,
            'webhookUrl' => $webhookURL,
            'metadata' => json_encode($metadata)
        );

        $ch = curl_init("$baseURL/payment/api/create_payment");

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        curl_close($ch);

        $result = json_decode($response, true);

    $resultURL = $result['paymentURL'];

    header("Location: $resultURL");
}
