<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Rfq;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $userType = $request->get('user_type', 'seller'); // Default to seller

        if ($userType === 'seller') {
            $stats = $this->getSellerStats($user->id);
        } else {
            $stats = $this->getBuyerStats($user->id);
        }

        return $this->apiResponse($stats, 'Statistics retrieved successfully');
    }

    private function getSellerStats(int $sellerId): array
    {
        $productStats = Product::query()
            ->where('seller_id', $sellerId)
            ->select(
                DB::raw('count(*) as total'),
                DB::raw('count(case when is_featured = 1 then 1 end) as featured'),
                DB::raw('count(case when is_approved = 1 then 1 end) as approved'),
                DB::raw('count(case when is_approved = 0 then 1 end) as pending_approval'),
                DB::raw('count(case when is_active = 1 then 1 end) as active'),
                DB::raw('count(case when is_active = 0 then 1 end) as inactive')
            )
            ->first()
            ->toArray();

        // RFQ Stats
        $rfqStats = Rfq::query()
            ->where('seller_id', $sellerId)
            ->selectRaw('
                status,
                count(*) as count
            ')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->status => [
                        'count' => $item->count,
                    ],
                ];
            })
            ->toArray();

        $rfqStats['total'] = array_sum(array_column($rfqStats, 'count'));

        // Quote Stats
        $quoteStats = Quote::query()
            ->where('quotes.seller_id', $sellerId)
            ->leftJoin('quote_items', 'quotes.id', '=', 'quote_items.quote_id')
            ->selectRaw('
                quotes.status,
                COUNT(DISTINCT quotes.id) as count,
                SUM(quote_items.quantity * quote_items.unit_price) as value
            ')
            ->groupBy('quotes.status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->status => [
                        'count' => $item->count,
                        'value' => (float) $item->value,
                    ],
                ];
            })
            ->toArray();
        $quoteStats['total'] = array_sum(array_column($quoteStats, 'count'));

        // Contract Stats
        $contractStats = Contract::query()
            ->where('seller_id', $sellerId)
            ->select(
                'status',
                DB::raw('count(*) as count'),
                DB::raw('sum(total_amount) as value')
            )
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->status => [
                        'count' => $item->count,
                        'value' => (float) $item->value,
                    ],
                ];
            })
            ->toArray();
        $contractStats['total'] = array_sum(array_column($contractStats, 'count'));

        return [
            'products'  => $productStats,
            'rfqs'      => $rfqStats,
            'quotes'    => $quoteStats,
            'contracts' => $contractStats,
        ];
    }

    private function getBuyerStats(int $buyerId): array
    {
        // RFQ Stats
        $rfqStats = Rfq::query()
            ->where('buyer_id', $buyerId)
            ->selectRaw('
                status,
                count(*) as count
            ')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->status => [
                        'count' => $item->count,
                    ],
                ];
            })
            ->toArray();

        $rfqStats['total'] = array_sum(array_column($rfqStats, 'count'));


        // Quote Stats
        $quoteStats = Quote::query()
            ->where('quotes.buyer_id', $buyerId)
            ->leftJoin('quote_items', 'quotes.id', '=', 'quote_items.quote_id')
            ->selectRaw('
                quotes.status,
                COUNT(DISTINCT quotes.id) as count,
                SUM(quote_items.quantity * quote_items.unit_price) as value
            ')
            ->groupBy('quotes.status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->status => [
                        'count' => $item->count,
                        'value' => (float) $item->value,
                    ],
                ];
            })
            ->toArray();
        $quoteStats['total'] = array_sum(array_column($quoteStats, 'count'));

        // Contract Stats
        $contractStats = Contract::query()
            ->where('buyer_id', $buyerId)
            ->select(
                'status',
                DB::raw('count(*) as count'),
                DB::raw('sum(total_amount) as value')
            )
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->status => [
                        'count' => $item->count,
                        'value' => (float) $item->value,
                    ],
                ];
            })
            ->toArray();
        $contractStats['total'] = array_sum(array_column($contractStats, 'count'));

        return [
            'rfqs'      => $rfqStats,
            'quotes'    => $quoteStats,
            'contracts' => $contractStats,
        ];
    }
}
