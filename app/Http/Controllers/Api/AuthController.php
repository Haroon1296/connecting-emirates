<?php

namespace App\Http\Controllers\Api;

use App\Traits\ApiResponser;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\{
    User,
    SocialMediaLink
};
use App\Notifications\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;

class AuthController extends Controller
{
    use ApiResponser;

    /** User login */
    public function login(Request $request)
    {
        $this->validate($request, [
            'user_type'   =>  'required|in:user,hat',
            'email'       =>  'required|email|exists:users,email',
            'device_type' =>  'in:ios,android,web'
        ]);

        $user = User::where('email', $request->email)->first(); 
        
        if($user->user_type == $request->user_type){
            if($user->is_verified == 1){
                if($user->is_blocked == 0){
                    $user->verified_code = 123456; // mt_rand(1000,9000);
                    $user->save();
                    try{
                        $user->subject =  'Sign in Verification';
                        $user->message =  'Please use the verification code below to sign in. ' . '<br> <br> <b>' . $user->verified_code . '</b>' ;
                        
                        Notification::send($user, new Otp($user));
                    }catch (\Exception $exception){
                    }
        
                    $data = [
                        'user_id' => $user->id
                    ];
                    return $this->successDataResponse('Please enter verification.', $data, 200);

                } else{
                    return $this->errorResponse('Your account is blocked.', 400);
                }
            } else{
                $userResource = new UserResource($user);
                return $this->successDataResponse('Your account is not verfied.', $userResource, 200);
            }
        } else{
            return $this->errorResponse('Invalid credentials.', 400);
        }
    }

    /** Social login */
    public function socialLogin(Request $request)
    {
        $this->validate($request, [
            'social_type'       =>  'required|in:google,apple',
            'social_token'      =>  'required',
            'device_type'       =>  'in:ios,android,web',
            'user_type'         =>  'required|in:user,hat'
        ]);

        try{
            DB::beginTransaction();
            $user = User::where('social_token', $request->social_token)->first();

            if(!empty($user)){
                if($user->user_type == $request->user_type){
                    $user->device_type = $request->device_type;
                    $user->device_token = $request->device_token;
                    $user->save();
                } else{
                    return $this->errorResponse('Invalid credentials.', 400);
                }
            } else{
                $user = new User;
                $user->social_type = $request->social_type;
                $user->social_token = $request->social_token;
                $user->user_type = $request->user_type;
                $user->is_verified = '1';
                $user->is_social = '1';
                $user->is_profile_complete = '0';
                $user->device_type = $request->device_type;
                $user->device_token = $request->device_token;
                $user->save();

                $stripe = new \Stripe\StripeClient($this->stripe_secret_key);
                $customers_create = $stripe->customers->create([
                    'name'  => $user->id
                ]);
                User::whereId($user->id)->update(['customer_id' => $customers_create->id]);
            }

            $token = $user->createToken('AuthToken');
            $userResource = new UserResource($user);
            
            DB::commit();
            return $this->loginResponse('Social login successfully.', $token->plainTextToken, $userResource);
        } catch (\Exception $exception){
            DB::rollBack();
            return $this->errorResponse($exception->getMessage(), 400);
        }
    }

    /** User register */
    public function register(Request $request)
    {
        $this->validate($request, [
            'user_type'       =>      'required|in:user,hat,guest',
            'first_name'      =>      'required',
            'last_name'       =>      'required',
            'email'           =>      $request->user_type == 'guest' ? 'nullable' : 'required|unique:users|email|max:255',
            'device_type'     =>      $request->user_type == 'guest' ? 'required|in:ios,android,web' : 'nullable',
            'device_token'    =>      $request->user_type == 'guest' ? 'required' : 'nullable'
        ]);

        if($request->user_type == 'guest'){
            $userExists = User::where(['device_type' => $request->device_type, 'device_token' => $request->device_token])->first();

            if(!empty($userExists)){
                if(empty($request->email)){
                    $token = $userExists->createToken('AuthToken');
    
                    $userResource = new UserResource($userExists); 
                    return $this->loginResponse('Guest login successfully.', $token->plainTextToken, $userResource);
                } else if(!empty($request->email)){

                    $emailExists = User::where(['email' => $request->email])->exists();

                    if($emailExists){
                        return $this->errorResponse('The email has already been taken.', 400);
                    } else {
                        $userExists->email = $request->email;
                        $userExists->user_type = 'user';
                        $userExists->device_type = null;
                        $userExists->device_token = null;
                        $userExists->save();
    
                        if($userExists){
                            try{
                                $userExists->subject =  'Account Verification';
                                $userExists->message =  'Please use the verification code below to sign up. ' . '<br> <br> <b>' . $userExists->verified_code . '</b>' ;
                                
                                Notification::send($userExists, new Otp($userExists));
                            }catch (\Exception $exception){
                            }
                
                            $data = [
                                'user_id' => $userExists->id
                            ];
                            return $this->successDataResponse('Sign up successfully.', $data, 200);
                        } else {
                            return $this->errorResponse('Something went wrong.', 400);
                        }
                    }
                }
            } else {
                    
                $created =  User::create($request->only('user_type', 'first_name', 'last_name', 'device_type', 'device_token'));

                $stripe = new \Stripe\StripeClient($this->stripe_secret_key);
                $customers_create = $stripe->customers->create([
                    'name'  => $created->id
                ]);
                User::whereId($created->id)->update(['customer_id' => $customers_create->id]);

                $token = $created->createToken('AuthToken');

                $userResource = new UserResource($created); 
                return $this->loginResponse('Guest login successfully.', $token->plainTextToken, $userResource);
            }
        } else {
            $created =  User::create($request->only('user_type', 'first_name', 'last_name', 'email'));
            
            $stripe = new \Stripe\StripeClient($this->stripe_secret_key);
            $customers_create = $stripe->customers->create([
                'name'  => $created->id
            ]);
            User::whereId($created->id)->update(['customer_id' => $customers_create->id]);

            if($created){
    
                try{
                    $created->subject =  'Account Verification';
                    $created->message =  'Please use the verification code below to sign up. ' . '<br> <br> <b>' . $created->verified_code . '</b>' ;
                    
                    Notification::send($created, new Otp($created));
                }catch (\Exception $exception){
                }
    
                $data = [
                    'user_id' => $created->id
                ];
                return $this->successDataResponse(ucfirst($request->user_type) . ' registered successfully.', $data, 200);
            } else {
                return $this->errorResponse('Something went wrong.', 400);
            }
        }
    }

    /** Forgot password */
    public function forgotPassword(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->email)->first();
        $user->verified_code = 123456; // mt_rand(1000,9000);
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
            'user_id'       =>  'required|exists:users,id',
            'verified_code' =>  'required',
            'device_type'   =>  'in:ios,android,web'
        ]);

        $userExists = User::whereId($request->user_id)->where('verified_code', $request->verified_code)->exists();

        if($userExists){
            $updateUser = User::whereId($request->user_id)->where('verified_code', $request->verified_code)->update(['device_type' => $request->device_type, 'device_token' => $request->device_token, 'is_verified' => '1', 'verified_code' => null]);
            if($updateUser){
                $user = User::find($request->user_id);
                $token = $user->createToken('AuthToken');

                $userResource = new UserResource($user); 
                return $this->loginResponse('Your verification completed successfully.', $token->plainTextToken, $userResource);
            } else {
                return $this->errorResponse('Something went wrong.', 400);
            }
        } else {
            return $this->errorResponse('Invalid details.', 400);
        }
    }

    /** Resend code */
    public function reSendCode(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::whereId($request->user_id)->first();
        $user->verified_code = 123456; // mt_rand(1000,9000);

        if($user->save()){
            return $this->successResponse('Resend code successfully send on your given email.', 200);
        } else {
            return $this->errorResponse('Something went wrong.', 400);
        }
    }

    /** Complete profile */
    public function completeProfile(Request $request)
    {
        $this->validate($request, [
            'profile_image'             =>    'mimes:jpeg,png,jpg',
            'push_notification'         =>    'in:0,1'
        ]);

        $authUser = auth()->user();
        $authId = $authUser->id;
        $completeProfile = $request->only('first_name', 'last_name', 'profile_image', 'phone_number', 'specialty', 'bio', 'zip_code');

        if($request->hasFile('profile_image')){
            $profile_image = $request->profile_image->store('public/profile_image');
            $path = Storage::url($profile_image);
            $completeProfile['profile_image'] = $path;
        }

        if(count($request->social_media_title) > 0){ 
            SocialMediaLink::where('user_id', $authId)->delete();
            foreach($request->social_media_title as $key => $title){
                $attachmentData['user_id'] = $authId;
                $attachmentData['title'] = $title;
                $attachmentData['link'] = $request->social_media_link[$key];
                SocialMediaLink::create($attachmentData);
            }
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
        } else {
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

    /** Logout */
    public function logout(Request $request)
    {
        $deleteTokens = $request->user()->currentAccessToken()->delete();
        
        if($deleteTokens){
            $update_user = User::whereId(auth()->user()->id)->update(['device_type' => null, 'device_token' => null]);
            if($update_user){
                return $this->successResponse('User logout successfully.', 200);
            }else{
                return $this->errorResponse('Something went wrong.', 400);
            }
        }else{
            return $this->errorResponse('Something went wrong11.', 400);
        }                
    }
}
