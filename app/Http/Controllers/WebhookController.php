<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PaymentLogs;
use App\Order;
class WebhookController extends Controller
{
    public function midtransHandler(Request $request){
        $data=$request->all();
        $signature_key=$data["signature_key"];
        $order_id=$data["order_id"];
        $statusCode=$data["status_code"];
        $grossAmount=$data["gross_amount"];
        $server_key=env("MIDTRANS_SERVER_KEY");
        $mySignatureKey=hash('sha512',$order_id.$statusCode.$grossAmount.$server_key);
        $transactionStatus=$data["transaction_status"];
        $type=$data["payment_type"];
        $fraudStatus=$data["fraud_status"];
        if($signature_key!==$mySignatureKey){
            return response()->json(["status"=>"error","massage"=>"invalid signature key"],400);
        }
        $realOrderId=explode('-',$order_id);
        $order=Order::find($realOrderId[0]);
        if(!$order){
            return response()->json(["status"=>"error","massage"=>"order id not found"],404);
        }
        if($order->status==="succes"){
            return response()->json(["status"=>"error","massage"=>"oparation not permited"],405);
        }
       
        if ($transactionStatus == 'capture'){
            if ($fraudStatus == 'challenge'){
              
                $order->status='challange';
            } else if ($fraudStatus == 'accept'){
                    $order->status='succes';
            }
        } else if ($transactionStatus == 'settlement'){
          
                  $order->status='succes';
        } else if ($transactionStatus == 'cancel' ||
          $transactionStatus == 'deny' ||
          $transactionStatus == 'expire'){
         
            $order->status='failure';
        } else if ($transactionStatus == 'pending'){
      
          $order->status='pending';
        }
        $logsData=[
            'status'=>$transactionStatus,
            'raw_response'=>json_encode($data),
            'order_id'=>$realOrderId[0],
            'payment_type'=>$type,
        ];
        PaymentLogs::create($logsData);
        $order->save();
    
        if($order->status==="succes"){
           createPremiumAcces([
                'user_id'=>$order->user_id,
                'course_id'=>$order->course_id
            ]);
        }
       return response()->json("ok");

    }
}
