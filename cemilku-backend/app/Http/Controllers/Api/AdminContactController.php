<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class AdminContactController extends Controller
{
    /**
     * Menampilkan semua pesan kontak.
     */
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Akses hanya untuk admin.'
            ], 403);
        }

        $contacts = Contact::latest()->get();

        return response()->json($contacts);
    }

    /**
     * Menampilkan detail pesan.
     */
    public function show(Request $request, Contact $contact)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Akses hanya untuk admin.'
            ], 403);
        }

        return response()->json($contact);
    }

    /**
     * Menghapus pesan kontak.
     */
    public function destroy(Request $request, Contact $contact)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Akses hanya untuk admin.'
            ], 403);
        }

        $contact->delete();

        return response()->json([
            'message' => 'Pesan kontak berhasil dihapus.'
        ]);
    }
}
