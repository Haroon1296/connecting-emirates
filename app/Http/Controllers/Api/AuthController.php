<?php

namespace App\Http\Controllers\Api;

use App\Traits\ApiResponser;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    use ApiResponser;

    /** User login */
    public function login(Request $request)
    {
        $this->validate($request, [
            'email'       =>  'required|email',
            'password'    =>  ['required', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'device_type' =>  'in:ios,android,web',
            'user_type'   =>  'required|in:customer,business'
        ]);

        $user = User::where('email', $request->email)->first(); 

        if(!empty($user)){
            if($user->is_deleted == '1'){
                return $this->errorDataResponse('Your account has been deleted as per your request.', ['user_id' => $user->id, 'is_deleted' => $user->is_deleted],400);
            } else {
                if($user->user_type == $request->user_type){
                    if (Hash::check($request->password, $user->password)){
                        if($user->is_verified == 1){
                            if($user->is_blocked == 0){
                                Auth::attempt($request->only('email', 'password'));
                
                                $token = $user->createToken('AuthToken');
                                User::whereId($user->id)->update(['device_type' => $request->device_type, 'device_token' => $request->device_token]);  
                                $userUpdate = User::find($user->id);
            
                                $userResource = new UserResource($userUpdate);
                                if($userUpdate->user_type == 'customer'){
                                    return $this->loginResponse('Customer login successfully.', $token->plainTextToken, $userResource);
                                } else {
                                    return $this->loginResponse('Business login successfully.', $token->plainTextToken, $userResource);
                                }
                            } else {
                                return $this->errorResponse('Your account is blocked.', 400);
                            }
                        }  else {            
                            return $this->successDataResponse('Your account is not verfied.', ['user_id' => $user->id, 'is_verified' => $user->is_verified], 200);
                        }
                    } else{
                        return $this->errorResponse('Password is incorrect.', 400);
                    }
                } else{
                    return $this->errorResponse('Invalid credentials.', 400);
                }
            }
        } else{
            return $this->errorResponse('Email not found.', 400);
        }
    }

    /** Social login */
    public function socialLogin(Request $request)
    {
        $this->validate($request, [
            'social_type'       =>  'required|in:google,apple,phone',
            'social_token'      =>  'required',
            'user_type'         =>  'required|in:customer,business',
            'device_type'       =>  'in:ios,android,web'
        ]);

        $user = User::where(['social_token' => $request->social_token, 'user_type' => $request->user_type])->first();

        if(!empty($user)){

            $user->device_type = $request->device_type;
            $user->device_token = $request->device_token;
            $user->save();
        }else{
            $user = new User;
            $user->user_type = $request->user_type;
            $user->social_type = $request->social_type;
            $user->social_token = $request->social_token;
            $user->is_profile_complete = '0';
            $user->is_verified = '1';
            $user->is_social = '1';
            $user->device_type = $request->device_type;
            $user->device_token = $request->device_token;
            $user->save();
        }

        $token = $user->createToken('AuthToken');
        $userResource = new UserResource($user);
          
        return $this->loginResponse('Social login successfully.', $token->plainTextToken, $userResource);
    }

    /** User register */
    public function register(Request $request)
    {
        $this->validate($request, [
            'user_type'         =>  'required|in:customer,business',
            'full_name'         =>  'required',
            'restaurant_name'   =>  'required',
            'phone_number'      =>  'required',
            'email'             =>  'required|unique:users|email|max:255',
            'password'          =>  ['required', Password::min(8)->letters()->mixedCase()->numbers()->symbols()]
        ]);

        $created =  User::create($request->only('full_name', 'restaurant_name', 'phone_number', 'email', 'password', 'user_type'));

        if($created){

            try{
                $created->subject =  'Account Verification';
                $created->message =  'Please use the verification code below to sign up. ' . '<b>' . $created->verified_code . '</b>' ;
                
                Notification::send($created, new Otp($created));
            }catch (\Exception $exception){
            }
             
            $data = [
                'user_id' => $created->id
            ];
            return $this->successDataResponse(ucfirst($request->user_type) . ' register successfully.', $data, 200);
        }
        else{
            return $this->errorResponse('Something went wrong.', 400);
        }
    }

    /** Forgot password */
    public function forgotPassword(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->email)->first();
        $user->verified_code = $user->user_type == 'user' ? 1234 : 123456; // mt_rand(1000,9000);
        $user->is_forgot = '1';

        if($user->save()){

            try{
                $user->subject =  'Forgot Your Password';
                $user->message =  'We received a request to reset the password for your account. Please use the verification code below to change password.' . '<br> <br> <b>' . $user->verified_code . '</b>' ;

                Notification::send($user, new Otp($user));
            }catch (\Exception $exception){
            }

            $data = [
                'user_id' => $user->id
            ];
            return $this->successDataResponse('Forgot Password email has been send on your email.', $data, 200);
        }
        else{
            return $this->errorResponse('Something went wrong.', 400);
        }
    }

    /** User verification */
    public function verification(Request $request)
    {
        $this->validate($request, [
            'user_id'       => 'required|exists:users,id',
            'verified_code' => 'required',
            'type'          => 'required|in:forgot,account_verify',
            'device_type'   =>  'in:ios,android,web'
        ]);   

        $userExists = User::whereId($request->user_id)->where('verified_code', $request->verified_code)->exists();

        if($userExists){
            if($request->type == 'forgot'){
                $updateUser = User::whereId($request->user_id)->where('verified_code', $request->verified_code)->update(['device_type' => $request->device_type, 'device_token' => $request->device_token, 'is_forgot' => '0', 'verified_code' => null]);
            }else{
                $updateUser = User::whereId($request->user_id)->where('verified_code', $request->verified_code)->update(['device_type' => $request->device_type, 'device_token' => $request->device_token, 'is_verified' => '1', 'verified_code' => null]);
            }

            if($updateUser){
                $user = User::find($request->user_id);
                $token = $user->createToken('AuthToken');

                $userResource = new UserResource($user);
 
                return $this->loginResponse('Your verification completed successfully.', $token->plainTextToken, $userResource);
            }
            else{
                return $this->errorResponse('Something went wrong.', 400);
            }
        }
        else{
            return $this->errorResponse('Invalid OTP.', 400);
        }
    }

    /** Resend code */
    public function reSendCode(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::whereId($request->user_id)->first();
        $user->verified_code = 123456;//mt_rand(10000,90000);

        if($user->save()){

            try{
                $user->subject =  'Resent Otp';
                $user->message =  'We have received a request to resend the OTP for verification. Please use the verification code below.' . '<br> <br> <b>' . $user->verified_code . '</b>' ;

                Notification::send($user, new Otp($user));
            }catch (\Exception $exception){
            }

            return $this->successResponse('Resend code successfully send on your given email.', 200);
        }
        else{
            return $this->errorResponse('Something went wrong.', 400);
        }
    }

    /** Update password */
    public function updatePassword(Request $request)
    {
        $this->validate($request, [
            'new_password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        if(empty($request->old_password)){
            $updateUser = User::whereId(auth()->user()->id)->update(['password' => Hash::make($request->new_password), 'is_forgot' => '0']);
            if($updateUser){
                return $this->successResponse('New Password set successfully.', 200);
            } else {
                return $this->errorResponse('Something went wrong.', 400);
            }
        }
        else{
            $user = User::whereId(auth()->user()->id)->first();  
            if (Hash::check($request->old_password , $user->password)){
                $updateUser = User::whereId(auth()->user()->id)->update(['password' => Hash::make($request->new_password)]);
                if($updateUser){
                    return $this->successResponse('Password update successfully.', 200);
                } else {
                    return $this->errorResponse('Something went wrong.', 400);
                }
            } else {
                return $this->errorResponse('Old password is incorrect.', 400);
            }
        }        
    }

    /** Complete profile */
    public function completeProfile(Request $request)
    {
        $this->validate($request, [
            'profile_image'             =>    'mimes:jpeg,png,jpg',
            'date_of_birth'             =>    'date_format:Y-m-d',
            'push_notification'         =>    'in:0,1',
            'gender'                    =>    'in:male,female,other',
            'registering_as'            =>    'in:restaurant,adventure,event',
            'menu_image'                =>    'mimes:jpeg,png,jpg',
            'license_image'             =>    'mimes:jpeg,png,jpg',
            'venue_type_id'             =>    'mimes:jpeg,png,jpg'
        ]);

        $authUser = auth()->user();
        $authId = $authUser->id;
        $completeProfile = $request->all();

        if($request->hasFile('profile_image')){
            $profile_image = $request->profile_image->store('public/profile_image');
            $path_profile_image = Storage::url($profile_image);
            $completeProfile['profile_image'] = $path_profile_image;
        }

        if($request->hasFile('menu_image')){
            $menu_image = $request->menu_image->store('public/business');
            $path_menu_image = Storage::url($menu_image);
            $completeProfile['menu_image'] = $path_menu_image;
        }

        if($request->hasFile('license_image')){
            $license_image = $request->license_image->store('public/business');
            $path_license_image = Storage::url($license_image);
            $completeProfile['license_image'] = $path_license_image;
        }

        $completeProfile['is_profile_complete'] = '1';
        $update_user = User::whereId($authId)->update($completeProfile);
        
        if($update_user){
            $user = User::find($authId);

            $userResource = new UserResource($user);
            
            if($authUser->is_profile_complete == '0'){
                return $this->successDataResponse('Profile completed successfully.', $userResource);
            } else{
                return $this->successDataResponse('Profile updated successfully.', $userResource);
            }
        }else{
            return $this->errorResponse('Something went wrong.', 400);
        }
    }

    /** Content */
    public function content(Request $request)
    {
        $this->validate($request, [
            'type' => 'required|exists:contents,type'
        ]);

        return $this->successDataResponse('Content found.', ['url' => url('content', $request->type)], 200);
    }

    /** Faq */
    public function faq()
    {
        $faqs = Faq::latest()->select('question', 'answer')->get();

        if(count($faqs) > 0){
            return $this->successDataResponse('Faq found.', $faqs, 200);
        } else{
            return $this->errorResponse('Faq not found.', 400);
        }
    }

    /** Logout */
    public function logout(Request $request)
    {
        $deleteTokens = $request->user()->currentAccessToken()->delete(); // $request->user()->tokens()->delete();
        
        if($deleteTokens){
            $update_user = User::whereId(auth()->user()->id)->update(['device_type' => null, 'device_token' => null]);
            if($update_user){
                return $this->successResponse('Logout successfully.', 200);
            }else{
                return $this->errorResponse('Something went wrong.', 400);
            }
        }else{
            return $this->errorResponse('Something went wrong.', 400);
        }                
    }
}
