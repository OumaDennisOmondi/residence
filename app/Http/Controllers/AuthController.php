<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\SMSController;
class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    protected $SMSController;
    public function __construct(SMSController $SMSController){
        $this->SMSController = $SMSController;
       // $this->middleware('auth', ['except' => ['home','pay','view','action','send_sms']]);
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
            return response()->json(['error' => 'Wrong credentials'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchUser()
    { 
        if(auth()->user()){
           return response()->json(auth()->user());
        }
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    /**
     * update the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|unique:users|email',
            'phone' => 'required|numeric|unique:users',
           
          ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        if(auth()->user()){
         $user= User::find(auth()->user()->id);
         $user->name = $request->name;
         $user->email = $request->email;
         $user->phone = $request->phone;
         $user->save();
         //$this->refresh();
           return response()->json(['success' =>'Profile Updated succesfully','user' =>$user],200);
        }
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function updateProfilePic(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'image_path' => 'required|mimes:jpeg,jpg,png|max:2048',
           
          ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $fileName = time().'.'.$request->image_path->extension();  
   
        $request->image_path->move(public_path('profile_pics'), $fileName);
        $image_path=config('app.url').'/profile_pics/'.$fileName;
        
        if(auth()->user()){
         $user= User::find(auth()->user()->id);
         $user->profile_pic = $image_path;
         $user->save();
         //$this->refresh();
           return response()->json(['success' =>'Profile Picture Updated succesfully','user' =>$user],200);
        }
        return response()->json(['error' => 'Unauthorized'], 401);
    }
 ///update user password
    public function updatePassword(Request $request)
    { 
        if(auth()->user()){
         $user= User::find(auth()->user()->id);
         $user->password = Hash::make($request->password);
         $user->save();
         //$this->refresh();
           return response()->json(['success' =>'Password Updated succesfully'],200);
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
        
       $message = 'Dear '.$name.', Your Residence Verification Code is '.$otp_code;
       $this->SMSController->sendSMS($message, strval($phone));
       
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
        
       $message = 'Your Residence Verification Code is '.$otp_code;
       $this->SMSController->sendSMS($message, strval($phone));
       
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
       $message = 'Your new Residence Password is '.$new_password.'.Please change once you login in';
       $this->SMSController->sendSMS($message, strval($phone));
       
         return response()->json(['success' => 'Password reset succesfully'], 200);
        }
        if(!$user){
            return response()->json(['error' => 'User not found'], 401);
        }

    }
}