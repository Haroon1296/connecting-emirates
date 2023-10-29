<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\StripeCard;
use App\Models\VenueType;
use App\Traits\ApiResponser;
use Stripe\StripeClient;

class GeneralController extends Controller
{
    use ApiResponser;

    /** Venue type list */
    public function venueTypeList()
    {
        try {
            $venueTypes = VenueType::active()->orderBy('title')->select('id', 'title')->get();

            if(count($venueTypes) > 0){
                return $this->successDataResponse('Venue type list.', $venueTypes);
            } else{
                return $this->errorResponse('Venue type list not found.', 400);
            }

        } catch (\Exception $exception){
            return $this->errorResponse($exception->getMessage(), 400);
        }
    }


    /** List notification */
    public function notificationList(Request $request)
    {
        $this->validate($request, [
            'offset'       =>       'required|numeric'
        ]);

        $notifications = Notification::with('user:id,first_name,last_name,profile_image')->where('receiver_id', auth()->id());
        $notificationCount = $notifications->count();
        $notifications = $notifications->latest()->skip($request->offset)->take(10)->get();

        if(count($notifications) > 0){
            $data = [
                'total_notifications' => $notificationCount,
                'notifications'       => $notifications
            ];
            Notification::where(['receiver_id' => auth()->id(), 'read_at' => null, 'seen' => '0'])->update(['read_at' => now(), 'seen' => '1']);
            return $this->successDataResponse('Notification list found.', $data, 200);
        } else {
            return $this->errorResponse('Notification list not found.', 400);
        }
    }

    /** List card */
    public function listCard()
    {
        try {

            $stripeCard = StripeCard::where('user_id', auth()->id())->latest('is_default')->get();

            // $stripe = new \Stripe\StripeClient($this->stripe_secret_key);

            // $card_list = $stripe->customers->allSources(
            //     auth()->user()->customer_id, ['object' => 'card']
            // );

            if(count($stripeCard) > 0){
                return $this->successDataResponse('Card list.', $stripeCard);
            } else{
                return $this->errorResponse('Card list not found.', 400);
            }

        }catch (\Exception $exception){
            return $this->errorResponse($exception->getMessage(), 400);
        }
    }

    /** Add card */
    public function addCard(Request $request)
    {
        $this->validate($request, [
            'card_number'        =>  'required',
            'expiry_month'       =>  'required',
            'expiry_year'        =>  'required',
            'cvc'                =>  'required'
        ]);

        try{
            $stripe = new StripeClient($this->stripe_secret_key);
            $card_token_response = $stripe->tokens->create([
                'card' => [
                    'number' => $request->card_number,
                    'exp_month' => $request->expiry_month,
                    'exp_year' => $request->expiry_year,
                    'cvc' => $request->cvc
                ],
            ]);

            if(isset($card_token_response->error)){
                return $this->errorResponse($card_token_response->error->message, 400);
            }
            else{
                $card_create_response = $stripe->customers->createSource(
                    auth()->user()->customer_id,
                    ['source' => $card_token_response->id]
                );
    
                if(isset($card_create_response->error)){
                    return $this->errorResponse($card_create_response->error->message, 400);
                }
                else{      
                    
                    $stripeCardCount = StripeCard::where('user_id', auth()->id())->count();
                    
                    $userCard = new StripeCard();
                    $userCard->user_id = auth()->id();
                    $userCard->brand = $card_create_response->brand;
                    $userCard->exp_month = $card_create_response->exp_month;
                    $userCard->exp_year = $card_create_response->exp_year;
                    $userCard->last4 = $card_create_response->last4;
                    $userCard->fingerprint = $card_create_response->fingerprint;
                    $userCard->token = $card_create_response->id;
                    $userCard->is_default = $stripeCardCount == 0 ? '1': '0';
                    $userCard->save();
    
                    return $this->successResponse('Card added successfully.');
                }
            }
        } catch (\Exception $exception){
            return $this->errorResponse($exception->getMessage(), 400);
        }
    }

    /** Delete card */
    public function deleteCard(Request $request)
    {
        $this->validate($request , [
            'card_id'  =>  'required'
        ]);

        try{
            $stripe = new \Stripe\StripeClient($this->stripe_secret_key);

            $deleteResponse = $stripe->customers->deleteSource(
                auth()->user()->customer_id,
                $request->card_id,
                []
            );

            if(isset($deleteResponse->deleted)){
                StripeCard::where(['user_id' => auth()->id(), 'token' => $request->card_id])->delete();
                return $this->successResponse('Card deleted successfully.');
            }
            else{
                return $this->errorResponse('Something went wrong.', 400);
            }
        }catch (\Exception $exception){
            return $this->errorResponse($exception->getMessage(), 400);
        }
    }

    /** Set as default card */
    public function setAsDefaultCard(Request $request)
    {
        $this->validate($request , [
            'card_id'  =>  'required'
        ]);

        try{
            $stripe = new \Stripe\StripeClient($this->stripe_secret_key);

            $updated = $stripe->customers->update(
                auth()->user()->customer_id,
                ['default_source' => $request->card_id]
            );

            if(isset($updated)){
                StripeCard::where(['user_id' => auth()->id()])->update(['is_default' => '0']);
                StripeCard::where(['user_id' => auth()->id(), 'token' => $request->card_id])->update(['is_default' => '1']);
                return $this->successResponse('Card set as default successfully.');
            }
            else{
                return $this->errorResponse('Something went wrong.', 400);
            }

        }catch (\Exception $exception){
            return $this->errorResponse($exception->getMessage(), 400);
        }
    }
}
