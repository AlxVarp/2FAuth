<?php

namespace App\Http\Controllers;

use App\Group;
use App\TwoFAccount;
use App\Classes\Options;
use App\Http\Requests\TwoFAccountReorderRequest;
use App\Http\Requests\TwoFAccountStoreRequest;
use App\Http\Requests\TwoFAccountEditRequest;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TwoFAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(TwoFAccount::ordered()->get()->toArray());
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\TwoFAccountStoreRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TwoFAccountStoreRequest $request)
    {

        $validated = $request->validated();

        // Two possible cases :
        // - The most common case, the uri is provided by the QuickForm, thanks to a QR code live scan or file upload
        //     -> We use this uri to populate the account
        // - The advanced form has been used and provide no uri but all individual parameters
        //     -> We use the parameters array to populate the account
        $twofaccount = new TwoFAccount;
        $twofaccount->service = $validated['service'];
        $twofaccount->account = $validated['account'];
        $twofaccount->icon = $validated['icon'];

        if( $request->uri ) {
            $twofaccount->uri = $request->uri;
        }
        else {
            $twofaccount->populate($request->all());
        }

        $twofaccount->save();

        // Possible group association
        $groupId = Options::get('defaultGroup') === '-1' ? (int) Options::get('activeGroup') : (int) Options::get('defaultGroup');
        
        // 0 is the pseudo group 'All', only groups with id > 0 are true user groups
        if( $groupId > 0 ) {
            $group = Group::find($groupId);

            if($group) {
                $group->twofaccounts()->save($twofaccount);
            }
        }

        return response()->json($twofaccount, 201);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\TwoFAccount  $twofaccount
     * @return \Illuminate\Http\Response
     */
    public function show(TwoFAccount $twofaccount)
    {
        return response()->json($twofaccount, 200);
    }


    /**
     * Display the specified resource with all attributes.
     *
     * @param  \App\TwoFAccount  $twofaccount
     * @return \Illuminate\Http\Response
     */
    public function showWithSensitive(TwoFAccount $twofaccount)
    {
        return response()->json($twofaccount->makeVisible(['uri', 'secret', 'algorithm']), 200);
    }


    /**
     * Save new order.
     *
     * @param  App\Http\Requests\TwoFAccountReorderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function reorder(TwoFAccountReorderRequest $request)
    {
        $validated = $request->validated();
        return response()->json('order saved', 200);
    }



    /**
     * Preview account using an uri, without any db moves
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function preview(Request $request)
    {

        $this->validate($request, [
            'uri' => 'required|string|regex:/^otpauth:\/\/[h,t]otp\//i',
        ]);

        $twofaccount = new TwoFAccount;
        $twofaccount->uri = $request->uri;

        // If present, use the imageLink parameter to prefill the icon field
        if( $twofaccount->imageLink ) {

            try {

                $chunks = explode('.', $twofaccount->imageLink);
                $hashFilename = Str::random(40) . '.' . end($chunks);

                Storage::disk('local')->put('imagesLink/' . $hashFilename, file_get_contents($twofaccount->imageLink));

                if( in_array(Storage::mimeType('imagesLink/' . $hashFilename), ['image/png', 'image/jpeg', 'image/webp', 'image/bmp']) ) {
                    if( getimagesize(storage_path() . '/app/imagesLink/' . $hashFilename) ) {

                        Storage::move('imagesLink/' . $hashFilename, 'public/icons/' . $hashFilename);
                        $twofaccount->icon = $hashFilename;
                    }
                }
            }
            catch( \Exception $e ) {
                // do nothing
            }
        }

        return response()->json($twofaccount->makeVisible(['uri', 'secret', 'algorithm']), 200);
    }


    /**
     * Generate an OTP token
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function token(Request $request)
    {
        // When the method is called during the process of creating/editing an HOTP account the 
        // sensitive data have to be returned, because of the hotpCounter increment
        $shouldResponseWithSensitiveData = false;

        if( $request->id ) {

            // The request data is the Id of an existing account
            $twofaccount = TwoFAccount::FindOrFail($request->id);
        }
        else if( $request->otp['uri'] ) {

            // The request data contain an uri
            $twofaccount = new TwoFAccount;
            $twofaccount->uri = $request->otp['uri'];
            $shouldResponseWithSensitiveData = true;
        }
        else {

            // The request data should contain all otp parameter
            $twofaccount = new TwoFAccount;
            $twofaccount->populate($request->otp);
            $shouldResponseWithSensitiveData = true;
        }

        $response = [
            'token' => $twofaccount->token,
        ];

        if( $twofaccount->otpType === 'hotp' ) {

            // returned counter & uri will be updated
            $twofaccount->increaseHotpCounter();

            // and the db too
            if( $request->id ) {
                $twofaccount->save();
            }

            if( $shouldResponseWithSensitiveData ) {
                $response['hotpCounter'] = $twofaccount->hotpCounter;
                $response['uri'] = $twofaccount->uri;
            }
        }
        else {

            $response['totpPeriod'] = $twofaccount->totpPeriod;
            $response['totpTimestamp'] = $twofaccount->totpTimestamp;
        }

        return response()->json($response, 200);
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\TwoFAccountEditRequest  $request
     * @param  \App\TwoFAccount  $twofaccount
     * @return \Illuminate\Http\Response
     */
    public function update(TwoFAccountEditRequest $request, $id)
    {

        $validated = $request->validated();

        // Here we catch a possible missing model exception in order to
        // delete orphan submited icon
        try {

            $twofaccount = TwoFAccount::FindOrFail($id);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            if( $validated['icon'] ) {
                Storage::delete('public/icons/' . $validated['icon']);
            }
            
            throw $e;
        }

        $twofaccount->populate($validated);
        $twofaccount->save();

        return response()->json($twofaccount, 200);

    }


    /**
     * A simple and light method to get the account count.
     *
     * @param  \App\TwoFAccount  $twofaccount
     * @return \Illuminate\Http\Response
     */
    public function count(Request $request)
    {
        return response()->json([ 'count' => TwoFAccount::count() ], 200);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\TwoFAccount  $twofaccount
     * @return \Illuminate\Http\Response
     */
    public function destroy(TwoFAccount $twofaccount)
    {
        $twofaccount->delete();

        return response()->json(null, 204);
    }


    /**
     * Remove the specified resources from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function batchDestroy(Request $request)
    {
        $ids = $request->all();
        
        TwoFAccount::destroy($ids);

        return response()->json(null, 204);
    }

}
