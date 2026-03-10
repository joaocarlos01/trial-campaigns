<?php

namespace App\Http\Middleware;

use App\Models\Campaign;
use Closure;
use Illuminate\Http\Request;

class EnsureCampaignIsDraft
{
    public function handle(Request $request, Closure $next)
    {
        $campaign = Campaign::findOrFail($request->route('campaign'));

        //if ($campaign->status === 'draft') {
        //    return response()->json(['error' => 'Campaign must be in draft status.'], 422);
        //}

        // Erro diz que a campanha devia estar em draft para passar, mas o if faz o contrário.

        if (! $campaign->isDraft()) {
            return response()->json(['error' => 'Campaign must be in draft status to be dispatched.'], 422);
        }

        return $next($request);
    }
}
