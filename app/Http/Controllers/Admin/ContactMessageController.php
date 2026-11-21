<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['filter' => ['nullable', 'in:unread,read'], 'search' => ['nullable', 'string', 'max:120']]);

        return response()->json([
            'items' => ContactMessage::query()
                ->when($request->filter === 'unread', fn ($q) => $q->where('is_read', false))
                ->when($request->filter === 'read', fn ($q) => $q->where('is_read', true))
                ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('subject', 'like', "%{$s}%")->orWhere('message', 'like', "%{$s}%")))
                ->latest()
                ->paginate(25),
            'unread' => ContactMessage::where('is_read', false)->count(),
        ]);
    }

    public function update(Request $request, ContactMessage $contactMessage)
    {
        $contactMessage->update($request->validate(['is_read' => ['required', 'boolean']]));

        return $contactMessage;
    }

    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();

        return response()->noContent();
    }
}
