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
     * Memperbarui status baca pesan kontak (Dibaca / Belum Dibaca).
     */
    public function update(Request $request, Contact $contact)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Akses hanya untuk admin.'
            ], 403);
        }

        $contact->update([
            'is_read' => $request->has('is_read') ? $request->boolean('is_read') : $contact->is_read,
            'status'  => $request->input('status', $contact->status),
        ]);

        return response()->json([
            'message' => 'Status pesan berhasil diperbarui.',
            'data'    => $contact
        ]);
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