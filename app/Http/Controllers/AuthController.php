<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
       // $this->middleware('auth:api', ['except' => ['login']]);
    }
    public function createAccount(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|unique:users|email',
            'phone' => 'required|numeric|unique:users',
            'password' =>'required|string|max:255',
           
          ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        try{
            $otp_code=$this->generateOTP();
        User::create([
            'name' => $request->name,
            'email'=> $request->email,
            'phone'=> $request->phone,
            'password' => Hash::make($request->password),
            'otp_code' => $otp_code
        ]);
        $this->sendOTP($request->name,$request->phone,$otp_code);
        return response()->json(['success' => 'Account Created Succesfully'], 200);
        }
        catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 400);
        }
        
    }
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    { 
        if(auth()->user()){
           return response()->json(auth()->user());
        }
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        if(auth()->user()->hasVerifiedEmail()){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
        }
        return response()->json(['error' => 'Email has not been verified']);
    }

    //generate_otp
    public function generateOTP()
    {
        $otp=rand(1000,9999);
        return $otp;
    }
    //send otp
    public function sendOTP($name,$phone,$otp_code){
        $ch = curl_init();
          $params=[
         'apiKey' => '120ca9639da262edc34804590b59cb40',
         'shortCode' => 'VasPro',
         'recipient' => strval($phone),
         'enqueue' => 0,
         'message' => 'Dear '.$name.', Your Residence Verification Code is '.$otp_code,
         "callbackURL" => "http://vaspro.co.ke/dlr"
       ];
       
       
       $headers = array(
           'Cache-control: no-cache',
       );
       $url = "https://api.vaspro.co.ke/v3/BulkSMS/api/create";
       curl_setopt($ch,CURLOPT_URL, $url);
       curl_setopt($ch,CURLOPT_POST, 1);                //0 for a get request
       curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($params));
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);;
       curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,300);
       curl_setopt($ch,CURLOPT_TIMEOUT, 20);
       $response1 = curl_exec($ch);
       curl_close ($ch);
    }
      //resend otp
      public function resendOTP(Request $request){
        $phone=$request->phone;
        $user=User::where('phone',$phone)->first();
        if($user){
        $otp_code = $this->generateOTP();
        $user=User::find($user->id);
        $user->otp_code= $otp_code;
        $user->save();
        $ch = curl_init();
          $params=[
         'apiKey' => '120ca9639da262edc34804590b59cb40',
         'shortCode' => 'VasPro',
         'recipient' => strval($phone),
         'enqueue' => 0,
         'message' => 'Your Residence Verification Code is '.$otp_code,
         "callbackURL" => "http://vaspro.co.ke/dlr"
       ];
       
       
       $headers = array(
           'Cache-control: no-cache',
       );
       $url = "https://api.vaspro.co.ke/v3/BulkSMS/api/create";
       curl_setopt($ch,CURLOPT_URL, $url);
       curl_setopt($ch,CURLOPT_POST, 1);                //0 for a get request
       curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($params));
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);;
       curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,300);
       curl_setopt($ch,CURLOPT_TIMEOUT, 20);
       $response1 = curl_exec($ch);
       curl_close ($ch);
       return response()->json(['success' => 'Verification code resent'], 200);
    }
    if(!$user){
        return response()->json(['error' => 'User not found'], 401);
    }
    }

    public function verifyOTP(Request $request){
      $phone=$request->phone;
      $otp_code=$request->otp_code;

      $isVerified=User::where('phone',$phone)->where('otp_code',$otp_code)->first();

      if(!$isVerified){
        return response()->json(['error' => 'The Verification code is invalid']);
      }
      $user=User::find($isVerified->id);
      $user->email_verified_at=now();
      $user->save();
      return response()->json(['success' => 'Verification succesfull'], 200);
    }
    //generate unique password
    public function randomPassword() {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

    public function resetPassword(Request $request){
        $phone = $request->phone;
        $user=User::where('phone',$phone)->first();
        if($user){
            $new_password=$this->randomPassword();
            $user->password= Hash::make($new_password);
            $user->save();

        $ch = curl_init();
          $params=[
         'apiKey' => '120ca9639da262edc34804590b59cb40',
         'shortCode' => 'VasPro',
         'recipient' => strval($phone),
         'enqueue' => 0,
         'message' => 'Your new Residence Password is '.$new_password.'.Please change once you login in',
         "callbackURL" => "http://vaspro.co.ke/dlr"
       ];
       
       
       $headers = array(
           'Cache-control: no-cache',
       );
       $url = "https://api.vaspro.co.ke/v3/BulkSMS/api/create";
       curl_setopt($ch,CURLOPT_URL, $url);
       curl_setopt($ch,CURLOPT_POST, 1);                //0 for a get request
       curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($params));
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);;
       curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,300);
       curl_setopt($ch,CURLOPT_TIMEOUT, 20);
       $response1 = curl_exec($ch);
       curl_close ($ch);
         return response()->json(['success' => 'Password reset succesfully'], 200);
        }
        if(!$user){
            return response()->json(['error' => 'User not found'], 401);
        }

    }
}