<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class FailoverController extends Controller
{
    /**
     * Retrieve the user for the given ID.
     *
     * @param  Request  $request
     * @return Response
     */
    public function show(Request $request)
    {
        $data = $request->json()->all();
        if(!isset($data['businessId'])){
            abort(404, 'businessId not found');
        }

        $product = \Illuminate\Support\Facades\DB::table('product')->where('business_id', $data['businessId'])->take(5)->get();

        if(is_null($product)){
            abort(404, 'product not Found');
        }

        $response = array();
        foreach ($product as $p) {
            $view_data = array();
            $info = json_decode($p->info);

            $view_data['raw_price'] = $info->Price;
            $view_data['name'] = $info->Name;
            $view_data['url'] = $p->url;
            $view_data['image'] = $p->image;

            $json_data = array('item' => $p->id,
                    'score' => 0,
                    'view' => $view_data
                );
            
            array_push($response, $json_data);
        }

        return response()->json($response);
    }
}