<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImageController extends Controller
{
    /**
     * Retrieve the user for the given ID.
     *
     * @param  string  $id
     * @return Response
     */
    public function show($hash_id)
    {
        $product = DB::table('product')->where('hashid', $hash_id)->first();

        if(is_null($product) || is_null($product->image)){
            abort(404, 'Image Not Found');
        }

        $headers = array('Content-Type' => 'image/jpeg');
        $response = response()->download($product->image, 'image', $headers);
        ob_end_clean();
        return $response;
    }
}