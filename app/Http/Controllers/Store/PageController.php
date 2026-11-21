<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show(string $slug)
    {
        return Page::where('slug', $slug)->where('is_published', true)->firstOrFail();
    }

    /** Contact form. A hidden "website" field catches simple spam bots. */
    public function contact(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'phone'   => ['required_without:email', 'nullable', 'string', 'max:20'],
            'email'   => ['required_without:phone', 'nullable', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
            'website' => ['nullable', 'max:0'],
        ], [
            'phone.required_without' => 'Give a phone number or an email so we can reply.',
            'email.required_without' => 'Give a phone number or an email so we can reply.',
            'website.max'            => 'Your message could not be sent.',
        ]);
        unset($data['website']);

        ContactMessage::create($data);

        return response()->json(['message' => 'Thank you. We will get back to you soon.'], 201);
    }
}
