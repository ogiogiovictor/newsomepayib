<?php

namespace Pjet\Runjet\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Pjet\Runjet\Models\ZoneECMI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Pjet\Runjet\Models\MakeSave;
use Pjet\Runjet\Models\ECMITransactions;
use Pjet\Runjet\Models\SubAccountPayment;
use Pjet\Runjet\Models\ECMIPaymentTransaction;
use Pjet\Runjet\Models\ECMITokenLogs;
use Pjet\Runjet\Models\Ecmsdb;
use Pjet\Runjet\Models\ECMIHoldMode;
use Pjet\Runjet\Models\AuditSubAccountPayment;
use Pjet\Runjet\Models\AuditTokenLog;
use Pjet\Runjet\Models\AuditTransaction;
use Pjet\Runjet\Models\AuditPaymentTransaction;
use Pjet\Runjet\Models\AuditNewTransactions;
use Pjet\Runjet\Models\LogDeletedTransactions;
use Pjet\Runjet\Models\LogSubAccountPayments;
use Pjet\Runjet\Models\LogInsertSubAccountDeductions;
use Illuminate\Support\Facades\Log;



class PreController extends Controller 
{

    public function prepareIntegration(Request $request){

        //return view('pjet::pjet');

        $appSecret = $request->header('app-isecret-key');

        if($appSecret !== "muWndsl123MdinslQokjsd928923jjsmjhjdsjj"){ // Change this
            return response()->json(['message' => "Invalid Authentication Head" ], Response::HTTP_BAD_REQUEST);
        }
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'meter_number' => 'required|string',
            'amount' => 'required|numeric',
            'once_valid_seek' => 'required',
        ]);

        if(!$request->once_valid_seek){
            return response()->json(['message' => "Missing code Request", "Validation Error" ], Response::HTTP_BAD_REQUEST);
        }

        if($request->once_valid_seek != "MNioMUD123ksmA89@3%ksa1829882ABDUio"){
           return response()->json(['message' => "Missing code Request no same", "Validation Error" ], Response::HTTP_BAD_REQUEST);
        }
    
        if ($validator->fails()) {
            return response()->json(['message' => "Invalid input data" ], Response::HTTP_BAD_REQUEST);
        }

         $checkMeter = ZoneECMI::where("MeterNo", $request->meter_number)->first();
    
        if (!$checkMeter) {
            return response()->json(['message' => "No Record Found" ], Response::HTTP_BAD_REQUEST);
        }

        $uuid = Str::uuid()->toString();
        $limitedUuid = str_replace('-', '', substr($uuid, 0, 15));
    
        $meterNo = $checkMeter->MeterNo;
        $transReference = $limitedUuid;
        $amount = $request->amount;
        $merchant = "ABCD";
        $transDate = date("Y-m-d");
        $buid = $checkMeter->BUID;
        $mobile = isset($checkMeter->Mobile) ? $checkMeter->Mobile : "23458065326834";

         // Construct the URL
         $originalnotify = "http://192.168.15.156:9494/IBEDCWebServices/webresources/Payment/$meterNo/prepaid/113/$transReference/$amount/$merchant/$transDate/$buid/$mobile";
    

            try {
                $response = Http::post($originalnotify);
                // $response->json(); // Return the response content as JSON
                 $newResponse =  $response->json();
    
                 //if($newResponse['transactionStatus'] == "success"){
                if (isset($newResponse['transactionStatus']) && $newResponse['transactionStatus'] === "success") {
                     //Save Response to Warehouse. //Create a new database
                    return $this->processMainTransaction($newResponse);
                 }else {
                    //return $newResponse;
                    return response()->json(['message' => 'Transaction failed or unexpected response', 'response' => $newResponse], Response::HTTP_BAD_REQUEST);
                }
            } catch (\Exception $e) {
                // Return a structured error response
                return response()->json(['Request failed' => $e->getMessage() ], Response::HTTP_BAD_REQUEST);
            }


    }




    private function processMainTransaction($data){


        $checkTExist = ECMITransactions::where("transref", $data['transactionReference'])->first();


        try {

            // Disable the trigger for the specific connection
           // DB::connection('ecmi_prod')->unprepared('DISABLE TRIGGER [TRANSACTION_TRIGGER] ON [ECMI].[dbo].[Transactions]');

            $checkSubAccount = SubAccountPayment::where("TransactionNo",  $checkTExist->TransactionNo)->first();
            if($checkSubAccount){  $checkSubAccount->delete(); }

            if( $checkTExist){  $checkTExist->delete(); }

             //Delete token from paymentTransaction
          $checkpaymentTrans = ECMIPaymentTransaction::where("transref", $data['transactionReference'])->first();
          if($checkpaymentTrans){  $checkpaymentTrans->delete(); }

           //Delete Token from token log // TO FIX THIS
          $trimSpaces = str_replace(' ', '', $data['recieptNumber']);
          $tokeLogs = ECMITokenLogs::where("Token", $trimSpaces)->first();
          if($tokeLogs){  $tokeLogs->delete(); }

          $trigD = Ecmsdb::where("Token_before",  $trimSpaces)->first();
          if($trigD){ $trigD->delete(); }

           //[HoldModeTransactions] Delete Token if Exists  // check this
          $HoldMode = ECMIHoldMode::where("Token", $trimSpaces)->first();
          if($HoldMode){ $HoldMode->delete(); }

        // Enable the trigger again for the same connection
      // DB::connection('ecmi_prod')->unprepared('ENABLE TRIGGER [TRANSACTION_TRIGGER] ON [ECMI].[dbo].[Transactions]');

          //Check Audit
      $subAudSubPayment = AuditSubAccountPayment::where("TransactionNo",  $checkTExist->TransactionNo)->first();
      if($subAudSubPayment){  $subAudSubPayment->delete(); }


      $tokenAudLog = AuditTokenLog::where("Token", $trimSpaces)->first();
      if($tokenAudLog){  $tokenAudLog->delete(); }

      $auditTrans = AuditTransaction::where("Token", $trimSpaces)->first();
      if( $auditTrans){  $auditTrans->delete(); }

      $auditpTransaction = AuditPaymentTransaction::where("transref", $data['transactionReference'])->first();
      if($auditpTransaction){  $auditpTransaction->delete(); }

      $checkAduitnew = AuditNewTransactions::where("transref", $data['transactionReference'])->first();
      if( $checkAduitnew){  $checkAduitnew->delete(); }

      $logDeletedTrans = LogDeletedTransactions::where("transactionno", $checkTExist->TransactionNo)->first();
      if($logDeletedTrans){  $logDeletedTrans->delete(); }

      $logSubApayment = LogSubAccountPayments::where("TransactionNo", $checkTExist->TransactionNo)->first();
      if($logSubApayment){  $logSubApayment->delete(); }

      $logInserted = LogInsertSubAccountDeductions::where("transactionno", $checkTExist->TransactionNo)->first();
      if($logInserted){  $logInserted->delete(); }

      return response()->json(['Data' => $data ], Response::HTTP_BAD_REQUEST);

      }catch(\Exception $e){

        return response()->json(['Error' => $e->getMessage() ], Response::HTTP_BAD_REQUEST);

      }




    }



    public function getTKM(Request $request){

        $checkTExist = ECMITransactions::orderby("TransactionDateTime", "desc")->where("MeterNo", $request->meterno)->paginate(10);
      
        return response()->json($checkTExist, Response::HTTP_BAD_REQUEST);
    }


}