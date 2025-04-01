<?php

namespace App\Http\Controllers;

use App\Models\DemoOrder;
use Illuminate\Http\Request;

class DemoOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $Countries = DemoOrder::all()->toArray();
        foreach ($Countries as &$order) {
           
            if ($order['status'] == 1) {
                $order['status'] = 'delivered';
            } else {
                $order['status'] = 'pending';
            }
        }
       
        return array_reverse($Countries);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(DemoOrder $demoOrder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DemoOrder $demoOrder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DemoOrder $demoOrder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DemoOrder $demoOrder)
    {
        //
    }
}
