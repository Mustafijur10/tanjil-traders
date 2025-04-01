<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $Countries = Country::all()->toArray();
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

        $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'status' => ['required'],
        ]);

        $request_data = [
            'title' => $request->input('title'),
            'status' => $request->input('status'),
        ];
        //dd($request_data);
        $item = new Country($request_data);
        $item->save();
        return response()->json('Item created!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Country $country)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Country $country)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Country $country)
    {
        //
        $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'status' => ['required'],
        ]);

        $request_data = [
            'title' => $request->input('title'),
            'status' => $request->input('status'),
        ];

        $country->update($request_data);
        return response()->json('Item updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $country)
    {
        //

        // dd($country);
        $country->delete();
        return response()->json('Item deleted!');
    }
}
