<?php

namespace App\Http\Controllers;

use App\Services\StandbyOfferService;
use Illuminate\Http\Request;

class StandbyOfferController extends Controller
{
    public function aceptar(Request $request, \App\Services\StandbyOfferService $svc)
    {
        $token = (string) $request->query('token');
        if (!$token) return view('standby.oferta_result', ['ok'=>false,'msg'=>'Token inválido.']);
        $r = app(\App\Services\StandbyOfferServiceAccept::class, ['svc'=>$svc])->aceptar($token);
        return view('pwa.subsidio.standby.oferta_result', $r);
    }
} 