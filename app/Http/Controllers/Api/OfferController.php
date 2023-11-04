<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Offer;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OfferController extends Controller
{
    use ApiResponser;

    /** List offer */
    public function index(Request $request)
    {
        
    }

    /** Create offer */
    public function create(Request $request)
    {
        $this->validate($request, [                         
            'title'                        =>      'required|max:255', 
            'brand_name'                   =>      'required|max:255', 
            'model_nmae'                   =>      'required|max:255', 
            'category'                     =>      'required|max:255', 
            'description'                  =>      'required', 
            'amount'                       =>      'required', 
            'stock_quantity'               =>      'required', 
            'discount'                     =>      'required', 
            'promotion_period_start_at'    =>      'required|date_format:Y-m-d H:i:s|after_or_equal:today', 
            'promotion_period_end_at'      =>      'required|date_format:Y-m-d H:i:s|after:promotion_period_start_at'
        ]);
        
        try{ 
            DB::beginTransaction();

            $data = $request->only('title', 'brand_name', 'model_nmae', 'category', 'description', 'amount', 'stock_quantity', 'discount', 'promotion_period_start_at', 'promotion_period_end_at') +
                            ['business_id' => auth()->id()];

            $created = Offer::create($data);

            if($request->hasFile('attachments')){ 
                foreach($request->attachments as $attachment){
                    $attachment_type = explode('/', $attachment->getClientMimeType())[0];
                    
                    $image = $attachment->store('public/offer');
                    $path = Storage::url($image);
                    $attachmentData['attachment'] = $path;
                    $attachmentData['attachment_type'] = $attachment_type;
                    $attachmentData['record_id'] = $created->id;
                    $attachmentData['type'] = 'offer';
                    Attachment::create($attachmentData);
                }
            }

            DB::commit(); 
            return $this->successResponse('Offer has been created successfully.', 200);
        } catch (\Exception $exception){
            DB::rollBack();
            return $this->errorResponse($exception->getMessage(), 400);
        }
    }

    /** Update offer */
    public function update(Request $request)
    {
        $this->validate($request, [        
            'offer_id'                     =>      'required|exists:offers,id',                             
            'title'                        =>      [Rule::requiredIf($request->has('title')), 'max:255'], 
            'brand_name'                   =>      [Rule::requiredIf($request->has('brand_name')), 'max:255'], 
            'model_nmae'                   =>      [Rule::requiredIf($request->has('model_nmae')), 'max:255'], 
            'category'                     =>      [Rule::requiredIf($request->has('category')), 'max:255'], 
            'description'                  =>      Rule::requiredIf($request->has('description')), 
            'amount'                       =>      Rule::requiredIf($request->has('amount')), 
            'stock_quantity'               =>      Rule::requiredIf($request->has('stock_quantity')), 
            'discount'                     =>      Rule::requiredIf($request->has('discount')), 
            'promotion_period_start_at'    =>      [Rule::requiredIf($request->has('promotion_period_start_at')), 'date_format:Y-m-d H:i:s', 'after_or_equal:today'], 
            'promotion_period_end_at'      =>      [Rule::requiredIf($request->has('promotion_period_end_at')), 'date_format:Y-m-d H:i:s', 'after:promotion_period_start_at']
        ]);
        
        try{ 
            DB::beginTransaction();

            $data = $request->only('title', 'brand_name', 'model_nmae', 'category', 'description', 'amount', 'stock_quantity', 'discount', 'promotion_period_start_at', 'promotion_period_end_at') +
                            ['business_id' => auth()->id()];

            $udapted = Offer::whereId($request->offer_id)->update($data);

            if($request->has('attachment_deleted_ids') && count($request->attachment_deleted_ids) > 0){
                $deleteAttachment = GeneralController::deleteAttachment($request->attachment_deleted_ids);
                if($deleteAttachment != 1){
                    return $this->errorResponse($deleteAttachment, 400);
                }
            }

            if($request->hasFile('attachments')){ 
                foreach($request->attachments as $attachment){
                    $attachment_type = explode('/', $attachment->getClientMimeType())[0];
                    
                    $image = $attachment->store('public/offer');
                    $path = Storage::url($image);
                    $attachmentData['attachment'] = $path;
                    $attachmentData['attachment_type'] = $attachment_type;
                    $attachmentData['record_id'] = $request->offer_id;
                    $attachmentData['type'] = 'offer';
                    Attachment::create($attachmentData);
                }
            }

            DB::commit(); 
            return $this->successResponse('Offer has been udapted successfully.', 200);
        } catch (\Exception $exception){
            DB::rollBack();
            return $this->errorResponse($exception->getMessage(), 400);
        }
    }

    /** Delete offer */
    public function delete(Request $request)
    {
        
    }
}
