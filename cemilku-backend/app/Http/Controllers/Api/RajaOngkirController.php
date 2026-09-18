<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RajaOngkirController extends Controller
{
    public function provinces()
    {
        $response = Http::withHeaders([
            'key' => config('services.rajaongkir.key')
        ])->get(
            config('services.rajaongkir.base_url') . '/destination/province'
        );

        return response()->json(
            $response->json(),
            $response->status()
        );
    }

    public function cities($provinceId)
    {
        $response = Http::withHeaders([
            'key' => config('services.rajaongkir.key')
        ])->get(
            config('services.rajaongkir.base_url') . '/destination/city/' . $provinceId
        );

        return response()->json(
            $response->json(),
            $response->status()
        );
    }

    public function cost(Request $request)
    {
        $data = $request->validate([
            'origin' => ['required', 'integer'],
            'destination' => ['required', 'integer'],
            'weight' => ['required', 'numeric', 'min:1'],
            'courier' => ['required', 'string'],
        ]);

        $response = Http::asForm()
            ->withHeaders([
                'key' => config('services.rajaongkir.key'),
                'Accept' => 'application/json',
            ])
            ->post(
                config('services.rajaongkir.base_url') . '/calculate/domestic-cost',
                [
                    'origin' => $data['origin'],
                    'destination' => $data['destination'],
                    'weight' => $data['weight'],
                    'courier' => $data['courier'],
                    'price' => 'lowest',
                ]
            );

        return response()->json(
            $response->json(),
            $response->status()
        );
    }
}