<?php

namespace App\Http\Controllers;

use App\Models\SpreadsheetImport;
use App\Models\Transaction;
use App\Models\BankAccount;
use App\Models\User;
use App\Services\SpreadsheetParserService;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function index()
    {
        $user    = User::firstOrCreate(
            ['email' => 'admin@financeapp.local'],
            ['name'  => 'Admin User', 'password' => bcrypt('password')]
        );
        $imports = SpreadsheetImport::where('user_id', $user->id)->latest()->limit(20)->get();

        return view('import', compact('imports'));
    }

    /**
     * Preview rows from uploaded file before final import.
     */
    public function preview(Request $request, SpreadsheetParserService $parser)
    {
        $request->validate([
            'file' => 'required|file|max:5120',
        ]);

        try {
            $rows    = $parser->parse($request->file('file'));
            $headers = !empty($rows) ? array_keys($rows[0]) : [];
            $mapping = $parser->detectColumnMapping($headers);
            $preview = array_slice($rows, 0, 5);

            return response()->json([
                'success'  => true,
                'headers'  => $headers,
                'mapping'  => $mapping,
                'preview'  => $preview,
                'total'    => count($rows),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * Final import: parse, map, persist transactions.
     */
    public function upload(Request $request, SpreadsheetParserService $parser)
    {
        $request->validate([
            'file'         => 'required|file|max:5120',
            'mapping'      => 'nullable|array',
            'account_name' => 'nullable|string',
        ]);

        $user = User::firstOrCreate(
            ['email' => 'admin@financeapp.local'],
            ['name'  => 'Admin User', 'password' => bcrypt('password')]
        );

        try {
            $rows    = $parser->parse($request->file('file'));
            $mapping = $request->input('mapping') ?? [];

            // Auto-detect if none provided
            if (empty($mapping) && !empty($rows)) {
                $mapping = $parser->detectColumnMapping(array_keys($rows[0]));
            }

            $imported = 0;
            $skipped  = 0;

            $targetAccount = null;
            if ($request->filled('account_name')) {
                $targetAccount = BankAccount::where('user_id', $user->id)
                    ->where('name', $request->input('account_name'))
                    ->first();
            }

            foreach ($rows as $row) {
                $mapped = $parser->mapRow($row, $mapping);

                if (!$mapped) {
                    $skipped++;
                    continue;
                }

                if ($targetAccount) {
                    $mapped['bank_account_id'] = $targetAccount->id;
                    $mapped['account_name'] = $targetAccount->name;
                }

                // Check for duplicates
                $query = Transaction::where('date', $mapped['date'])
                    ->where('amount', $mapped['amount'])
                    ->where('description', $mapped['description']);
                    
                if (isset($mapped['bank_account_id'])) {
                    $query->where('bank_account_id', $mapped['bank_account_id']);
                } else {
                    $query->whereNull('bank_account_id');
                }

                if ($query->exists()) {
                    $skipped++;
                    continue;
                }

                Transaction::create($mapped);
                $imported++;
            }

            SpreadsheetImport::create([
                'user_id'        => $user->id,
                'filename'       => $request->file('file')->getClientOriginalName(),
                'type'           => 'transactions',
                'rows_imported'  => $imported,
                'rows_skipped'   => $skipped,
                'status'         => $skipped > 0 ? 'partial' : 'success',
                'column_mapping' => $mapping,
            ]);

            return response()->json([
                'success'  => true,
                'imported' => $imported,
                'skipped'  => $skipped,
                'message'  => "{$imported} transações importadas com sucesso!",
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
