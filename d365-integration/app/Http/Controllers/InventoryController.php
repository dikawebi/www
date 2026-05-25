<?php

namespace App\Http\Controllers;

use App\Services\D365Service;
//use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Request;

//use Illuminate\Http\Request;

class InventoryController extends Controller
{
   public function index(Request $request, D365Service $d365)
{
    $page = $request->get('page', 0);
    $fromDate = $request->get('from_date');
    $toDate = $request->get('to_date');
    $unit = $request->get('unit');
    $sortField = $request->get('sort', 'TransactionDate'); // Default sort
    $sortOrder = $request->get('order', 'desc');         // Default order

    $data = $d365->getFuelConsumption($page, $fromDate, $toDate, $unit, $sortField, $sortOrder);

    $transactions = $data['value'] ?? [];
    $hasNextPage = isset($data['@odata.nextLink']);

    return view('inventtrans.index', compact(
        'transactions', 'page', 'fromDate', 'toDate', 'unit', 'sortField', 'sortOrder', 'hasNextPage'
    ));
}
}
