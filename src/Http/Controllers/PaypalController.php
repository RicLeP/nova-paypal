<?php

namespace Naif\Paypal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Paypal;
include 'Paypal.php';

class PayPalController extends Controller
{
    private $paypal;
    private $info;

    public function __construct(){
        $this->paypal = new Paypal(env('NOVA_PAYPAL_USERNAME'), env('NOVA_PAYPAL_PASSWORD'), env('NOVA_PAYPAL_SIGNATURE'));
    }

    public function index(Request $request)
    {
        $days = $request->get('days');
        $count = $request->get('count');
        if (!$days){
            $days = 5;
        } else if (!$count){
            $count = 10;
        }
        $data = $this->paypal->call('GetBalance');
        $response['balance']['ACK'] = data_get($data,'ACK');
        $response['balance']['L_AMT0'] = data_get($data,'L_AMT0');
        $response['balance']['L_SEVERITYCODE0'] = data_get($data,'L_SEVERITYCODE0');
        $response['balance']['L_ERRORCODE0'] = data_get($data,'L_ERRORCODE0');
        $response['balance']['L_LONGMESSAGE0'] = data_get($data,'L_LONGMESSAGE0');
        $this->info = 'USER=' . env('NOVA_PAYPAL_USERNAME')
            . '&PWD=' . env('NOVA_PAYPAL_PASSWORD')
            . '&SIGNATURE=' . env('NOVA_PAYPAL_SIGNATURE')
            . '&METHOD=TransactionSearch'
            . '&TRANSACTIONCLASS=RECEIVED'
            . '&STARTDATE='.date_create(date('Y-m-d',strtotime("-".$days." days")))->format('c')
            . '&VERSION=94';

        $transactions[] = [];
        $curl = curl_init('https://api-3t.paypal.com/nvp');
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->info);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        $result = curl_exec($curl);
        $result = explode("&", $result);
        foreach ($result as $value) {
            $value = explode("=", $value);
            $temp[$value[0]] = $value[1];
        }
        for ($i = 0; $i <= count($temp) / 19; $i++) {
            if (isset($temp["L_TIMEZONE" . $i])) {
                $transactions['transactions'][$i] = array(
                    "timestamp" => date("Y-m-d", strtotime(urldecode($temp["L_TIMESTAMP" . $i]))),
                    "timezone" => urldecode($temp["L_TIMEZONE" . $i]),
                    "type" => urldecode($temp["L_TYPE" . $i]),
                    "email" => urldecode($temp["L_EMAIL" . $i]),
                    "name" => urldecode($temp["L_NAME" . $i]),
                    "transaction_id" => urldecode($temp["L_TRANSACTIONID" . $i]),
                    "status" => urldecode($temp["L_STATUS" . $i]),
                    "amt" => urldecode($temp["L_AMT" . $i]),
                    "currency_code" => urldecode($temp["L_CURRENCYCODE" . $i]),
                    "fee_amount" => urldecode($temp["L_FEEAMT" . $i]),
                    "net_amount" => urldecode($temp["L_NETAMT" . $i])
                );
            }
        }
        if (array_has($transactions,'transactions')) {
            $response['transactions'] = array_slice($transactions['transactions'], 0, $count);
        } else{
            $response['transactions'] = [];
        }
        return $response;
    }
}