<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use App\Models\Member; // <-- Add this to find the member
use App\Http\Resources\ProductResource;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PublicProductController extends Controller
{
    public function index(Request $request)
    {
        // Start building the query (only active products)
        $query = Product::where('status', 'active');

        // If the frontend asks for a specific business/member (e.g., ?member_id=64)
        if ($request->filled('member_id')) {
            $member = Member::find($request->member_id);

            // Translate the Member ID (64) into the User ID (67)
            if ($member && $member->user_id) {
                $query->where('user_id', $member->user_id);
            } else {
                // If the member doesn't exist or has no user account, return an empty list
                $query->where('user_id', 0);
            }
        }

        // Fetch the results
        $products = $query->latest()->paginate(10);

        return ProductResource::collection($products);
    }
}
